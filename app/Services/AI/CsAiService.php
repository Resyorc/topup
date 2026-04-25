<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AiKnowledgeSource;
use App\Models\Transaction;

class CsAiService
{
    public function __construct(private readonly AiClient $client) {}

    /**
     * Generate draft balasan CS berdasarkan pesan customer dan data transaksi opsional.
     *
     * @return array{reply:string,escalation_needed:bool,escalation_reason:string|null,tone_used:string}
     */
    public function generateReply(
        string $customerMessage,
        ?string $invoiceId = null,
        string $tone = 'friendly',
        ?int $adminId = null,
    ): array {
        $transactionContext = '';

        if ($invoiceId) {
            $transaction = Transaction::with('product.game')->where('invoice_id', $invoiceId)->first();

            if ($transaction) {
                $transactionContext = <<<CONTEXT

Data Transaksi:
- Invoice: {$transaction->invoice_id}
- Game: {$transaction->product->game->name}
- Produk: {$transaction->product->name}
- Status: {$transaction->status}
- Payment: {$transaction->payment_status} ({$transaction->payment_name})
- Nominal: Rp {$transaction->amount}
- Alasan gagal: {$transaction->failure_reason}
- SN/Serial: {$transaction->sn}
- Dibuat: {$transaction->created_at->format('d M Y H:i')}
CONTEXT;
            }
        }

        $knowledgeBase = AiKnowledgeSource::getForAi();

        $toneMap = [
            'friendly' => 'ramah dan hangat',
            'formal'   => 'formal dan profesional',
            'concise'  => 'singkat dan to-the-point',
            'firm'     => 'tegas namun sopan',
            'polite'   => 'sangat sopan dan hormat',
        ];
        $toneDesc = $toneMap[$tone] ?? 'ramah dan hangat';

        $systemPrompt = <<<PROMPT
Kamu adalah AI CS Reply Assistant untuk Nuvelo — platform top up game online Indonesia.
Tugasmu membuat draft balasan customer service berdasarkan pesan dan data transaksi.

Aturan:
- Tone: {$toneDesc}
- Jangan menjanjikan refund jika belum ada persetujuan admin
- Jangan mengarang status transaksi
- Jika kasus sensitif, beri label escalation_needed: true
- Selalu kembalikan output sebagai JSON valid
- Bahasa Indonesia

{$knowledgeBase}
PROMPT;

        $userPrompt = <<<PROMPT
Pesan customer:
"{$customerMessage}"
{$transactionContext}

Kembalikan JSON dengan struktur:
{
  "reply": "draft balasan lengkap siap kirim",
  "escalation_needed": true/false,
  "escalation_reason": "alasan jika perlu eskalasi, null jika tidak",
  "suggested_action": "tindakan admin yang disarankan (opsional)"
}
PROMPT;

        $raw    = $this->client->chat(
            messages: [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            module: 'cs',
            feature: 'generate_reply',
            adminId: $adminId,
            maxTokens: 1024,
            jsonMode: true,
        );

        $parsed = json_decode($raw, true) ?? [];

        return [
            'reply'              => $parsed['reply'] ?? $raw,
            'escalation_needed'  => (bool) ($parsed['escalation_needed'] ?? false),
            'escalation_reason'  => $parsed['escalation_reason'] ?? null,
            'suggested_action'   => $parsed['suggested_action'] ?? null,
            'tone_used'          => $tone,
        ];
    }
}
