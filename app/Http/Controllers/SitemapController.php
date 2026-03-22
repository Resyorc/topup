<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $games = Game::where('is_active', true)
            ->select('slug', 'updated_at')
            ->orderBy('name')
            ->get();

        $xml = view('sitemap', compact('games'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
