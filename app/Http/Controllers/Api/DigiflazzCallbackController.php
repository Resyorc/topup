<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppNotification;
use App\Models\ErrorLog;
use App\Models\Game;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CoinService;
use App\Services\LoyaltyService;
use App\Services\TierService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

// Catatan: Digiflazz tidak menggunakan HMAC signature di webhook-nya.
// Keamanan dijamin via IP whitelist (ALLOWED_IPS).

class DigiflazzCallbackController extends Controller
{
    /**
     * Handle the incoming Digiflazz Webhook request.
     */
    // IP server Digiflazz yang diizinkan mengirim callback
    private const ALLOWED_IPS = ['52.74.250.133'];

    public function handle(Request $request)
    {
        $ip = $request->ip();

        // LOG IP untuk debug — identifikasi IP asli yang digunakan Digiflazz
        Log::info('Digiflazz callback masuk', ['ip' => $ip, 'allowed' => self::ALLOWED_IPS]);

        // TODO: aktifkan kembali setelah IP Digiflazz terverifikasi
        // if (! in_array($ip, self::ALLOWED_IPS, true)) {
        //     return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        // }

        $payload = $request->getContent();
        $data = json_decode($payload, true);

        Log::info('Digiflazz callback diterima', [
            'ref_id' => data_get($data, 'data.ref_id', 'unknown'),
            'status' => data_get($data, 'data.status', 'unknown'),
        ]);

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

            ErrorLog::create([
                'level'       => 'error',
                'message'     => "Digiflazz callback: transaksi tidak ditemukan untuk ref_id '{$refId}'.",
                'exception'   => 'DigiflazzTransactionNotFound',
                'file'        => __FILE__,
                'line'        => __LINE__,
                'trace'       => 'ref_id dari Digiflazz: '.$refId."\nStatus dari Digiflazz: ".($trxData['status'] ?? 'unknown'),
                'url'         => request()->fullUrl(),
                'method'      => 'POST',
                'ip'          => request()->ip(),
                'occurred_at' => now(),
            ]);

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
