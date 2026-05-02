<?php

use App\Models\Category;
use App\Models\Game;
use App\Models\Product;
use App\Models\ProviderProduct;
use App\Services\ProviderCatalogImportService;

function makeCatalogImportGame(): Game
{
    $category = Category::create([
        'name' => 'Catalog Import Cat '.uniqid(),
        'slug' => 'catalog-import-cat-'.uniqid(),
    ]);

    return Game::create([
        'name' => 'Mobile Legends',
        'slug' => 'mobile-legends-'.uniqid(),
        'is_active' => true,
        'category_id' => $category->id,
    ]);
}

function makeRawProviderProduct(
    string $sku,
    string $name,
    int $price,
    ?int $productId = null,
): ProviderProduct {
    return ProviderProduct::create([
        'provider_name' => 'digiflazz',
        'provider_sku' => $sku,
        'product_name' => $name,
        'brand' => 'Mobile Legends',
        'price' => $price,
        'seller_name' => 'Seller '.$sku,
        'is_active' => true,
        'priority' => 100,
        'product_id' => $productId,
    ]);
}

it('creates products in bulk from provider SKUs and groups duplicate names as alternatives', function () {
    $game = makeCatalogImportGame();

    $skuA = makeRawProviderProduct('ML86-A', 'Mobile Legends 86 Diamonds', 10000);
    $skuB = makeRawProviderProduct('ML86-B', 'Mobile Legends - 86 Diamonds', 9500);
    $skuC = makeRawProviderProduct('MLWDP-A', 'Mobile Legends Weekly Diamond Pass', 25000);

    $result = app(ProviderCatalogImportService::class)->import(
        collect([$skuA, $skuB, $skuC]),
        [
            'game_id' => $game->id,
            'margin_flat' => 1500,
            'group' => 'Diamonds',
            'priority' => 50,
            'merge_same_name' => true,
            'skip_mapped' => true,
        ],
    );

    expect($result)->toMatchArray([
        'created' => 2,
        'reused' => 0,
        'mapped' => 3,
        'skipped' => 0,
    ]);

    $diamondProduct = Product::where('game_id', $game->id)
        ->where('name', '86 Diamonds')
        ->firstOrFail();

    expect($diamondProduct->providerProducts()->count())->toBe(2)
        ->and((int) $diamondProduct->price_cost)->toBe(9500)
        ->and((int) $diamondProduct->price_sell)->toBe(11000)
        ->and($diamondProduct->group)->toBe('Diamonds');

    expect(ProviderProduct::whereIn('provider_sku', ['ML86-A', 'ML86-B', 'MLWDP-A'])
        ->where('priority', 50)
        ->count())->toBe(3);
});

it('skips already mapped provider SKUs when requested', function () {
    $game = makeCatalogImportGame();

    $existingProduct = Product::create([
        'game_id' => $game->id,
        'name' => 'Existing Product',
        'price_cost' => 10000,
        'margin_flat' => 1500,
        'price_sell' => 11500,
        'is_available' => true,
    ]);

    $mappedSku = makeRawProviderProduct('MAPPED', 'Mobile Legends 10 Diamonds', 1000, $existingProduct->id);
    $newSku = makeRawProviderProduct('NEW', 'Mobile Legends 20 Diamonds', 2000);

    $result = app(ProviderCatalogImportService::class)->import(
        collect([$mappedSku, $newSku]),
        [
            'game_id' => $game->id,
            'margin_flat' => 500,
            'merge_same_name' => true,
            'skip_mapped' => true,
        ],
    );

    expect($result)->toMatchArray([
        'created' => 1,
        'mapped' => 1,
        'skipped' => 1,
    ]);

    expect($mappedSku->refresh()->product_id)->toBe($existingProduct->id)
        ->and($newSku->refresh()->product_id)->not->toBeNull();
});
