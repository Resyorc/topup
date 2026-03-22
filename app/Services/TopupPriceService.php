<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;

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
     *
     * @return array{updated: int, skipped: int}
     */
    public function syncPrices(): array
    {
        $digiflazz = app(DigiflazzService::class);
        $updated = 0;
        $skipped = 0;

        try {
            $apiProducts = $digiflazz->getPrepaidProducts();

            // Pluck API products by SKU for O(1) matching
            $apiProductMap = collect($apiProducts)->keyBy('buyer_sku_code');

            // Iterate through our database products chunk by chunk to prevent memory issues
            \App\Models\Product::chunk(200, function ($products) use ($apiProductMap, &$updated, &$skipped) {
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

                        $updated++;
                    } else {
                        $skipped++;
                    }
                }
            });

            return ['updated' => $updated, 'skipped' => $skipped];
        } catch (\Exception $e) {
            Log::error('Digiflazz Sync Failed: '.$e->getMessage());

            $this->notifyAdminSyncFailed($e->getMessage());

            return ['updated' => $updated, 'skipped' => $skipped];
        }
    }

    /**
     * Kirim notifikasi WhatsApp ke admin jika sync gagal.
     */
    private function notifyAdminSyncFailed(string $errorMessage): void
    {
        $adminPhone = config('services.fonnte.admin_whatsapp');

        if (empty($adminPhone)) {
            return;
        }

        $time = now()->format('d/m/Y H:i');
        $message = "⚠️ *Digiflazz Sync Gagal*\n\n"
            . "Waktu: {$time}\n"
            . "Error: {$errorMessage}\n\n"
            . "Harga produk mungkin tidak terupdate. Cek log server untuk detail.";

        app(FonnteService::class)->send($adminPhone, $message);
    }
}
