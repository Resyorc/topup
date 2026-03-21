<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim($request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $games = Game::where('is_active', true)
            ->where('name', 'like', '%' . $q . '%')
            ->select('name', 'slug', 'thumbnail', 'image')
            ->orderByRaw("CASE WHEN name LIKE ? THEN 0 ELSE 1 END", [$q . '%'])
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn ($g) => [
                'name'      => $g->name,
                'slug'      => $g->slug,
                'thumbnail' => $g->thumbnail ?? $g->image,
            ]);

        return response()->json($games);
    }
}
