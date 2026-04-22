<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use App\Services\TopupPriceService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            // --- BARIS ATAS: 2 KOLOM ---
            Grid::make(2)
                ->schema([
                    // Kolom Kiri: Informasi Dasar
                    Section::make('Informasi Dasar')
                        ->schema([
                            Select::make('game_id')
                                ->relationship('game', 'name')
                                ->label('Pilih Game')
                                ->searchable()
                                ->preload()
                                ->required(),
                            TextInput::make('name')
                                ->label('Nama SKU / Produk')
                                ->placeholder('Contoh: 5 Diamonds')
                                ->required(),
                            TextInput::make('group')
                                ->label('Grup Kategori')
                                ->placeholder('Contoh: Weekly Pass')
                                ->nullable(),
                            Toggle::make('is_available')
                                ->label('Status Penjualan')
                                ->onColor('success')
                                ->default(true),
                        ]),

                    // Kolom Kanan: Flash Sale
                    Section::make('Promosi & Flash Sale')
                        ->schema([
                            TextInput::make('fake_price')
                                ->label('Harga Coret')
                                ->prefix('Rp')
                                ->numeric(),
                            Grid::make(2)->schema([
                                TextInput::make('flash_sale_price')
                                    ->label('Harga Flash Sale')
                                    ->numeric()
                                    ->prefix('Rp'),
                                DateTimePicker::make('flash_sale_ends_at')
                                    ->label('Berakhir Pada'),
                            ]),
                            Grid::make(2)->schema([
                                TextInput::make('flash_sale_stock')
                                    ->label('Stok Maksimal')
                                    ->numeric()
                                    ->nullable()
                                    ->helperText('Kosongkan = tidak terbatas.'),
                                TextInput::make('flash_sale_purchased')
                                    ->label('Sudah Dibeli')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                        ]),
                ])
                ->columnSpanFull(),

            // --- BARIS BAWAH: FULL KOLOM ---
            Section::make('Konfigurasi Harga')
                ->schema([
                    TextInput::make('price_cost')
                        ->label('Harga Modal (Digiflazz)')
                        ->prefix('Rp')
                        ->disabled()
                        ->dehydrated(false)
                        ->extraInputAttributes(['class' => 'font-bold text-primary-600']),

                    Grid::make(2)->schema([
                        TextInput::make('margin_flat')
                            ->label('Margin (Rp)')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $cost = (float) $get('price_cost');
                                $margin = (float) $get('margin_flat');
                                $set('price_sell', app(TopupPriceService::class)->calculateSellPrice($cost, $margin));
                            })
                            ->required(),

                        TextInput::make('price_sell')
                            ->label('Harga Jual')
                            ->prefix('Rp')
                            ->readOnly()
                            ->default(0)
                            ->required(),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
