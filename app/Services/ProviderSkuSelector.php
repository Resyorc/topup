<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProviderProduct;
use Illuminate\Database\Eloquent\Collection;

class ProviderSkuSelector
{
    public function bestForProduct(Product $product): ?ProviderProduct
    {
        return $this->queryActiveCandidates($product)->first();
    }

    /**
     * @return Collection<int, ProviderProduct>
     */
    public function activeCandidates(Product $product): Collection
    {
        return $this->queryActiveCandidates($product)->get();
    }

    public function activeBySkuForProduct(
        Product $product,
        string $providerSku,
        ?string $providerName = null,
    ): ?ProviderProduct {
        return $product->providerProducts()
            ->where('provider_sku', $providerSku)
            ->when($providerName, fn ($query) => $query->where('provider_name', $providerName))
            ->where('is_active', true)
            ->first();
    }

    private function queryActiveCandidates(Product $product)
    {
        return $product->providerProducts()
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('price')
            ->orderBy('id');
    }
}
