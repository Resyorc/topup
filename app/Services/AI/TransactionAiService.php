<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\Transaction;
use Illuminate\Support\Carbon;

class TransactionAiService
{
    public function __construct(private readonly AiClient $client) {}

    /**
     * Generate ringkasan harian transaksi dengan insight AI.
     *
     * @return array<string,mixed>
     */
    public function dailySummary(string $date, ?int $adminId = null): array
    {
        $day   = Carbon::parse($date);
        $start = $day->copy()->startOfDay();
        $end   = $day->copy()->endOfDay();

        $transactions = Transaction::with('product.game')
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $total    = $transactions->count();
        $success  = $transactions->where('status', 'success')->count();
        $failed   = $transactions->where('status', 'failed')->count();
        $pending  = $transactions->whereIn('status', ['pending', 'processing'])->count();
        $revenue  = $transactions->where('status', 'success')->sum('amount');
        $profit   = $transactions->where('status', 'success')->sum('profit');
        $discount = $transactions->sum('discount');

        $topGames = $transactions->where('status', 'success')
            ->groupBy(fn ($t) => $t->product?->game?->name ?? 'Unknown')
            ->map(fn ($g) => $g->count())
            ->sortDesc()
            ->take(5)
            ->map(fn ($count, $name) => "{$name}: {$count} transaksi")
            ->implode(', ');

        $failureReasons = $transactions->where('status', 'failed')
            ->groupBy('failure_reason')
            ->map(fn ($g) => $g->count())
            ->sortDesc()
            ->take(5)
            ->map(fn ($count, $reason) => ($reason ?: 'Tidak diketahui').": {$count}x")
            ->implode(', ');

        $topMethods = $transactions->groupBy('payment_name')
            ->map(fn ($g) => $g->count())
            ->sortDesc()
            ->take(3)
            ->map(fn ($count, $name) => ($name ?: '-').": {$count}")
            ->implode(', ');

        $systemPrompt = <<<'PROMPT'
Kamu adalah AI Transaction Analyst untuk Nuvelo — platform top up game online Indonesia.
Analisis data transaksi dan berikan insight bisnis yang actionable.
Kembalikan output sebagai JSON valid. Bahasa Indonesia.
PROMPT;

        $userPrompt = <<<PROMPT
Analisis transaksi Nuvelo untuk tanggal {$day->format('d M Y')}:

Ringkasan:
- Total transaksi: {$total}
- Sukses: {$success}
- Gagal: {$failed}
- Pending/Processing: {$pending}
- Omzet: Rp {$revenue}
- Profit: Rp {$profit}
- Total diskon: Rp {$discount}

Top game (sukses): {$topGames}
Metode bayar: {$topMethods}
Alasan gagal: {$failureReasons}

Kembalikan JSON:
{
  "summary": "ringkasan 2-3 kalimat kondisi transaksi hari ini",
  "highlights": ["poin penting 1", "poin penting 2"],
  "concerns": ["masalah yang perlu diperhatikan"],
  "recommendations": ["rekomendasi tindakan konkret"],
  "health_score": 0-100
}
PROMPT;

        $raw    = $this->client->chat(
            messages: [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            module: 'transaction',
            feature: 'daily_summary',
            adminId: $adminId,
            maxTokens: 1024,
            jsonMode: true,
        );

        $insight = json_decode($raw, true) ?? [];

        return [
            'date'         => $day->format('d M Y'),
            'stats'        => compact('total', 'success', 'failed', 'pending', 'revenue', 'profit', 'discount'),
            'top_games'    => $topGames,
            'top_methods'  => $topMethods,
            'failure_reasons' => $failureReasons,
            'insight'      => $insight,
        ];
    }

    /**
     * Deteksi transaksi pending yang bermasalah (terlalu lama).
     *
     * @return list<array{invoice_id:string,status:string,game:string,product:string,minutes_pending:int}>
     */
    public function detectStuckPending(): array
    {
        $stuck = Transaction::with('product.game')
            ->where(function ($q) {
                $q->where('status', 'pending')->where('created_at', '<=', now()->subMinutes(15))
                  ->orWhere('status', 'processing')->where('created_at', '<=', now()->subMinutes(30));
            })
            ->where('payment_status', 'paid')
            ->orderBy('created_at')
            ->limit(50)
            ->get();

        return $stuck->map(fn ($t) => [
            'invoice_id'      => $t->invoice_id,
            'status'          => $t->status,
            'payment_status'  => $t->payment_status,
            'game'            => $t->product?->game?->name ?? '-',
            'product'         => $t->product?->name ?? '-',
            'minutes_pending' => (int) $t->created_at->diffInMinutes(now()),
            'amount'          => (int) $t->amount,
        ])->values()->all();
    }
}
