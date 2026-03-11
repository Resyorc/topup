<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Services\DigiflazzService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TripayCallbackController extends Controller
{
    /**
     * Handle the incoming Tripay Webhook request.
     */
    public function handle(Request $request, DigiflazzService $digiflazzService)
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
