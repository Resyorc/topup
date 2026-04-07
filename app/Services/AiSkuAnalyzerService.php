<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProviderProduct;
use App\Models\Game;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSkuAnalyzerService
{
    private const ANTHROPIC_API = 'https://api.anthropic.com/v1/messages';
    private const MODEL          = 'claude-haiku-4-5-20251001';
    private const MAX_TOKENS     = 8192;
    private const BATCH_SIZE     = 30;
    private const CACHE_KEY      = 'ai_sku_suggestions';
    private const CACHE_TTL      = 60 * 60 * 24; // 24 jam

    /**
     * Analisis SKU yang belum dipetakan menggunakan AI.
     * SKU diproses per batch agar response tidak terpotong.
     *
     * @return array{count: int, error: string|null}
     */
    public function analyze(): array
    {
        $apiKey = config('services.anthropic.api_key');
        if (empty($apiKey)) {
            return ['count' => 0, 'error' => 'ANTHROPIC_API_KEY belum dikonfigurasi di .env'];
        }

        // SKU yang belum punya product_id
        $unmappedSkus = ProviderProduct::where('provider_name', 'digiflazz')
            ->whereNull('product_id')
            ->orderBy('brand')
            ->orderBy('product_name')
            ->get(['provider_sku as sku_code', 'product_name', 'brand', 'seller_name', 'price']);

        if ($unmappedSkus->isEmpty()) {
            return ['count' => 0, 'error' => 'Tidak ada SKU yang belum dipetakan.'];
        }

        $games         = Game::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $skuMap        = $unmappedSkus->keyBy('sku_code')->toArray();
        $batches       = $unmappedSkus->chunk(self::BATCH_SIZE);
        $salesContext  = $this->buildSalesContext();
        $marginContext = $this->buildMarginContext();

        $allSuggestions = [];

        foreach ($batches as $batch) {
            $prompt = $this->buildPrompt($batch->toArray(), $games->toArray(), $salesContext, $marginContext);

            try {
                $response = Http::withHeaders([
                    'x-api-key'         => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ])->timeout(90)->post(self::ANTHROPIC_API, [
                    'model'      => self::MODEL,
                    'max_tokens' => self::MAX_TOKENS,
                    'messages'   => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

                if (! $response->successful()) {
                    Log::error('AiSkuAnalyzer: API error', ['status' => $response->status(), 'body' => $response->body()]);
                    return ['count' => 0, 'error' => 'Anthropic API error: ' . $response->status()];
                }

                $text             = $response->json('content.0.text', '');
                $batchSuggestions = $this->parseResponse($text, $skuMap);
                $allSuggestions   = array_merge($allSuggestions, $batchSuggestions);
            } catch (\Exception $e) {
                Log::error('AiSkuAnalyzer: Exception — ' . $e->getMessage());
                return ['count' => 0, 'error' => $e->getMessage()];
            }
        }

        cache()->put(self::CACHE_KEY, $allSuggestions, self::CACHE_TTL);

        return ['count' => count($allSuggestions), 'error' => null];
    }

    /**
     * Ambil hasil analisis dari cache.
     */
    public function getSuggestions(): array
    {
        return cache()->get(self::CACHE_KEY, []);
    }

    /**
     * Hapus satu saran dari cache (setelah di-approve atau di-skip).
     */
    public function removeSuggestion(string $skuCode): void
    {
        $suggestions = $this->getSuggestions();
        $filtered    = array_values(array_filter($suggestions, fn ($s) => $s['sku_code'] !== $skuCode));
        cache()->put(self::CACHE_KEY, $filtered, self::CACHE_TTL);
    }

    /**
     * Hapus semua hasil analisis dari cache.
     */
    public function clearSuggestions(): void
    {
        cache()->forget(self::CACHE_KEY);
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Jumlah transaksi sukses per game_id (indikator popularitas).
     */
    private function buildSalesContext(): array
    {
        return DB::table('transactions')
            ->join('products', 'transactions.product_id', '=', 'products.id')
            ->where('transactions.status', 'success')
            ->selectRaw('products.game_id, COUNT(*) as total_sold')
            ->groupBy('products.game_id')
            ->pluck('total_sold', 'game_id')
            ->toArray();
    }

    /**
     * Rata-rata margin silver yang sudah berlaku per game_id.
     */
    private function buildMarginContext(): array
    {
        return Product::selectRaw('game_id, ROUND(AVG(margin_silver_flat)) as avg_margin')
            ->where('margin_silver_flat', '>', 0)
            ->groupBy('game_id')
            ->pluck('avg_margin', 'game_id')
            ->toArray();
    }

    private function buildPrompt(array $skus, array $games, array $salesContext, array $marginContext): string
    {
        $gameList = collect($games)->map(function ($g) use ($salesContext, $marginContext) {
            $sold      = $salesContext[$g['id']] ?? 0;
            $avgMargin = $marginContext[$g['id']] ?? null;
            $line      = "  - ID {$g['id']}: {$g['name']} | {$sold} transaksi";
            if ($avgMargin) {
                $line .= " | margin rata-rata berlaku: Rp " . number_format($avgMargin, 0, ',', '.');
            }
            return $line;
        })->join("\n");

        $skuList = collect($skus)
            ->map(fn ($s) => "  {$s['sku_code']} | {$s['product_name']} | Brand: {$s['brand']} | Seller: {$s['seller_name']} | Rp " . number_format($s['price'], 0, ',', '.'))
            ->join("\n");

        return <<<PROMPT
Kamu adalah asisten penetapan harga untuk toko top-up game Indonesia bernama Nuvelo.id.

Game yang tersedia beserta data penjualan historis:
{$gameList}

Berikut adalah daftar SKU dari supplier Digiflazz yang belum dipetakan ke produk manapun:
{$skuList}

Tugasmu: Analisis setiap SKU dan buat saran produk dengan aturan berikut:

1. "game_id" harus salah satu ID dari daftar game di atas. Jika tidak ada yang cocok, isi null.
2. "product_name" harus singkat, bersih, dan dalam Bahasa Indonesia. Contoh: "5 Diamond", "Weekly Diamond Pass", "100 Diamond (Malaysia)".
3. "suggested_margin" adalah margin jual flat dalam Rupiah untuk tier STANDARD (silver). Sistem akan hitung tier lain otomatis.
   Panduan penetapan margin:
   - Game dengan transaksi tinggi (> 500) → margin ketat, lebih kompetitif
   - Game dengan transaksi rendah (< 100) → margin lebih longgar
   - Denominasi kecil (harga modal < Rp 5.000) → margin Rp 150–400
   - Denominasi sedang (Rp 5.000–50.000) → margin Rp 300–800
   - Denominasi besar (> Rp 50.000) → margin Rp 500–2.000
   - Jika game punya "margin rata-rata berlaku", usahakan tidak jauh dari angka itu agar harga katalog konsisten
   - Minimum Rp 150, maksimum Rp 5.000
4. "recommended" = true jika produk layak dijual (denominasi umum, stok aktif).
   "recommended" = false jika kurang direkomendasikan (region asing jarang, bundle event khusus, dll).
5. "reason" = alasan singkat dalam Bahasa Indonesia (maks 80 karakter), sertakan reasoning margin jika relevan.

Balas HANYA dengan JSON array yang valid, tanpa teks lain. Format:
[
  {
    "sku_code": "mli-5",
    "game_id": 1,
    "product_name": "5 Diamond",
    "suggested_margin": 300,
    "recommended": true,
    "reason": "Denominasi terkecil ML, volume tinggi — margin kompetitif"
  }
]
PROMPT;
    }

    private function parseResponse(string $text, array $skuMap): array
    {
        // Bersihkan markdown code block jika ada (```json ... ```)
        $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
        $text = preg_replace('/\s*```\s*$/', '', $text);

        // Coba decode langsung
        $parsed = json_decode($text, true);

        // Jika gagal, cari substring antara [ pertama dan ] terakhir
        if (! is_array($parsed)) {
            $start = strpos($text, '[');
            $end   = strrpos($text, ']');
            if ($start === false || $end === false || $end <= $start) {
                Log::error('AiSkuAnalyzer: Respons AI bukan JSON valid', ['text' => substr($text, 0, 500)]);
                return [];
            }
            $json   = substr($text, $start, $end - $start + 1);
            $parsed = json_decode($json, true);
        }

        if (! is_array($parsed)) {
            Log::error('AiSkuAnalyzer: Gagal decode JSON', ['error' => json_last_error_msg()]);
            return [];
        }

        // Validasi & enrichment setiap item
        $result = [];
        foreach ($parsed as $item) {
            $skuCode = $item['sku_code'] ?? null;
            if (! $skuCode || ! isset($skuMap[$skuCode])) {
                continue;
            }

            $originalSku = $skuMap[$skuCode];
            $result[]    = [
                'sku_code'         => $skuCode,
                'game_id'          => $item['game_id'] ?? null,
                'product_name'     => $item['product_name'] ?? $originalSku['product_name'],
                'suggested_margin' => max(150, (int) ($item['suggested_margin'] ?? 500)),
                'recommended'      => (bool) ($item['recommended'] ?? true),
                'reason'           => $item['reason'] ?? '',
                // Data asli dari Digiflazz untuk referensi
                'original_name'    => $originalSku['product_name'],
                'brand'            => $originalSku['brand'],
                'seller_name'      => $originalSku['seller_name'],
                'price'            => $originalSku['price'],
            ];
        }

        return $result;
    }
}
