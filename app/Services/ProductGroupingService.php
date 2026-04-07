<?php

namespace App\Services;

use App\Models\Game;
use Illuminate\Support\Collection;

class ProductGroupingService
{
    /**
     * Group products using per-game grouping_rules and region_map from DB.
     *
     * If game has region_map:
     *   Returns: ['ID' => ['Diamonds' => [...], 'Membership' => [...]], 'MY' => [...]]
     *
     * If no region_map:
     *   Returns: ['Diamonds' => [...], 'Membership' => [...]]
     */
    public function groupByGame(Collection $products, Game $game): array
    {
        $fallbackGroupLabel = (string) config(
            'services.product_grouping.fallback_label',
            'Produk Lainnya',
        );

        $keywordRules = $this->buildKeywordRules($game);
        // region_map stored as [['country' => 'ID', 'sku_prefix' => 'mli'], ...]
        $regionMap = $this->buildRegionMap($game);

        $mapped = $products->map(fn ($product) => $this->mapProduct($product));

        if (empty($regionMap)) {
            return $this->groupByCategory($mapped, $keywordRules, $fallbackGroupLabel);
        }

        // Build reverse map: sku_prefix → country_code
        // e.g. ['mli' => 'ID', 'mlm' => 'MY']
        $prefixToRegion = [];
        foreach ($regionMap as $countryCode => $skuPrefix) {
            $prefixToRegion[strtolower($skuPrefix)] = strtoupper($countryCode);
        }

        // Group by region first, then by category
        $result = [];

        foreach ($mapped->groupBy(function (array $product) use ($prefixToRegion) {
            foreach ($prefixToRegion as $prefix => $region) {
                if (str_starts_with(strtolower($product['sku']), $prefix)) {
                    return $region;
                }
            }
            return 'OTHER';
        }) as $region => $regionProducts) {
            $result[$region] = $this->groupByCategory(
                collect($regionProducts),
                $keywordRules,
                $fallbackGroupLabel,
            );
        }

        return $result;
    }

    private function groupByCategory(Collection $products, array $keywordRules, string $fallbackLabel): array
    {
        $grouped = $products
            ->groupBy(function (array $product) use ($keywordRules, $fallbackLabel) {
                $name = strtolower($product['name']);
                foreach ($keywordRules as $groupLabel => $keywords) {
                    foreach ($keywords as $keyword) {
                        if (str_contains($name, strtolower(trim($keyword)))) {
                            return $groupLabel;
                        }
                    }
                }
                return $fallbackLabel;
            })
            ->map(fn ($group) => $group->values()->toArray());

        // Urutkan kategori sesuai urutan grouping_rules, fallback di akhir
        $order = array_keys($keywordRules);
        return collect($order)
            ->filter(fn ($key) => isset($grouped[$key]))
            ->mapWithKeys(fn ($key) => [$key => $grouped[$key]])
            ->when(isset($grouped[$fallbackLabel]), fn ($c) => $c->put($fallbackLabel, $grouped[$fallbackLabel]))
            ->toArray();
    }

    private function mapProduct($product): array
    {
        $userRole = auth()->check() ? auth()->user()->roles->first()?->name : 'Guest';
        $tier = strtolower($userRole ?? 'Guest');
        
        // Allowed tiers
        if (!in_array($tier, ['guest', 'bronze', 'silver', 'gold', 'platinum'])) {
            $tier = 'guest';
        }
        $priceField = "price_{$tier}";
        $basePrice = $product->$priceField ?? $product->price_guest ?? 0;

        $isFlashSale = $product->flash_sale_price !== null
            && $product->flash_sale_ends_at !== null
            && $product->flash_sale_ends_at->gt(now());

        $effectivePrice = $isFlashSale ? $product->flash_sale_price : $basePrice;
        $skuCode = $product->providerProducts?->first()?->provider_sku ?? ('SKU-' . $product->id);

        // Harga coret untuk flash sale: price_guest → fake_price → flash_sale_price × 1.2
        $flashOriginal = $basePrice ?: ($product->fake_price ?: (int) ceil($product->flash_sale_price * 1.2));

        return [
            'id'               => $product->id,
            'sku'              => $skuCode,
            'name'             => $product->name,
            'price'            => (int) ceil($effectivePrice),
            'original_price'   => $isFlashSale
                ? (int) ceil($flashOriginal)
                : ($product->fake_price ? (int) ceil($product->fake_price) : null),
            'discount_percent' => $isFlashSale
                ? ($flashOriginal > 0 ? (int) round((($flashOriginal - $product->flash_sale_price) / $flashOriginal) * 100) : 0)
                : ($product->fake_price && $product->fake_price > 0
                    ? (int) round((($product->fake_price - $basePrice) / $product->fake_price) * 100)
                    : 0),
            'extra'            => str_contains($product->name, '(')
                ? substr($product->name, strpos($product->name, '('))
                : null,
            'clean_name'       => str_contains($product->name, '(')
                ? trim(substr($product->name, 0, strpos($product->name, '(')))
                : $product->name,
            'flash_sale_ends_at'  => $isFlashSale ? $product->flash_sale_ends_at->timestamp : null,
            'flash_sale_stock'    => $isFlashSale ? $product->flash_sale_stock : null,
            'flash_sale_purchased' => $isFlashSale ? (int) $product->flash_sale_purchased : 0,
        ];
    }

    /**
     * Convert region_map repeater format to ['ID' => 'mli', 'MY' => 'mlm'].
     */
    private function buildRegionMap(Game $game): array
    {
        $raw = $game->region_map;
        if (empty($raw)) {
            return [];
        }

        $map = [];
        foreach ($raw as $entry) {
            $country = strtoupper($entry['country'] ?? '');
            $prefix = strtolower($entry['sku_prefix'] ?? '');
            if ($country && $prefix) {
                $map[$country] = $prefix;
            }
        }

        return $map;
    }

    /**
     * Convert game's grouping_rules JSON to keyword rules array.
     * DB format: [['group' => 'Weekly', 'keywords' => 'weekly, wdp'], ...]
     * Returns: ['Weekly' => ['weekly', 'wdp'], ...]
     */
    private function buildKeywordRules(Game $game): array
    {
        $rules = $game->grouping_rules;

        if (! empty($rules)) {
            $keywordRules = [];
            foreach ($rules as $rule) {
                $group = $rule['group'] ?? null;
                $keywords = $rule['keywords'] ?? '';
                if ($group) {
                    $keywordRules[$group] = array_map('trim', explode(',', $keywords));
                }
            }
            return $keywordRules;
        }

        $rulesBySlug = (array) config('services.product_grouping.rules_by_slug', []);
        $defaultRules = (array) config('services.product_grouping.default_rules', [
            'Diamond' => ['diamond'],
            'Event Top Up' => ['event'],
        ]);

        return $rulesBySlug[$game->slug] ?? $defaultRules;
    }
}
