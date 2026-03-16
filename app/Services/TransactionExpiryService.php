<?php

namespace App\Services;

use App\Models\Transaction;

class TransactionExpiryService
{
    /**
     * Mark overdue pending transactions as failed.
     */
    public function expireOverdue(?string $invoiceId = null, ?int $userId = null): int
    {
        $query = Transaction::query()
            ->where('status', 'pending')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now());

        if ($invoiceId !== null) {
            $query->where('invoice_id', $invoiceId);
        }

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $count = 0;
        $query->each(function (Transaction $transaction) use (&$count) {
            $transaction->update([
                'status' => 'failed',
                'payment_status' => 'expired',
                'failure_reason' => 'Pembayaran melewati batas waktu (expired).',
            ]);
            $count++;
        });

        return $count;
    }
}
