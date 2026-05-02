<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Game;
use App\Models\Product;
use App\Models\ProviderProduct;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DigiflazzCatalogBootstrapService
{
    /**
     * Ambil pricelist Digiflazz, update provider_products, lalu buat game/product
     * baru untuk SKU aktif yang belum pernah dipetakan.
     *
     * @return array{
     *     provider_synced: int,
     *     provider_active: int,
     *     provider_inactive: int,
     *     games_created: int,
     *     products_created: int,
     *     products_reused: int,
     *     sku_mapped: int,
     *     skipped: int
     * }
     */
    public function bootstrap(array $options = []): array
    {
        $syncResult = app(TopupPriceService::class)->syncDigiflazzCatalog();

        $importResult = $this->bootstrapFromProviderProducts(
            ProviderProduct::query()
                ->where('provider_name', 'digiflazz')
                ->where('is_active', true)
                ->orderBy('brand')
                ->orderBy('product_name')
                ->get(),
            $options,
        );

        return [
            ...$importResult,
            'provider_synced' => $syncResult['synced'],
            'provider_active' => $syncResult['active'],
            'provider_inactive' => $syncResult['inactive'],
        ];
    }

    /**
     * @param  Collection<int, ProviderProduct>  $providerProducts
     * @return array{
     *     provider_synced: int,
     *     provider_active: int,
     *     provider_inactive: int,
     *     games_created: int,
     *     products_created: int,
     *     products_reused: int,
     *     sku_mapped: int,
     *     skipped: int
     * }
     */
    public function bootstrapFromProviderProducts(Collection $providerProducts, array $options = []): array
    {
        $marginPercent = (float) ($options['margin_percent'] ?? Setting::get('pricing_pct', 4.0));
        $gameCache = [];
        $productIds = collect();
        $newProductIds = collect();

        $result = [
            'provider_synced' => 0,
            'provider_active' => $providerProducts->where('is_active', true)->count(),
            'provider_inactive' => $providerProducts->where('is_active', false)->count(),
            'games_created' => 0,
            'products_created' => 0,
            'products_reused' => 0,
            'sku_mapped' => 0,
            'skipped' => 0,
        ];

        $items = $providerProducts
            ->filter(fn (ProviderProduct $providerProduct): bool => $providerProduct->provider_name === 'digiflazz')
            ->map(function (ProviderProduct $providerProduct) use (&$result): ?array {
                if (! $providerProduct->is_active || (int) $providerProduct->price <= 0) {
                    $result['skipped']++;

                    return null;
                }

                $brand = $this->cleanString($providerProduct->brand) ?: 'Lainnya';
                $productName = $this->cleanProductName($providerProduct);

                if ($productName === '') {
                    $result['skipped']++;

                    return null;
                }

                return [
                    'brand' => $brand,
                    'product_name' => $productName,
                    'group' => $this->inferProductGroup($productName),
                    'provider_product_id' => $providerProduct->id,
                ];
            })
            ->filter()
            ->values();

        $groups = $items->groupBy(
            fn (array $item): string => $this->normalizeKey($item['brand']).'|'.$this->normalizeKey($item['product_name'])
        );

        DB::transaction(function () use ($groups, $marginPercent, &$gameCache, &$result, $productIds, $newProductIds): void {
            foreach ($groups as $groupedItems) {
                /** @var Collection<int, array{brand: string, product_name: string, group: string, provider_product_id: int}> $groupedItems */
                $reference = $groupedItems->first();

                if (! $reference) {
                    continue;
                }

                $providerProducts = ProviderProduct::query()
                    ->whereKey($groupedItems->pluck('provider_product_id')->all())
                    ->lockForUpdate()
                    ->get();

                $unmappedProviderProducts = $providerProducts
                    ->filter(fn (ProviderProduct $providerProduct): bool => $providerProduct->product_id === null)
                    ->values();

                if ($unmappedProviderProducts->isEmpty()) {
                    $result['skipped'] += $providerProducts->count();

                    continue;
                }

                $game = $this->resolveGame($reference['brand'], $gameCache, $result);
                $productName = $reference['product_name'];

                $product = Product::query()
                    ->where('game_id', $game->id)
                    ->where('name', $productName)
                    ->first();

                if ($product) {
                    $result['products_reused']++;
                } else {
                    $bestProviderProduct = $unmappedProviderProducts
                        ->sortBy([
                            ['priority', 'asc'],
                            ['price', 'asc'],
                            ['id', 'asc'],
                        ])
                        ->first();

                    $cost = $bestProviderProduct ? (float) $bestProviderProduct->price : 0;
                    $margin = $this->calculateAutoMargin($cost, $marginPercent);

                    $product = Product::create([
                        'game_id' => $game->id,
                        'name' => $productName,
                        'group' => $reference['group'],
                        'price_cost' => 0,
                        'margin_flat' => $margin,
                        'price_sell' => 0,
                        'is_available' => false,
                    ]);

                    $newProductIds->push($product->id);
                    $result['products_created']++;
                }

                foreach ($providerProducts as $providerProduct) {
                    if ($providerProduct->product_id !== null) {
                        $result['skipped']++;

                        continue;
                    }

                    $providerProduct->forceFill([
                        'product_id' => $product->id,
                    ])->save();

                    $result['sku_mapped']++;
                }

                $productIds->push($product->id);
            }
        });

        Product::whereKey($productIds->unique()->values())
            ->get()
            ->each(fn (Product $product) => app(TopupPriceService::class)->refreshProductPricing($product));

        Product::whereKey($newProductIds->unique()->values())
            ->update(['is_available' => false]);

        return $result;
    }

    /**
     * @param  array<string, Game>  $gameCache
     * @param  array<string, int>  $result
     */
    private function resolveGame(string $brand, array &$gameCache, array &$result): Game
    {
        $cacheKey = $this->normalizeKey($brand);

        if (isset($gameCache[$cacheKey])) {
            return $gameCache[$cacheKey];
        }

        $slug = Str::slug($brand) ?: 'digiflazz-brand';
        $game = Game::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($brand)])
            ->orWhere('slug', $slug)
            ->first();

        if ($game) {
            return $gameCache[$cacheKey] = $game;
        }

        $game = Game::create([
            'category_id' => $this->resolveGamesCategory()->id,
            'name' => $brand,
            'slug' => $this->uniqueGameSlug($slug),
            'is_active' => false,
        ]);

        $result['games_created']++;

        return $gameCache[$cacheKey] = $game;
    }

    private function resolveGamesCategory(): Category
    {
        $category = Category::query()
            ->where('slug', 'games')
            ->orWhereRaw('LOWER(name) = ?', ['games'])
            ->first();

        if ($category) {
            return $category;
        }

        return Category::create([
            'name' => 'Games',
            'slug' => 'games',
        ]);
    }

    private function cleanProductName(ProviderProduct $providerProduct): string
    {
        $name = $this->cleanString($providerProduct->product_name ?: $providerProduct->provider_sku);
        $brand = $this->cleanString($providerProduct->brand);

        if ($brand !== '' && str_starts_with(mb_strtolower($name), mb_strtolower($brand))) {
            $name = trim(substr($name, strlen($brand)));
            $name = trim($name, " \t\n\r\0\x0B-_:|/");
        }

        $name = preg_replace('/\s+/', ' ', $name) ?: $name;

        return $this->cleanString($name);
    }

    private function inferProductGroup(string $productName): string
    {
        $name = mb_strtolower($productName);

        if (str_contains($name, 'weekly') || str_contains($name, 'monthly') || str_contains($name, 'membership') || str_contains($name, 'pass')) {
            return 'Membership';
        }

        if (str_contains($name, 'diamond') || str_contains($name, 'dm')) {
            return 'Diamonds';
        }

        if (str_contains($name, 'crystal') || str_contains($name, 'genesis')) {
            return 'Crystals';
        }

        if (str_contains($name, 'voucher')) {
            return 'Voucher';
        }

        return 'Top Up';
    }

    private function calculateAutoMargin(float $cost, float $marginPercent): int
    {
        if ($cost <= 0) {
            return 0;
        }

        return max(50, $this->roundTo50($cost * $marginPercent / 100));
    }

    private function roundTo50(float $value): int
    {
        return (int) (round($value / 50) * 50);
    }

    private function cleanString(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function normalizeKey(string $value): string
    {
        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value) ?: $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?: $value);
    }

    private function uniqueGameSlug(string $baseSlug): string
    {
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'digiflazz-brand';
        $slug = $baseSlug;
        $counter = 2;

        while (Game::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
