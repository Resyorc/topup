<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Game;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class DashboardStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        $revenueToday = Transaction::where('status', 'success')
            ->whereDate('created_at', $today)
            ->sum('amount');

        $revenueMonth = Transaction::where('status', 'success')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('amount');

        $profitMonth = Transaction::where('status', 'success')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('profit');

        $successCount = Transaction::where('status', 'success')
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        $pendingCount = Transaction::where('status', 'pending')->count();

        $failedCount = Transaction::where('status', 'failed')
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        $totalUsers = User::count();
        $totalGames = Game::where('is_active', true)->count();
        $totalProducts = Product::where('is_available', true)->count();

        return [
            Stat::make('Revenue Hari Ini', 'Rp ' . number_format($revenueToday, 0, ',', '.'))
                ->description('Total omzet transaksi sukses hari ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Revenue Bulan Ini', 'Rp ' . number_format($revenueMonth, 0, ',', '.'))
                ->description('Total omzet transaksi sukses bulan ini')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),

            Stat::make('Profit Bulan Ini', 'Rp ' . number_format($profitMonth, 0, ',', '.'))
                ->description('Keuntungan bersih bulan ini')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),

            Stat::make('Transaksi Sukses', $successCount)
                ->description('Transaksi berhasil bulan ini')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Transaksi Pending', $pendingCount)
                ->description('Menunggu pembayaran dari pelanggan')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Transaksi Gagal', $failedCount)
                ->description('Transaksi gagal bulan ini')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Total User Terdaftar', $totalUsers)
                ->description('Semua pengguna terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Game Aktif', $totalGames)
                ->description('Total game yang aktif')
                ->descriptionIcon('heroicon-m-puzzle-piece')
                ->color('info'),

            Stat::make('Produk Aktif', $totalProducts)
                ->description('Total produk yang aktif')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('info'),
        ];
    }
}
