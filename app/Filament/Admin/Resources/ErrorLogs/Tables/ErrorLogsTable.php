<?php

namespace App\Filament\Admin\Resources\ErrorLogs\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ErrorLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('occurred_at', 'desc')
            ->columns([
                TextColumn::make('occurred_at')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable(),

                TextColumn::make('level')
                    ->label('Level')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'error'    => 'warning',
                        'warning'  => 'info',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),

                TextColumn::make('exception')
                    ->label('Exception')
                    ->limit(45)
                    ->searchable()
                    ->fontFamily('mono')
                    ->tooltip(fn ($record) => $record->exception),

                TextColumn::make('message')
                    ->label('Pesan')
                    ->limit(60)
                    ->searchable()
                    ->tooltip(fn ($record) => $record->message),

                TextColumn::make('url')
                    ->label('URL')
                    ->limit(40)
                    ->searchable(),

                TextColumn::make('ip')
                    ->label('IP')
                    ->fontFamily('mono')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user.name')
                    ->label('User')
                    ->default('Guest')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('level')
                    ->label('Level')
                    ->options([
                        'critical' => 'Critical',
                        'error'    => 'Error',
                        'warning'  => 'Warning',
                    ]),

                Filter::make('today')
                    ->label('Hari ini saja')
                    ->query(fn (Builder $q) => $q->whereDate('occurred_at', today())),

                Filter::make('has_user')
                    ->label('Dari user login')
                    ->query(fn (Builder $q) => $q->whereNotNull('user_id')),
            ])
            ->recordActions([
                Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record) => $record->exception ?? 'Error Detail')
                    ->modalContent(fn ($record) => view('filament.error-log-detail', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->poll('60s');
    }
}
