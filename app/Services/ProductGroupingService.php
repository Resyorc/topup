<?php

namespace App\Services;

use App\Models\Game;
use Illuminate\Support\Collection;

class ProductGroupingService
{
    /**
     * Group products using per-game grouping_rules from DB.
     * Falls back to config-based keyword rules if game has no rules defined.
     */
    public function groupByGame(Collection $products, Game $game): array
    {
        $fallbackGroupLabel = (string) config(
            'services.product_grouping.fallback_label',
            'Produk Lainnya',
        );

        // Build keyword rules from game's DB config
        $keywordRules = $this->buildKeywordRules($game);

        return $products
            ->map(function ($product) {
                return [
                    'id'         => $product->id,
                    'name'       => $product->name,
                    'price'      => (float) $product->price_sell,
                    'extra'      => str_contains($product->name, '(')
                        ? substr($product->name, strpos($product->name, '('))
                        : null,
                    'clean_name' => str_contains($product->name, '(')
                        ? trim(substr($product->name, 0, strpos($product->name, '(')))
                        : $product->name,
                ];
            })
            ->groupBy(function (array $product) use ($keywordRules, $fallbackGroupLabel) {
                $name = strtolower($product['name']);

                foreach ($keywordRules as $groupLabel => $keywords) {
                    foreach ($keywords as $keyword) {
                        if (str_contains($name, strtolower(trim($keyword)))) {
                            return $groupLabel;
                        }
                    }
                }

                return $fallbackGroupLabel;
            })
            ->map(fn ($group) => $group->values()->toArray())
            ->toArray();
    }

    /**
     * Convert game's grouping_rules JSON to keyword rules array.
     * DB format: [['group' => 'Weekly', 'keywords' => 'weekly, wdp'], ...]
     * Returns: ['Weekly' => ['weekly', 'wdp'], ...]
     */
    private function buildKeywordRules(Game $game): array
    {
        $rules = $game->grouping_rules;

        if (!empty($rules)) {
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

        // Fallback to config-based rules
        $rulesBySlug = (array) config('services.product_grouping.rules_by_slug', []);
        $defaultRules = (array) config('services.product_grouping.default_rules', [
            'Diamond' => ['diamond'],
            'Event Top Up' => ['event'],
        ]);

        return $rulesBySlug[$game->slug] ?? $defaultRules;
    }
}
