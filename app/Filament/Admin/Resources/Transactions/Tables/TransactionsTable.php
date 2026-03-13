<?php

namespace App\Filament\Admin\Resources\Transactions\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('invoice_id')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('product.name')
                    ->searchable(),
                TextColumn::make('customer_game_id')
                    ->searchable(),
                TextColumn::make('customer_zone_id')
                    ->searchable(),
                TextColumn::make('customer_whatsapp')
                    ->searchable(),
                TextColumn::make('amount')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('profit')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('sn')
                    ->searchable(),
                TextColumn::make('reference_id_provider')
                    ->searchable(),
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
                //
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
