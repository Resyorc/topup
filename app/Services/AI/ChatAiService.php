<?php

declare(strict_types=1);

namespace App\Services\AI;

class ChatAiService
{
    public function __construct(private readonly AiClient $client) {}

    /**
     * Hasilkan balasan Nova untuk customer chat.
     *
     * @param  array<array{role:string,content:string}>  $history
     */
    public function reply(string $systemPrompt, array $history, string $message): string
    {
        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        foreach ($history as $turn) {
            if (! in_array($turn['role'] ?? '', ['user', 'assistant'], true)) {
                continue;
            }
            $messages[] = [
                'role'    => $turn['role'],
                'content' => mb_substr($turn['content'] ?? '', 0, 2000),
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        return $this->client->chat(
            messages: $messages,
            module: 'nova',
            feature: 'customer_chat',
            maxTokens: 1024,
        );
    }
}
