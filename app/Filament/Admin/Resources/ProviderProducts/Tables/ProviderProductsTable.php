<?php

namespace App\Filament\Admin\Resources\ProviderProducts\Tables;

use App\Models\Game;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProviderProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('provider_sku')
                    ->label('Provider SKU')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('product_name')
                    ->label('Nama Provider')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('brand')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('product.name')
                    ->label('Mapped Product')
                    ->searchable()
                    ->placeholder('Belum di-associate')
                    ->wrap(),

                TextColumn::make('product.game.name')
                    ->label('Game')
                    ->badge()
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Harga Modal')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                TextColumn::make('seller_name')
                    ->label('Seller')
                    ->searchable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('product.game_id')
                    ->label('Game')
                    ->relationship('product.game', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label('Status Provider')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),

                TernaryFilter::make('product_id')
                    ->label('Association')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('product_id'),
                        false: fn ($query) => $query->whereNull('product_id'),
                        blank: fn ($query) => $query,
                    )
                    ->trueLabel('Sudah terhubung')
                    ->falseLabel('Belum terhubung'),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
