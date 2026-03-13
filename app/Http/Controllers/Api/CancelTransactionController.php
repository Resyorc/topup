<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoinTopup;
use App\Models\Transaction;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class CancelTransactionController extends Controller
{
    public function cancel(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|string',
        ]);

        $invoiceId = $validated['invoice_id'];

        // Coba di transactions dulu
        $transaction = Transaction::where('invoice_id', $invoiceId)
            ->where('status', 'pending')
            ->first();

        if ($transaction) {
            $transaction->update(['status' => 'canceled']);
            AuditLogger::log(
                event: 'cancel',
                description: 'Transaksi dibatalkan: ' . $invoiceId,
                subjectType: 'Transaction',
                subjectId: $invoiceId,
                request: $request,
            );
            return response()->json(['success' => true]);
        }

        // Coba di coin_topups
        $coinTopup = CoinTopup::where('invoice_id', $invoiceId)
            ->where('status', 'pending')
            ->first();

        if ($coinTopup) {
            $coinTopup->update(['status' => 'canceled']);
            AuditLogger::log(
                event: 'cancel',
                description: 'Top up coin dibatalkan: ' . $invoiceId,
                subjectType: 'CoinTopup',
                subjectId: $invoiceId,
                request: $request,
            );
            return response()->json(['success' => true]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Pesanan tidak ditemukan atau tidak bisa dibatalkan.',
        ], 422);
    }
}
