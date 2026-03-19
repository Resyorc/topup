<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class DigiflazzService
{
    private string $username;

    private string $apiKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->username = config('services.digiflazz.username') ?? '';
        $this->apiKey = config('services.digiflazz.api_key') ?? '';
        $this->baseUrl = config('services.digiflazz.base_url') ?? 'https://api.digiflazz.com/v1';
    }

    /**
     * Get pre-configured HTTP client
     */
    private function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->timeout(30)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);
    }

    /**
     * Generate MD5 signature required by Digiflazz API
     */
    private function generateSignature(string $postfix): string
    {
        return md5($this->username.$this->apiKey.$postfix);
    }

    /**
     * Get account balance
     */
    public function checkBalance(): array
    {
        $payload = [
            'cmd' => 'deposit',
            'username' => $this->username,
            'sign' => $this->generateSignature('depo'),
        ];

        $response = $this->client()->post('/cek-saldo', $payload);

        if (! $response->successful()) {
            throw new Exception('Digiflazz API Error: '.$response->body());
        }

        return $response->json('data') ?? [];
    }

    /**
     * Get prepaid products (Price List)
     */
    public function getPrepaidProducts(): array
    {
        $payload = [
            'cmd' => 'prepaid',
            'username' => $this->username,
            'sign' => $this->generateSignature('pricelist'),
        ];

        $response = $this->client()->post('/price-list', $payload);

        if (! $response->successful()) {
            throw new Exception('Digiflazz API Error: '.$response->body());
        }

        return $response->json('data') ?? [];
    }

    /**
     * Create a top-up transaction
     */
    public function createTransaction(string $sku, string $customerNo, string $refId): array
    {
        $payload = [
            'username' => $this->username,
            'buyer_sku_code' => $sku,
            'customer_no' => $customerNo,
            'ref_id' => $refId,
            'sign' => $this->generateSignature($refId),
        ];

        $response = $this->client()->post('/transaction', $payload);

        if (! $response->successful()) {
            throw new Exception('Digiflazz API Error: '.$response->body());
        }

        $data = $response->json('data') ?? [];

        if (isset($data['status']) && strtolower($data['status']) === 'gagal') {
            throw new Exception('Digiflazz Transaction Error: '.($data['message'] ?? 'Unknown error'));
        }

        return $data;
    }

    /**
     * Request a deposit ticket
     */
    public function deposit(int $amount, string $bank, string $ownerName): array
    {
        $payload = [
            'username' => $this->username,
            'amount' => $amount,
            'Bank' => $bank,
            'owner_name' => $ownerName,
            'sign' => $this->generateSignature('deposit'),
        ];

        $response = $this->client()->post('/deposit', $payload);

        if (! $response->successful()) {
            throw new Exception('Digiflazz API Error: '.$response->body());
        }

        return $response->json('data') ?? [];
    }
}
