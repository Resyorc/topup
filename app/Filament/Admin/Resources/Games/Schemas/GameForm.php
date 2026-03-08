<?php

namespace App\Filament\Admin\Resources\Games\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

class GameForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                        if (($get('slug') ?? '') !== Str::slug($old)) {
                            return;
                        }
                        $set('slug', Str::slug($state));
                    }),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('publisher')
                    ->nullable(),
                FileUpload::make('image')
                    ->image()
                    ->disk('public')
                    ->directory('games'),
                FileUpload::make('thumbnail')
                    ->image()
                    ->disk('public')
                    ->directory('games/thumbnails'),
                Toggle::make('is_active')
                    ->required(),
                Section::make('Codashop User ID Check')
                    ->description('Konfigurasi untuk mengecek ID pemain di Codashop')
                    ->schema([
                        Toggle::make('is_check_id')
                            ->label('Aktifkan Pengecekan ID')
                            ->reactive(),
                        TextInput::make('codashop_voucher_id')
                            ->label('Codashop Voucher ID')
                            ->helperText('Contoh: hsrp-1 (HSR) / 1 (MLBB)')
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('is_check_id'))
                            ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('is_check_id')),
                        TextInput::make('codashop_price')
                            ->label('Codashop Price')
                            ->helperText('Contoh: 15150')
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('is_check_id'))
                            ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('is_check_id')),
                        TextInput::make('codashop_voucher_type')
                            ->label('Codashop Voucher Type')
                            ->helperText('Contoh: HONKAI_STAR_RAIL_P / MOBILE_LEGENDS')
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('is_check_id'))
                            ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('is_check_id')),
                        Toggle::make('codashop_need_zone')
                            ->label('Membutuhkan Zone / Server ID?')
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('is_check_id')),
                    ])
                    ->columns(2),
            ]);
    }
}
