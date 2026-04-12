<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Game;
use Inertia\Inertia;

class PriceListController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('name')->get(['id', 'name', 'slug']);

        $games = Game::where('is_active', true)
            ->with(['category', 'products' => function ($query) {
                $query->where('is_available', true)->orderBy('price_guest', 'asc');
            }])
            ->orderBy('name', 'asc')
            ->get();

        $priceList = $games->map(function ($game) {
            return [
                'id'            => $game->id,
                'name'          => $game->name,
                'slug'          => $game->slug,
                'thumbnail'     => $game->thumbnail ? '/storage/' . $game->thumbnail : null,
                'category_id'   => $game->category_id,
                'category_name' => $game->category?->name,
                'product_count' => $game->products->count(),
                'products'      => $game->products->map(fn ($p) => [
                    'id'            => $p->id,
                    'name'          => $p->name,
                    'price_guest'   => (int) $p->price_guest,
                    'price_bronze'  => (int) $p->price_bronze,
                    'price_silver'  => (int) $p->price_silver,
                    'price_gold'    => (int) $p->price_gold,
                    'price_platinum'=> (int) $p->price_platinum,
                ])->values(),
            ];
        });

        return Inertia::render('price-list', [
            'categories' => $categories,
            'priceList'  => $priceList,
        ]);
    }
}
