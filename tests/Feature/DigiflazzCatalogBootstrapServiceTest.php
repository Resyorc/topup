<?php

use App\Models\Product;
use App\Models\ProviderProduct;
use App\Services\DigiflazzCatalogBootstrapService;

function makeBootstrapProviderProduct(
    string $sku,
    string $brand,
    string $name,
    int $price,
    ?int $productId = null,
): ProviderProduct {
    return ProviderProduct::create([
        'provider_name' => 'digiflazz',
        'provider_sku' => $sku,
        'product_name' => $name,
        'brand' => $brand,
        'price' => $price,
        'seller_name' => 'Seller '.$sku,
        'is_active' => true,
        'priority' => 100,
        'product_id' => $productId,
    ]);
}

it('bootstraps games and products from active Digiflazz provider products', function () {
    $skuA = makeBootstrapProviderProduct('ML86-A', 'Mobile Legends', 'Mobile Legends 86 Diamonds', 10000);
    $skuB = makeBootstrapProviderProduct('ML86-B', 'Mobile Legends', 'Mobile Legends - 86 Diamonds', 9500);
    $skuC = makeBootstrapProviderProduct('FF70', 'Free Fire', 'Free Fire 70 Diamonds', 10000);

    $result = app(DigiflazzCatalogBootstrapService::class)->bootstrapFromProviderProducts(
        collect([$skuA, $skuB, $skuC]),
    );

    expect($result)->toMatchArray([
        'games_created' => 2,
        'products_created' => 2,
        'products_reused' => 0,
        'sku_mapped' => 3,
        'skipped' => 0,
    ]);

    $mobileLegendsProduct = Product::query()
        ->whereHas('game', fn ($query) => $query->where('name', 'Mobile Legends'))
        ->where('name', '86 Diamonds')
        ->firstOrFail();

    expect($mobileLegendsProduct->providerProducts()->count())->toBe(2)
        ->and((int) $mobileLegendsProduct->price_cost)->toBe(9500)
        ->and((int) $mobileLegendsProduct->price_sell)->toBe(9900)
        ->and($mobileLegendsProduct->is_available)->toBeFalse()
        ->and($mobileLegendsProduct->game->is_active)->toBeFalse()
        ->and($mobileLegendsProduct->group)->toBe('Diamonds')
        ->and($mobileLegendsProduct->game->category->name)->toBe('Games');

    expect(Product::query()
        ->whereHas('game', fn ($query) => $query->where('name', 'Free Fire'))
        ->where('name', '70 Diamonds')
        ->exists())->toBeTrue();
});

it('skips mapped SKUs and reuses existing products on repeated bootstrap', function () {
    $skuA = makeBootstrapProviderProduct('ML5-A', 'Mobile Legends', 'Mobile Legends 5 Diamonds', 1000);
    $skuB = makeBootstrapProviderProduct('ML5-B', 'Mobile Legends', 'Mobile Legends 5 Diamonds', 900);

    $service = app(DigiflazzCatalogBootstrapService::class);

    $firstRun = $service->bootstrapFromProviderProducts(collect([$skuA, $skuB]));
    $secondRun = $service->bootstrapFromProviderProducts(ProviderProduct::whereIn('provider_sku', ['ML5-A', 'ML5-B'])->get());

    expect($firstRun)->toMatchArray([
        'products_created' => 1,
        'sku_mapped' => 2,
        'skipped' => 0,
    ]);

    expect($secondRun)->toMatchArray([
        'games_created' => 0,
        'products_created' => 0,
        'products_reused' => 0,
        'sku_mapped' => 0,
        'skipped' => 2,
    ]);

    expect(Product::where('name', '5 Diamonds')->count())->toBe(1);
});

it('uses Games category for every auto-generated game', function () {
    $sku = makeBootstrapProviderProduct('UNCAT-1', 'Uncategorized Game', 'Uncategorized Game 10 Coins', 1000);

    app(DigiflazzCatalogBootstrapService::class)->bootstrapFromProviderProducts(collect([$sku]));

    $product = Product::query()
        ->whereHas('game', fn ($query) => $query->where('name', 'Uncategorized Game'))
        ->where('name', '10 Coins')
        ->firstOrFail();

    expect($product->game->category->name)->toBe('Games')
        ->and($product->game->category->slug)->toBe('games')
        ->and($product->game->is_active)->toBeFalse()
        ->and($product->is_available)->toBeFalse();
});
