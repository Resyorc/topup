<?php

declare(strict_types=1);

namespace App\Services;

class TopupPriceService
{
    /**
     * Calculate selling price based on cost price and margins.
     */
    public function calculateSellPrice(float $costPrice, float $marginFlat, float $marginPercent): float
    {
        return $costPrice + $marginFlat + ($costPrice * ($marginPercent / 100)); // + Gateway fee
    }

    /**
     * Sync prices from Provider.
     */
    public function syncPrices(): void
    {
        $digiflazz = app(DigiflazzService::class);
        
        try {
            $apiProducts = $digiflazz->getPrepaidProducts();
            
            // Pluck API products by SKU for O(1) matching
            $apiProductMap = collect($apiProducts)->keyBy('buyer_sku_code');

            // Iterate through our database products chunk by chunk to prevent memory issues
            \App\Models\Product::chunk(200, function ($products) use ($apiProductMap) {
                foreach ($products as $product) {
                    if ($apiProductMap->has($product->provider_sku)) {
                        $providerData = $apiProductMap->get($product->provider_sku);
                        
                        $cost = (float) $providerData['price'];
                        // Digiflazz depends on both seller and buyer status
                        $isAvailable = $providerData['buyer_product_status'] === true && $providerData['seller_product_status'] === true;

                        // Calculate new sell price
                        $sell = $this->calculateSellPrice($cost, (float) $product->margin_flat, (float) $product->margin_percent);

                        $product->update([
                            'price_cost' => $cost,
                            'price_sell' => $sell,
                            'is_available' => $isAvailable,
                        ]);
                    }
                }
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Digiflazz Sync Failed: ' . $e->getMessage());
        }
    }
}
