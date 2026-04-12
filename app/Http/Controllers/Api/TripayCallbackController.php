<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppNotification;
use App\Models\CoinTopup;
use App\Models\ErrorLog;
use App\Models\MembershipOrder;
use App\Models\Transaction;
use App\Services\CoinService;
use App\Services\DigiflazzService;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        if (! hash_equals($signature, (string) $callbackSignature)) {
            Log::warning('Tripay Callback Invalid Signature', ['received' => $callbackSignature, 'calculated' => $signature]);

            ErrorLog::create([
                'level'       => 'warning',
                'message'     => 'Tripay callback ditolak: signature tidak cocok.',
                'exception'   => 'TripaySignatureMismatch',
                'file'        => __FILE__,
                'line'        => __LINE__,
                'trace'       => "Received: {$callbackSignature}\nCalculated: {$signature}\nPrivate key configured: ".($privateKey ? 'YES ('.strlen($privateKey).' chars)' : 'NOT SET'),
                'url'         => request()->fullUrl(),
                'method'      => 'POST',
                'ip'          => request()->ip(),
                'occurred_at' => now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid signature',
            ], 403);
        }

        if ($request->header('X-Callback-Event') !== 'payment_status') {
            return response()->json([
                'success' => false,
                'message' => 'Not a payment event',
            ], 400);
        }

        $data = json_decode($json);

        if (! isset($data->reference) || ! isset($data->merchant_ref)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data payload representation',
            ], 400);
        }

        // Sesuai dokumentasi Tripay — hanya proses closed payment (is_closed_payment = 1)
        // Open payment (0) tidak didukung karena jumlah bayar bisa berbeda
        if (! isset($data->is_closed_payment) || ! $data->is_closed_payment) {
            return response()->json([
                'success' => false,
                'message' => 'Open payment is not supported',
            ], 400);
        }

        // ── Cek MembershipOrder terlebih dahulu ────────────────────────────
        $membershipOrder = MembershipOrder::where('invoice_id', $data->merchant_ref)->first();

        if ($membershipOrder) {
            DB::transaction(function () use ($membershipOrder, $data) {
                $order = MembershipOrder::whereKey($membershipOrder->id)
                    ->lockForUpdate()->first();

                if (in_array($order->status, ['paid', 'failed', 'expired'], true)) {
                    return;
                }

                if ($data->status === 'PAID') {
                    // Upgrade tier user — hanya jika lebih tinggi
                    $tierOrder = ['bronze', 'silver', 'gold', 'platinum'];
                    $currentIdx = array_search($order->user->tier, $tierOrder);
                    $toIdx = array_search($order->to_tier, $tierOrder);

                    if ($toIdx !== false && ($currentIdx === false || $toIdx > $currentIdx)) {
                        $order->user->update(['tier' => $order->to_tier]);
                    }

                    $order->update([
                        'status'  => 'paid',
                        'paid_at' => now(),
                    ]);

                    Log::info("Membership upgraded: user #{$order->user_id} → {$order->to_tier} [{$order->invoice_id}]");
                } elseif (in_array($data->status, ['EXPIRED', 'FAILED'])) {
                    $order->update([
                        'status' => $data->status === 'EXPIRED' ? 'expired' : 'failed',
                    ]);
                }
            });

            return response()->json(['success' => true]);
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

                    dispatch(SendWhatsAppNotification::coinTopupSuccess($lockedTopup->load('user')));
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

        if (! $transaction) {
            Log::error('Tripay Callback Transaction Not Found: '.$data->merchant_ref);

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
                // Gunakan SKU yang di-snapshot saat checkout (paling reliable).
                // Fallback ke provider_products untuk transaksi lama yang dibuat sebelum kolom ini ada.
                $sku = (string) ($transaction->provider_sku ?? '');

                if ($sku === '') {
                    $product = $transaction->product;

                    if (! $product) {
                        $reason = 'Produk tidak ditemukan (product_id tidak valid atau sudah dihapus).';
                        $trace  = ['cause' => 'product_not_found'];
                    } else {
                        $totalProviderProducts  = $product->providerProducts()->count();
                        $activeProviderProducts = $product->providerProducts()->where('is_active', true)->count();

                        if ($totalProviderProducts === 0) {
                            $reason = 'Produk belum di-mapping ke provider (tidak ada provider_products).';
                            $trace  = ['cause' => 'no_provider_products', 'product_id' => $product->id, 'product_name' => $product->name];
                        } elseif ($activeProviderProducts === 0) {
                            $reason = 'Semua provider_products untuk produk ini tidak aktif (is_active = false).';
                            $trace  = ['cause' => 'no_active_provider_products', 'product_id' => $product->id, 'product_name' => $product->name, 'total' => $totalProviderProducts];
                        } else {
                            $reason = null;
                            $trace  = null;
                        }
                    }

                    if ($reason === null) {
                        $sku = (string) ($product->providerProducts()
                            ->where('is_active', true)
                            ->orderBy('price', 'asc')
                            ->value('provider_sku') ?? '');
                    }

                    if ($sku === '' && $reason === null) {
                        $reason = 'Record provider_products aktif ditemukan namun nilai provider_sku kosong/null.';
                        $trace  = ['cause' => 'empty_provider_sku_value', 'product_id' => $product->id, 'product_name' => $product->name];
                    }
                } else {
                    $reason = null;
                    $trace  = null;
                }

                if ($sku === '') {
                    Log::error('Tripay Callback Missing provider_sku', [
                        'invoice_id'    => $transaction->invoice_id,
                        'product_id'    => $transaction->product_id,
                        'merchant_ref'  => $data->merchant_ref,
                        'reason'        => $reason,
                    ]);

                    ErrorLog::create([
                        'level'      => 'error',
                        'message'    => "Tripay callback gagal diproses: provider_sku kosong. {$reason}",
                        'exception'  => 'MissingProviderSku',
                        'file'       => __FILE__,
                        'line'       => __LINE__,
                        'trace'      => json_encode(array_merge($trace ?? [], [
                            'invoice_id'   => $transaction->invoice_id,
                            'merchant_ref' => $data->merchant_ref,
                        ]), JSON_UNESCAPED_UNICODE),
                        'url'        => request()->fullUrl(),
                        'method'     => request()->method(),
                        'ip'         => request()->ip(),
                        'occurred_at' => now(),
                    ]);

                    $transaction->update([
                        'payment_status' => 'paid',
                        'status'         => 'failed',
                        'failure_reason' => "Produk tidak valid: {$reason}",
                    ]);

                    return;
                }

                try {
                    $topupResult = $digiflazzService->createTransaction(
                        $sku,
                        $transaction->customer_game_id.$transaction->customer_zone_id,
                        $transaction->invoice_id,
                    );

                    $transaction->update([
                        'payment_status' => 'paid',
                        'status' => 'processing',
                    ]);

                    dispatch(SendWhatsAppNotification::paymentReceived($transaction->load('product.game')));

                    Log::info('Digiflazz Topup Result', ['ref' => $transaction->invoice_id, 'result' => $topupResult]);
                } catch (Throwable $e) {
                    Log::error('Tripay Callback Digiflazz Transaction Failed', [
                        'invoice_id' => $transaction->invoice_id,
                        'product_id' => $transaction->product_id,
                        'merchant_ref' => $data->merchant_ref,
                        'error' => $e->getMessage(),
                    ]);

                    ErrorLog::create([
                        'level' => 'error',
                        'message' => 'Tripay callback gagal saat kirim transaksi ke Digiflazz.',
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                        'url' => request()->fullUrl(),
                        'method' => request()->method(),
                        'ip' => request()->ip(),
                        'occurred_at' => now(),
                    ]);

                    $transaction->update([
                        'payment_status' => 'paid',
                        'status' => 'failed',
                        'failure_reason' => 'Topup gagal diproses provider. Silakan hubungi CS.',
                    ]);
                }

            } elseif (in_array($data->status, ['EXPIRED', 'FAILED'])) {
                $transaction->update([
                    'payment_status' => 'expired',
                    'status' => 'failed',
                ]);
            }
        });

        return response()->json(['success' => true]);
    }
}
