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
    public function handle(TopupPriceService $priceService)
    {
        $this->info('Starting Digiflazz product synchronization...');

        $priceService->syncPrices();

        $this->info('Synchronization completed successfully!');
    }
}
