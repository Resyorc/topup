<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppNotification;
use App\Models\Game;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CoinService;
use App\Services\LoyaltyService;
use App\Services\TierService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DigiflazzCallbackController extends Controller
{
    /**
     * Handle the incoming Digiflazz Webhook request.
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('x-hub-signature');
        $secret = config('services.digiflazz.webhook_secret'); // Secret used by Digiflazz as HMAC key

        $computedSignature = 'sha1='.hash_hmac('sha1', $payload, $secret);

        if (! hash_equals($computedSignature, (string) $signature)) {
            Log::warning('Digiflazz Callback Invalid Signature', ['received' => $signature, 'calculated' => $computedSignature]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid signature',
            ], 403);
        }

        $data = json_decode($payload, true);

        if (! isset($data['data']) || ! isset($data['data']['ref_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data payload representation',
            ], 400);
        }

        $trxData = $data['data'];
        $refId = $trxData['ref_id'];

        $transaction = Transaction::where('invoice_id', $refId)->first();

        if (! $transaction) {
            Log::error('Digiflazz Callback Transaction Not Found: '.$refId);

            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        if (in_array($transaction->status, ['success', 'failed'])) {
            return response()->json([
                'success' => true,
                'message' => 'Transaction already processed',
            ]);
        }

        // Processing the final top-up status from Provider
        $status = strtolower($trxData['status']);

        if ($status === 'sukses') {
            $transaction->update([
                'status' => 'success',
                'sn' => $trxData['sn'] ?? null,
            ]);

            // Increment total_sold di tabel games
            $gameId = $transaction->load('product.game')->product->game_id ?? null;
            if ($gameId) {
                Game::where('id', $gameId)->increment('total_sold');
            }

            // Berikan reward loyalitas (hanya user login, bukan bayar via Coin)
            app(LoyaltyService::class)->awardFromTransaction($transaction->load('product.game'));

            // Recalculate tier setelah transaksi sukses
            if ($transaction->user_id) {
                $txUser = User::find($transaction->user_id);
                if ($txUser) {
                    app(TierService::class)->recalculate($txUser);
                }
            }

            // Refresh agar loyalty_coins sudah terisi sebelum dikirim ke WA
            dispatch(SendWhatsAppNotification::topupSuccess($transaction->refresh()));

            Log::info("Digiflazz Topup SUKSES for Invoice: {$refId}");
        } elseif ($status === 'gagal') {
            $rc = $trxData['rc'] ?? 'Unknown RC';
            $transaction->update([
                'status' => 'failed',
                'failure_reason' => $rc,
            ]);

            // Refund coin jika transaksi dibayar dengan COIN
            if ($transaction->payment_method === 'COIN' && $transaction->user_id) {
                try {
                    $user = User::find($transaction->user_id);
                    if ($user) {
                        $refundAmount = (int) ($transaction->amount + $transaction->fee);
                        app(CoinService::class)->credit(
                            $user,
                            $refundAmount,
                            'Refund pesanan gagal: '.$transaction->invoice_id,
                            $transaction->invoice_id,
                        );
                        Log::info("Coin refunded {$refundAmount} to user {$user->id} for failed invoice: {$refId}");
                    }
                } catch (\Exception $e) {
                    Log::error("Coin refund failed for invoice {$refId}: ".$e->getMessage());
                }
            }

            dispatch(SendWhatsAppNotification::topupFailed($transaction->load('product.game')));

            Log::info("Digiflazz Topup GAGAL for Invoice: {$refId} - RC: {$rc}");
        }

        return response()->json(['success' => true]);
    }
}
