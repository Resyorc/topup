<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi User')
                ->schema([
                    TextInput::make('name')
                        ->label('Nama')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->maxLength(255),
                ])
                ->columns(2),

            Section::make('Saldo & Role')
                ->schema([
                    TextInput::make('coin_balance')
                        ->label('Saldo Coin')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->helperText('Ubah saldo coin user secara manual. Perubahan tidak dicatat di riwayat transaksi coin.'),

                    Select::make('roles')
                        ->label('Role')
                        ->relationship('roles', 'name')
                        ->multiple()
                        ->preload(),
                ])
                ->columns(2),
        ]);
    }
}
