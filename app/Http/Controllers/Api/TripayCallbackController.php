<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppNotification;
use App\Models\CoinTopup;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Services\CoinService;
use App\Services\DigiflazzService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TripayCallbackController extends Controller
{
    /**
     * Handle the incoming Tripay Webhook request.
     */
    public function handle(Request $request, DigiflazzService $digiflazzService, CoinService $coinService)
    {
        $callbackSignature = $request->header('X-Callback-Signature');
        $json = $request->getContent();
        $privateKey = config('services.tripay.private_key');

        $signature = hash_hmac('sha256', $json, $privateKey);

        if (!hash_equals($signature, (string) $callbackSignature)) {
            Log::warning('Tripay Callback Invalid Signature', ['received' => $callbackSignature, 'calculated' => $signature]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid signature',
            ], 403);
        }

        if ('payment_status' !== $request->header('X-Callback-Event')) {
            return response()->json([
                'success' => false,
                'message' => 'Not a payment event',
            ], 400);
        }

        $data = json_decode($json);

        if (!isset($data->reference) || !isset($data->merchant_ref)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data payload representation',
            ], 400);
        }

        // Sesuai dokumentasi Tripay — hanya proses closed payment (is_closed_payment = 1)
        // Open payment (0) tidak didukung karena jumlah bayar bisa berbeda
        if (!isset($data->is_closed_payment) || !$data->is_closed_payment) {
            return response()->json([
                'success' => false,
                'message' => 'Open payment is not supported',
            ], 400);
        }

        $coinTopup = CoinTopup::where('invoice_id', $data->merchant_ref)->first();

        if ($coinTopup) {
            DB::transaction(function () use ($coinTopup, $data, $coinService) {
                $lockedTopup = CoinTopup::whereKey($coinTopup->id)
                    ->lockForUpdate()
                    ->first();

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

                    $lockedTopup->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);

                    SendWhatsAppNotification::coinTopupSuccess($lockedTopup->load('user'))
                        ->dispatch();
                } elseif (in_array($data->status, ['EXPIRED', 'FAILED'])) {
                    $lockedTopup->update([
                        'status' => $data->status === 'EXPIRED' ? 'expired' : 'failed',
                        'failure_reason' => $data->status === 'EXPIRED'
                            ? 'Pembayaran melewati batas waktu (expired).'
                            : 'Pembayaran gagal.',
                    ]);
                }
            });

            return response()->json(['success' => true]);
        }

        // Check existence first before acquiring lock
        $transaction = Transaction::where('invoice_id', $data->merchant_ref)->first();

        if (!$transaction) {
            Log::error('Tripay Callback Transaction Not Found: ' . $data->merchant_ref);
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        // lockForUpdate must be inside DB::transaction to hold the row lock
        DB::transaction(function () use ($data, $digiflazzService) {
            $transaction = Transaction::where('invoice_id', $data->merchant_ref)
                ->lockForUpdate()
                ->first();

            // If the transaction is already paid or success, we don't process it again to prevent double topup
            if (in_array($transaction->status, ['paid', 'success', 'failed'])) {
                return;
            }

            // Handle Payment Status
            if ($data->status === 'PAID') {
                $product = $transaction->product;
                $topupResult = $digiflazzService->createTransaction(
                    $product->provider_sku,
                    $transaction->customer_game_id . $transaction->customer_zone_id,
                    $transaction->invoice_id,
                );

                $transaction->update([
                    'payment_status' => 'paid',
                    'status'         => 'processing',
                ]);

                SendWhatsAppNotification::paymentReceived($transaction->load('product.game'))
                    ->dispatch();

                Log::info('Digiflazz Topup Result', ['ref' => $transaction->invoice_id, 'result' => $topupResult]);

            } elseif (in_array($data->status, ['EXPIRED', 'FAILED'])) {
                $transaction->update([
                    'payment_status' => 'expired',
                    'status'         => 'failed',
                ]);
            }
        });

        return response()->json(['success' => true]);
    }
}