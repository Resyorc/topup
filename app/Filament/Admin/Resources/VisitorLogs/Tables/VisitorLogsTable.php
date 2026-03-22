<?php

namespace App\Filament\Admin\Resources\VisitorLogs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VisitorLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('visited_at', 'desc')
            ->columns([
                TextColumn::make('visited_at')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable(),

                TextColumn::make('ip')
                    ->label('IP Address')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('url')
                    ->label('Halaman')
                    ->searchable()
                    ->limit(50),

                TextColumn::make('user.name')
                    ->label('User')
                    ->default('Guest')
                    ->searchable(),

                TextColumn::make('referer')
                    ->label('Referer')
                    ->limit(40)
                    ->default('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user_agent')
                    ->label('Browser / Device')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('url')
                    ->label('Halaman')
                    ->options([
                        '/'         => 'Beranda',
                        '/invoice'  => 'Cek Invoice',
                        '/login'    => 'Login',
                        '/register' => 'Register',
                    ]),

                Filter::make('guest_only')
                    ->label('Guest saja')
                    ->query(fn (Builder $query) => $query->whereNull('user_id')),

                Filter::make('member_only')
                    ->label('Member saja')
                    ->query(fn (Builder $query) => $query->whereNotNull('user_id')),
            ])
            ->poll('30s'); // auto-refresh setiap 30 detik
    }
}
