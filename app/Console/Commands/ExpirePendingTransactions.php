<?php

namespace App\Console\Commands;

use App\Models\CoinTopup;
use App\Services\TransactionExpiryService;
use Illuminate\Console\Command;

class ExpirePendingTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:expire-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark overdue pending transactions as failed';

    /**
     * Execute the console command.
     */
    public function handle(TransactionExpiryService $transactionExpiryService): int
    {
        $expiredCount = $transactionExpiryService->expireOverdue();

        $expiredCoinTopups = 0;
        CoinTopup::query()
            ->where('status', 'pending')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->each(function (CoinTopup $coinTopup) use (&$expiredCoinTopups) {
                $coinTopup->update([
                    'status' => 'expired',
                    'failure_reason' => 'Pembayaran melewati batas waktu (expired).',
                ]);
                $expiredCoinTopups++;
            });

        $this->info("Expired {$expiredCount} pending transaction(s) and {$expiredCoinTopups} coin topup(s).");

        return self::SUCCESS;
    }
}
