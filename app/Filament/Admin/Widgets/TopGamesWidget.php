<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Transaction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TopGamesWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Top 10 Game Terlaris — 30 Hari Terakhir';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::success()
                    ->with('product.game')
                    ->where('created_at', '>=', Carbon::now()->subDays(30))
                    ->select('product_id')
                    ->selectRaw('COUNT(*) as total_orders')
                    ->selectRaw('SUM(amount) as total_revenue')
                    ->selectRaw('SUM(profit) as total_profit')
                    ->groupBy('product_id')
                    ->orderByDesc('total_orders')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('product.game.name')
                    ->label('Game')
                    ->searchable(),

                TextColumn::make('product.name')
                    ->label('Produk')
                    ->limit(40),

                TextColumn::make('total_orders')
                    ->label('Total Order')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                TextColumn::make('total_revenue')
                    ->label('Total Revenue')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),

                TextColumn::make('total_profit')
                    ->label('Total Profit')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->color('success'),
            ])
            ->paginated(false);
    }
}
