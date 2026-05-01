<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessFulfilmentJob;
use App\Jobs\SendWhatsAppNotification;
use App\Models\CoinTopup;
use App\Models\Transaction;
use App\Services\CoinService;
use App\Services\OperationalLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class TripayCallbackController extends Controller
{
    public function handle(Request $request, CoinService $coinService)
    {
        $callbackSignature = (string) $request->header('X-Callback-Signature', '');
        $json = $request->getContent();
        $privateKey = (string) config('services.tripay.private_key', '');

        if ($privateKey === '') {
            OperationalLogger::critical('Tripay Callback Private Key Missing', [
                'event' => $request->header('X-Callback-Event'),
            ], request: $request, channel: 'payments');

            return response()->json(['success' => false, 'message' => 'Callback is not configured'], 500);
        }

        $signature = hash_hmac('sha256', $json, $privateKey);

        if (! hash_equals($signature, $callbackSignature)) {
            OperationalLogger::warning('Tripay Callback Invalid Signature', [
                'received' => $callbackSignature,
                'calculated' => $signature,
                'event' => $request->header('X-Callback-Event'),
            ], $request, 'payments');

            return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
        }

        if ($request->header('X-Callback-Event') !== 'payment_status') {
            return response()->json(['success' => false, 'message' => 'Not a payment event'], 400);
        }

        $data = json_decode($json);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_object($data)) {
            return response()->json(['success' => false, 'message' => 'Invalid JSON payload'], 400);
        }

        if (! isset($data->reference, $data->merchant_ref, $data->status)) {
            return response()->json(['success' => false, 'message' => 'Invalid data payload representation'], 400);
        }

        if (! isset($data->is_closed_payment) || ! $data->is_closed_payment) {
            return response()->json(['success' => false, 'message' => 'Open payment is not supported'], 400);
        }

        $status = strtoupper((string) $data->status);
        if (! in_array($status, ['PAID', 'EXPIRED', 'FAILED', 'REFUND'], true)) {
            OperationalLogger::warning('Tripay Callback Unknown Status', [
                'merchant_ref' => $data->merchant_ref,
                'reference' => $data->reference,
                'status' => $data->status,
            ], $request, 'payments');

            return response()->json(['success' => false, 'message' => 'Unknown payment status'], 400);
        }

        $coinTopup = CoinTopup::where('invoice_id', $data->merchant_ref)->first();

        if ($coinTopup) {
            $topupOutcome = DB::transaction(function () use ($coinTopup, $data, $status, $coinService) {
                $lockedTopup = CoinTopup::whereKey($coinTopup->id)->lockForUpdate()->firstOrFail();

                $referenceError = $this->validateTripayReference($lockedTopup, (string) $data->reference);
                if ($referenceError) {
                    return ['type' => 'invalid_reference', 'message' => $referenceError];
                }

                $amountError = $this->validateCallbackAmount(
                    $data,
                    (int) $lockedTopup->amount,
                    (int) $lockedTopup->amount + (int) data_get($lockedTopup->api_logs ?? [], 'fee_customer', 0)
                );
                if ($amountError) {
                    return ['type' => 'invalid_amount', 'message' => $amountError];
                }

                if (in_array($lockedTopup->status, ['paid', 'failed', 'expired'], true)) {
                    return ['type' => 'already_processed'];
                }

                if ($status === 'PAID') {
                    try {
                        $coinService->credit(
                            $lockedTopup->user,
                            (int) $lockedTopup->amount,
                            'Top up Krysta Coin',
                            $lockedTopup->invoice_id,
                        );
                    } catch (Throwable $e) {
                        if (! str_contains($e->getMessage(), 'Duplikat credit coin')) {
                            throw $e;
                        }
                    }

                    $lockedTopup->update(['status' => 'paid', 'paid_at' => now()]);

                    return [
                        'type' => 'coin_paid',
                        'invoice_id' => $lockedTopup->invoice_id,
                    ];
                }

                if (in_array($status, ['EXPIRED', 'FAILED', 'REFUND'], true)) {
                    $lockedTopup->update([
                        'status' => $status === 'EXPIRED' ? 'expired' : 'failed',
                        'failure_reason' => $status === 'EXPIRED'
                            ? 'Pembayaran melewati batas waktu (expired).'
                            : 'Pembayaran gagal.',
                    ]);

                    return ['type' => 'coin_failed'];
                }

                return ['type' => 'ignored'];
            });

            if (in_array($topupOutcome['type'], ['invalid_reference', 'invalid_amount'], true)) {
                OperationalLogger::warning('Tripay Coin Callback Ditolak', [
                    'merchant_ref' => $data->merchant_ref,
                    'reference' => $data->reference,
                    'status' => $status,
                    'reason' => $topupOutcome['message'],
                ], $request, 'payments');

                return response()->json(['success' => false, 'message' => $topupOutcome['message']], 422);
            }

            if ($topupOutcome['type'] === 'coin_paid') {
                $paidTopup = CoinTopup::with('user')
                    ->where('invoice_id', $topupOutcome['invoice_id'])
                    ->first();

                if ($paidTopup) {
                    dispatch(SendWhatsAppNotification::coinTopupSuccess($paidTopup));
                }
            }

            return response()->json(['success' => true]);
        }

        $transaction = Transaction::where('invoice_id', $data->merchant_ref)->first();

        if (! $transaction) {
            OperationalLogger::error('Tripay Callback Transaction Not Found', [
                'merchant_ref' => $data->merchant_ref,
                'reference' => $data->reference ?? null,
                'status' => $data->status ?? null,
            ], request: $request, channel: 'payments');

            return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
        }

        $shouldDispatchFulfilment = false;
        $fulfilmentInvoiceId = null;
        $shouldNotifyPaymentReceived = false;

        $transactionOutcome = DB::transaction(function () use ($data, $status, &$shouldDispatchFulfilment, &$fulfilmentInvoiceId, &$shouldNotifyPaymentReceived) {
            $transaction = Transaction::where('invoice_id', $data->merchant_ref)
                ->lockForUpdate()
                ->firstOrFail();

            $referenceError = $this->validateTripayReference($transaction, (string) $data->reference);
            if ($referenceError) {
                return ['type' => 'invalid_reference', 'message' => $referenceError];
            }

            $netAmount = max(0, (int) $transaction->amount - (int) $transaction->discount);
            $grossAmount = $netAmount + (int) $transaction->fee;
            $amountError = $this->validateCallbackAmount($data, $netAmount, $grossAmount);
            if ($amountError) {
                return ['type' => 'invalid_amount', 'message' => $amountError];
            }

            if ($status === 'PAID') {
                if ($transaction->status === 'success' || $transaction->fulfilment_status === 'success') {
                    return ['type' => 'already_processed'];
                }

                if ($transaction->payment_status !== 'paid') {
                    $shouldNotifyPaymentReceived = true;
                }

                if (! in_array($transaction->fulfilment_status, ['processing', 'success'], true)) {
                    $shouldDispatchFulfilment = true;
                    $fulfilmentInvoiceId = $transaction->invoice_id;
                }

                $transaction->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                    'fulfilment_status' => $shouldDispatchFulfilment
                        ? 'processing'
                        : $transaction->fulfilment_status,
                ]);

                return [
                    'type' => 'paid',
                    'invoice_id' => $transaction->invoice_id,
                ];
            }

            if (in_array($status, ['EXPIRED', 'FAILED', 'REFUND'], true)) {
                if ($transaction->payment_status === 'paid'
                    || in_array($transaction->fulfilment_status, ['processing', 'success'], true)) {
                    return ['type' => 'ignored_paid_order'];
                }

                $transaction->update([
                    'payment_status' => 'expired',
                    'status' => 'failed',
                    'fulfilment_status' => 'failed',
                    'failure_reason' => $status === 'EXPIRED'
                        ? 'Pembayaran melewati batas waktu (expired).'
                        : 'Pembayaran gagal.',
                ]);

                return ['type' => 'failed'];
            }

            return ['type' => 'ignored'];
        });

        if (in_array($transactionOutcome['type'], ['invalid_reference', 'invalid_amount'], true)) {
            OperationalLogger::warning('Tripay Callback Ditolak', [
                'merchant_ref' => $data->merchant_ref,
                'reference' => $data->reference,
                'status' => $status,
                'reason' => $transactionOutcome['message'],
            ], $request, 'payments');

            return response()->json(['success' => false, 'message' => $transactionOutcome['message']], 422);
        }

        if ($shouldNotifyPaymentReceived && isset($transactionOutcome['invoice_id'])) {
            $paidTransaction = Transaction::with('product.game')
                ->where('invoice_id', $transactionOutcome['invoice_id'])
                ->first();

            if ($paidTransaction) {
                dispatch(SendWhatsAppNotification::paymentReceived($paidTransaction));
            }
        }

        if ($shouldDispatchFulfilment && $fulfilmentInvoiceId) {
            ProcessFulfilmentJob::dispatch($fulfilmentInvoiceId)->afterCommit();
        }

        return response()->json(['success' => true]);
    }

    private function validateTripayReference(Transaction|CoinTopup $record, string $callbackReference): ?string
    {
        $expectedReference = $this->expectedTripayReference($record);

        if ($expectedReference !== null && ! hash_equals($expectedReference, $callbackReference)) {
            return 'Reference Tripay tidak cocok.';
        }

        return null;
    }

    private function expectedTripayReference(Transaction|CoinTopup $record): ?string
    {
        $apiLogs = $record->api_logs ?? [];

        $reference = data_get($apiLogs, 'tripay.reference')
            ?? data_get($apiLogs, 'reference')
            ?? $record->reference_id_provider;

        if (! is_string($reference) || $reference === '' || $reference === $record->invoice_id) {
            return null;
        }

        return $reference;
    }

    private function validateCallbackAmount(object $data, int ...$expectedAmounts): ?string
    {
        $callbackAmounts = collect([
            $data->total_amount ?? null,
            $data->amount ?? null,
            $data->paid_amount ?? null,
        ])
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();

        if ($callbackAmounts === []) {
            return 'Callback Tripay tidak memiliki nominal pembayaran.';
        }

        $expectedAmounts = array_values(array_unique(array_filter(
            $expectedAmounts,
            fn (int $amount) => $amount >= 0
        )));

        foreach ($callbackAmounts as $callbackAmount) {
            if (in_array($callbackAmount, $expectedAmounts, true)) {
                return null;
            }
        }

        return 'Nominal callback Tripay tidak cocok.';
    }
}
