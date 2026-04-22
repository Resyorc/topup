<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Services\ProductGroupingService;
use Illuminate\Http\Request;

class ResellerProductController extends Controller
{
    public function games()
    {
        $games = Game::where('is_active', true)
            ->select('id', 'name', 'slug', 'publisher', 'category_id')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $games,
        ]);
    }

    public function products(Request $request, ProductGroupingService $groupingService)
    {
        $validated = $request->validate([
            'game_slug' => 'required|string|exists:games,slug',
        ]);

        $game = Game::where('slug', $validated['game_slug'])
            ->where('is_active', true)
            ->with(['products' => fn ($q) => $q->where('is_available', true)->orderBy('price_sell')])
            ->firstOrFail();

        $grouped = $groupingService->groupByGame($game->products, $game);

        return response()->json([
            'success' => true,
            'data'    => [
                'game'     => ['id' => $game->id, 'name' => $game->name, 'slug' => $game->slug],
                'products' => $grouped,
            ],
        ]);
    }
}
