<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppNotification;
use App\Models\Game;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CoinService;
use App\Services\LoyaltyService;
use App\Services\OperationalLogger;
use App\Services\TopupPriceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Catatan: Digiflazz tidak menggunakan HMAC signature di webhook-nya.
// Keamanan dijamin via IP whitelist (ALLOWED_IPS).

class DigiflazzCallbackController extends Controller
{
    /**
     * Handle the incoming Digiflazz Webhook request.
     */
    private const ALLOWED_IPS = ['52.74.250.133'];

    public function handle(Request $request)
    {
        $ip = $request->ip();

        OperationalLogger::info('Digiflazz callback masuk', [
            'ip' => $ip,
        ], 'payments');

        if (! in_array($ip, self::ALLOWED_IPS, true)) {
            OperationalLogger::warning('Digiflazz callback ditolak - IP tidak diizinkan', [
                'ip' => $ip,
            ], $request, 'payments');

            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $payload = $request->getContent();
        $data = json_decode($payload, true);

        OperationalLogger::info('Digiflazz callback diterima', [
            'ref_id' => data_get($data, 'data.ref_id', 'unknown'),
            'status' => data_get($data, 'data.status', 'unknown'),
        ], 'payments');

        if (! isset($data['data'], $data['data']['ref_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data payload representation',
            ], 400);
        }

        $trxData = $data['data'];
        $refId = $trxData['ref_id'];
        $status = strtolower((string) ($trxData['status'] ?? ''));

        $outcome = DB::transaction(function () use ($refId, $status, $trxData) {
            $transaction = Transaction::with(['product.game'])
                ->where('invoice_id', $refId)
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                return ['type' => 'missing'];
            }

            if (in_array($transaction->status, ['success', 'failed'], true)) {
                return ['type' => 'already_processed'];
            }

            if ($status === 'sukses') {
                $transaction->update([
                    'status' => 'success',
                    'sn' => $trxData['sn'] ?? null,
                ]);

                $gameId = $transaction->product->game_id ?? null;
                if ($gameId) {
                    Game::where('id', $gameId)->increment('total_sold');
                }

                return [
                    'type' => 'success',
                    'invoice_id' => $transaction->invoice_id,
                ];
            }

            if ($status === 'gagal') {
                $failureReason = $trxData['rc'] ?? 'Unknown RC';

                $transaction->update([
                    'status' => 'failed',
                    'failure_reason' => $failureReason,
                ]);

                return [
                    'type' => 'failed',
                    'invoice_id' => $transaction->invoice_id,
                    'failure_reason' => $failureReason,
                    'user_id' => $transaction->user_id,
                    'payment_method' => $transaction->payment_method,
                    'refund_amount' => max(0, (int) $transaction->amount - (int) $transaction->discount + (int) $transaction->fee),
                ];
            }

            if ($status === 'gangguan') {
                $skuCode = $trxData['buyer_sku_code'] ?? null;

                if ($skuCode) {
                    app(TopupPriceService::class)->failoverProductBySku($skuCode);
                }

                return [
                    'type' => 'gangguan',
                    'invoice_id' => $transaction->invoice_id,
                    'sku_code' => $skuCode,
                    'failure_reason' => $trxData['rc'] ?? '-',
                ];
            }

            return ['type' => 'ignored'];
        });

        if ($outcome['type'] === 'missing') {
            OperationalLogger::error('Digiflazz Callback Transaction Not Found', [
                'ref_id' => $refId,
                'status' => $trxData['status'] ?? 'unknown',
            ], request: $request, channel: 'payments');

            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        if ($outcome['type'] === 'already_processed') {
            return response()->json([
                'success' => true,
                'message' => 'Transaction already processed',
            ]);
        }

        if ($outcome['type'] === 'success') {
            $transaction = Transaction::with(['product.game'])
                ->where('invoice_id', $outcome['invoice_id'])
                ->firstOrFail();

            app(LoyaltyService::class)->awardFromTransaction($transaction);
            dispatch(SendWhatsAppNotification::topupSuccess($transaction->fresh(['product.game'])));

            OperationalLogger::info('Digiflazz Topup SUKSES', [
                'invoice_id' => $refId,
                'transaction_id' => $transaction->id,
            ], 'payments');
        } elseif ($outcome['type'] === 'failed') {
            $transaction = Transaction::with(['product.game'])
                ->where('invoice_id', $outcome['invoice_id'])
                ->firstOrFail();

            if ($outcome['payment_method'] === 'COIN' && $outcome['user_id']) {
                try {
                    $user = User::find($outcome['user_id']);

                    if ($user && $outcome['refund_amount'] > 0) {
                        app(CoinService::class)->credit(
                            $user,
                            $outcome['refund_amount'],
                            'Refund pesanan gagal: '.$transaction->invoice_id,
                            $transaction->invoice_id,
                        );

                        OperationalLogger::info('Coin refund berhasil untuk transaksi gagal', [
                            'invoice_id' => $refId,
                            'user_id' => $user->id,
                            'refund_amount' => $outcome['refund_amount'],
                        ], 'payments');
                    }
                } catch (\Exception $e) {
                    OperationalLogger::error('Coin refund gagal untuk transaksi Digiflazz failed', [
                        'invoice_id' => $refId,
                        'refund_amount' => $outcome['refund_amount'],
                    ], $e, $request, 'payments');
                }
            }

            dispatch(SendWhatsAppNotification::topupFailed($transaction));

            OperationalLogger::info('Digiflazz Topup GAGAL', [
                'invoice_id' => $refId,
                'failure_reason' => $outcome['failure_reason'],
            ], 'payments');
        } elseif ($outcome['type'] === 'gangguan') {
            Log::channel('digiflazz')->warning(
                "Webhook GANGGUAN diterima: ref_id={$outcome['invoice_id']}, SKU=".($outcome['sku_code'] ?? '-').", RC={$outcome['failure_reason']}"
            );
        }

        return response()->json(['success' => true]);
    }
}
