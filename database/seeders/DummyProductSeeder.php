<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DummyProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $games = Game::all();

        if ($games->isEmpty()) {
            $this->command->warn('No games found. Please create games from Admin Panel or DatabaseSeeder first.');
            return;
        }

        // To prevent duplicate seeding
        Product::query()->delete();

        foreach ($games as $game) {
            $this->seedProductsForGame($game);
        }

        $this->command->info('Successfully seeded dummy products for all games.');
    }

    private function seedProductsForGame(Game $game): void
    {
        $products = [
            ['name' => '5 Diamonds', 'price_cost' => 1400, 'price_sell' => 1500],
            ['name' => '12 Diamonds', 'price_cost' => 3300, 'price_sell' => 3500],
            ['name' => '50 Diamonds', 'price_cost' => 13500, 'price_sell' => 14000],
            ['name' => '70 Diamonds', 'price_cost' => 18500, 'price_sell' => 19500],
            ['name' => '140 Diamonds', 'price_cost' => 36500, 'price_sell' => 38000],
            ['name' => '355 Diamonds', 'price_cost' => 92000, 'price_sell' => 95000],
            ['name' => '720 Diamonds', 'price_cost' => 185000, 'price_sell' => 190000],
            ['name' => 'Starlight Member', 'price_cost' => 125000, 'price_sell' => 135000],
            ['name' => 'Twilight Pass', 'price_cost' => 135000, 'price_sell' => 140000],
            ['name' => 'Weekly Diamond Pass', 'price_cost' => 26500, 'price_sell' => 28000],
        ];

        foreach ($products as $index => $prod) {
            // Adapt name for PUBG/FF if needed, but for dummy just use diamond names
            $itemName = str_replace('Diamonds', str_contains(strtolower($game->name), 'pubg') ? 'UC' : 
                                    (str_contains(strtolower($game->name), 'valorant') ? 'VP' : 'Diamonds'), $prod['name']);
            
            Product::create([
                'game_id' => $game->id,
                'provider_sku' => Str::slug($game->name . '-' . $itemName) . '-' . rand(1000, 9999),
                'name' => $itemName,
                'price_cost' => $prod['price_cost'],
                'margin_flat' => $prod['price_sell'] - $prod['price_cost'],
                'margin_percent' => 0,
                'price_sell' => $prod['price_sell'],
                'is_available' => true,
            ]);
        }
    }
}
