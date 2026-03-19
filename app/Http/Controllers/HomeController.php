<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Game;
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
        $categories = Category::all(['id', 'name', 'slug']);

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

        return Inertia::render('welcome', [
            'banners' => $banners,
            'categories' => $categories,
            'games' => $games,
            'trendingGames' => $trendingGames,
            'trendingTotalSold' => $trendingTotalSold,
            'loyaltyMinAmount' => (int) Setting::get('loyalty_min_amount', config('services.loyalty.min_amount', 5000)),
            'loyaltyRate' => (float) Setting::get('loyalty_rate_percent', config('services.loyalty.rate_percent', 1)),
        ]);
    }
}
