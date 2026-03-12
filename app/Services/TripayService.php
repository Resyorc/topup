<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use Exception;

class TripayService
{
    private string $apiKey;
    private string $privateKey;
    private string $merchantCode;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.tripay.api_key') ?? '';
        $this->privateKey = config('services.tripay.private_key') ?? '';
        $this->merchantCode = config('services.tripay.merchant_code') ?? '';
        
        $mode = config('services.tripay.mode') ?? 'sandbox';
        $this->baseUrl = $mode === 'production' 
            ? 'https://tripay.co.id/api/' 
            : 'https://tripay.co.id/api-sandbox/';
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->timeout(30)
            ->withToken($this->apiKey)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]);
    }

    public function generateSignature(string $merchantRef, int $amount): string
    {
        $signaturePayload = $this->merchantCode . $merchantRef . $amount;
        return hash_hmac('sha256', $signaturePayload, $this->privateKey);
    }

    public function getPaymentChannels(): array
    {
        $response = $this->client()->get('merchant/payment-channel');
        if (!$response->successful()) {
            throw new Exception('Tripay API Error: ' . $response->body());
        }
        return $response->json('data') ?? [];
    }

    public function calculateFee(int $amount, ?string $method = null): array
    {
        $payload = ['amount' => $amount];
        if ($method) {
            $payload['code'] = $method;
        }
        $response = $this->client()->get('merchant/fee-calculator', $payload);
        if (!$response->successful()) {
            throw new Exception('Tripay API Error: ' . $response->body());
        }
        return $response->json('data') ?? [];
    }

    /**
     * Create a payment transaction
     *
     * @param int $expiredTime Unix timestamp kapan transaksi expired.
     *                         Default 0 = otomatis set 1 jam dari sekarang.
     */
    public function createTransaction(
        string $method,
        string $merchantRef,
        int $amount,
        string $customerName,
        string $customerEmail,
        string $customerPhone,
        array $orderItems,
        int $expiredTime = 0
    ): array {
        // Default expired 1 jam dari sekarang jika tidak di-set
        if ($expiredTime === 0) {
            $expiredTime = time() + (1 * 60 * 60);
        }

        $payload = [
            'method'         => $method,
            'merchant_ref'   => $merchantRef,
            'amount'         => $amount,
            'customer_name'  => $customerName,
            'customer_email' => $customerEmail,
            'customer_phone' => $customerPhone,
            'order_items'    => $orderItems,
            'expired_time'   => $expiredTime,
            'signature'      => $this->generateSignature($merchantRef, $amount),
        ];

        $response = $this->client()->post('transaction/create', $payload);

        if (!$response->successful()) {
            throw new Exception('Tripay Create Transaction Error: ' . $response->body());
        }

        return $response->json('data') ?? [];
    }
}