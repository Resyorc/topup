<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AiLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly int $timeout,
        private readonly int $maxTokens,
        private readonly bool $logEnabled,
    ) {}

    public static function make(): static
    {
        return new static(
            apiKey: config('ai.api_key', ''),
            model: config('ai.default_model', 'gpt-4o-mini'),
            timeout: config('ai.timeout', 30),
            maxTokens: config('ai.max_tokens', 2048),
            logEnabled: config('ai.log_enabled', true),
        );
    }

    /**
     * Kirim pesan ke OpenAI dan kembalikan teks balasan.
     *
     * @param  array<array{role:string,content:string}>  $messages
     */
    public function chat(
        array $messages,
        ?string $module = null,
        ?string $feature = null,
        ?int $adminId = null,
        ?int $maxTokens = null,
        bool $jsonMode = false,
    ): string {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('AI_API_KEY tidak dikonfigurasi.');
        }

        $payload = [
            'model'      => $this->model,
            'max_tokens' => $maxTokens ?? $this->maxTokens,
            'messages'   => $messages,
        ];

        if ($jsonMode) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        try {
            $httpResponse = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout($this->timeout)->post('https://api.openai.com/v1/chat/completions', $payload);

            if (! $httpResponse->successful()) {
                throw new \RuntimeException(
                    'OpenAI API error: '.$httpResponse->status().' '.$httpResponse->body()
                );
            }

            $data  = $httpResponse->json();
            $reply = $data['choices'][0]['message']['content'] ?? '';

            $this->writeLog(
                module: $module ?? 'unknown',
                feature: $feature ?? 'chat',
                messages: $messages,
                response: $reply,
                status: 'success',
                errorMessage: null,
                usage: $data['usage'] ?? [],
                adminId: $adminId,
            );

            return $reply;

        } catch (\Throwable $e) {
            $this->writeLog(
                module: $module ?? 'unknown',
                feature: $feature ?? 'chat',
                messages: $messages,
                response: null,
                status: 'error',
                errorMessage: $e->getMessage(),
                usage: [],
                adminId: $adminId,
            );

            throw $e;
        }
    }

    private function writeLog(
        string $module,
        string $feature,
        array $messages,
        ?string $response,
        string $status,
        ?string $errorMessage,
        array $usage,
        ?int $adminId,
    ): void {
        if (! $this->logEnabled) {
            return;
        }

        try {
            $prompt = '';
            foreach ($messages as $msg) {
                if (($msg['role'] ?? '') === 'user') {
                    $prompt = mb_substr($msg['content'] ?? '', 0, 3000);
                }
            }

            AiLog::create([
                'admin_id'      => $adminId,
                'module'        => $module,
                'feature'       => $feature,
                'model'         => $this->model,
                'prompt'        => $prompt,
                'response'      => $response ? mb_substr($response, 0, 5000) : null,
                'status'        => $status,
                'error_message' => $errorMessage ? mb_substr($errorMessage, 0, 1000) : null,
                'input_tokens'  => $usage['prompt_tokens'] ?? null,
                'output_tokens' => $usage['completion_tokens'] ?? null,
                'total_tokens'  => $usage['total_tokens'] ?? null,
                'ip_address'    => request()?->ip(),
                'user_agent'    => mb_substr(request()?->userAgent() ?? '', 0, 255),
            ]);
        } catch (\Throwable $e) {
            Log::warning('AiClient: gagal menulis ai_log — '.$e->getMessage());
        }
    }
}
