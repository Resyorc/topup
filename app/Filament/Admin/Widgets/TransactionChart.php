<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class TransactionChart extends ChartWidget
{
    protected ?string $heading = 'Transaksi Sukses per Minggu (Bulan Ini)';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $weeks = [];
        $revenues = [];
        $counts = [];

        $current = $startOfMonth->copy()->startOfWeek();
        while ($current->lte($endOfMonth)) {
            $weekStart = $current->copy()->max($startOfMonth);
            $weekEnd = $current->copy()->endOfWeek()->min($endOfMonth);

            $weeks[] = $weekStart->format('d/m').'–'.$weekEnd->format('d/m');

            $revenues[] = (int) Transaction::where('status', 'success')
                ->whereBetween('created_at', [$weekStart->startOfDay(), $weekEnd->endOfDay()])
                ->sum('amount');

            $counts[] = Transaction::where('status', 'success')
                ->whereBetween('created_at', [$weekStart->startOfDay(), $weekEnd->endOfDay()])
                ->count();

            $current->addWeek();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (Rp)',
                    'data' => $revenues,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Jumlah Transaksi',
                    'data' => $counts,
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $weeks,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Revenue (Rp)',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Jumlah Transaksi',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }
}
