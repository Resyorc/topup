<?php

namespace App\Console\Commands;

use App\Services\DigiflazzCatalogBootstrapService;
use Illuminate\Console\Command;

class BootstrapDigiflazzCatalog extends Command
{
    protected $signature = 'digiflazz:bootstrap-catalog';

    protected $description = 'Sync pricelist Digiflazz dan auto-create game/product yang belum ada';

    public function handle(DigiflazzCatalogBootstrapService $catalogBootstrap): int
    {
        $this->info('Mengambil pricelist Digiflazz dan generate katalog...');

        $start = microtime(true);
        $result = $catalogBootstrap->bootstrap();
        $elapsed = round(microtime(true) - $start, 2);

        $this->info("Selesai dalam {$elapsed}s.");
        $this->table(['Metric', 'Count'], [
            ['SKU disync', $result['provider_synced']],
            ['SKU aktif', $result['provider_active']],
            ['SKU inactive', $result['provider_inactive']],
            ['Game baru', $result['games_created']],
            ['Produk baru', $result['products_created']],
            ['Produk existing dipakai', $result['products_reused']],
            ['SKU dipetakan', $result['sku_mapped']],
            ['Dilewati', $result['skipped']],
        ]);

        return self::SUCCESS;
    }
}
