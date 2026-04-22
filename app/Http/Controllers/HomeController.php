<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Game;
use App\Models\Product;
use App\Models\Setting;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Display the Homepage Dashboard.
     */
    public function index()
    {
        // 1. Fetch Categories
        $categories = Category::all(['id', 'name', 'slug', 'icon']);

        // 2. Fetch Active Games
        $games = Game::where('is_active', true)
            ->select('id', 'category_id', 'name', 'slug', 'image', 'thumbnail', 'publisher')
            ->orderBy('name', 'asc')
            ->get();

        // 3. Trending games — 5 game paling banyak dibeli (all-time)
        $trendingGames = Game::where('is_active', true)
            ->where('total_sold', '>', 0)
            ->select('id', 'category_id', 'name', 'slug', 'image', 'thumbnail', 'publisher', 'total_sold')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        $trendingTotalSold = $trendingGames->sum('total_sold');

        // 4. Active banners ordered by sort_order
        $banners = Banner::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['image', 'link']);

        // 5. Active flash sale products
        $flashSaleItems = Product::where('is_available', true)
            ->whereNotNull('flash_sale_price')
            ->whereNotNull('flash_sale_ends_at')
            ->where('flash_sale_ends_at', '>', now())
            ->with('game:id,name,slug,icon_rules,grouping_rules')
            ->orderBy('flash_sale_ends_at')
            ->get()
            ->map(function ($p) {
                $salePrice    = (int) ceil($p->flash_sale_price);
                // Harga coret dari fake_price
                $fakePrice    = $p->fake_price ? (int) ceil($p->fake_price) : null;
                $cleanName    = str_contains($p->name, '(')
                    ? trim(substr($p->name, 0, strpos($p->name, '(')))
                    : $p->name;

                return [
                    'id'                  => $p->id,
                    'name'                => $p->name,
                    'clean_name'          => $cleanName,
                    'game_name'           => $p->game->name,
                    'game_slug'           => $p->game->slug,
                    'logo_url'            => $p->game->resolveProductIcon($p),
                    'flash_sale_price'    => $salePrice,
                    'regular_price'       => $fakePrice ?? 0,
                    'discount_percent'    => ($fakePrice && $fakePrice > $salePrice)
                        ? (int) round((($fakePrice - $salePrice) / $fakePrice) * 100)
                        : 0,
                    'flash_sale_ends_at'  => $p->flash_sale_ends_at->timestamp,
                    'flash_sale_stock'    => $p->flash_sale_stock,
                    'flash_sale_purchased' => (int) $p->flash_sale_purchased,
                ];
            })
            ->values()
            ->toArray();

        // Untuk kompatibilitas PromoBanner — tetap pakai produk pertama
        $activeFlashSale = count($flashSaleItems) > 0 ? $flashSaleItems[0] : null;

        return Inertia::render('welcome', [
            'banners'          => $banners,
            'categories'       => $categories,
            'games'            => $games,
            'trendingGames'    => $trendingGames,
            'trendingTotalSold' => $trendingTotalSold,
            'activeFlashSale'  => $activeFlashSale,
            'flashSaleItems'   => $flashSaleItems,
        ]);
    }

}
