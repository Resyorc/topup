<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProviderProduct;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TopupPriceService
{
    public function calculateSellPrice(float $costPrice, float $marginFlat): float
    {
        return $costPrice + $marginFlat;
    }

    /**
     * Sync prices from Digiflazz.
     *
     * @return array{updated: int, skipped: int}
     */
    public function syncPrices(): array
    {
        $updated = 0;
        $skipped = 0;

        try {
            ProviderProduct::where('provider_name', 'digiflazz')->update(['is_active' => false]);

            $this->syncDigiflazzCatalog();

            Product::with(['providerProducts' => function ($q) {
                $q->where('is_active', true)->orderBy('price', 'asc');
            }])->chunk(200, function ($products) use (&$updated, &$skipped) {
                foreach ($products as $product) {
                    $cheapestProvider = $product->providerProducts->first();

                    if ($cheapestProvider) {
                        $cost = (float) $cheapestProvider->price;

                        $product->update([
                            'price_cost' => $cost,
                            'price_sell' => $this->calculateSellPrice($cost, (float) $product->margin_flat),
                            'is_available' => true,
                        ]);

                        $updated++;
                    } else {
                        if ($product->is_available) {
                            $product->update(['is_available' => false]);
                            Log::channel('payments')->warning(
                                "Auto-Failover [Sync]: Product ID {$product->id} ({$product->name}) diputus - Tidak ada SKU provider yang aktif."
                            );
                            $updated++;
                        } else {
                            $skipped++;
                        }
                    }
                }
            });

            return ['updated' => $updated, 'skipped' => $skipped];
        } catch (\Exception $e) {
            Log::channel('payments')->error('Provider Sync Failed: '.$e->getMessage());
            $this->notifyAdminSyncFailed($e->getMessage());

            return ['updated' => $updated, 'skipped' => $skipped];
        }
    }

    public function failoverProductBySku(string $skuCode): void
    {
        $providerProduct = ProviderProduct::where('provider_name', 'digiflazz')
            ->where('provider_sku', $skuCode)
            ->first();

        if ($providerProduct && $providerProduct->is_active) {
            $providerProduct->update(['is_active' => false]);
            Log::channel('payments')->warning("Auto-Failover [Webhook]: Digiflazz SKU '{$skuCode}' dimatikan.");

            if ($providerProduct->product_id) {
                $product = Product::with(['providerProducts' => function ($q) {
                    $q->where('is_active', true)->orderBy('price', 'asc');
                }])->find($providerProduct->product_id);

                if ($product) {
                    $cheapest = $product->providerProducts->first();
                    if ($cheapest) {
                        $cost = (float) $cheapest->price;
                        $product->update([
                            'price_cost' => $cost,
                            'price_sell' => $this->calculateSellPrice($cost, (float) $product->margin_flat),
                        ]);
                        Log::channel('payments')->info(
                            "Auto-Recovery: Product ID {$product->id} switch ke seller {$cheapest->seller_name} dengan biaya Rp{$cost}"
                        );
                    } else {
                        $product->update(['is_available' => false]);
                        Log::channel('payments')->warning("Auto-Failover: Product ID {$product->id} diputus (Tidak ada alternatif seller).");
                    }
                }
            }
        }
    }

    private function notifyAdminSyncFailed(string $errorMessage): void
    {
        $adminEmail = config('services.admin.email');

        if (empty($adminEmail)) {
            return;
        }

        $time = now()->format('d/m/Y H:i');
        $body = "[Nuvelo] Provider Sync Gagal\n\n"
            . "Waktu: {$time}\n"
            . "Error: {$errorMessage}\n\n"
            . "Harga produk mungkin tidak terupdate. Cek log server untuk detail.";

        Mail::raw($body, function ($message) use ($adminEmail, $time) {
            $message->to($adminEmail)
                ->subject("[Nuvelo] Provider Sync Gagal - {$time}");
        });
    }

    private function syncDigiflazzCatalog(): void
    {
        $apiProducts = app(DigiflazzService::class)->getPrepaidProducts();

        foreach ($apiProducts as $item) {
            $sku = $item['buyer_sku_code'] ?? null;
            if (! $sku) {
                continue;
            }

            $isAvailable = ($item['buyer_product_status'] ?? false) === true
                && ($item['seller_product_status'] ?? false) === true;

            ProviderProduct::updateOrCreate(
                ['provider_name' => 'digiflazz', 'provider_sku' => $sku],
                [
                    'product_name' => $item['product_name'] ?? $sku,
                    'brand' => $item['brand'] ?? null,
                    'price' => (int) ($item['price'] ?? 0),
                    'seller_name' => $item['seller_name'] ?? '-',
                    'is_active' => $isAvailable,
                ]
            );
        }
    }

}
