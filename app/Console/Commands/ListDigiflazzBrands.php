<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\ProviderProduct;
use Illuminate\Console\Command;

class ListDigiflazzBrands extends Command
{
    protected $signature = 'digiflazz:brands';

    protected $description = 'Tampilkan daftar brand unik dari Digiflazz dan status kecocokan dengan tabel games';

    public function handle(): void
    {
        $brands = ProviderProduct::query()
            ->where('provider_name', 'digiflazz')
            ->whereNotNull('brand')
            ->select('brand')
            ->selectRaw('COUNT(*) as sku_count')
            ->selectRaw('MIN(price) as min_price')
            ->groupBy('brand')
            ->orderBy('brand')
            ->get();

        if ($brands->isEmpty()) {
            $this->warn('Belum ada data SKU. Jalankan: php artisan digiflazz:bootstrap-catalog');

            return;
        }

        $gameNames = Game::pluck('name')
            ->mapWithKeys(fn ($name) => [mb_strtolower(trim($name)) => $name]);

        $rows = $brands->map(function ($b) use ($gameNames) {
            $key = mb_strtolower(trim($b->brand));
            $matched = $gameNames->has($key);

            return [
                $b->brand,
                $b->sku_count,
                'Rp '.number_format($b->min_price, 0, ',', '.'),
                $matched ? '<fg=green>✓ Match</>' : '<fg=red>✗ Belum ada di games</>',
            ];
        })->toArray();

        $this->table(
            ['Brand (Digiflazz)', 'Jumlah SKU', 'Harga Terendah', 'Status di Games Table'],
            $rows
        );

        $unmatched = $brands->filter(function ($b) use ($gameNames) {
            return ! $gameNames->has(mb_strtolower(trim($b->brand)));
        });

        if ($unmatched->isNotEmpty()) {
            $this->newLine();
            $this->warn("{$unmatched->count()} brand belum ada di tabel games. Tambahkan game dengan nama persis seperti kolom Brand di atas.");
        } else {
            $this->info('Semua brand sudah terdaftar di tabel games.');
        }
    }
}
