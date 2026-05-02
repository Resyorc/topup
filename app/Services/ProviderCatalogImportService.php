<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Game;
use App\Models\Product;
use App\Models\ProviderProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProviderCatalogImportService
{
    /**
     * @param  Collection<int, ProviderProduct>  $providerProducts
     * @param  array{
     *     game_id: int|string,
     *     margin_flat: int|float|string,
     *     group?: string|null,
     *     priority?: int|string|null,
     *     merge_same_name?: bool,
     *     skip_mapped?: bool
     * }  $options
     * @return array{created: int, reused: int, mapped: int, skipped: int}
     */
    public function import(Collection $providerProducts, array $options): array
    {
        $game = Game::findOrFail((int) $options['game_id']);
        $marginFlat = (float) ($options['margin_flat'] ?? 0);
        $group = filled($options['group'] ?? null) ? trim((string) $options['group']) : null;
        $priority = (int) ($options['priority'] ?? 100);
        $mergeSameName = (bool) ($options['merge_same_name'] ?? true);
        $skipMapped = (bool) ($options['skip_mapped'] ?? true);

        $result = [
            'created' => 0,
            'reused' => 0,
            'mapped' => 0,
            'skipped' => 0,
        ];
        $productIds = collect();

        DB::transaction(function () use (
            $providerProducts,
            $game,
            $marginFlat,
            $group,
            $priority,
            $mergeSameName,
            $skipMapped,
            &$result,
            $productIds,
        ): void {
            $records = ProviderProduct::query()
                ->whereKey($providerProducts->pluck('id'))
                ->lockForUpdate()
                ->get();

            if ($skipMapped) {
                $result['skipped'] += $records->whereNotNull('product_id')->count();
                $records = $records->whereNull('product_id')->values();
            }

            $groups = $mergeSameName
                ? $records->groupBy(fn (ProviderProduct $providerProduct) => $this->normalizeProductName($providerProduct))
                : $records->groupBy(fn (ProviderProduct $providerProduct) => (string) $providerProduct->id);

            foreach ($groups as $groupedRecords) {
                /** @var Collection<int, ProviderProduct> $groupedRecords */
                $reference = $groupedRecords
                    ->sortBy([
                        ['price', 'asc'],
                        ['id', 'asc'],
                    ])
                    ->first();

                if (! $reference) {
                    continue;
                }

                $productName = $this->cleanProductName($reference);

                $product = Product::query()
                    ->where('game_id', $game->id)
                    ->where('name', $productName)
                    ->first();

                if ($product) {
                    $result['reused']++;
                } else {
                    $product = Product::create([
                        'game_id' => $game->id,
                        'name' => $productName,
                        'group' => $group,
                        'price_cost' => 0,
                        'margin_flat' => $marginFlat,
                        'price_sell' => 0,
                        'is_available' => true,
                    ]);
                    $result['created']++;
                }

                ProviderProduct::whereKey($groupedRecords->pluck('id'))->update([
                    'product_id' => $product->id,
                    'priority' => $priority,
                ]);

                $result['mapped'] += $groupedRecords->count();
                $productIds->push($product->id);
            }
        });

        Product::whereKey($productIds->unique()->values())
            ->get()
            ->each(fn (Product $product) => app(TopupPriceService::class)->refreshProductPricing($product));

        return $result;
    }

    private function cleanProductName(ProviderProduct $providerProduct): string
    {
        $name = trim((string) ($providerProduct->product_name ?: $providerProduct->provider_sku));
        $brand = trim((string) $providerProduct->brand);

        if ($brand !== '' && str_starts_with(mb_strtolower($name), mb_strtolower($brand))) {
            $name = trim(substr($name, strlen($brand)));
            $name = trim($name, " \t\n\r\0\x0B-_:|/");
        }

        $name = preg_replace('/\s+/', ' ', $name) ?: $name;

        return trim($name) !== '' ? trim($name) : (string) $providerProduct->provider_sku;
    }

    private function normalizeProductName(ProviderProduct $providerProduct): string
    {
        $name = mb_strtolower($this->cleanProductName($providerProduct));
        $name = preg_replace('/[^a-z0-9]+/i', ' ', $name) ?: $name;

        return trim(preg_replace('/\s+/', ' ', $name) ?: $name);
    }
}
