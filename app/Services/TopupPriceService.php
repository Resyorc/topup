<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Game;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
     * - UPDATE: produk yang sudah ada di DB, update harga & status.
     * - CREATE: produk baru dari Digiflazz yang brand-nya cocok dengan game di DB.
     * - SKIP: produk yang brand-nya tidak ada di DB (bukan game kita).
     *
     * @return array{updated: int, created: int, skipped: int}
     */
    public function syncPrices(): array
    {
        $digiflazz = app(DigiflazzService::class);
        $updated = 0;
        $created = 0;
        $skipped = 0;

        try {
            $apiProducts = $digiflazz->getPrepaidProducts();

            // Pluck API products by SKU for O(1) matching
            $apiProductMap = collect($apiProducts)->keyBy('buyer_sku_code');

            // Ambil semua SKU yang sudah ada di DB untuk deteksi produk baru
            $existingSkus = Product::pluck('provider_sku')->flip();

            // Buat map game: lowercase(name) => game_id untuk matching brand Digiflazz
            $gameMap = Game::pluck('id', 'name')
                ->mapWithKeys(fn ($id, $name) => [mb_strtolower(trim($name)) => $id]);

            // 1. UPDATE produk yang sudah ada
            Product::chunk(200, function ($products) use ($apiProductMap, &$updated, &$skipped) {
                foreach ($products as $product) {
                    if ($apiProductMap->has($product->provider_sku)) {
                        $providerData = $apiProductMap->get($product->provider_sku);

                        $cost        = (float) $providerData['price'];
                        $isAvailable = $providerData['buyer_product_status'] === true
                            && $providerData['seller_product_status'] === true;
                        $sell        = $this->calculateSellPrice($cost, (float) $product->margin_flat, (float) $product->margin_percent);

                        $product->update([
                            'price_cost'   => $cost,
                            'price_sell'   => $sell,
                            'is_available' => $isAvailable,
                        ]);

                        $updated++;
                    } else {
                        $skipped++;
                    }
                }
            });

            // 2. CREATE produk baru — hanya yang brand-nya cocok dengan game di DB
            foreach ($apiProducts as $item) {
                $sku = $item['buyer_sku_code'] ?? null;

                if (! $sku || $existingSkus->has($sku)) {
                    continue; // sudah ada, sudah diupdate di atas
                }

                $brandKey = mb_strtolower(trim($item['brand'] ?? ''));
                $gameId   = $gameMap->get($brandKey);

                if (! $gameId) {
                    continue; // brand tidak dikenal, bukan game kita
                }

                $isAvailable = ($item['buyer_product_status'] ?? false) === true
                    && ($item['seller_product_status'] ?? false) === true;
                $cost = (float) ($item['price'] ?? 0);

                Product::create([
                    'game_id'      => $gameId,
                    'provider_sku' => $sku,
                    'name'         => $item['product_name'] ?? $sku,
                    'price_cost'   => $cost,
                    'price_sell'   => $cost, // margin 0 by default, admin set via Filament
                    'margin_flat'  => 0,
                    'margin_percent' => 0,
                    'is_available' => $isAvailable,
                ]);

                $created++;
            }

            return ['updated' => $updated, 'created' => $created, 'skipped' => $skipped];
        } catch (\Exception $e) {
            Log::error('Digiflazz Sync Failed: '.$e->getMessage());

            $this->notifyAdminSyncFailed($e->getMessage());

            return ['updated' => $updated, 'created' => $created, 'skipped' => $skipped];
        }
    }

    /**
     * Kirim notifikasi email ke admin jika sync gagal.
     */
    private function notifyAdminSyncFailed(string $errorMessage): void
    {
        $adminEmail = config('services.admin.email');

        if (empty($adminEmail)) {
            return;
        }

        $time = now()->format('d/m/Y H:i');
        $body = "[Nuvelo] Digiflazz Sync Gagal\n\n"
            . "Waktu: {$time}\n"
            . "Error: {$errorMessage}\n\n"
            . "Harga produk mungkin tidak terupdate. Cek log server untuk detail.";

        Mail::raw($body, function ($message) use ($adminEmail, $time) {
            $message->to($adminEmail)
                ->subject("[Nuvelo] ⚠️ Digiflazz Sync Gagal — {$time}");
        });
    }
}
