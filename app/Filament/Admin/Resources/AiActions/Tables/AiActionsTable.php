<?php

namespace App\Filament\Admin\Resources\AiActions\Tables;

use App\Models\AiAction;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AiActionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('module')
                    ->label('Modul')
                    ->badge()
                    ->sortable(),

                TextColumn::make('action_type')
                    ->label('Tipe Aksi')
                    ->sortable(),

                TextColumn::make('target_type')
                    ->label('Target')
                    ->formatStateUsing(fn ($state, $record) => $state ? "{$state} #{$record->target_id}" : '—')
                    ->color('gray'),

                TextColumn::make('admin.name')
                    ->label('Admin')
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'draft'     => 'gray',
                        'pending'   => 'warning',
                        'approved'  => 'success',
                        'rejected'  => 'danger',
                        'executed'  => 'info',
                        'failed'    => 'danger',
                        'cancelled' => 'gray',
                        default     => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft'     => 'Draft',
                        'pending'   => 'Pending',
                        'approved'  => 'Approved',
                        'rejected'  => 'Rejected',
                        'executed'  => 'Executed',
                        'failed'    => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('module')
                    ->label('Modul')
                    ->options([
                        'blog'             => 'Blog',
                        'seo'              => 'SEO',
                        'product'          => 'Product',
                        'provider_monitor' => 'Provider Monitor',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('approve')
                    ->label('Approve Draft')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (AiAction $record) => in_array($record->status, ['draft', 'pending']))
                    ->requiresConfirmation()
                    ->action(function (AiAction $record) {
                        $record->update([
                            'status'      => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);

                        Notification::make()->title('Aksi AI disetujui')->success()->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (AiAction $record) => in_array($record->status, ['draft', 'pending']))
                    ->requiresConfirmation()
                    ->action(function (AiAction $record) {
                        $record->update(['status' => 'rejected']);
                        Notification::make()->title('Aksi AI ditolak')->warning()->send();
                    }),
            ]);
    }
}
