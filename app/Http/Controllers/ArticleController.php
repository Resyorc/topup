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
            ->get(['id', 'title', 'slug', 'excerpt', 'thumbnail', 'published_at', 'content']);

        return Inertia::render('blog', [
            'articles' => $articles->map(fn ($a) => [
                'id'           => $a->id,
                'title'        => $a->title,
                'slug'         => $a->slug,
                'excerpt'      => $a->excerpt,
                'thumbnail'    => $a->thumbnail ? '/storage/'.$a->thumbnail : null,
                'published_at' => $a->published_at?->translatedFormat('d F Y') ?? null,
                'reading_time' => max(1, (int) ceil(str_word_count(strip_tags($a->content ?? '')) / 200)),
            ]),
        ]);
    }

    public function show(string $slug)
    {
        $article = Article::published()
            ->where('slug', $slug)
            ->firstOrFail();

        $related = Article::published()
            ->where('id', '!=', $article->id)
            ->orderByDesc('published_at')
            ->limit(3)
            ->get(['id', 'title', 'slug', 'excerpt', 'thumbnail', 'published_at', 'content'])
            ->map(fn ($a) => [
                'id'           => $a->id,
                'title'        => $a->title,
                'slug'         => $a->slug,
                'excerpt'      => $a->excerpt,
                'thumbnail'    => $a->thumbnail ? '/storage/'.$a->thumbnail : null,
                'published_at' => $a->published_at?->translatedFormat('d F Y') ?? null,
                'reading_time' => max(1, (int) ceil(str_word_count(strip_tags($a->content ?? '')) / 200)),
            ])
            ->values();

        return Inertia::render('blog-detail', [
            'article' => [
                'id'           => $article->id,
                'title'        => $article->title,
                'slug'         => $article->slug,
                'excerpt'      => $article->excerpt,
                'content'      => $article->content,
                'thumbnail'    => $article->thumbnail ? '/storage/'.$article->thumbnail : null,
                'published_at' => $article->published_at?->translatedFormat('d F Y') ?? null,
                'reading_time' => max(1, (int) ceil(str_word_count(strip_tags($article->content ?? '')) / 200)),
            ],
            'related_articles' => $related,
        ]);
    }
}
