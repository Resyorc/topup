<?php

namespace App\Filament\Admin\Resources\Products\Tables;

use App\Models\Game;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo_url')
                    ->label('Logo')
                    ->circular()
                    ->disk('public'),

                TextColumn::make('game.name')
                    ->label('Game')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('providerProducts_count')
                    ->counts('providerProducts')
                    ->label('Seller')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('price_cost')
                    ->label('Modal')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                TextColumn::make('price_guest')
                    ->label('Guest')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->color('gray'),

                TextColumn::make('price_bronze')
                    ->label('Bronze')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->color('warning'),

                TextColumn::make('price_silver')
                    ->label('Silver')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->color('gray'),

                TextColumn::make('price_gold')
                    ->label('Gold')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->color('success'),

                TextColumn::make('price_platinum')
                    ->label('Platinum')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->color('info'),

                TextColumn::make('flash_sale_price')
                    ->label('Flash Sale')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_available')
                    ->label('Tersedia')
                    ->boolean(),
                    
                TextColumn::make('updated_at')
                    ->label('Terakhir Update')
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
