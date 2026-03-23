<?php

namespace App\Filament\Admin\Resources\Vouchers\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class VouchersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->weight('bold'),

                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'percent' => 'info',
                        'flat' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'percent' => 'Persentase',
                        'flat' => 'Nominal',
                        default => $state,
                    }),

                TextColumn::make('value')
                    ->label('Nilai')
                    ->formatStateUsing(fn ($state, $record) => $record->type === 'percent'
                        ? "{$state}%"
                        : 'Rp '.number_format($state, 0, ',', '.')),

                TextColumn::make('min_amount')
                    ->label('Min. Transaksi')
                    ->formatStateUsing(fn ($state) => $state > 0
                        ? 'Rp '.number_format($state, 0, ',', '.')
                        : '—'),

                TextColumn::make('used_count')
                    ->label('Dipakai')
                    ->formatStateUsing(fn ($state, $record) => $record->usage_limit
                        ? "{$state} / {$record->usage_limit}"
                        : "{$state} / ∞"),

                TextColumn::make('valid_until')
                    ->label('Kadaluarsa')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('—'),

                TextColumn::make('min_tier')
                    ->label('Min. Tier')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'silver'   => 'info',
                        'gold'     => 'warning',
                        'platinum' => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => match ($state) {
                        'silver'   => '🥈 Silver',
                        'gold'     => '🥇 Gold',
                        'platinum' => '💎 Platinum',
                        'bronze'   => '🥉 Bronze',
                        default    => '—',
                    })
                    ->placeholder('—'),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipe')
                    ->options(['flat' => 'Nominal', 'percent' => 'Persentase']),

                TernaryFilter::make('is_active')
                    ->label('Status'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
