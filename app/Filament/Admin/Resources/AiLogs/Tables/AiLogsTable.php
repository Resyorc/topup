<?php

namespace App\Filament\Admin\Resources\AiLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AiLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('module')
                    ->label('Modul')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('feature')
                    ->label('Fitur')
                    ->sortable(),

                TextColumn::make('model')
                    ->label('Model AI')
                    ->color('gray'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'success' => 'success',
                        'error'   => 'danger',
                        default   => 'gray',
                    }),

                TextColumn::make('total_tokens')
                    ->label('Token')
                    ->numeric()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('admin.name')
                    ->label('Admin')
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('module')
                    ->label('Modul')
                    ->options([
                        'nova'             => 'Nova Chat',
                        'blog'             => 'Blog Generator',
                        'seo'              => 'SEO Assistant',
                        'product'          => 'Product Assistant',
                        'cs'               => 'CS Reply',
                        'transaction'      => 'Transaction Analyst',
                        'report'           => 'Report Generator',
                        'provider_monitor' => 'Provider Monitor',
                    ]),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'success' => 'Sukses',
                        'error'   => 'Error',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
