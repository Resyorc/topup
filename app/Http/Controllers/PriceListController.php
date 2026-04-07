<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Game;
use App\Services\ProductGroupingService;
use Inertia\Inertia;

class PriceListController extends Controller
{
    public function index(ProductGroupingService $groupingService)
    {
        $categories = Category::orderBy('name')->get(['id', 'name', 'slug']);

        $games = Game::where('is_active', true)
            ->with(['category', 'products' => function ($query) {
                $query->where('is_available', true)->orderBy('price_guest', 'asc');
            }])
            ->orderBy('name', 'asc')
            ->get();

        $priceList = $games->map(function ($game) use ($groupingService) {
            return [
                'id'            => $game->id,
                'name'          => $game->name,
                'slug'          => $game->slug,
                'thumbnail'     => $game->thumbnail ? '/storage/'.$game->thumbnail : null,
                'category_id'   => $game->category_id,
                'category_name' => $game->category?->name,
                'product_count' => $game->products->count(),
                'min_price'     => $game->products->min('price_guest')
                    ? (int) ceil($game->products->min('price_guest'))
                    : null,
                'products'      => $groupingService->groupByGame($game->products, $game),
            ];
        });

        return Inertia::render('price-list', [
            'categories' => $categories,
            'priceList'  => $priceList,
        ]);
    }
}
