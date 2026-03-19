<?php

use App\Models\Category;
use App\Models\Game;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Str;

// ── Helper ────────────────────────────────────────────────────────────────────

function makeActiveGame(): Game
{
    $category = Category::create(['name' => 'Cat ' . uniqid(), 'slug' => 'cat-' . uniqid()]);

    return Game::create([
        'name'        => 'DoubleOrder Game ' . uniqid(),
        'slug'        => 'do-game-' . uniqid(),
        'is_active'   => true,
        'category_id' => $category->id,
    ]);
}

function makeAvailableProduct(Game $game): Product
{
    return Product::create([
        'game_id'      => $game->id,
        'name'         => 'DO Product',
        'provider_sku' => 'DO-SKU-' . uniqid(),
        'price_cost'   => 8000,
        'price_sell'   => 10000,
        'is_available' => true,
    ]);
}

function makePendingTrxFor(User $user, Product $product, string $gameId = '99999', array $overrides = []): Transaction
{
    return Transaction::create(array_merge([
        'invoice_id'        => 'INV-' . strtoupper(Str::ulid()),
        'user_id'           => $user->id,
        'product_id'        => $product->id,
        'customer_game_id'  => $gameId,
        'customer_whatsapp' => '08123456789',
        'amount'            => 10000,
        'status'            => 'pending',
        'expired_at'        => now()->addHour(),
    ], $overrides));
}

// Payload checkout minimal (tidak termasuk payment_method=COIN agar tidak masuk coin path)
function checkoutPayload(Product $product, string $gameId = '99999'): array
{
    return [
        'product_id'        => $product->id,
        'customer_game_id'  => $gameId,
        'customer_whatsapp' => '08123456789',
        'payment_method'    => 'QRIS',  // Tripay path — tapi akan di-short-circuit oleh duplicate check
    ];
}

// ── BLACK-BOX: duplicate check behavior dari luar ─────────────────────────────

describe('Checkout Double Order — BLACK-BOX', function () {

    test('[BLACK-BOX] guest tidak terkena duplicate check — tidak ada 409 dari check awal', function () {
        $game    = makeActiveGame();
        $product = makeAvailableProduct($game);

        // Guest order sebelumnya (user_id = null) tidak memblokir guest baru
        Transaction::create([
            'invoice_id'        => 'INV-' . strtoupper(Str::ulid()),
            'user_id'           => null,
            'product_id'        => $product->id,
            'customer_game_id'  => '99999',
            'customer_whatsapp' => '08123456789',
            'amount'            => 10000,
            'status'            => 'pending',
            'expired_at'        => now()->addHour(),
        ]);

        // Guest checkout — duplicate check di controller hanya berlaku untuk user login.
        // Response tidak boleh 409 (akan gagal di Tripay, tapi bukan karena duplicate check).
        $response = $this->postJson('/api/checkout', checkoutPayload($product));

        expect($response->status())->not->toBe(409);
    });

    test('[BLACK-BOX] user login dengan pesanan pending aktif mendapat 409', function () {
        $user    = User::factory()->create();
        $game    = makeActiveGame();
        $product = makeAvailableProduct($game);

        makePendingTrxFor($user, $product, '99999', ['status' => 'pending']);

        $this->actingAs($user)
            ->postJson('/api/checkout', checkoutPayload($product))
            ->assertStatus(409)
            ->assertJson(['success' => false]);
    });

    test('[BLACK-BOX] user login dengan pesanan processing aktif mendapat 409', function () {
        $user    = User::factory()->create();
        $game    = makeActiveGame();
        $product = makeAvailableProduct($game);

        makePendingTrxFor($user, $product, '99999', ['status' => 'processing']);

        $this->actingAs($user)
            ->postJson('/api/checkout', checkoutPayload($product))
            ->assertStatus(409)
            ->assertJson(['success' => false]);
    });
});

// ── GRAY-BOX: authorization + expiry logic ────────────────────────────────────

describe('Checkout Double Order — GRAY-BOX', function () {

    test('[GRAY-BOX] pesanan user lain dengan produk sama tidak memblokir user ini', function () {
        $userA   = User::factory()->create();
        $userB   = User::factory()->create();
        $game    = makeActiveGame();
        $product = makeAvailableProduct($game);

        // User A punya pending order untuk produk yang sama dengan game_id yang sama
        makePendingTrxFor($userA, $product, '99999');

        // User B tidak boleh kena 409 — duplicate check hanya per user
        $response = $this->actingAs($userB)
            ->postJson('/api/checkout', checkoutPayload($product));

        expect($response->status())->not->toBe(409);
    });

    test('[GRAY-BOX] pesanan pending yang sudah expired tidak memblokir order baru', function () {
        $user    = User::factory()->create();
        $game    = makeActiveGame();
        $product = makeAvailableProduct($game);

        // Buat pending order yang expired
        makePendingTrxFor($user, $product, '99999', [
            'status'     => 'pending',
            'expired_at' => now()->subMinutes(5), // sudah expired
        ]);

        // Expired order tidak boleh memblokir — tidak boleh 409
        $response = $this->actingAs($user)
            ->postJson('/api/checkout', checkoutPayload($product));

        expect($response->status())->not->toBe(409);
    });

    test('[GRAY-BOX] pesanan success tidak memblokir order baru untuk produk yang sama', function () {
        $user    = User::factory()->create();
        $game    = makeActiveGame();
        $product = makeAvailableProduct($game);

        // Order lama sudah success — tidak boleh memblokir
        makePendingTrxFor($user, $product, '99999', ['status' => 'success', 'expired_at' => null]);

        $response = $this->actingAs($user)
            ->postJson('/api/checkout', checkoutPayload($product));

        expect($response->status())->not->toBe(409);
    });

    test('[GRAY-BOX] pesanan pending untuk game_id berbeda tidak memblokir', function () {
        $user    = User::factory()->create();
        $game    = makeActiveGame();
        $product = makeAvailableProduct($game);

        // Pending order untuk game ID yang BERBEDA
        makePendingTrxFor($user, $product, '11111'); // game ID 11111

        // Order baru untuk game ID 99999 — tidak boleh kena 409
        $response = $this->actingAs($user)
            ->postJson('/api/checkout', checkoutPayload($product, '99999'));

        expect($response->status())->not->toBe(409);
    });

    test('[GRAY-BOX] response 409 mengandung invoice_id dan status pesanan aktif', function () {
        $user    = User::factory()->create();
        $game    = makeActiveGame();
        $product = makeAvailableProduct($game);

        $existingTrx = makePendingTrxFor($user, $product, '99999');

        $this->actingAs($user)
            ->postJson('/api/checkout', checkoutPayload($product))
            ->assertStatus(409)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['invoice_id', 'status'],
            ])
            ->assertJson([
                'data' => [
                    'invoice_id' => $existingTrx->invoice_id,
                    'status'     => 'pending',
                ],
            ]);
    });
});
