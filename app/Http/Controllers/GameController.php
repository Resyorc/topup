<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Services\TripayService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class GameController extends Controller
{
    /**
     * Display the specified game detail and its products.
     */
    public function show($slug)
    {
        // 1. Fetch the Game including its active products and category
        $game = Game::with(['category', 'products' => function ($query) {
            $query->where('is_available', true)
                  ->orderBy('price_sell', 'asc');
        }])
        ->where('slug', $slug)
        ->where('is_active', true)
        ->firstOrFail();

        // 2. Format the game data for the frontend
        $gameData = [
            'id' => $game->id,
            'name' => $game->name,
            'slug' => $game->slug,
            'publisher' => $game->publisher ?? 'Nebu Publisher',
            'thumbnail' => $game->thumbnail ? '/storage/' . $game->thumbnail : null,
            'image' => $game->image ? '/storage/' . $game->image : null, // Background banner
            'rating' => 4.99, // Static for now, could be dynamic
            'reviews_count' => '10.2M+',
        ];

        // 3. Group products by a simulated category for the tabs (e.g. Diamond, WDP, Event)
        // For now, since we only have one product type in DB, we'll put them all in "Diamond"
        $productsGrouped = [
            'Diamond' => $game->products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => (float) $product->price_sell,
                    // If the product name has extra info like "19 Diamonds (17 + 2 Bonus)", we can parse it, 
                    // but for now we'll just use the name or a static extra for the UI mockup.
                    'extra' => str_contains($product->name, '(') 
                        ? substr($product->name, strpos($product->name, '(')) 
                        : null,
                    'clean_name' => str_contains($product->name, '(') 
                        ? trim(substr($product->name, 0, strpos($product->name, '('))) 
                        : $product->name,
                ];
            })->values()->toArray(),
            'WDP' => [], // Empty for now, can be expanded later
            'Event Top Up' => [], 
        ];

        // 4. Fetch real payment channels from Tripay
        $paymentMethods = [];
        try {
            $tripay = new TripayService();
            $channels = $tripay->getPaymentChannels();

            foreach ($channels as $channel) {
                if (!$channel['active']) continue;

                $group = $channel['group'];
                if (!isset($paymentMethods[$group])) {
                    $paymentMethods[$group] = [];
                }

                $paymentMethods[$group][] = [
                    'id' => $channel['code'],
                    'name' => $channel['name'],
                    'icon_url' => $channel['icon_url'] ?? null,
                    'fee_flat' => (float) ($channel['total_fee']['flat'] ?? 0),
                    'fee_percent' => (float) ($channel['total_fee']['percent'] ?? 0),
                    'minimum_amount' => (int) ($channel['minimum_amount'] ?? 0),
                ];
            }
        } catch (\Exception $e) {
            Log::error('Tripay Payment Channels Error: ' . $e->getMessage());
            $paymentMethods = []; // Fallback empty
        }

        return Inertia::render('game-detail', [
            'game' => $gameData,
            'productGroups' => $productsGrouped,
            'paymentMethods' => $paymentMethods,
        ]);
    }
}
