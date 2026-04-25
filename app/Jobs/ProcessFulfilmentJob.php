<?php

namespace App\Jobs;

use App\Models\ErrorLog;
use App\Models\Transaction;
use App\Services\DigiflazzService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
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
            Log::warning('ProcessFulfilmentJob: transaksi tidak ditemukan', ['invoice_id' => $this->invoiceId]);
            return;
        }

        // Idempotency guard — sudah sukses atau gagal, tidak perlu diproses ulang
        if (in_array($transaction->fulfilment_status, ['success', 'failed'], true)) {
            return;
        }

        // Pastikan sudah dibayar sebelum fulfilment
        if ($transaction->payment_status !== 'paid') {
            Log::warning('ProcessFulfilmentJob: transaksi belum dibayar', ['invoice_id' => $this->invoiceId]);
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
                Log::error('ProcessFulfilmentJob: provider_sku kosong', [
                    'invoice_id' => $this->invoiceId,
                    'reason'     => $reason ?? 'unknown',
                ]);

                ErrorLog::create([
                    'level'       => 'error',
                    'message'     => "Fulfilment gagal: provider_sku kosong. {$reason}",
                    'exception'   => 'MissingProviderSku',
                    'file'        => __FILE__,
                    'line'        => __LINE__,
                    'trace'       => json_encode(['invoice_id' => $this->invoiceId]),
                    'url'         => null,
                    'method'      => 'JOB',
                    'ip'          => null,
                    'occurred_at' => now(),
                ]);

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
            $topupResult = $digiflazzService->createTransaction(
                $sku,
                $transaction->customer_game_id.$transaction->customer_zone_id,
                $transaction->invoice_id,
            );

            $transaction->update([
                'status'            => 'processing',
                'fulfilment_status' => 'processing',
            ]);

            Log::info('ProcessFulfilmentJob: Digiflazz request berhasil', [
                'invoice_id' => $this->invoiceId,
                'result'     => $topupResult,
            ]);
        } catch (Throwable $e) {
            Log::error('ProcessFulfilmentJob: Digiflazz gagal', [
                'invoice_id' => $this->invoiceId,
                'error'      => $e->getMessage(),
            ]);

            ErrorLog::create([
                'level'       => 'error',
                'message'     => 'Fulfilment gagal saat kirim ke Digiflazz: '.$e->getMessage(),
                'exception'   => get_class($e),
                'file'        => $e->getFile(),
                'line'        => $e->getLine(),
                'trace'       => mb_substr($e->getTraceAsString(), 0, 65535),
                'url'         => null,
                'method'      => 'JOB',
                'ip'          => null,
                'occurred_at' => now(),
            ]);

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
