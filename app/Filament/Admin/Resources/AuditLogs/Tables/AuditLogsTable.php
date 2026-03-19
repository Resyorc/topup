<?php

namespace App\Filament\Admin\Resources\AuditLogs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
                TextColumn::make('event')
                    ->label('Event')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'login' => 'success',
                        'register' => 'info',
                        'checkout' => 'warning',
                        'coin_topup' => 'warning',
                        'cancel' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->default('Guest'),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('subject_id')
                    ->label('ID Subjek')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label('Event')
                    ->options([
                        'login' => 'Login',
                        'register' => 'Register',
                        'checkout' => 'Checkout',
                        'coin_topup' => 'Top Up Coin',
                        'cancel' => 'Batal',
                    ]),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
