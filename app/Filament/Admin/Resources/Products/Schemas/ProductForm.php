<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use App\Services\TopupPriceService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
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
    private static function calculateTierPrice(Get $get, Set $set, string $tier): void
    {
        $cost = (float) $get('price_cost');
        $margin = (float) $get("margin_{$tier}_flat");
        $set("price_{$tier}", app(TopupPriceService::class)->calculateSellPrice($cost, $margin));
    }

    public static function configure(Schema $schema): Schema
    {
        // Setup Tier Fields Generator
        $tierFields = function (string $tier, string $label, string $color) {
            return Grid::make(2)->schema([
                TextInput::make("margin_{$tier}_flat")
                    ->label("Margin {$label}")
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('Rp')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::calculateTierPrice($get, $set, $tier)),
                
                TextInput::make("price_{$tier}")
                    ->label("Harga Final {$label}")
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('Rp')
                    ->readOnly()
                    ->extraInputAttributes(['class' => "text-{$color}-600 font-bold"]),
            ]);
        };

        return $schema
            ->components([
                Section::make('Informasi Dasar')
                    ->schema([
                        Select::make('game_id')
                            ->relationship('game', 'name')
                            ->searchable()
                            ->required(),

                        TextInput::make('name')
                            ->label('Nama Produk')
                            ->required(),

                        FileUpload::make('logo_url')
                            ->label('Logo Produk (Opsional)')
                            ->image()
                            ->directory('products')
                            ->disk('public'),

                        Toggle::make('is_available')
                            ->label('Tersedia untuk dijual')
                            ->default(true)
                            ->required(),
                    ])->columns(2),

                Section::make('Multi-Tier Pricing')
                    ->description('Harga final dihitung otomatis dari Harga Modal + Margin.')
                    ->schema([
                        TextInput::make('price_cost')
                            ->label('Harga Modal (Termurah saat ini)')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->prefix('Rp')
                            ->helperText('Otomatis disinkronkan dari Provider Termurah jika SKU dikaitkan via halaman Provider SKU.')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                foreach (['guest', 'bronze', 'silver', 'gold', 'platinum'] as $t) {
                                    self::calculateTierPrice($get, $set, $t);
                                }
                            }),

                        Fieldset::make('Tier: Guest (Publik)')
                            ->schema([$tierFields('guest', 'Guest', 'gray')]),
                            
                        Fieldset::make('Tier: Bronze')
                            ->schema([$tierFields('bronze', 'Bronze', 'orange')]),
                            
                        Fieldset::make('Tier: Silver')
                            ->schema([$tierFields('silver', 'Silver', 'slate')]),
                            
                        Fieldset::make('Tier: Gold')
                            ->schema([$tierFields('gold', 'Gold', 'yellow')]),
                            
                        Fieldset::make('Tier: Platinum')
                            ->schema([$tierFields('platinum', 'Platinum', 'purple')]),
                    ]),

                Section::make('Promosi & Flash Sale')
                    ->schema([
                        TextInput::make('fake_price')
                            ->label('Harga Coret Promosi (Rp)')
                            ->numeric()
                            ->nullable()
                            ->prefix('Rp')
                            ->helperText('Akan dicoret merah. Diskon % dihitung dari Harga Guest vs Harga Coret ini.'),

                        Fieldset::make('Konfigurasi Flash Sale')
                            ->schema([
                                TextInput::make('flash_sale_price')
                                    ->label('Harga Flash Sale (Rp)')
                                    ->numeric()
                                    ->nullable()
                                    ->prefix('Rp'),

                                DateTimePicker::make('flash_sale_ends_at')
                                    ->label('Flash Sale Berakhir Pada')
                                    ->nullable(),

                                TextInput::make('flash_sale_stock')
                                    ->label('Stok Maksimal')
                                    ->numeric()
                                    ->nullable()
                                    ->minValue(1)
                                    ->helperText('Kosongkan = tidak terbatas. Contoh: 99'),

                                TextInput::make('flash_sale_purchased')
                                    ->label('Sudah Dibeli')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Diupdate otomatis saat checkout flash sale.'),
                            ])->columns(2),
                    ])->collapsible(),
            ]);
    }
}
