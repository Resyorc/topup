<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessFulfilmentJob;
use App\Jobs\SendWhatsAppNotification;
use App\Models\CoinTopup;
use App\Models\Transaction;
use App\Services\CoinService;
use App\Services\OperationalLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripayCallbackController extends Controller
{
    public function handle(Request $request, CoinService $coinService)
    {
        $callbackSignature = $request->header('X-Callback-Signature');
        $json = $request->getContent();
        $privateKey = config('services.tripay.private_key');

        $signature = hash_hmac('sha256', $json, $privateKey);

        if (! hash_equals($signature, (string) $callbackSignature)) {
            OperationalLogger::warning('Tripay Callback Invalid Signature', [
                'received' => $callbackSignature,
                'calculated' => $signature,
                'event' => $request->header('X-Callback-Event'),
            ], $request, 'payments');

            return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
        }

        if ($request->header('X-Callback-Event') !== 'payment_status') {
            return response()->json(['success' => false, 'message' => 'Not a payment event'], 400);
        }

        $data = json_decode($json);

        if (! isset($data->reference) || ! isset($data->merchant_ref)) {
            return response()->json(['success' => false, 'message' => 'Invalid data payload representation'], 400);
        }

        if (! isset($data->is_closed_payment) || ! $data->is_closed_payment) {
            return response()->json(['success' => false, 'message' => 'Open payment is not supported'], 400);
        }

        // ===== Coin Topup =====
        $coinTopup = CoinTopup::where('invoice_id', $data->merchant_ref)->first();

        if ($coinTopup) {
            DB::transaction(function () use ($coinTopup, $data, $coinService) {
                $lockedTopup = CoinTopup::whereKey($coinTopup->id)->lockForUpdate()->first();

                if (in_array($lockedTopup->status, ['paid', 'failed', 'expired'], true)) {
                    return;
                }

                if ($data->status === 'PAID') {
                    $coinService->credit(
                        $lockedTopup->user,
                        (int) $lockedTopup->amount,
                        'Top up Krysta Coin',
                        $lockedTopup->invoice_id,
                    );

                    $lockedTopup->update(['status' => 'paid', 'paid_at' => now()]);

                    dispatch(SendWhatsAppNotification::coinTopupSuccess($lockedTopup->load('user')));
                } elseif (in_array($data->status, ['EXPIRED', 'FAILED'])) {
                    $lockedTopup->update([
                        'status'         => $data->status === 'EXPIRED' ? 'expired' : 'failed',
                        'failure_reason' => $data->status === 'EXPIRED'
                            ? 'Pembayaran melewati batas waktu (expired).'
                            : 'Pembayaran gagal.',
                    ]);
                }
            });

            return response()->json(['success' => true]);
        }

        // ===== Game Transaction =====
        $transaction = Transaction::where('invoice_id', $data->merchant_ref)->first();

        if (! $transaction) {
            OperationalLogger::error('Tripay Callback Transaction Not Found', [
                'merchant_ref' => $data->merchant_ref,
                'reference' => $data->reference ?? null,
                'status' => $data->status ?? null,
            ], request: $request, channel: 'payments');

            return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
        }

        DB::transaction(function () use ($data) {
            $transaction = Transaction::where('invoice_id', $data->merchant_ref)
                ->lockForUpdate()
                ->first();

            // Sudah diproses sebelumnya — abaikan callback duplikat
            if (in_array($transaction->status, ['success', 'failed'], true)) {
                return;
            }

            if ($data->status === 'PAID') {
                $transaction->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                    'fulfilment_status' => 'processing',
                ]);

                // Dispatch job — fulfilment diproses di background, callback cepat kembali 200
                ProcessFulfilmentJob::dispatch($transaction->invoice_id);

                dispatch(SendWhatsAppNotification::paymentReceived($transaction->load('product.game')));
            } elseif (in_array($data->status, ['EXPIRED', 'FAILED'])) {
                $transaction->update([
                    'payment_status'    => 'expired',
                    'status'            => 'failed',
                    'fulfilment_status' => 'failed',
                ]);
            }
        });

        return response()->json(['success' => true]);
    }
}
