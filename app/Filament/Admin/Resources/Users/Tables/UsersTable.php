<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('email_verified_at')
                    ->label('Verifikasi Email')
                    ->boolean()
                    ->getStateUsing(fn ($record) => ! is_null($record->email_verified_at))
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('tier')
                    ->label('Tier')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'silver'   => 'info',
                        'gold'     => 'warning',
                        'platinum' => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('coin_balance')
                    ->label('Saldo Coin')
                    ->numeric(thousandsSeparator: '.')
                    ->sortable()
                    ->alignRight(),

                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Super Admin' => 'danger',
                        'Staff'       => 'info',
                        'CS'          => 'warning',
                        default       => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('reset_password')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->modalHeading('Reset Password User')
                    ->modalDescription(fn ($record) => "Password baru untuk {$record->name}. Salin sebelum menutup popup ini.")
                    ->modalSubmitActionLabel('Terapkan Password')
                    ->form([
                        TextInput::make('new_password')
                            ->label('Password Baru')
                            ->default(fn () => Str::random(12))
                            ->readOnly()
                            ->copyable()
                            ->helperText('Klik ikon copy untuk menyalin password.'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update(['password' => Hash::make($data['new_password'])]);
                        \Filament\Notifications\Notification::make()
                            ->title('Password Berhasil Direset')
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => auth()->user()->hasAnyRole(['Super Admin', 'CS'])),
            ])
            ->filters([
                TernaryFilter::make('email_verified_at')
                    ->label('Verifikasi Email')
                    ->nullable()
                    ->placeholder('Semua')
                    ->trueLabel('Sudah Verifikasi')
                    ->falseLabel('Belum Verifikasi'),

                SelectFilter::make('roles')
                    ->label('Role')
                    ->relationship('roles', 'name'),
            ])
            ->striped();
    }
}
