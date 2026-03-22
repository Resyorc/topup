<?php

namespace App\Filament\Admin\Resources\BlockedIps\Tables;

use App\Models\BlockedIp;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class BlockedIpsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('ip')
                    ->label('IP Address')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->badge()
                    ->color('danger'),

                TextColumn::make('reason')
                    ->label('Alasan')
                    ->limit(50),

                IconColumn::make('is_auto')
                    ->label('Auto-block')
                    ->boolean()
                    ->trueColor('warning')
                    ->falseColor('info'),

                TextColumn::make('blocked_until')
                    ->label('Diblokir Sampai')
                    ->dateTime('d M Y, H:i')
                    ->default('Permanen')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Diblokir Pada')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_auto')
                    ->label('Jenis')
                    ->trueLabel('Auto-block')
                    ->falseLabel('Manual'),
            ])
            ->recordActions([
                Action::make('unblock')
                    ->label('Unblock')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (BlockedIp $record) {
                        BlockedIp::unblock($record->ip);

                        Notification::make()
                            ->title('IP berhasil di-unblock')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Unblock semua yang dipilih'),
                ]),
            ]);
    }
}
