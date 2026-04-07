<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Inertia\Inertia;

class ArticleController extends Controller
{
    public function index()
    {
        $articles = Article::published()
            ->orderByDesc('published_at')
            ->get(['id', 'title', 'slug', 'excerpt', 'thumbnail', 'published_at']);

        return Inertia::render('blog', [
            'articles' => $articles->map(fn ($a) => [
                'id'           => $a->id,
                'title'        => $a->title,
                'slug'         => $a->slug,
                'excerpt'      => $a->excerpt,
                'thumbnail'    => $a->thumbnail ? '/storage/'.$a->thumbnail : null,
                'published_at' => $a->published_at?->translatedFormat('d F Y') ?? null,
            ]),
        ]);
    }

    public function show(string $slug)
    {
        $article = Article::published()
            ->where('slug', $slug)
            ->firstOrFail();

        return Inertia::render('blog-detail', [
            'article' => [
                'id'           => $article->id,
                'title'        => $article->title,
                'slug'         => $article->slug,
                'excerpt'      => $article->excerpt,
                'content'      => $article->content,
                'thumbnail'    => $article->thumbnail ? '/storage/'.$article->thumbnail : null,
                'published_at' => $article->published_at?->translatedFormat('d F Y') ?? null,
            ],
        ]);
    }
}
