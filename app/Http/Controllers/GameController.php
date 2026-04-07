<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Setting;
use App\Services\ProductGroupingService;
use App\Services\TripayService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class GameController extends Controller
{
    /**
     * Display the specified game detail and its products.
     */
    public function show($slug, ProductGroupingService $productGroupingService)
    {
        // 1. Fetch the Game including its active products and category
        $game = Game::with(['category', 'products' => function ($query) {
            $query->with('providerProducts')
                ->where('is_available', true)
                ->orderBy('price_guest', 'asc');
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
            'thumbnail' => $game->thumbnail ? '/storage/'.$game->thumbnail : null,
            'image' => $game->image ? '/storage/'.$game->image : null,
            'rating' => $game->reviews_count > 0 ? number_format($game->rating, 2) : '—',
            'reviews_count' => $game->reviews_count >= 1000
                ? number_format($game->reviews_count / 1000, 1).'K+'
                : (string) $game->reviews_count,
            'icon_rules' => $game->icon_rules ?? [],
            'need_zone' => (bool) (config("services.user_id_check.games.{$game->slug}.need_zone", false)),
            'region_map' => collect($game->region_map ?? [])->mapWithKeys(
                fn ($entry) => [strtoupper($entry['country'] ?? '') => strtolower($entry['sku_prefix'] ?? '')]
            )->filter()->toArray(),
            'input_fields' => $game->input_fields,
            'guide_image' => $game->guide_image,
            'guide_content' => $game->guide_content,
        ];

        // 3. Pisahkan produk flash sale dari produk biasa
        $now = now();
        $flashSaleProducts = $game->products->filter(function ($p) use ($now) {
            return $p->flash_sale_price !== null
                && $p->flash_sale_ends_at !== null
                && $p->flash_sale_ends_at->gt($now);
        });
        $regularProducts = $game->products->filter(function ($p) use ($now) {
            return ! ($p->flash_sale_price !== null
                && $p->flash_sale_ends_at !== null
                && $p->flash_sale_ends_at->gt($now));
        });

        // 4. Group regular products via dedicated service
        $productsGrouped = $productGroupingService->groupByGame(
            $regularProducts,
            $game,
        );

        // 5. Map flash sale products
        $mappedFlashSale = $productGroupingService->groupByGame(
            $flashSaleProducts,
            $game,
        );

        // 4. Fetch real payment channels from Tripay
        $paymentMethods = [];
        try {
            $tripay = new TripayService;
            $channels = $tripay->getPaymentChannels();

            foreach ($channels as $channel) {
                if (! $channel['active']) {
                    continue;
                }

                $group = str_contains(strtoupper($channel['name']), 'QRIS') ? 'QRIS' : $channel['group'];
                if (! isset($paymentMethods[$group])) {
                    $paymentMethods[$group] = [];
                }

                $paymentMethods[$group][] = [
                    'id' => $channel['code'],
                    'name' => $channel['name'],
                    'icon_url' => $channel['icon_url'] ?? null,
                    'fee_flat' => (float) ($channel['total_fee']['flat'] ?? 0),
                    'fee_percent' => (float) ($channel['total_fee']['percent'] ?? 0),
                    'minimum_amount' => (int) ($channel['minimum_amount'] ?? 0),
                    'is_coin' => false,
                    'disabled' => false,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Tripay Payment Channels Error: '.$e->getMessage());
            $paymentMethods = [];
        }

        // 5. Tambah Krysta Coin lalu urutkan: Krysta Coin, QRIS, E-Wallet, Virtual Account, Convenience Store
        $user = auth()->user();
        $paymentMethods['Krysta Coin'] = [
            [
                'id' => 'COIN',
                'name' => 'Krysta Coin',
                'icon_url' => '/coin.png',
                'fee_flat' => 0,
                'fee_percent' => 0,
                'minimum_amount' => 0,
                'is_coin' => true,
                'disabled' => ! $user,
                'coin_balance' => $user?->coin_balance ?? 0,
            ],
        ];

        $order = ['Krysta Coin', 'QRIS', 'E-Wallet', 'Virtual Account', 'Convenience Store'];
        $sorted = [];
        foreach ($order as $key) {
            if (isset($paymentMethods[$key])) {
                $sorted[$key] = $paymentMethods[$key];
            }
        }
        foreach ($paymentMethods as $key => $value) {
            if (! isset($sorted[$key])) {
                $sorted[$key] = $value;
            }
        }
        $paymentMethods = $sorted;

        return Inertia::render('game-detail', [
            'game'           => $gameData,
            'productGroups'  => $productsGrouped,
            'flashSaleGroups' => $mappedFlashSale,
            'paymentMethods' => $paymentMethods,
            'loyaltyMinAmount' => (int) Setting::get('loyalty_min_amount', config('services.loyalty.min_amount', 5000)),
            'loyaltyRate' => (float) Setting::get('loyalty_rate_percent', config('services.loyalty.rate_percent', 1)),
        ]);
    }
}
