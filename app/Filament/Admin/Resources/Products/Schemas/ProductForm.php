<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use App\Services\TopupPriceService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ProductForm
{
    private static function calculateTierPrice(Get $get, Set $set, string $tier): void
    {
        $cost = (float) $get('price_cost');
        $margin = (float) $get("margin_{$tier}_flat");
        $set("price_{$tier}", app(TopupPriceService::class)->calculateSellPrice($cost, $margin));
    }

    private static function getTierGroup(string $tierLabel, string $tierKey)
    {
        return Group::make([
            Placeholder::make("tier_$tierLabel")
                ->label("Tier $tierLabel")
                ->content(new \Illuminate\Support\HtmlString('<hr class="my-2">')),
            
            Grid::make(2)->schema([
                TextInput::make("margin_{$tierKey}_flat")
                    ->label('Margin (Rp)')
                    ->numeric()
                    ->default(0)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::calculateTierPrice($get, $set, $tierKey))
                    ->required(),
                    
                TextInput::make("price_{$tierKey}")
                    ->label('Final')
                    ->prefix('Rp')
                    ->readOnly()
                    ->default(0)
                    ->required(),
            ]),
        ]);
    }

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
            Section::make('Konfigurasi Harga Multi-Tier')
                ->description('Sesuaikan margin untuk setiap level member. Harga final dihitung otomatis.')
                ->schema([
                    TextInput::make('price_cost')
                        ->label('Harga Modal (Digiflazz)')
                        ->prefix('Rp')
                        ->disabled()
                        ->dehydrated(false)
                        ->extraInputAttributes(['class' => 'font-bold text-primary-600']),

                    Grid::make(3) // Di sini kita buat 3 kolom agar tier tidak terlalu memanjang ke bawah
                        ->schema([
                            self::getTierGroup('Guest', 'guest'),
                            self::getTierGroup('Bronze', 'bronze'),
                            self::getTierGroup('Silver', 'silver'),
                            self::getTierGroup('Gold', 'gold'),
                            self::getTierGroup('Platinum', 'platinum'),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
