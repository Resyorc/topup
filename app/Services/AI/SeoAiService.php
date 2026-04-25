<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\Article;
use App\Models\Game;

class SeoAiService
{
    public function __construct(private readonly AiClient $client) {}

    /**
     * Generate SEO metadata untuk halaman game.
     *
     * @return array{meta_title:string,meta_description:string,slug:string,keywords:string,faq:list<array{q:string,a:string}>}
     */
    public function generateForGame(int $gameId, ?int $adminId = null): array
    {
        $game = Game::with(['products' => fn ($q) => $q->where('is_available', true)->limit(5)])
            ->findOrFail($gameId);

        $products = $game->products->map(fn ($p) => $p->name)->implode(', ');

        return $this->generate(
            context: "Game: {$game->name}\nPublisher: {$game->publisher}\nKategori produk: {$products}",
            targetType: 'game',
            targetName: $game->name,
            adminId: $adminId,
        );
    }

    /**
     * Generate SEO metadata untuk artikel blog.
     *
     * @return array{meta_title:string,meta_description:string,slug:string,keywords:string,faq:list<array{q:string,a:string}>}
     */
    public function generateForArticle(int $articleId, ?int $adminId = null): array
    {
        $article = Article::findOrFail($articleId);

        $excerpt = mb_substr(strip_tags($article->content ?? ''), 0, 500);

        return $this->generate(
            context: "Judul: {$article->title}\nIsi: {$excerpt}",
            targetType: 'article',
            targetName: $article->title,
            adminId: $adminId,
        );
    }

    private function generate(string $context, string $targetType, string $targetName, ?int $adminId): array
    {
        $systemPrompt = <<<'PROMPT'
Kamu adalah SEO Assistant untuk Nuvelo — platform top up game online Indonesia.
Buat metadata SEO yang optimal untuk meningkatkan traffic organik.
Selalu kembalikan output sebagai JSON valid. Bahasa Indonesia.
PROMPT;

        $userPrompt = <<<PROMPT
Buat metadata SEO untuk halaman {$targetType} Nuvelo berikut:

{$context}

Kembalikan JSON dengan struktur berikut:
{
  "meta_title": "maks 60 karakter, sertakan nama game/topik dan brand Nuvelo",
  "meta_description": "maks 160 karakter, deskriptif, ada CTA",
  "slug": "slug-url-optimal",
  "keywords": "kata kunci 1, kata kunci 2, kata kunci 3",
  "faq": [
    {"q": "pertanyaan yang sering dicari", "a": "jawaban singkat"}
  ]
}
PROMPT;

        $raw    = $this->client->chat(
            messages: [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            module: 'seo',
            feature: 'generate_metadata',
            adminId: $adminId,
            maxTokens: 1024,
            jsonMode: true,
        );

        $parsed = json_decode($raw, true) ?? [];

        return [
            'meta_title'       => $parsed['meta_title'] ?? '',
            'meta_description' => $parsed['meta_description'] ?? '',
            'slug'             => $parsed['slug'] ?? '',
            'keywords'         => $parsed['keywords'] ?? '',
            'faq'              => $parsed['faq'] ?? [],
        ];
    }
}
