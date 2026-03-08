<?php

namespace App\Filament\Admin\Resources\Transactions\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Transaction;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalIncome = Transaction::where('status', 'success')->sum('amount');
        $totalProfit = Transaction::where('status', 'success')->sum('profit');
        $pendingCount = Transaction::where('status', 'pending')->count();

        return [
            Stat::make('Total Pemasukan (Sukses)', 'Rp ' . number_format($totalIncome, 0, ',', '.'))
                ->description('Total omzet dari transaksi sukses.')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            
            Stat::make('Total Laba / Profit (Sukses)', 'Rp ' . number_format($totalProfit, 0, ',', '.'))
                ->description('Total keuntungan bersih.')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),
            
            Stat::make('Transaksi Pending / Belum Dibayar', $pendingCount)
                ->description('Menunggu pembayaran dari pelanggan.')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
