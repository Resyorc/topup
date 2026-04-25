<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AiReport;
use App\Models\Transaction;
use Illuminate\Support\Carbon;

class ReportAiService
{
    public function __construct(private readonly AiClient $client) {}

    public function generateDailyReport(string $date, ?int $adminId = null): AiReport
    {
        $day   = Carbon::parse($date);
        $start = $day->copy()->startOfDay();
        $end   = $day->copy()->endOfDay();

        return $this->generate(
            reportType: 'daily',
            title: 'Laporan Harian '.$day->format('d M Y'),
            start: $start,
            end: $end,
            adminId: $adminId,
        );
    }

    public function generateWeeklyReport(string $weekStart, ?int $adminId = null): AiReport
    {
        $start = Carbon::parse($weekStart)->startOfWeek();
        $end   = $start->copy()->endOfWeek();

        return $this->generate(
            reportType: 'weekly',
            title: 'Laporan Mingguan '.$start->format('d M').' – '.$end->format('d M Y'),
            start: $start,
            end: $end,
            adminId: $adminId,
        );
    }

    public function generateMonthlyReport(string $month, ?int $adminId = null): AiReport
    {
        $start = Carbon::parse($month)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        return $this->generate(
            reportType: 'monthly',
            title: 'Laporan Bulanan '.$start->format('M Y'),
            start: $start,
            end: $end,
            adminId: $adminId,
        );
    }

    private function generate(string $reportType, string $title, Carbon $start, Carbon $end, ?int $adminId): AiReport
    {
        $transactions = Transaction::with('product.game')
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $revenue  = $transactions->where('status', 'success')->sum('amount');
        $profit   = $transactions->where('status', 'success')->sum('profit');
        $success  = $transactions->where('status', 'success')->count();
        $failed   = $transactions->where('status', 'failed')->count();
        $total    = $transactions->count();
        $discount = $transactions->sum('discount');

        $topGames = $transactions->where('status', 'success')
            ->groupBy(fn ($t) => $t->product?->game?->name ?? 'Unknown')
            ->map(fn ($g) => ['count' => $g->count(), 'revenue' => $g->sum('amount')])
            ->sortByDesc('count')
            ->take(10)
            ->map(fn ($d, $name) => "{$name}: {$d['count']} tx (Rp ".number_format($d['revenue'], 0, ',', '.').')')
            ->implode(', ');

        $topProducts = $transactions->where('status', 'success')
            ->groupBy(fn ($t) => $t->product?->name ?? 'Unknown')
            ->map(fn ($g) => $g->count())
            ->sortDesc()
            ->take(5)
            ->map(fn ($c, $n) => "{$n}: {$c}")
            ->implode(', ');

        $systemPrompt = <<<'PROMPT'
Kamu adalah AI Report Generator untuk Nuvelo — platform top up game online Indonesia.
Buat laporan bisnis yang informatif, terstruktur, dan actionable.
Kembalikan output sebagai JSON valid. Bahasa Indonesia.
PROMPT;

        $userPrompt = <<<PROMPT
Buat {$reportType} report untuk Nuvelo:
Periode: {$start->format('d M Y')} – {$end->format('d M Y')}

Data:
- Total transaksi: {$total}
- Sukses: {$success} | Gagal: {$failed}
- Omzet: Rp {$revenue}
- Profit bersih: Rp {$profit}
- Total diskon: Rp {$discount}
- Top game: {$topGames}
- Top produk: {$topProducts}

Kembalikan JSON:
{
  "summary": "ringkasan eksekutif 3-4 kalimat",
  "highlights": ["poin positif 1", "poin positif 2"],
  "concerns": ["area yang perlu perhatian"],
  "recommendations": ["rekomendasi strategis 1", "rekomendasi 2"],
  "content_html": "laporan lengkap dalam HTML (h2, h3, p, table, ul)"
}
PROMPT;

        $raw    = $this->client->chat(
            messages: [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            module: 'report',
            feature: "generate_{$reportType}",
            adminId: $adminId,
            maxTokens: 3000,
            jsonMode: true,
        );

        $parsed = json_decode($raw, true) ?? [];

        return AiReport::create([
            'report_type'  => $reportType,
            'title'        => $title,
            'summary'      => $parsed['summary'] ?? '',
            'content'      => $parsed['content_html'] ?? $raw,
            'period_start' => $start->toDateString(),
            'period_end'   => $end->toDateString(),
            'generated_by' => $adminId,
        ]);
    }
}
