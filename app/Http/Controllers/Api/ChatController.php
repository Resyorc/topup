<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class ChatController extends Controller
{
    public function __construct(private ChatContextService $contextService) {}

    public function send(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'history' => 'nullable|array|max:10',
            'history.*.role' => 'required|in:user,assistant',
            'history.*.content' => 'required|string|max:2000',
            'context' => 'nullable|array',
            'context.page' => 'nullable|string|max:100',
            'context.invoice_id' => 'nullable|string|max:50',
            'context.game_slug' => 'nullable|string|max:100',
        ]);

        // Rate limiting: 20 pesan per menit per IP
        $key = 'chat:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 20)) {
            return response()->json([
                'success' => false,
                'message' => 'Terlalu banyak pesan. Coba lagi sebentar.',
            ], 429);
        }
        RateLimiter::hit($key, 60);

        $apiKey = config('services.openai.api_key');

        if (empty($apiKey)) {
            Log::error('ChatController: OPENAI_API_KEY tidak dikonfigurasi.');

            return response()->json([
                'success' => false,
                'message' => 'Fitur chat sedang tidak tersedia.',
            ], 503);
        }

        // Injeksi user_id ke context jika login
        $context = $validated['context'] ?? [];
        if ($request->user()) {
            $context['user_id'] = $request->user()->id;
        }

        $systemPrompt = $this->contextService->buildSystemPrompt($context);

        // Bangun array messages: system prompt + history + pesan baru
        // History hanya boleh mengandung role user/assistant — tidak boleh system
        $messages = [['role' => 'system', 'content' => $systemPrompt]];
        foreach ($validated['history'] ?? [] as $turn) {
            if (! in_array($turn['role'], ['user', 'assistant'], true)) {
                continue;
            }
            $messages[] = [
                'role' => $turn['role'],
                'content' => mb_substr($turn['content'], 0, 2000), // hard cap konten
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $validated['message']];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'max_tokens' => 1024,
                'messages' => $messages,
            ]);

            if (! $response->successful()) {
                Log::error('ChatController: OpenAI API error', ['status' => $response->status(), 'body' => $response->body()]);

                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghubungi asisten. Coba lagi.',
                ], 500);
            }

            $data = $response->json();
            $reply = $data['choices'][0]['message']['content'] ?? '';

            return response()->json([
                'success' => true,
                'reply' => $reply,
            ]);

        } catch (\Exception $e) {
            Log::error('ChatController: OpenAI Exception — '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan. Coba lagi.',
            ], 500);
        }
    }
}
