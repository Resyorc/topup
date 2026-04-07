<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProviderProduct;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TopupPriceService
{
    /**
     * Calculate selling price based on cost price and margins.
     */
    public function calculateSellPrice(float $costPrice, float $marginFlat): float
    {
        return $costPrice + $marginFlat;
    }

    /**
     * Sync prices from Digiflazz API.
     *
     * Step 1 — Perbarui katalog provider_products dari Digiflazz (harga, status, brand).
     * Step 2 — Update semua products. Cari SKU termurah di antara ProviderProducts milik product tsb.
     *           Tentukan price_cost termurah, lalu hitung ulang 5 tingkatan harga.
     *
     * @return array{updated: int, skipped: int}
     */
    public function syncPrices(): array
    {
        $digiflazz = app(DigiflazzService::class);
        $updated   = 0;
        $skipped   = 0;

        try {
            $apiProducts = $digiflazz->getPrepaidProducts();

            // ── Step 1: Reset & sync katalog untuk provider digiflazz ──────────
            ProviderProduct::where('provider_name', 'digiflazz')->update(['is_active' => false]);

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
                        'brand'        => $item['brand'] ?? null,
                        'price'        => (int) ($item['price'] ?? 0),
                        'seller_name'  => $item['seller_name'] ?? '-',
                        'is_active'    => $isAvailable,
                    ]
                );
            }

            // ── Step 2: Smart Routing & Update Products ────────────
            Product::with(['providerProducts' => function($q) {
                $q->where('is_active', true)->orderBy('price', 'asc');
            }])->chunk(200, function ($products) use (&$updated, &$skipped) {
                foreach ($products as $product) {
                    // Ambil provider_product yang aktif dan paling murah! (Smart Routing)
                    $cheapestProvider = $product->providerProducts->first();

                    if ($cheapestProvider) {
                        $cost = (float) $cheapestProvider->price;
                        
                        $product->update([
                            'price_cost'     => $cost,
                            // Kalkulasi 5 Tier Harga Berdasarkan Fixed Margin masing-masing
                            'price_guest'    => $this->calculateSellPrice($cost, (float) $product->margin_guest_flat),
                            'price_bronze'   => $this->calculateSellPrice($cost, (float) $product->margin_bronze_flat),
                            'price_silver'   => $this->calculateSellPrice($cost, (float) $product->margin_silver_flat),
                            'price_gold'     => $this->calculateSellPrice($cost, (float) $product->margin_gold_flat),
                            'price_platinum' => $this->calculateSellPrice($cost, (float) $product->margin_platinum_flat),
                            'is_available'   => true,
                        ]);

                        $updated++;
                    } else {
                        // Tidak ada SKU aktif untuk produk ini
                        if ($product->is_available) {
                            $product->update(['is_available' => false]);
                            Log::channel('digiflazz')->warning(
                                "Auto-Failover [Sync]: Product ID {$product->id} ({$product->name}) "
                                . "diputus — Tidak ada SKU provider yang aktif."
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
            Log::channel('digiflazz')->error('Digiflazz Sync Failed: ' . $e->getMessage());
            $this->notifyAdminSyncFailed($e->getMessage());

            return ['updated' => $updated, 'skipped' => $skipped];
        }
    }

    /**
     * Nonaktifkan SKU Digiflazz saat webhook gangguan diterima.
     * Karena mapping 1:N, matikan SKU-nya, lalu re-calculate Product (mencari jika ada SKU lain).
     */
    public function failoverProductBySku(string $skuCode): void
    {
        $providerProduct = ProviderProduct::where('provider_name', 'digiflazz')
            ->where('provider_sku', $skuCode)
            ->first();

        if ($providerProduct && $providerProduct->is_active) {
            $providerProduct->update(['is_active' => false]);
            Log::channel('digiflazz')->warning("Auto-Failover [Webhook]: SKU '{$skuCode}' dimatikan.");
            
            // Re-evaluasi Produk yang menempel pada SKU ini (Cari seller alternatif)
            if ($providerProduct->product_id) {
                $product = Product::with(['providerProducts' => function($q) {
                    $q->where('is_active', true)->orderBy('price', 'asc');
                }])->find($providerProduct->product_id);

                if ($product) {
                    $cheapest = $product->providerProducts->first();
                    if ($cheapest) {
                        // Gunakan harga alternatif jika ada
                        $cost = (float) $cheapest->price;
                        $product->update([
                            'price_cost'     => $cost,
                            'price_guest'    => $this->calculateSellPrice($cost, (float) $product->margin_guest_flat),
                            'price_bronze'   => $this->calculateSellPrice($cost, (float) $product->margin_bronze_flat),
                            'price_silver'   => $this->calculateSellPrice($cost, (float) $product->margin_silver_flat),
                            'price_gold'     => $this->calculateSellPrice($cost, (float) $product->margin_gold_flat),
                            'price_platinum' => $this->calculateSellPrice($cost, (float) $product->margin_platinum_flat),
                        ]);
                        Log::channel('digiflazz')->info("Auto-Recovery: Product ID {$product->id} switch ke Seller: {$cheapest->seller_name} (Rp{$cost})");
                    } else {
                        // Tidak ada alternatif
                        $product->update(['is_available' => false]);
                        Log::channel('digiflazz')->warning("Auto-Failover: Product ID {$product->id} diputus (Tidak ada alternatif seller).");
                    }
                }
            }
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
