<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProviderProduct;
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
            $this->syncDigiflazzCatalog();

            Product::query()->chunk(200, function ($products) use (&$updated, &$skipped) {
                foreach ($products as $product) {
                    if ($this->refreshProductPricing($product)) {
                        $updated++;

                        continue;
                    }

                    $skipped++;
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
                $product = Product::find($providerProduct->product_id);

                if ($product) {
                    $this->refreshProductPricing($product);
                }
            }
        }
    }

    public function refreshProductPricing(Product $product): bool
    {
        $bestProvider = app(ProviderSkuSelector::class)->bestForProduct($product);

        if ($bestProvider) {
            $cost = (float) $bestProvider->price;

            $product->update([
                'price_cost' => $cost,
                'price_sell' => $this->calculateSellPrice($cost, (float) $product->margin_flat),
                'is_available' => true,
            ]);

            Log::channel('payments')->info('Product pricing refreshed from provider alternative', [
                'product_id' => $product->id,
                'provider_sku' => $bestProvider->provider_sku,
                'seller_name' => $bestProvider->seller_name,
                'priority' => $bestProvider->priority,
                'price_cost' => $cost,
            ]);

            return true;
        }

        if ($product->is_available) {
            $product->update(['is_available' => false]);
            Log::channel('payments')->warning(
                "Auto-Failover: Product ID {$product->id} ({$product->name}) dinonaktifkan - tidak ada SKU provider aktif."
            );

            return true;
        }

        return false;
    }

    /**
     * Ambil seluruh pricelist Digiflazz dan sinkronkan ke provider_products.
     *
     * @return array{synced: int, active: int, inactive: int}
     */
    public function syncDigiflazzCatalog(): array
    {
        ProviderProduct::where('provider_name', 'digiflazz')->update(['is_active' => false]);

        return $this->upsertDigiflazzCatalog(app(DigiflazzService::class)->getPrepaidProducts());
    }

    private function notifyAdminSyncFailed(string $errorMessage): void
    {
        $adminEmail = config('services.admin.email');

        if (empty($adminEmail)) {
            return;
        }

        $time = now()->format('d/m/Y H:i');
        $body = "[Nuvelo] Provider Sync Gagal\n\n"
            ."Waktu: {$time}\n"
            ."Error: {$errorMessage}\n\n"
            .'Harga produk mungkin tidak terupdate. Cek log server untuk detail.';

        Mail::raw($body, function ($message) use ($adminEmail, $time) {
            $message->to($adminEmail)
                ->subject("[Nuvelo] Provider Sync Gagal - {$time}");
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $apiProducts
     * @return array{synced: int, active: int, inactive: int}
     */
    private function upsertDigiflazzCatalog(array $apiProducts): array
    {
        $synced = 0;
        $active = 0;
        $inactive = 0;

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

            $synced++;
            $isAvailable ? $active++ : $inactive++;
        }

        return [
            'synced' => $synced,
            'active' => $active,
            'inactive' => $inactive,
        ];
    }
}
