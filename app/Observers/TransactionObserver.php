<?php

namespace App\Observers;

use App\Events\TransactionStatusUpdated;
use App\Models\Transaction;

class TransactionObserver
{
    public function updated(Transaction $transaction): void
    {
        if ($transaction->wasChanged('status') && $transaction->user_id) {
            TransactionStatusUpdated::dispatch($transaction);
        }
    }
}
