<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\DigiflazzService;
use App\Services\OperationalLogger;
use App\Services\ProviderSkuSelector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessFulfilmentJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public int $uniqueFor = 300;

    public function __construct(
        public readonly string $invoiceId,
        public readonly bool $force = false,
    ) {}

    public function uniqueId(): string
    {
        return $this->invoiceId;
    }

    public function handle(DigiflazzService $digiflazzService, ProviderSkuSelector $providerSkuSelector): void
    {
        Cache::lock('fulfilment:'.$this->invoiceId, 300)->block(5, function () use ($digiflazzService, $providerSkuSelector) {
            $this->process($digiflazzService, $providerSkuSelector);
        });
    }

    private function process(DigiflazzService $digiflazzService, ProviderSkuSelector $providerSkuSelector): void
    {
        $transaction = DB::transaction(function () use ($providerSkuSelector) {
            $transaction = Transaction::with('product')
                ->where('invoice_id', $this->invoiceId)
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                OperationalLogger::warning('ProcessFulfilmentJob: transaksi tidak ditemukan', [
                    'invoice_id' => $this->invoiceId,
                ], channel: 'payments');

                return null;
            }

            if ($transaction->fulfilment_status === 'success') {
                return null;
            }

            if (! $this->force && $transaction->fulfilment_status === 'failed') {
                return null;
            }

            if ($transaction->payment_status !== 'paid') {
                OperationalLogger::warning('ProcessFulfilmentJob: transaksi belum dibayar', [
                    'invoice_id' => $this->invoiceId,
                    'payment_status' => $transaction->payment_status,
                ], channel: 'payments');

                return null;
            }

            $providerProduct = $this->resolveProviderProduct($transaction, $providerSkuSelector);

            if ($providerProduct === null) {
                return null;
            }

            $transaction->forceFill([
                'provider_sku' => $providerProduct->provider_sku,
                'provider_name' => $providerProduct->provider_name,
                'status' => 'processing',
                'fulfilment_status' => 'processing',
                'failure_reason' => null,
            ])->save();

            return $transaction->fresh(['product']);
        });

        if (! $transaction) {
            return;
        }

        $sku = (string) $transaction->provider_sku;

        try {
            $customerNo = (string) $transaction->customer_game_id.(string) ($transaction->customer_zone_id ?? '');
            $topupResult = $digiflazzService->createTransaction(
                $sku,
                $customerNo,
                $transaction->invoice_id
            );

            $transaction->update([
                'status' => 'processing',
                'fulfilment_status' => 'processing',
                'reference_id_provider' => $topupResult['ref_id']
                    ?? $topupResult['message']
                    ?? $transaction->reference_id_provider,
                'api_logs' => $this->mergeApiLogs($transaction->fresh() ?? $transaction, 'digiflazz', $topupResult),
                'failure_reason' => null,
            ]);

            OperationalLogger::info('ProcessFulfilmentJob: provider request berhasil', [
                'invoice_id' => $this->invoiceId,
                'provider_sku' => $sku,
                'result' => $topupResult,
            ], 'payments');
        } catch (Throwable $e) {
            OperationalLogger::warning('ProcessFulfilmentJob: provider request ambigu, mencoba sinkronisasi ulang', [
                'invoice_id' => $this->invoiceId,
                'provider_sku' => $sku,
                'error' => $e->getMessage(),
            ], channel: 'payments');

            $this->reconcileAfterProviderException($transaction->fresh(), $digiflazzService, $sku, $e);

            throw $e;
        }
    }

    private function resolveProviderProduct(
        Transaction $transaction,
        ProviderSkuSelector $providerSkuSelector,
    ): ?\App\Models\ProviderProduct {
        $sku = (string) ($transaction->provider_sku ?? '');
        $product = $transaction->product;

        if (! $product) {
            $reason = 'Produk tidak ditemukan (product_id tidak valid atau sudah dihapus).';
        } else {
            if ($sku !== '') {
                $currentProvider = $providerSkuSelector->activeBySkuForProduct(
                    $product,
                    $sku,
                    $transaction->provider_name,
                );

                if ($currentProvider) {
                    return $currentProvider;
                }

                OperationalLogger::warning('ProcessFulfilmentJob: provider_sku transaksi tidak aktif, memilih alternatif', [
                    'invoice_id' => $this->invoiceId,
                    'provider_sku' => $sku,
                    'provider_name' => $transaction->provider_name,
                ], channel: 'payments');
            }

            $providerProduct = $providerSkuSelector->bestForProduct($product);

            if ($providerProduct) {
                return $providerProduct;
            }

            $totalProviderProducts = $product->providerProducts()->count();
            $activeProviderProducts = $product->providerProducts()->where('is_active', true)->count();

            $reason = match (true) {
                $totalProviderProducts === 0 => 'Produk belum di-mapping ke provider (tidak ada provider_products).',
                $activeProviderProducts === 0 => 'Semua provider_products untuk produk ini tidak aktif.',
                default => 'provider_sku aktif ditemukan namun tidak bisa dipilih.',
            };
        }

        OperationalLogger::error('ProcessFulfilmentJob: provider_sku kosong', [
            'invoice_id' => $this->invoiceId,
            'reason' => $reason ?? 'unknown',
        ], channel: 'payments');

        $transaction->update([
            'status' => 'failed',
            'fulfilment_status' => 'failed',
            'failure_reason' => "Produk tidak valid: {$reason}",
        ]);

        return null;
    }

    private function reconcileAfterProviderException(
        Transaction $transaction,
        DigiflazzService $digiflazzService,
        string $sku,
        Throwable $exception,
    ): void {
        $customerNo = (string) $transaction->customer_game_id.(string) ($transaction->customer_zone_id ?? '');

        try {
            $statusResult = $digiflazzService->checkTransactionStatus(
                $sku,
                $customerNo,
                $transaction->invoice_id
            );

            $status = strtolower((string) ($statusResult['status'] ?? $statusResult['transaction_status'] ?? ''));
            $serialNumber = $statusResult['sn'] ?? $statusResult['serial_number'] ?? null;
            $failureReason = $statusResult['rc'] ?? $statusResult['message'] ?? $statusResult['note'] ?? $exception->getMessage();

            if (in_array($status, ['sukses', 'success', 'sandbox - sukses'], true)) {
                $transaction->update([
                    'status' => 'success',
                    'payment_status' => $transaction->payment_status ?: 'paid',
                    'fulfilment_status' => 'success',
                    'reference_id_provider' => $statusResult['ref_id']
                        ?? $statusResult['message']
                        ?? $transaction->reference_id_provider,
                    'sn' => $serialNumber,
                    'api_logs' => $this->mergeApiLogs($transaction, 'digiflazz_reconcile', $statusResult),
                    'failure_reason' => null,
                ]);

                OperationalLogger::info('ProcessFulfilmentJob: transaksi berhasil direkonsiliasi setelah error provider', [
                    'invoice_id' => $transaction->invoice_id,
                    'provider_sku' => $sku,
                    'result' => $statusResult,
                ], 'payments');

                return;
            }

            if (in_array($status, ['gagal', 'failed'], true)) {
                $transaction->update([
                    'status' => 'failed',
                    'fulfilment_status' => 'failed',
                    'reference_id_provider' => $statusResult['ref_id']
                        ?? $statusResult['message']
                        ?? $transaction->reference_id_provider,
                    'api_logs' => $this->mergeApiLogs($transaction, 'digiflazz_reconcile', $statusResult),
                    'failure_reason' => $failureReason,
                ]);

                OperationalLogger::warning('ProcessFulfilmentJob: transaksi gagal setelah rekonsiliasi provider', [
                    'invoice_id' => $transaction->invoice_id,
                    'provider_sku' => $sku,
                    'result' => $statusResult,
                ], channel: 'payments');

                return;
            }
        } catch (Throwable $syncException) {
            OperationalLogger::warning('ProcessFulfilmentJob: sync status setelah error provider juga gagal', [
                'invoice_id' => $transaction->invoice_id,
                'provider_sku' => $sku,
                'error' => $syncException->getMessage(),
            ], channel: 'payments');
        }

        $transaction->update([
            'status' => 'processing',
            'fulfilment_status' => 'processing',
            'failure_reason' => 'Transaksi sedang diverifikasi ke provider setelah gangguan koneksi.',
        ]);
    }

    private function mergeApiLogs(Transaction $transaction, string $key, array $payload): array
    {
        $logs = $transaction->api_logs ?? [];
        if (! is_array($logs)) {
            $logs = [];
        }

        $logs[$key] = $payload;

        return $logs;
    }
}
