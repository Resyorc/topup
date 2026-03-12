<?php

namespace App\Services;

use Illuminate\Support\Collection;

class ProductGroupingService
{
    /**
     * Group products dynamically by game slug + keyword rules.
     */
    public function groupByGameSlug(Collection $products, string $gameSlug): array
    {
        $rulesBySlug = (array) config('services.product_grouping.rules_by_slug', []);
        $defaultRules = (array) config('services.product_grouping.default_rules', [
            'Diamond' => ['diamond'],
            'Event Top Up' => ['event'],
        ]);
        $fallbackGroupLabel = (string) config(
            'services.product_grouping.fallback_label',
            'Produk Lainnya',
        );

        $activeGroupRules = $rulesBySlug[$gameSlug] ?? $defaultRules;

        return $products
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => (float) $product->price_sell,
                    'extra' => str_contains($product->name, '(')
                        ? substr($product->name, strpos($product->name, '('))
                        : null,
                    'clean_name' => str_contains($product->name, '(')
                        ? trim(substr($product->name, 0, strpos($product->name, '(')))
                        : $product->name,
                ];
            })
            ->groupBy(function (array $product) use ($activeGroupRules, $fallbackGroupLabel) {
                $name = strtolower($product['name']);

                foreach ($activeGroupRules as $groupLabel => $keywords) {
                    foreach ($keywords as $keyword) {
                        if (str_contains($name, strtolower($keyword))) {
                            return $groupLabel;
                        }
                    }
                }

                return $fallbackGroupLabel;
            })
            ->map(fn ($group) => $group->values()->toArray())
            ->toArray();
    }
}
