<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\DigiflazzService;
use App\Services\OperationalLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessFulfilmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public readonly string $invoiceId) {}

    public function handle(DigiflazzService $digiflazzService): void
    {
        $transaction = Transaction::where('invoice_id', $this->invoiceId)
            ->lockForUpdate()
            ->first();

        if (! $transaction) {
            OperationalLogger::warning('ProcessFulfilmentJob: transaksi tidak ditemukan', [
                'invoice_id' => $this->invoiceId,
            ], channel: 'payments');
            return;
        }

        // Idempotency guard — sudah sukses atau gagal, tidak perlu diproses ulang
        if (in_array($transaction->fulfilment_status, ['success', 'failed'], true)) {
            return;
        }

        // Pastikan sudah dibayar sebelum fulfilment
        if ($transaction->payment_status !== 'paid') {
            OperationalLogger::warning('ProcessFulfilmentJob: transaksi belum dibayar', [
                'invoice_id' => $this->invoiceId,
                'payment_status' => $transaction->payment_status,
            ], channel: 'payments');
            return;
        }

        $sku = (string) ($transaction->provider_sku ?? '');

        if ($sku === '') {
            $product = $transaction->product;

            if (! $product) {
                $reason = 'Produk tidak ditemukan (product_id tidak valid atau sudah dihapus).';
            } else {
                $totalProviderProducts  = $product->providerProducts()->count();
                $activeProviderProducts = $product->providerProducts()->where('is_active', true)->count();

                if ($totalProviderProducts === 0) {
                    $reason = 'Produk belum di-mapping ke provider (tidak ada provider_products).';
                } elseif ($activeProviderProducts === 0) {
                    $reason = 'Semua provider_products untuk produk ini tidak aktif.';
                } else {
                    $reason = null;
                }

                if ($reason === null) {
                    $sku = (string) ($product->providerProducts()
                        ->where('is_active', true)
                        ->orderBy('price', 'asc')
                        ->value('provider_sku') ?? '');

                    if ($sku === '') {
                        $reason = 'provider_sku aktif ditemukan namun nilainya kosong/null.';
                    }
                }
            }

            if ($sku === '') {
                OperationalLogger::error('ProcessFulfilmentJob: provider_sku kosong', [
                    'invoice_id' => $this->invoiceId,
                    'reason' => $reason ?? 'unknown',
                ], channel: 'payments');

                $transaction->update([
                    'status'            => 'failed',
                    'fulfilment_status' => 'failed',
                    'failure_reason'    => "Produk tidak valid: {$reason}",
                ]);

                return;
            }
        }

        $transaction->update(['fulfilment_status' => 'processing']);

        try {
            $transaction->forceFill([
                'provider_sku' => $sku,
            ])->save();

            $customerNo = (string) $transaction->customer_game_id.(string) ($transaction->customer_zone_id ?? '');
            $topupResult = $digiflazzService->createTransaction(
                $sku,
                $customerNo,
                $transaction->invoice_id
            );

            $transaction->update([
                'status'            => 'processing',
                'fulfilment_status' => 'processing',
                'reference_id_provider' => $topupResult['ref_id']
                    ?? $topupResult['message']
                    ?? $transaction->reference_id_provider,
                'api_logs' => $topupResult,
            ]);

            OperationalLogger::info('ProcessFulfilmentJob: provider request berhasil', [
                'invoice_id' => $this->invoiceId,
                'provider_sku' => $sku,
                'result' => $topupResult,
            ], 'payments');
        } catch (Throwable $e) {
            OperationalLogger::error('ProcessFulfilmentJob: provider gagal', [
                'invoice_id' => $this->invoiceId,
                'provider_sku' => $sku,
                'error' => $e->getMessage(),
            ], $e, channel: 'payments');

            $transaction->update([
                'status'            => 'failed',
                'fulfilment_status' => 'failed',
                'failure_reason'    => 'Topup gagal diproses provider. Silakan hubungi CS.',
            ]);

            // Lempar ulang agar job bisa di-retry oleh queue worker
            throw $e;
        }
    }
}
