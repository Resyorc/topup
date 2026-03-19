<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Services\ProductGroupingService;
use App\Services\TripayService;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class GameController extends Controller
{
    /**
     * Display the specified game detail and its products.
     */
    public function show($slug, ProductGroupingService $productGroupingService)
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
            'publisher' => $game->publisher ?? 'Nuvelo Publisher',
            'thumbnail' => $game->thumbnail ? '/storage/' . $game->thumbnail : null,
            'image' => $game->image ? '/storage/' . $game->image : null,
            'rating' => $game->reviews_count > 0 ? number_format($game->rating, 2) : '—',
            'reviews_count' => $game->reviews_count >= 1000
                ? number_format($game->reviews_count / 1000, 1) . 'K+'
                : (string) $game->reviews_count,
            'icon_rules' => $game->icon_rules ?? [],
            'need_zone'  => (bool) (config("services.user_id_check.games.{$game->slug}.need_zone", false)),
        ];

        // 3. Group products via dedicated service (per-game dynamic rules)
        $productsGrouped = $productGroupingService->groupByGame(
            $game->products,
            $game,
        );

        // 4. Fetch real payment channels from Tripay
        $paymentMethods = [];
        try {
            $tripay = new TripayService();
            $channels = $tripay->getPaymentChannels();

            foreach ($channels as $channel) {
                if (!$channel['active']) continue;

                $group = str_contains(strtoupper($channel['name']), 'QRIS') ? 'QRIS' : $channel['group'];
                if (!isset($paymentMethods[$group])) {
                    $paymentMethods[$group] = [];
                }

                $paymentMethods[$group][] = [
                    'id'             => $channel['code'],
                    'name'           => $channel['name'],
                    'icon_url'       => $channel['icon_url'] ?? null,
                    'fee_flat'       => (float) ($channel['total_fee']['flat'] ?? 0),
                    'fee_percent'    => (float) ($channel['total_fee']['percent'] ?? 0),
                    'minimum_amount' => (int) ($channel['minimum_amount'] ?? 0),
                    'is_coin'        => false,
                    'disabled'       => false,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Tripay Payment Channels Error: ' . $e->getMessage());
            $paymentMethods = [];
        }

        // 5. Tambah Krysta Coin lalu urutkan: Krysta Coin, QRIS, E-Wallet, Virtual Account, Convenience Store
        $user = auth()->user();
        $paymentMethods['Krysta Coin'] = [
            [
                'id'             => 'COIN',
                'name'           => 'Krysta Coin',
                'icon_url'       => '/coin.png',
                'fee_flat'       => 0,
                'fee_percent'    => 0,
                'minimum_amount' => 0,
                'is_coin'        => true,
                'disabled'       => !$user,
                'coin_balance'   => $user?->coin_balance ?? 0,
            ]
        ];

        $order = ['Krysta Coin', 'QRIS', 'E-Wallet', 'Virtual Account', 'Convenience Store'];
        $sorted = [];
        foreach ($order as $key) {
            if (isset($paymentMethods[$key])) {
                $sorted[$key] = $paymentMethods[$key];
            }
        }
        foreach ($paymentMethods as $key => $value) {
            if (!isset($sorted[$key])) {
                $sorted[$key] = $value;
            }
        }
        $paymentMethods = $sorted;

        return Inertia::render('game-detail', [
            'game'           => $gameData,
            'productGroups'  => $productsGrouped,
            'paymentMethods' => $paymentMethods,
        ]);
    }
}