<?php

namespace App\Http\Controllers;

use App\Models\CoinTopup;
use App\Models\GameReview;
use App\Models\Transaction;
use App\Services\TransactionExpiryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function searchByPhone(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
        ]);

        // Ambil 9 digit terakhir untuk matching +62/0/tanpa prefix
        $digits = preg_replace('/[^0-9]/', '', $validated['phone']);
        if (strlen($digits) < 8) {
            return response()->json(['success' => false, 'message' => 'Nomor telepon tidak valid.'], 422);
        }
        $tail = substr($digits, -9);

        $transactions = Transaction::where('customer_whatsapp', 'LIKE', "%{$tail}%")
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['invoice_id', 'status', 'created_at', 'amount']);

        $coinTopups = CoinTopup::where('customer_whatsapp', 'LIKE', "%{$tail}%")
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['invoice_id', 'status', 'created_at', 'amount']);

        $results = $transactions->map(fn ($t) => [
            'invoice_id' => $t->invoice_id,
            'type'       => 'transaction',
            'status'     => $t->status,
            'amount'     => (int) $t->amount,
            'created_at' => $t->created_at->format('d M Y H:i'),
        ])->concat($coinTopups->map(fn ($c) => [
            'invoice_id' => $c->invoice_id,
            'type'       => 'coin_topup',
            'status'     => $c->status,
            'amount'     => (int) $c->amount,
            'created_at' => $c->created_at->format('d M Y H:i'),
        ]))->sortByDesc('created_at')->values();

        if ($results->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Tidak ada transaksi ditemukan untuk nomor ini.'], 404);
        }

        return response()->json(['success' => true, 'data' => $results]);
    }

    public function show(Request $request, TransactionExpiryService $transactionExpiryService)
    {
        $invoiceId  = $request->query('invoice_id');
        $guestToken = $request->query('guest_token');
        $invoiceData = null;

        if ($invoiceId) {
            $transactionExpiryService->expireOverdue($invoiceId);

            $transaction = Transaction::with(['product.game', 'user'])
                ->where('invoice_id', $invoiceId)
                ->first();

            if ($transaction) {
                if (! $this->canAccessTransaction($transaction, $guestToken)) {
                    abort(403);
                }

                $invoiceData = $this->mapTransactionToInvoiceData($transaction);
                $invoiceData['has_reviewed'] = GameReview::where('transaction_id', $invoiceId)->exists();
            } else {
                $coinTopup = CoinTopup::with('user')
                    ->where('invoice_id', $invoiceId)
                    ->first();

                if ($coinTopup) {
                    if (! $this->canAccessCoinTopup($coinTopup, $guestToken)) {
                        abort(403);
                    }

                    if ($coinTopup->status === 'pending' && $coinTopup->expired_at?->isPast()) {
                        $coinTopup->update([
                            'status' => 'expired',
                            'failure_reason' => 'Pembayaran melewati batas waktu (expired).',
                        ]);
                        $coinTopup->refresh();
                    }

                    $invoiceData = $this->mapCoinTopupToInvoiceData($coinTopup);
                }
            }
        }

        return Inertia::render('invoice', [
            'initialInvoiceData' => $invoiceData,
            'searchedInvoiceId'  => $invoiceId,
        ]);
    }

    public function data(Request $request, TransactionExpiryService $transactionExpiryService)
    {
        $validated = $request->validate([
            'invoice_id'  => 'required|string',
            'guest_token' => 'nullable|string|max:64',
        ]);

        $transactionExpiryService->expireOverdue($validated['invoice_id']);

        $transaction = Transaction::with(['product.game', 'user'])
            ->where('invoice_id', $validated['invoice_id'])
            ->first();

        if ($transaction) {
            if (! $this->canAccessTransaction($transaction, $validated['guest_token'] ?? null)) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $this->mapTransactionToInvoiceData($transaction),
            ]);
        }

        $coinTopup = CoinTopup::with('user')
            ->where('invoice_id', $validated['invoice_id'])
            ->first();

        if (! $coinTopup) {
            return response()->json(['success' => false, 'message' => 'Invoice tidak ditemukan.'], 404);
        }

        if (! $this->canAccessCoinTopup($coinTopup, $validated['guest_token'] ?? null)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($coinTopup->status === 'pending' && $coinTopup->expired_at?->isPast()) {
            $coinTopup->update([
                'status' => 'expired',
                'failure_reason' => 'Pembayaran melewati batas waktu (expired).',
            ]);
            $coinTopup->refresh();
        }

        return response()->json([
            'success' => true,
            'data' => $this->mapCoinTopupToInvoiceData($coinTopup),
        ]);
    }

    private function canAccessTransaction(Transaction $transaction, ?string $guestToken): bool
    {
        // Transaksi milik user login — wajib match user
        if ($transaction->user_id) {
            return $transaction->user_id === auth()->id();
        }

        // Transaksi guest lama (sebelum sistem guest_token) — tetap aksesibel untuk backward compat
        if (! $transaction->guest_token) {
            return true;
        }

        // Transaksi guest baru — wajib ada token yang valid
        if (! $guestToken) {
            return false;
        }

        return hash_equals($transaction->guest_token, $guestToken);
    }

    private function canAccessCoinTopup(CoinTopup $coinTopup, ?string $guestToken): bool
    {
        if ($coinTopup->user_id) {
            return $coinTopup->user_id === auth()->id();
        }

        if (! $coinTopup->guest_token) {
            return true;
        }

        if (! $guestToken) {
            return false;
        }

        return hash_equals($coinTopup->guest_token, $guestToken);
    }

    private function mapTransactionToInvoiceData(Transaction $transaction): array
    {
        return [
            'type' => 'transaction',
            'invoice_no' => $transaction->invoice_id,
            'whatsapp' => maskPhoneNumber($transaction->customer_whatsapp),
            'status' => $transaction->status,
            'payment_status' => $transaction->payment_status,
            'method' => $transaction->payment_name ?? $transaction->payment_method ?? 'Manual',
            'created_at' => $transaction->created_at->format('d M Y H:i:s'),
            'paid_at' => $transaction->updated_at->format('Y/m/d H:i:s T'),
            'expired_at' => $transaction->expired_at?->format('d M Y H:i:s') ?? null,
            'expired_at_unix' => $transaction->expired_at?->timestamp,
            'game' => [
                'name' => $transaction->product->game->name,
                'publisher' => $transaction->product->game->publisher ?? 'Nuvelo Publisher',
                'image' => $transaction->product->game->image ?? '/storage/games/dummy-ml.jpg',
                'slug' => $transaction->product->game->slug ?? '',
            ],
            'account' => [
                'username' => $transaction->customer_name ?? $transaction->user?->name ?? 'Guest User',
                'id' => $transaction->customer_game_id,
                'server' => $transaction->customer_zone_id ?? '-',
            ],
            'product' => [
                'name' => $transaction->product->name,
                'extra' => '',
                'icon_url' => $transaction->product->game->resolveProductIcon($transaction->product),
            ],

            // ✅ Data pembayaran — untuk halaman pembayaran kustom
            'payment_url' => $transaction->payment_url,
            'pay_code' => $transaction->pay_code ?? null,   // Virtual Account
            'qr_url' => $transaction->qr_url ?? null,     // QRIS
            'pay_url' => $transaction->pay_url ?? null,    // eWallet redirect

            // ✅ Instructions dari api_logs — langkah cara bayar per metode
            'instructions' => isset($transaction->api_logs['instructions'])
                ? $transaction->api_logs['instructions']
                : [],

            'price' => (int) $transaction->amount,
            'qty' => 1,
            'fee' => (int) $transaction->fee,
            'discount' => (int) $transaction->discount,
            'total' => max(0, (int) $transaction->amount - (int) $transaction->discount) + (int) $transaction->fee,
            'loyalty_coins' => (int) $transaction->loyalty_coins,
        ];
    }

    private function mapCoinTopupToInvoiceData(CoinTopup $coinTopup): array
    {
        $displayStatus = match ($coinTopup->status) {
            'paid' => 'success',
            'expired' => 'failed',
            default => $coinTopup->status,
        };

        $paymentStatus = match ($coinTopup->status) {
            'paid' => 'paid',
            'expired' => 'expired',
            default => $coinTopup->status,
        };

        return [
            'type' => 'coin_topup',
            'invoice_no' => $coinTopup->invoice_id,
            'whatsapp' => maskPhoneNumber($coinTopup->customer_whatsapp),
            'status' => $displayStatus,
            'payment_status' => $paymentStatus,
            'method' => $coinTopup->payment_name ?? $coinTopup->payment_method ?? 'QRIS',
            'created_at' => $coinTopup->created_at->format('d M Y H:i:s'),
            'paid_at' => ($coinTopup->paid_at ?? $coinTopup->updated_at)?->format('Y/m/d H:i:s T'),
            'expired_at' => $coinTopup->expired_at?->format('d M Y H:i:s'),
            'expired_at_unix' => $coinTopup->expired_at?->timestamp,
            'game' => [
                'name' => 'Krysta Coins',
                'publisher' => 'Top Up Saldo',
                'image' => '/coin.png',
                'slug' => '',
            ],
            'account' => [
                'username' => $coinTopup->user?->name ?? 'User',
                'id' => 'Top Up Saldo',
                'server' => '-',
            ],
            'product' => [
                'name' => number_format((int) $coinTopup->amount, 0, ',', '.').' Coins',
                'extra' => 'Top Up Saldo',
                'icon_url' => '/coin.png',
            ],
            'payment_url' => $coinTopup->payment_url,
            'pay_code' => $coinTopup->pay_code,
            'qr_url' => $coinTopup->qr_url,
            'pay_url' => $coinTopup->pay_url,
            'instructions' => isset($coinTopup->api_logs['instructions'])
                ? $coinTopup->api_logs['instructions']
                : [],
            'price' => (int) $coinTopup->amount,
            'qty' => 1,
            'fee' => isset($coinTopup->api_logs['fee_customer']) ? (int) $coinTopup->api_logs['fee_customer'] : 0,
            'total' => isset($coinTopup->api_logs['amount']) ? (int) $coinTopup->api_logs['amount'] : ((int) $coinTopup->amount + (isset($coinTopup->api_logs['fee_customer']) ? (int) $coinTopup->api_logs['fee_customer'] : 0)),
        ];
    }
}
