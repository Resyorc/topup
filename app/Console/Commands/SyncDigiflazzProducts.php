<?php

namespace App\Console\Commands;

use App\Services\TopupPriceService;
use Illuminate\Console\Command;

class SyncDigiflazzProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'digiflazz:sync-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync product availability and cost prices from Digiflazz API';

    /**
     * Execute the console command.
     */
    public function handle(TopupPriceService $priceService): void
    {
        $this->info('Starting Digiflazz product synchronization...');

        $start = microtime(true);
        $result = $priceService->syncPrices();
        $elapsed = round(microtime(true) - $start, 2);

        $this->info("Synchronization completed in {$elapsed}s.");
        $this->table(['Metric', 'Count'], [
            ['Updated', $result['updated']],
            ['Created (new products)', $result['created']],
            ['Skipped (brand not in DB)', $result['skipped']],
        ]);
    }
}
