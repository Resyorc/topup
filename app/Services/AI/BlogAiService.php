<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AiAction;
use App\Models\AiKnowledgeSource;
use App\Models\Game;
use Illuminate\Support\Str;

class BlogAiService
{
    public function __construct(private readonly AiClient $client) {}

    /**
     * Generate draft artikel blog dan simpan ke AI actions.
     *
     * @return array{ai_action_id:int,title:string,slug:string,excerpt:string,content:string,meta_title:string,meta_description:string,internal_links:array}
     */
    public function generateFromTopic(
        string $topic,
        string $keyword,
        string $tone = 'friendly',
        ?int $gameId = null,
        ?int $adminId = null,
    ): array {
        $gameContext = '';
        if ($gameId) {
            $game = Game::with(['products' => fn ($q) => $q->where('is_available', true)->orderBy('price_sell')->limit(10)])
                ->find($gameId);
            if ($game) {
                $products = $game->products->map(fn ($p) => "{$p->name}: Rp ".number_format($p->price_sell, 0, ',', '.'))->implode(', ');
                $gameContext = "\nGame terkait: {$game->name} (publisher: {$game->publisher})\nProduk tersedia: {$products}";
            }
        }

        $knowledgeBase = AiKnowledgeSource::getForAi();

        $systemPrompt = <<<PROMPT
Kamu adalah AI Blog Generator untuk Nuvelo — platform top up game online Indonesia.
Tugasmu membuat draft artikel blog SEO berkualitas tinggi dalam Bahasa Indonesia.

Aturan:
- Jangan klaim Nuvelo sebagai official partner publisher kecuali data menyatakan demikian
- Tone: {$tone}
- Tulis dari sudut pandang Nuvelo (platform, bukan user)
- Sertakan CTA ke halaman top up game di akhir artikel
- Selalu kembalikan output sebagai JSON valid

{$knowledgeBase}
PROMPT;

        $userPrompt = <<<PROMPT
Buat draft artikel SEO untuk Nuvelo.

Topik: {$topic}
Keyword utama: {$keyword}
{$gameContext}

Kembalikan JSON dengan struktur berikut (tidak ada teks lain di luar JSON):
{
  "title": "judul artikel",
  "slug": "slug-url-artikel",
  "excerpt": "ringkasan 1-2 kalimat maks 200 karakter",
  "content": "isi artikel lengkap dalam HTML (gunakan h2, h3, p, ul, li)",
  "faq": [{"q": "pertanyaan", "a": "jawaban"}],
  "meta_title": "meta title maks 60 karakter",
  "meta_description": "meta description maks 160 karakter",
  "internal_links": ["saran link internal 1", "saran link internal 2"]
}
PROMPT;

        $raw = $this->client->chat(
            messages: [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            module: 'blog',
            feature: 'generate_from_topic',
            adminId: $adminId,
            maxTokens: 3000,
            jsonMode: true,
        );

        $parsed = json_decode($raw, true) ?? [];

        $title   = $parsed['title'] ?? $topic;
        $slug    = Str::slug($parsed['slug'] ?? $title);
        $excerpt = $parsed['excerpt'] ?? '';
        $content = $parsed['content'] ?? $raw;

        $faqHtml = '';
        if (! empty($parsed['faq']) && is_array($parsed['faq'])) {
            $faqHtml = '<h2>FAQ</h2>';
            foreach ($parsed['faq'] as $item) {
                $faqHtml .= '<h3>'.e($item['q'] ?? '').'</h3><p>'.e($item['a'] ?? '').'</p>';
            }
        }

        $fullContent = $content.($faqHtml ? "\n\n{$faqHtml}" : '');

        $action = AiAction::create([
            'admin_id'    => $adminId ?? auth()->id() ?? 1,
            'module'      => 'blog',
            'action_type' => 'draft_article',
            'target_type' => 'article',
            'target_id'   => null,
            'before_data' => null,
            'after_data'  => [
                'title'            => $title,
                'slug'             => $slug,
                'excerpt'          => $excerpt,
                'content'          => $fullContent,
                'meta_title'       => $parsed['meta_title'] ?? '',
                'meta_description' => $parsed['meta_description'] ?? '',
                'internal_links'   => $parsed['internal_links'] ?? [],
            ],
            'status' => 'draft',
        ]);

        return [
            'ai_action_id'     => $action->id,
            'title'            => $title,
            'slug'             => $slug,
            'excerpt'          => $excerpt,
            'content'          => $fullContent,
            'meta_title'       => $parsed['meta_title'] ?? '',
            'meta_description' => $parsed['meta_description'] ?? '',
            'internal_links'   => $parsed['internal_links'] ?? [],
        ];
    }
}
