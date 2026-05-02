<?php

use App\Jobs\ProcessFulfilmentJob;
use App\Models\Category;
use App\Models\Game;
use App\Models\Product;
use App\Models\ProviderProduct;
use App\Models\Transaction;
use App\Services\DigiflazzService;
use App\Services\ProviderSkuSelector;
use App\Services\TopupPriceService;
use Illuminate\Support\Str;

function makeProviderSelectorProduct(): Product
{
    $category = Category::create([
        'name' => 'Provider Selector Cat '.uniqid(),
        'slug' => 'provider-selector-cat-'.uniqid(),
    ]);

    $game = Game::create([
        'name' => 'Provider Selector Game '.uniqid(),
        'slug' => 'provider-selector-game-'.uniqid(),
        'is_active' => true,
        'category_id' => $category->id,
    ]);

    return Product::create([
        'game_id' => $game->id,
        'name' => '86 Diamonds',
        'price_cost' => 0,
        'margin_flat' => 1500,
        'price_sell' => 0,
        'is_available' => true,
    ]);
}

function addProviderAlternative(
    Product $product,
    string $sku,
    int $price,
    int $priority = 100,
    bool $active = true,
): ProviderProduct {
    return ProviderProduct::create([
        'provider_name' => 'digiflazz',
        'provider_sku' => $sku,
        'product_name' => $product->name,
        'brand' => $product->game->name,
        'price' => $price,
        'seller_name' => 'Seller '.$sku,
        'is_active' => $active,
        'priority' => $priority,
        'product_id' => $product->id,
    ]);
}

it('selects active provider SKU by priority then cheapest price', function () {
    $product = makeProviderSelectorProduct();

    addProviderAlternative($product, 'CHEAP-LOW-PRIORITY', 9000, 100);
    addProviderAlternative($product, 'PRIORITY-SELLER', 10000, 10);
    addProviderAlternative($product, 'INACTIVE-BEST', 1000, 1, false);

    $selected = app(ProviderSkuSelector::class)->bestForProduct($product);

    expect($selected?->provider_sku)->toBe('PRIORITY-SELLER');
});

it('uses cheapest provider when priority is the same and refreshes product pricing', function () {
    $product = makeProviderSelectorProduct();

    addProviderAlternative($product, 'EXPENSIVE', 12000, 50);
    addProviderAlternative($product, 'CHEAP', 9500, 50);

    app(TopupPriceService::class)->refreshProductPricing($product);

    $product->refresh();

    expect((int) $product->price_cost)->toBe(9500)
        ->and((int) $product->price_sell)->toBe(11000)
        ->and($product->is_available)->toBeTrue();
});

it('marks product unavailable when no active provider alternative exists', function () {
    $product = makeProviderSelectorProduct();

    addProviderAlternative($product, 'INACTIVE', 9500, 10, false);

    app(TopupPriceService::class)->refreshProductPricing($product);

    expect($product->refresh()->is_available)->toBeFalse();
});

it('switches fulfilment to an active alternative when stored SKU is inactive', function () {
    $product = makeProviderSelectorProduct();

    addProviderAlternative($product, 'OLD-INACTIVE', 9000, 10, false);
    addProviderAlternative($product, 'NEW-ACTIVE', 9500, 20, true);

    $transaction = Transaction::create([
        'invoice_id' => 'INV-'.strtoupper(Str::ulid()),
        'product_id' => $product->id,
        'provider_sku' => 'OLD-INACTIVE',
        'provider_name' => 'digiflazz',
        'customer_game_id' => '123456',
        'customer_whatsapp' => '08123456789',
        'amount' => 11000,
        'status' => 'paid',
        'payment_status' => 'paid',
        'fulfilment_status' => 'pending',
    ]);

    $digiflazz = Mockery::mock(DigiflazzService::class);
    $digiflazz->shouldReceive('createTransaction')
        ->once()
        ->with('NEW-ACTIVE', '123456', $transaction->invoice_id)
        ->andReturn([
            'ref_id' => $transaction->invoice_id,
            'status' => 'Pending',
            'message' => 'Transaksi Pending',
        ]);

    (new ProcessFulfilmentJob($transaction->invoice_id))->handle(
        $digiflazz,
        app(ProviderSkuSelector::class),
    );

    $transaction->refresh();

    expect($transaction->provider_sku)->toBe('NEW-ACTIVE')
        ->and($transaction->provider_name)->toBe('digiflazz')
        ->and($transaction->fulfilment_status)->toBe('processing');
});
