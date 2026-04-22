<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Setting;
use App\Services\TopupPriceService;
use Illuminate\Console\Command;

class RecalculateProductMargins extends Command
{
    protected $signature = 'products:recalculate-margins
                            {--dry-run  : Tampilkan preview tanpa menyimpan ke DB}
                            {--game=    : Batasi ke game_id tertentu}';

    protected $description = 'Recalculate margin & harga jual untuk semua produk berdasarkan Global Pricing Rules';

    public function handle(TopupPriceService $priceService): int
    {
        $isDryRun = $this->option('dry-run');
        $gameId   = $this->option('game');

        $pct = (float) Setting::get('pricing_pct', 4.0);

        $this->info('=== Global Pricing Rules — Recalculate Product Margins ===');
        $this->line("Margin: {$pct}%");

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
            $priceService, $isDryRun, $pct, &$total, &$skipped, &$updated
        ) {
            foreach ($products as $product) {
                $total++;
                $cost = (float) $product->price_cost;

                if ($cost <= 0) {
                    $this->line("  SKIP  #{$product->id} {$product->name} — price_cost = 0");
                    $skipped++;
                    continue;
                }

                $margin = max(50, $this->roundTo50($cost * $pct / 100));
                $price  = (int) $priceService->calculateSellPrice($cost, $margin);

                $this->line(sprintf(
                    '  %-4s #%-4d %-40s | cost %8s | sell %s',
                    $isDryRun ? 'PRV' : 'UPD',
                    $product->id,
                    substr($product->name, 0, 40),
                    number_format($cost, 0, ',', '.'),
                    number_format($price, 0, ',', '.'),
                ));

                if (! $isDryRun) {
                    $product->update([
                        'margin_flat' => $margin,
                        'price_sell'  => $price,
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
