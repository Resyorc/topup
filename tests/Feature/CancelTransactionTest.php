<?php

use App\Models\Category;
use App\Models\Game;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Str;

// ── Helper ────────────────────────────────────────────────────────────────────

function makeGame(): Game
{
    $category = Category::create(['name' => 'Cat ' . uniqid(), 'slug' => 'cat-' . uniqid()]);

    return Game::create([
        'name'        => 'Test Game ' . uniqid(),
        'slug'        => 'test-game-' . uniqid(),
        'is_active'   => true,
        'category_id' => $category->id,
    ]);
}

function makeProduct(Game $game): Product
{
    return Product::create([
        'game_id'      => $game->id,
        'name'         => 'Test Product',
        'provider_sku' => 'SKU-' . uniqid(),
        'price_cost'   => 8000,
        'price_sell'   => 10000,
        'is_available' => true,
    ]);
}

function makeTrx(array $overrides = []): Transaction
{
    $game    = makeGame();
    $product = makeProduct($game);

    return Transaction::create(array_merge([
        'invoice_id'        => 'INV-' . strtoupper(Str::ulid()),
        'product_id'        => $product->id,
        'customer_game_id'  => '123456',
        'customer_whatsapp' => '08123456789',
        'amount'            => 10000,
        'status'            => 'pending',
    ], $overrides));
}

// ── BLACK-BOX: endpoint /api/cancel ──────────────────────────────────────────

describe('Cancel Transaction — BLACK-BOX', function () {

    test('[BLACK-BOX] request tanpa invoice_id ditolak 422', function () {
        $this->postJson('/api/cancel', [])
            ->assertStatus(422);
    });

    test('[BLACK-BOX] invoice_id yang tidak ada mengembalikan 422', function () {
        $this->postJson('/api/cancel', ['invoice_id' => 'INV-TIDAKADA'])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    });

    test('[BLACK-BOX] guest bisa cancel transaksi guest yang pending', function () {
        $trx = makeTrx(['user_id' => null]);

        $this->postJson('/api/cancel', ['invoice_id' => $trx->invoice_id])
            ->assertOk()
            ->assertJson(['success' => true]);

        expect($trx->fresh()->status)->toBe('canceled');
    });

    test('[BLACK-BOX] tidak bisa cancel transaksi yang sudah bukan pending', function () {
        foreach (['processing', 'success', 'failed', 'canceled'] as $status) {
            $trx = makeTrx(['user_id' => null, 'status' => $status]);

            $this->postJson('/api/cancel', ['invoice_id' => $trx->invoice_id])
                ->assertStatus(422)
                ->assertJson(['success' => false]);

            expect($trx->fresh()->status)->toBe($status); // tidak berubah
        }
    });
});

// ── GRAY-BOX: authorization logic ────────────────────────────────────────────

describe('Cancel Transaction — GRAY-BOX', function () {

    test('[GRAY-BOX] user login bisa cancel transaksi miliknya sendiri', function () {
        $user = User::factory()->create();
        $trx  = makeTrx(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson('/api/cancel', ['invoice_id' => $trx->invoice_id])
            ->assertOk()
            ->assertJson(['success' => true]);

        expect($trx->fresh()->status)->toBe('canceled');
    });

    test('[GRAY-BOX] user login TIDAK bisa cancel transaksi guest — IDOR fix', function () {
        $user     = User::factory()->create();
        $guestTrx = makeTrx(['user_id' => null]); // milik guest

        $this->actingAs($user)
            ->postJson('/api/cancel', ['invoice_id' => $guestTrx->invoice_id])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        // Status harus tetap pending — tidak berubah
        expect($guestTrx->fresh()->status)->toBe('pending');
    });

    test('[GRAY-BOX] user login TIDAK bisa cancel transaksi user lain', function () {
        $user      = User::factory()->create();
        $otherUser = User::factory()->create();
        $trx       = makeTrx(['user_id' => $otherUser->id]);

        $this->actingAs($user)
            ->postJson('/api/cancel', ['invoice_id' => $trx->invoice_id])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        expect($trx->fresh()->status)->toBe('pending');
    });

    test('[GRAY-BOX] guest tidak bisa cancel transaksi milik user login', function () {
        $user = User::factory()->create();
        $trx  = makeTrx(['user_id' => $user->id]);

        // Request sebagai guest (tidak actingAs)
        $this->postJson('/api/cancel', ['invoice_id' => $trx->invoice_id])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        expect($trx->fresh()->status)->toBe('pending');
    });
});
