<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Services\DigiflazzService;
use Illuminate\Support\Facades\Log;

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

        if ($signature !== $callbackSignature) {
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

        $transaction = Transaction::where('invoice_id', $data->merchant_ref)->first();

        if (!$transaction) {
            Log::error('Tripay Callback Transaction Not Found: ' . $data->merchant_ref);
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        // If the transaction is already paid or success, we don't process it again to prevent double topup
        if (in_array($transaction->status, ['paid', 'success', 'failed'])) {
            return response()->json([
                'success' => true,
                'message' => 'Transaction already processed',
            ]);
        }

        // Handle Payment Status
        if ($data->status === 'PAID') {
            $transaction->update(['status' => 'paid']);
            
            Log::info("Tripay Callback PAID for Invoice: {$transaction->invoice_id}. Prompting Digiflazz topup.");

            try {
                // Instantly request the topup to Digiflazz
                $targetCustomerNo = $transaction->customer_zone_id 
                    ? $transaction->customer_game_id . $transaction->customer_zone_id 
                    : $transaction->customer_game_id;

                $digiflazzResponse = $digiflazzService->createTransaction(
                    $transaction->product->provider_sku,
                    $targetCustomerNo,
                    $transaction->invoice_id
                );

                Log::info("Digiflazz Topup Executed for Invoice: {$transaction->invoice_id}", $digiflazzResponse);

            } catch (\Exception $e) {
                // If the topup request fails to reach Digiflazz entirely, we can set it to FAILED or leave it for manual checking
                Log::error("Digiflazz Topup Failed for Invoice: {$transaction->invoice_id} - " . $e->getMessage());
                // Notice we DO NOT change the transaction back to unpaid. The user paid, but our topup engine failed.
            }
            
        } elseif (in_array($data->status, ['EXPIRED', 'FAILED'])) {
            $transaction->update(['status' => 'failed']);
            Log::info("Tripay Callback FAILED for Invoice: {$transaction->invoice_id}.");
        }

        return response()->json(['success' => true]);
    }
}
