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

        // 3. For "Trending Games" mockup, let's just pick the first 5 active games for now
        $trendingGames = $games->take(5);

        return Inertia::render('welcome', [
            'categories' => $categories,
            'games' => $games,
            'trendingGames' => $trendingGames,
        ]);
    }
}
