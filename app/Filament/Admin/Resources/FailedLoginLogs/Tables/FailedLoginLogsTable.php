<?php

namespace App\Filament\Admin\Resources\FailedLoginLogs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FailedLoginLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('attempted_at', 'desc')
            ->columns([
                TextColumn::make('attempted_at')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable(),

                TextColumn::make('ip')
                    ->label('IP Address')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->badge()
                    ->color('danger'),

                TextColumn::make('email_attempted')
                    ->label('Email Dicoba')
                    ->searchable(),

                TextColumn::make('user_agent')
                    ->label('Browser / Device')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('last_hour')
                    ->label('1 jam terakhir')
                    ->query(fn (Builder $q) => $q->where('attempted_at', '>=', now()->subHour())),

                Filter::make('today')
                    ->label('Hari ini')
                    ->query(fn (Builder $q) => $q->whereDate('attempted_at', today())),
            ])
            ->poll('15s');
    }
}
