<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Setting;
use App\Services\TopupPriceService;
use Illuminate\Console\Command;

class RecalculateProductMargins extends Command
{
    /**
     * Hitung ulang margin & harga semua produk berdasarkan Global Pricing Rules.
     * Persentase per tier dibaca dari Setting (bisa dikonfigurasi di Admin Panel).
     *
     * Default:
     *   guest 4% | bronze 3% | silver 2% | gold 1% | platinum 0.5%
     *
     * Margin flat = round(price_cost × pct%, kelipatan 50), min Rp 50.
     */
    protected $signature = 'products:recalculate-margins
                            {--dry-run  : Tampilkan preview tanpa menyimpan ke DB}
                            {--game=    : Batasi ke game_id tertentu}';

    protected $description = 'Recalculate margin & tier prices untuk semua produk berdasarkan Global Pricing Rules';

    public function handle(TopupPriceService $priceService): int
    {
        $isDryRun = $this->option('dry-run');
        $gameId   = $this->option('game');

        // Baca persentase tier dari Setting, fallback ke default
        $tiers = [
            'guest'    => (float) Setting::get('pricing_pct_guest',    4.0),
            'bronze'   => (float) Setting::get('pricing_pct_bronze',   3.0),
            'silver'   => (float) Setting::get('pricing_pct_silver',   2.0),
            'gold'     => (float) Setting::get('pricing_pct_gold',     1.0),
            'platinum' => (float) Setting::get('pricing_pct_platinum', 0.5),
        ];

        $this->info('=== Global Pricing Rules — Recalculate Product Margins ===');
        $this->table(
            ['Tier', '%'],
            collect($tiers)->map(fn ($pct, $tier) => [ucfirst($tier), "{$pct}%"])->values()->toArray()
        );

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
            $priceService, $isDryRun, $tiers, &$total, &$skipped, &$updated
        ) {
            foreach ($products as $product) {
                $total++;
                $cost = (float) $product->price_cost;

                if ($cost <= 0) {
                    $this->line("  SKIP  #{$product->id} {$product->name} — price_cost = 0");
                    $skipped++;
                    continue;
                }

                $margins = [];
                $prices  = [];
                foreach ($tiers as $tier => $pct) {
                    $m              = max(50, $this->roundTo50($cost * $pct / 100));
                    $margins[$tier] = $m;
                    $prices[$tier]  = (int) $priceService->calculateSellPrice($cost, $m);
                }

                $this->line(sprintf(
                    '  %-4s #%-4d %-40s | cost %8s | G:%s B:%s S:%s Gold:%s P:%s',
                    $isDryRun ? 'PRV' : 'UPD',
                    $product->id,
                    substr($product->name, 0, 40),
                    number_format($cost, 0, ',', '.'),
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

    private function roundTo50(float $value): int
    {
        return (int) (round($value / 50) * 50);
    }
}
