<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProviderProduct;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class AutoPilotService
{
    /**
     * Jalankan pipeline lengkap:
     *   1. Sync harga dari Digiflazz
     *   2. Analisis SKU baru dengan AI (termasuk saran margin per produk)
     *   3. Auto-approve semua saran yang direkomendasikan
     *
     * Margin per tier dihitung otomatis dari suggested_margin AI:
     *   guest=×1.5, bronze=×1.2, silver=×1.0, gold=×0.8, platinum=×0.6
     *
     * @return array{synced: int, created: int, skipped: int, ai_error: string|null}
     */
    public function run(): array
    {
        $priceService    = app(TopupPriceService::class);
        $analyzerService = app(AiSkuAnalyzerService::class);

        // ── Step 1: Sync katalog & harga produk yang sudah dipetakan ─────────
        $syncResult = $priceService->syncPrices();

        // ── Step 2: Analisis SKU yang belum dipetakan dengan AI ───────────────
        $aiResult = $analyzerService->analyze();

        if ($aiResult['error']) {
            Log::warning('AutoPilot: AI analyze gagal — ' . $aiResult['error']);
        }

        // ── Step 3: Auto-approve semua saran yang recommended & punya game ────
        $suggestions = $analyzerService->getSuggestions();
        $created     = 0;
        $skipped     = 0;

        foreach ($suggestions as $suggestion) {
            if (! $suggestion['recommended'] || ! $suggestion['game_id']) {
                $skipped++;
                continue;
            }

            $sku = ProviderProduct::where('provider_name', 'digiflazz')
                ->where('provider_sku', $suggestion['sku_code'])->first();
            if (! $sku) {
                $skipped++;
                continue;
            }

            // Skip jika SKU ini sudah terkait dengan product
            if ($sku->product_id != null) {
                $skipped++;
                continue;
            }

            $cost    = (float) $sku->price;
            $margins = $this->calcTierMargins((int) ($suggestion['suggested_margin'] ?? 500));

            $product = Product::create([
                'game_id'              => $suggestion['game_id'],
                'name'                 => $suggestion['product_name'],
                'price_cost'           => $cost,
                'margin_guest_flat'    => $margins['guest'],
                'margin_bronze_flat'   => $margins['bronze'],
                'margin_silver_flat'   => $margins['silver'],
                'margin_gold_flat'     => $margins['gold'],
                'margin_platinum_flat' => $margins['platinum'],
                'price_guest'          => $priceService->calculateSellPrice($cost, $margins['guest']),
                'price_bronze'         => $priceService->calculateSellPrice($cost, $margins['bronze']),
                'price_silver'         => $priceService->calculateSellPrice($cost, $margins['silver']),
                'price_gold'           => $priceService->calculateSellPrice($cost, $margins['gold']),
                'price_platinum'       => $priceService->calculateSellPrice($cost, $margins['platinum']),
                'is_available'         => $sku->is_active,
            ]);

            $sku->update(['product_id' => $product->id]);
            $created++;
        }

        $analyzerService->clearSuggestions();

        return [
            'synced'   => $syncResult['updated'],
            'created'  => $created,
            'skipped'  => $skipped,
            'ai_error' => $aiResult['error'],
        ];
    }

    /**
     * Hitung margin 5 tier dari base margin (silver).
     * Semua nilai dibulatkan ke kelipatan 50 terdekat, minimum Rp 150.
     *
     * guest ×1.5 | bronze ×1.2 | silver ×1.0 | gold ×0.8 | platinum ×0.6
     */
    public function calcTierMargins(int $baseMargin): array
    {
        return [
            'guest'    => max(150, $this->roundTo50($baseMargin * 1.5)),
            'bronze'   => max(150, $this->roundTo50($baseMargin * 1.2)),
            'silver'   => max(150, $baseMargin),
            'gold'     => max(150, $this->roundTo50($baseMargin * 0.8)),
            'platinum' => max(150, $this->roundTo50($baseMargin * 0.6)),
        ];
    }

    private function roundTo50(float $value): int
    {
        return (int) (round($value / 50) * 50);
    }
}
