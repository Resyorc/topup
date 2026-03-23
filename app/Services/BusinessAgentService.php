<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BusinessAgentService
{
    private string $provider;

    private string $apiKey;

    private string $model;

    public function __construct()
    {
        // Deteksi provider: gunakan Claude jika ANTHROPIC_API_KEY tersedia, fallback ke OpenAI
        $anthropicKey = config('services.anthropic.api_key');
        $openaiKey = config('services.openai.api_key');

        if (! empty($anthropicKey)) {
            $this->provider = 'anthropic';
            $this->apiKey = $anthropicKey;
            $this->model = 'claude-sonnet-4-6';
        } else {
            $this->provider = 'openai';
            $this->apiKey = $openaiKey;
            $this->model = 'gpt-4o-mini';
        }
    }

    /**
     * Kirim pesan ke LLM dengan tool-calling loop.
     *
     * Return:
     *   ['type' => 'message', 'content' => string, 'messages' => array]
     *   ['type' => 'action',  'tool' => string, 'args' => array, 'description' => string, 'messages' => array]
     */
    public function chat(array $messages): array
    {
        $maxIterations = 6; // Batas loop tool-calling
        $iteration = 0;

        while ($iteration < $maxIterations) {
            $iteration++;
            $response = $this->callLLM($messages);

            if (isset($response['error'])) {
                return ['type' => 'error', 'content' => $response['error'], 'messages' => $messages];
            }

            // Tidak ada tool call → balikan teks respons
            if (empty($response['tool_calls'])) {
                $content = $response['content'] ?? '';
                $messages[] = ['role' => 'assistant', 'content' => $content];

                return ['type' => 'message', 'content' => $content, 'messages' => $messages];
            }

            // Ada tool call → proses satu per satu
            $toolCall = $response['tool_calls'][0];
            $toolName = $toolCall['name'];
            $toolArgs = $toolCall['args'];
            $toolCallId = $toolCall['id'];

            // Tambahkan assistant message dengan tool_calls ke history
            $messages[] = [
                'role'       => 'assistant',
                'content'    => $response['content'] ?? null,
                'tool_calls' => [
                    [
                        'id'       => $toolCallId,
                        'type'     => 'function',
                        'function' => ['name' => $toolName, 'arguments' => json_encode($toolArgs)],
                    ],
                ],
            ];

            // Write tool → pause dan minta konfirmasi dari user
            if (BusinessAgentTools::requiresConfirmation($toolName)) {
                return [
                    'type'        => 'action',
                    'tool'        => $toolName,
                    'tool_call_id'=> $toolCallId,
                    'args'        => $toolArgs,
                    'description' => BusinessAgentTools::describeAction($toolName, $toolArgs),
                    'messages'    => $messages,
                ];
            }

            // Read tool → eksekusi langsung, lanjutkan loop
            $result = BusinessAgentTools::execute($toolName, $toolArgs);
            $messages[] = $this->makeToolResult($toolCallId, $toolName, $result);
        }

        return ['type' => 'error', 'content' => 'Terlalu banyak iterasi tool. Coba ajukan pertanyaan yang lebih spesifik.', 'messages' => $messages];
    }

    /**
     * Eksekusi write tool setelah dikonfirmasi user, lalu dapatkan respons akhir dari LLM.
     */
    public function executeConfirmedAction(array $messages, string $toolCallId, string $toolName, array $toolArgs): array
    {
        $result = BusinessAgentTools::execute($toolName, $toolArgs);
        $messages[] = $this->makeToolResult($toolCallId, $toolName, $result);

        return $this->chat($messages);
    }

    public function getSystemPrompt(): string
    {
        return <<<'PROMPT'
Anda adalah Nuvelo Business Agent — asisten bisnis internal untuk toko top-up game Nuvelo.
Peran Anda adalah menganalisis data bisnis, mendeteksi anomali, dan memberikan rekomendasi yang dapat ditindaklanjuti.
Anda TIDAK PERNAH mengeksekusi tindakan apapun tanpa persetujuan eksplisit dari pemilik toko.

## IDENTITAS
- Nama: Nuvelo Business Agent
- Bahasa: Bahasa Indonesia yang profesional dan ringkas
- Jangan gunakan jargon teknis kecuali diminta

## PRINSIP UTAMA

1. TIDAK ADA EKSEKUSI OTOMATIS
   Sebelum memanggil tool yang mengubah data (create_promo_code, deactivate_promo, update_product_price, send_notification, adjust_loyalty_points), Anda HARUS terlebih dahulu menjelaskan rencana tindakan dan menunggu konfirmasi. Sistem akan menampilkan dialog konfirmasi kepada pengguna secara otomatis ketika Anda memanggil write tool.

2. TRANSPARANSI PENUH
   Selalu jelaskan mengapa Anda membuat rekomendasi. Sertakan angka konkret.

3. PRIORITAS BERDASARKAN RISIKO
   Tandai rekomendasi dengan: 🔴 DARURAT | 🟡 PERHATIAN | 🟢 SARAN

4. JANGAN BERASUMSI
   Jika data tidak cukup, gunakan tool read untuk mengambil data aktual terlebih dahulu.

## KONTEKS BISNIS
- Platform: Toko top-up game online (Mobile Legend, Free Fire, PUBG, dll.)
- Mata uang: Rupiah (IDR)
- Zona waktu: WIB (UTC+7)
- Sistem loyalitas: Koin internal (1 koin = Rp 50, diperoleh dari setiap transaksi)
- Margin minimum yang dapat diterima: 10%
- Margin target rata-rata: 20-30%

## KEMAMPUAN TOOLS
Tools read (dapat digunakan langsung): get_products, get_transactions, get_customers, get_promo_codes, get_sales_report
Tools write (WAJIB konfirmasi dulu): create_promo_code, deactivate_promo, update_product_price, send_notification, adjust_loyalty_points

## FORMAT REKOMENDASI
Saat memberi rekomendasi:
```
[LEVEL] Judul singkat

TEMUAN: [data konkret]
REKOMENDASI: [tindakan spesifik]
DAMPAK ESTIMASI: [hasil yang diharapkan]
RISIKO JIKA DIABAIKAN: [konsekuensi]

→ Apakah Anda ingin saya terapkan ini?
```

## LARANGAN
- Jangan jalankan write tool tanpa menjelaskan dampaknya terlebih dahulu
- Jangan buat estimasi tanpa data pendukung
- Jangan sarankan diskon yang melebihi margin kotor produk
PROMPT;
    }

    // ── INTERNAL ─────────────────────────────────────────────────────────────

    private function callLLM(array $messages): array
    {
        if ($this->provider === 'anthropic') {
            return $this->callAnthropic($messages);
        }

        return $this->callOpenAI($messages);
    }

    private function callOpenAI(array $messages): array
    {
        try {
            // Sisipkan system prompt jika belum ada
            $apiMessages = $this->ensureSystemPrompt($messages);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                'model'      => $this->model,
                'max_tokens' => 2048,
                'messages'   => $apiMessages,
                'tools'      => BusinessAgentTools::definitions(),
                'tool_choice'=> 'auto',
            ]);

            if (! $response->successful()) {
                Log::error('BusinessAgent OpenAI error', ['status' => $response->status()]);

                return ['error' => 'Gagal menghubungi AI. Coba lagi.'];
            }

            $data = $response->json();
            $choice = $data['choices'][0] ?? null;
            $message = $choice['message'] ?? [];

            $toolCalls = [];
            foreach ($message['tool_calls'] ?? [] as $tc) {
                $toolCalls[] = [
                    'id'   => $tc['id'],
                    'name' => $tc['function']['name'],
                    'args' => json_decode($tc['function']['arguments'] ?? '{}', true) ?? [],
                ];
            }

            return [
                'content'    => $message['content'] ?? null,
                'tool_calls' => $toolCalls,
            ];
        } catch (\Throwable $e) {
            Log::error('BusinessAgent OpenAI exception: '.$e->getMessage());

            return ['error' => 'Terjadi kesalahan: '.$e->getMessage()];
        }
    }

    private function callAnthropic(array $messages): array
    {
        try {
            // Anthropic memisahkan system prompt dari messages
            $systemPrompt = $this->getSystemPrompt();
            $apiMessages = array_filter($messages, fn ($m) => $m['role'] !== 'system');
            $apiMessages = array_values($apiMessages);

            // Konversi format tool result ke format Anthropic
            $anthropicMessages = array_map(function ($m) {
                if ($m['role'] === 'tool') {
                    return [
                        'role'    => 'user',
                        'content' => [[
                            'type'        => 'tool_result',
                            'tool_use_id' => $m['tool_call_id'],
                            'content'     => $m['content'],
                        ]],
                    ];
                }
                if (! empty($m['tool_calls'])) {
                    $content = [];
                    if (! empty($m['content'])) {
                        $content[] = ['type' => 'text', 'text' => $m['content']];
                    }
                    foreach ($m['tool_calls'] as $tc) {
                        $content[] = [
                            'type'  => 'tool_use',
                            'id'    => $tc['id'],
                            'name'  => $tc['function']['name'],
                            'input' => json_decode($tc['function']['arguments'], true),
                        ];
                    }

                    return ['role' => 'assistant', 'content' => $content];
                }

                return $m;
            }, $apiMessages);

            // Konversi tool definitions ke format Anthropic
            $anthropicTools = array_map(fn ($t) => [
                'name'         => $t['function']['name'],
                'description'  => $t['function']['description'],
                'input_schema' => $t['function']['parameters'],
            ], BusinessAgentTools::definitions());

            $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type'      => 'application/json',
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model'      => $this->model,
                'max_tokens' => 2048,
                'system'     => $systemPrompt,
                'messages'   => $anthropicMessages,
                'tools'      => $anthropicTools,
            ]);

            if (! $response->successful()) {
                Log::error('BusinessAgent Anthropic error', ['status' => $response->status(), 'body' => $response->body()]);

                return ['error' => 'Gagal menghubungi AI. Coba lagi.'];
            }

            $data = $response->json();
            $content = $data['content'] ?? [];

            $text = '';
            $toolCalls = [];

            foreach ($content as $block) {
                if ($block['type'] === 'text') {
                    $text .= $block['text'];
                } elseif ($block['type'] === 'tool_use') {
                    $toolCalls[] = [
                        'id'   => $block['id'],
                        'name' => $block['name'],
                        'args' => $block['input'] ?? [],
                    ];
                }
            }

            return ['content' => $text ?: null, 'tool_calls' => $toolCalls];
        } catch (\Throwable $e) {
            Log::error('BusinessAgent Anthropic exception: '.$e->getMessage());

            return ['error' => 'Terjadi kesalahan: '.$e->getMessage()];
        }
    }

    private function makeToolResult(string $toolCallId, string $toolName, string $result): array
    {
        return [
            'role'         => 'tool',
            'tool_call_id' => $toolCallId,
            'name'         => $toolName,
            'content'      => $result,
        ];
    }

    private function ensureSystemPrompt(array $messages): array
    {
        if (! empty($messages) && $messages[0]['role'] === 'system') {
            return $messages;
        }

        return array_merge([['role' => 'system', 'content' => $this->getSystemPrompt()]], $messages);
    }

    public function getProviderInfo(): string
    {
        return $this->provider === 'anthropic'
            ? 'Claude ('.$this->model.')'
            : 'GPT ('.$this->model.')';
    }
}
