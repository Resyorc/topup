<?php

namespace App\Filament\Admin\Resources\Products\Tables;

use App\Models\Game;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('game.name')
                    ->searchable(),
                TextColumn::make('provider_sku')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('price_cost')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('margin_flat')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('margin_percent')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('price_sell')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('profit')
                    ->label('Profit')
                    ->getStateUsing(fn ($record) => $record->price_sell - $record->price_cost)
                    ->money('IDR', locale: 'id')
                    ->sortable(query: fn ($query, $direction) => $query->orderByRaw("(price_sell - price_cost) $direction")),
                IconColumn::make('is_available')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('game_id')
                    ->label('Game')
                    ->options(fn () => Game::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_available')
                    ->label('Ketersediaan')
                    ->trueLabel('Tersedia')
                    ->falseLabel('Tidak Tersedia'),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => auth()->user()->hasAnyRole(['Super Admin', 'Staff'])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
