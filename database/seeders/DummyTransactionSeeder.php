<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\Game;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Support\Str;

class DummyTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create();
        
        $category = Category::firstOrCreate(['slug' => 'top-up'], [
            'name' => 'Top Up Games'
        ]);
        
        // Ensure we don't duplicate constraints, but create a solid dummy game
        $game = Game::firstOrCreate(['slug' => 'mobile-legends-dummy'], [
            'category_id' => $category->id,
            'name' => 'Mobile Legends',
            'publisher' => 'Moonton',
            'thumbnail' => '/storage/games/dummy-thumb.jpg',
            'image' => '/storage/games/dummy-bg.jpg',
            'is_active' => true,
        ]);
        
        $products = collect();
        if ($game->products()->count() == 0) {
            $products->push(Product::create([
                'game_id' => $game->id,
                'provider_sku' => 'ML-5-DUMMY',
                'name' => '5 Diamonds',
                'price_cost' => 1400,
                'margin_flat' => 100,
                'margin_percent' => 0,
                'price_sell' => 1500,
                'is_available' => true,
            ]));
            $products->push(Product::create([
                'game_id' => $game->id,
                'provider_sku' => 'ML-10-DUMMY',
                'name' => '10 Diamonds',
                'price_cost' => 2800,
                'margin_flat' => 200,
                'margin_percent' => 0,
                'price_sell' => 3000,
                'is_available' => true,
            ]));
            $products->push(Product::create([
                'game_id' => $game->id,
                'provider_sku' => 'ML-86-DUMMY',
                'name' => '86 Diamonds',
                'price_cost' => 20000,
                'margin_flat' => 1500,
                'margin_percent' => 0,
                'price_sell' => 21500,
                'is_available' => true,
            ]));
        } else {
            $products = $game->products;
        }

        if($products->isEmpty()) {
            $this->command->error('No products found. Please create products first.');
            return;
        }
        
        $statuses = ['pending', 'paid', 'processing', 'success', 'failed'];
        
        for ($i = 0; $i < 15; $i++) {
            $product = $products->random();
            Transaction::create([
                'invoice_id' => 'INV-' . strtoupper(Str::random(10)),
                'user_id' => $user->id,
                'product_id' => $product->id,
                'customer_game_id' => 'USER' . rand(1000, 9999),
                'customer_zone_id' => rand(10, 99),
                'amount' => $product->price_sell,
                'profit' => $product->margin_flat,
                'status' => collect($statuses)->random(),
                'sn' => 'SN-' . Str::random(8),
                'payment_url' => 'https://tripay.co.id/checkout/xxx',
                'reference_id_provider' => 'DGF-' . Str::random(8),
                'created_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 24)),
            ]);
        }
        
        $this->command->info('15 Dummy transactions (along with Category, Game, and Products) created successfully.');
    }
}
