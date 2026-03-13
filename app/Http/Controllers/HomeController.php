<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Category;
use App\Models\Game;

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

        return Inertia::render('welcome', [
            'categories' => $categories,
            'games' => $games,
            'trendingGames' => $trendingGames,
            'trendingTotalSold' => $trendingTotalSold,
        ]);
    }
}
