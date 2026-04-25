<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AI\AiClient;
use App\Services\AI\ChatAiService;
use App\Services\ChatContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class ChatController extends Controller
{
    public function __construct(private ChatContextService $contextService) {}

    public function send(Request $request)
    {
        $validated = $request->validate([
            'message'            => 'required|string|max:1000',
            'history'            => 'nullable|array|max:10',
            'history.*.role'     => 'required|in:user,assistant',
            'history.*.content'  => 'required|string|max:2000',
            'context'            => 'nullable|array',
            'context.page'       => 'nullable|string|max:100',
            'context.invoice_id' => 'nullable|string|max:50',
            'context.game_slug'  => 'nullable|string|max:100',
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

        if (empty(config('ai.api_key'))) {
            Log::error('ChatController: AI_API_KEY tidak dikonfigurasi.');

            return response()->json([
                'success' => false,
                'message' => 'Fitur chat sedang tidak tersedia.',
            ], 503);
        }

        $context = $validated['context'] ?? [];
        if ($request->user()) {
            $context['user_id'] = $request->user()->id;
        }

        $systemPrompt = $this->contextService->buildSystemPrompt($context);

        try {
            $chatService = new ChatAiService(AiClient::make());
            $reply       = $chatService->reply($systemPrompt, $validated['history'] ?? [], $validated['message']);

            return response()->json(['success' => true, 'reply' => $reply]);

        } catch (\Throwable $e) {
            Log::error('ChatController: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghubungi asisten. Coba lagi.',
            ], 500);
        }
    }
}
