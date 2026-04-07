<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class OrderStatusChart extends ChartWidget
{
    protected ?string $heading = 'Status Pesanan (30 Hari Terakhir)';

    protected static ?int $sort = 2; // Keep it same row as TransactionChart

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $startDate = Carbon::now()->subDays(30);

        // Fetch counts for each status
        $successCount = Transaction::where('status', 'success')->where('created_at', '>=', $startDate)->count();
        $pendingCount = Transaction::where('status', 'pending')->where('created_at', '>=', $startDate)->count();
        $failedCount = Transaction::where('status', 'failed')->where('created_at', '>=', $startDate)->count();
        $expiredCount = Transaction::where('status', 'expired')->where('created_at', '>=', $startDate)->count();

        // Fallback agar chart bundar tetap muncul walau database kosong (0 transaksi)
        if (($successCount + $pendingCount + $failedCount + $expiredCount) === 0) {
            return [
                'datasets' => [
                    [
                        'label' => 'Pesanan',
                        'data' => [1],
                        'backgroundColor' => ['#374151'], // Dark mode gray
                        'hoverOffset' => 0
                    ],
                ],
                'labels' => ['Belum ada pesanan'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pesanan',
                    'data' => [$successCount, $pendingCount, $failedCount, $expiredCount],
                    'backgroundColor' => [
                        '#10b981', // Success (Emerald)
                        '#f59e0b', // Pending (Amber)
                        '#ef4444', // Failed (Red)
                        '#6b7280', // Expired (Gray)
                    ],
                    'hoverOffset' => 4
                ],
            ],
            'labels' => ['Sukses', 'Tertunda', 'Gagal', 'Kedaluwarsa'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'cutout' => '65%',
            'maintainAspectRatio' => false,
        ];
    }
}
