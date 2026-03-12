<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Transaction;
use App\Services\TransactionExpiryService;

class InvoiceController extends Controller
{
    public function show(Request $request, TransactionExpiryService $transactionExpiryService)
    {
        $invoiceId = $request->query('invoice_id');
        $invoiceData = null;

        if ($invoiceId) {
            $transactionExpiryService->expireOverdue($invoiceId);

            $transaction = Transaction::with(['product.game', 'user'])
                ->where('invoice_id', $invoiceId)
                ->first();

            if ($transaction) {
                $invoiceData = $this->mapTransactionToInvoiceData($transaction);
            }
        }

        // dd($invoiceData);

        return Inertia::render('invoice', [
            'initialInvoiceData' => $invoiceData,
            'searchedInvoiceId' => $invoiceId
        ]);
    }

    public function data(Request $request, TransactionExpiryService $transactionExpiryService)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|string',
        ]);

        $transactionExpiryService->expireOverdue($validated['invoice_id']);

        $transaction = Transaction::with(['product.game', 'user'])
            ->where('invoice_id', $validated['invoice_id'])
            ->first();

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->mapTransactionToInvoiceData($transaction),
        ]);
    }

    private function mapTransactionToInvoiceData(Transaction $transaction): array
    {
        return [
            'invoice_no'     => $transaction->invoice_id,
            'whatsapp'       => maskPhoneNumber($transaction->customer_whatsapp),
            'status'         => $transaction->status,
            'payment_status' => $transaction->payment_status,
            'method'         => $transaction->payment_name ?? $transaction->payment_method ?? 'Manual',
            'created_at'     => $transaction->created_at->format('d M Y H:i:s'),
            'paid_at'        => $transaction->updated_at->format('Y/m/d H:i:s T'),
            'expired_at'     => $transaction->expired_at?->format('d M Y H:i:s') ?? null,
            'expired_at_unix'=> $transaction->expired_at?->timestamp,
            'game' => [
                'name'      => $transaction->product->game->name,
                'publisher' => $transaction->product->game->publisher ?? 'Nebu Publisher',
                'image'     => $transaction->product->game->image ?? '/storage/games/dummy-ml.jpg',
                'slug'      => $transaction->product->game->slug ?? '',
            ],
            'account' => [
                'username' => $transaction->customer_name ?? $transaction->user?->name ?? 'Guest User',
                'id'       => $transaction->customer_game_id,
                'server'   => $transaction->customer_zone_id ?? '-',
            ],
            'product' => [
                'name'  => $transaction->product->name,
                'extra' => '',
            ],

            // ✅ Data pembayaran — untuk halaman pembayaran kustom
            'payment_url' => $transaction->payment_url,
            'pay_code'    => $transaction->pay_code ?? null,   // Virtual Account
            'qr_url'      => $transaction->qr_url ?? null,     // QRIS
            'pay_url'     => $transaction->pay_url ?? null,    // eWallet redirect

            // ✅ Instructions dari api_logs — langkah cara bayar per metode
            'instructions' => isset($transaction->api_logs['instructions'])
                ? $transaction->api_logs['instructions']
                : [],

            'price' => (int) $transaction->amount,
            'qty'   => 1,
            'fee'   => 0,
            'total' => (int) $transaction->amount,
        ];
    }
}