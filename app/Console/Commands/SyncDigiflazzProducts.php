<?php

namespace App\Console\Commands;

use App\Services\TopupPriceService;
use Illuminate\Console\Command;

class SyncDigiflazzProducts extends Command
{
    protected $signature = 'digiflazz:sync-products';

    protected $description = 'Sync harga & status produk dari Digiflazz API berdasarkan provider_sku yang sudah dipetakan admin';

    public function handle(TopupPriceService $priceService): void
    {
        $this->info('Starting Digiflazz product synchronization...');

        $start   = microtime(true);
        $result  = $priceService->syncPrices();
        $elapsed = round(microtime(true) - $start, 2);

        $this->info("Synchronization completed in {$elapsed}s.");
        $this->table(['Metric', 'Count'], [
            ['Products updated (harga & status)', $result['updated']],
            ['Products skipped (sudah inactive)', $result['skipped']],
        ]);
    }
}
