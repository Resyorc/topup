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

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $today     = Carbon::today();
        $yesterday = Carbon::yesterday();
        $startOfMonth     = Carbon::now()->startOfMonth();
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth   = Carbon::now()->subMonth()->endOfMonth();

        // Revenue & profit hari ini
        $revenueToday     = (int) Transaction::success()->whereDate('created_at', $today)->sum('amount');
        $revenueYesterday = (int) Transaction::success()->whereDate('created_at', $yesterday)->sum('amount');

        // Revenue & profit bulan ini vs bulan lalu
        $revenueMonth     = (int) Transaction::success()->where('created_at', '>=', $startOfMonth)->sum('amount');
        $revenueLastMonth = (int) Transaction::success()->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->sum('amount');

        $profitMonth     = (int) Transaction::success()->where('created_at', '>=', $startOfMonth)->sum('profit');
        $profitLastMonth = (int) Transaction::success()->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->sum('profit');

        // Transaksi bulan ini
        $successCount = Transaction::success()->where('created_at', '>=', $startOfMonth)->count();
        $pendingCount = Transaction::where('status', 'pending')->count();
        $failedCount  = Transaction::where('status', 'failed')->where('created_at', '>=', $startOfMonth)->count();

        $totalUsers    = User::count();
        $totalGames    = Game::where('is_active', true)->count();
        $totalProducts = Product::where('is_available', true)->count();

        return [
            Stat::make('Revenue Hari Ini', 'Rp '.number_format($revenueToday, 0, ',', '.'))
                ->description($this->trendDescription($revenueToday, $revenueYesterday, 'vs kemarin'))
                ->descriptionIcon($revenueToday >= $revenueYesterday ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueToday >= $revenueYesterday ? 'success' : 'danger'),

            Stat::make('Revenue Bulan Ini', 'Rp '.number_format($revenueMonth, 0, ',', '.'))
                ->description($this->trendDescription($revenueMonth, $revenueLastMonth, 'vs bulan lalu'))
                ->descriptionIcon($revenueMonth >= $revenueLastMonth ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueMonth >= $revenueLastMonth ? 'success' : 'danger'),

            Stat::make('Profit Bulan Ini', 'Rp '.number_format($profitMonth, 0, ',', '.'))
                ->description($this->trendDescription($profitMonth, $profitLastMonth, 'vs bulan lalu'))
                ->descriptionIcon($profitMonth >= $profitLastMonth ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($profitMonth >= $profitLastMonth ? 'success' : 'danger'),

            Stat::make('Transaksi Sukses', number_format($successCount, 0, ',', '.'))
                ->description('Transaksi berhasil bulan ini')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Transaksi Pending', number_format($pendingCount, 0, ',', '.'))
                ->description('Menunggu pembayaran dari pelanggan')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Transaksi Gagal', number_format($failedCount, 0, ',', '.'))
                ->description('Transaksi gagal bulan ini')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Total User Terdaftar', number_format($totalUsers, 0, ',', '.'))
                ->description('Semua pengguna terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Game Aktif', $totalGames)
                ->description('Total game yang aktif')
                ->descriptionIcon('heroicon-m-puzzle-piece')
                ->color('info'),

            Stat::make('Produk Aktif', $totalProducts)
                ->description('Total produk yang tersedia')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('info'),
        ];
    }

    private function trendDescription(int $current, int $previous, string $label): string
    {
        if ($previous === 0) {
            return $current > 0 ? "↑ Baru ada data · {$label}" : "Belum ada data";
        }

        $diff    = $current - $previous;
        $percent = round(abs($diff) / $previous * 100, 1);
        $arrow   = $diff >= 0 ? '↑' : '↓';

        return "{$arrow} {$percent}% {$label}";
    }
}
