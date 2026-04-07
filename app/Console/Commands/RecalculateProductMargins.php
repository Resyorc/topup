<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\TopupPriceService;
use Illuminate\Console\Command;

class RecalculateProductMargins extends Command
{
    /**
     * Hitung ulang margin & harga semua produk berdasarkan persentase dari price_cost.
     *
     * Tier multiplier (dari silver sebagai base):
     *   guest ×1.5 | bronze ×1.2 | silver ×1.0 | gold ×0.8 | platinum ×0.6
     *
     * Bracket margin otomatis berdasarkan harga modal:
     *   < 2.000      → 30%
     *   2.000–9.999  → 20%
     *   10.000–49.999 → 15%
     *   ≥ 50.000     → 10%
     *
     * Override manual via --percent=N untuk semua produk.
     */
    protected $signature = 'products:recalculate-margins
                            {--percent= : Override persentase margin silver (contoh: --percent=15)}
                            {--dry-run  : Tampilkan preview tanpa menyimpan ke DB}
                            {--game=    : Batasi ke game_id tertentu}';

    protected $description = 'Recalculate margin & tier prices untuk semua produk berdasarkan % price_cost';

    private const TIER_MULTIPLIERS = [
        'guest'    => 1.5,
        'bronze'   => 1.2,
        'silver'   => 1.0,
        'gold'     => 0.8,
        'platinum' => 0.6,
    ];

    public function handle(TopupPriceService $priceService): int
    {
        $isDryRun      = $this->option('dry-run');
        $forcePercent  = $this->option('percent') !== null ? (float) $this->option('percent') : null;
        $gameId        = $this->option('game');

        $this->info('=== Recalculate Product Margins ===');
        if ($isDryRun) {
            $this->warn('[DRY RUN] Tidak ada perubahan yang disimpan.');
        }

        $query = Product::query();
        if ($gameId) {
            $query->where('game_id', $gameId);
        }

        $total   = 0;
        $skipped = 0;
        $updated = 0;

        $query->chunk(200, function ($products) use (
            $priceService, $isDryRun, $forcePercent, &$total, &$skipped, &$updated
        ) {
            foreach ($products as $product) {
                $total++;
                $cost = (float) $product->price_cost;

                if ($cost <= 0) {
                    $this->line("  SKIP  #{$product->id} {$product->name} — price_cost = 0");
                    $skipped++;
                    continue;
                }

                $pct         = $forcePercent ?? $this->autoPercent($cost);
                $baseMargin  = $this->roundTo50($cost * $pct / 100);
                $baseMargin  = max(150, $baseMargin);

                $margins = [];
                $prices  = [];
                foreach (self::TIER_MULTIPLIERS as $tier => $multiplier) {
                    $m           = max(150, $this->roundTo50($baseMargin * $multiplier));
                    $margins[$tier] = $m;
                    $prices[$tier]  = (int) $priceService->calculateSellPrice($cost, $m);
                }

                $this->line(sprintf(
                    '  %-4s #%-4d %-40s | cost %6s | base %4s | G:%s B:%s S:%s Gold:%s P:%s',
                    $isDryRun ? 'PRV' : 'UPD',
                    $product->id,
                    substr($product->name, 0, 40),
                    number_format($cost, 0, ',', '.'),
                    number_format($baseMargin, 0, ',', '.'),
                    number_format($prices['guest'], 0, ',', '.'),
                    number_format($prices['bronze'], 0, ',', '.'),
                    number_format($prices['silver'], 0, ',', '.'),
                    number_format($prices['gold'], 0, ',', '.'),
                    number_format($prices['platinum'], 0, ',', '.'),
                ));

                if (! $isDryRun) {
                    $product->update([
                        'margin_guest_flat'    => $margins['guest'],
                        'margin_bronze_flat'   => $margins['bronze'],
                        'margin_silver_flat'   => $margins['silver'],
                        'margin_gold_flat'     => $margins['gold'],
                        'margin_platinum_flat' => $margins['platinum'],
                        'price_guest'          => $prices['guest'],
                        'price_bronze'         => $prices['bronze'],
                        'price_silver'         => $prices['silver'],
                        'price_gold'           => $prices['gold'],
                        'price_platinum'       => $prices['platinum'],
                    ]);
                }

                $updated++;
            }
        });

        $this->newLine();
        $this->info("Total   : {$total}");
        $this->info("Updated : {$updated}");
        $this->warn("Skipped : {$skipped} (price_cost = 0, perlu di-sync dulu dari Digiflazz)");

        if ($isDryRun) {
            $this->warn('Jalankan tanpa --dry-run untuk menyimpan perubahan.');
        }

        return self::SUCCESS;
    }

    /**
     * Bracket persentase margin silver otomatis berdasarkan harga modal.
     */
    private function autoPercent(float $cost): float
    {
        return match (true) {
            $cost < 2_000   => 30.0,
            $cost < 10_000  => 20.0,
            $cost < 50_000  => 15.0,
            default         => 10.0,
        };
    }

    private function roundTo50(float $value): int
    {
        return (int) (round($value / 50) * 50);
    }
}
