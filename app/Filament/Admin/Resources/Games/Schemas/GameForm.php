<?php

namespace App\Filament\Admin\Resources\Games\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class GameForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Game')
                ->schema([
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
                ])
                ->columns(2),

            Section::make('Kategori Produk')
                ->description('Buat tab/kategori untuk produk game ini. Rules diisi dengan kata yang ada di nama produk, pisahkan koma. Contoh: kategori "Weekly" → rules "weekly, wdp". Kosongkan jika tidak butuh tab.')
                ->schema([
                    Repeater::make('grouping_rules')
                        ->label('')
                        ->schema([
                            TextInput::make('group')
                                ->label('Nama Kategori / Tab')
                                ->required()
                                ->placeholder('contoh: Weekly, Diamond, Express'),
                            TextInput::make('keywords')
                                ->label('Rules (bagian nama produk, pisahkan koma)')
                                ->required()
                                ->placeholder('contoh: weekly, wdp, weekly diamond pass'),
                        ])
                        ->columns(2)
                        ->addActionLabel('Tambah Kategori')
                        ->reorderable()
                        ->collapsible()
                        ->defaultItems(0),
                ])
                ->collapsible(),

            Section::make('Region Produk')
                ->description('Mapping country code ke SKU prefix. Contoh: country "ID" → SKU prefix "mli" (untuk Mobile Legends Indonesia). Kosongkan jika produk tidak dibedakan per region.')
                ->schema([
                    Repeater::make('region_map')
                        ->label('')
                        ->schema([
                            TextInput::make('country')
                                ->label('Country Code')
                                ->required()
                                ->placeholder('contoh: ID, MY, PH'),
                            TextInput::make('sku_prefix')
                                ->label('SKU Prefix')
                                ->required()
                                ->placeholder('contoh: mli, mlm, mlp'),
                        ])
                        ->columns(2)
                        ->addActionLabel('Tambah Region')
                        ->reorderable(false)
                        ->collapsible()
                        ->defaultItems(0),
                ])
                ->collapsible(),

            Section::make('Icon Produk')
                ->description('Atur icon produk berdasarkan grup atau rentang jumlah. Rules diproses berurutan — rule pertama yang cocok yang dipakai. Kosongkan jika ingin pakai icon default game.')
                ->schema([
                    Repeater::make('icon_rules')
                        ->label('')
                        ->schema([
                            Select::make('type')
                                ->label('Tipe Rule')
                                ->options([
                                    'group'   => 'Nama Grup/Kategori',
                                    'range'   => 'Rentang Jumlah',
                                    'keyword' => 'Keyword Nama Produk',
                                ])
                                ->live(),
                            TextInput::make('match_group')
                                ->label('Nama Grup (misal: Diamond, Weekly Diamond Pass)')
                                ->hidden(fn (Get $get) => $get('type') !== 'group'),
                            TextInput::make('match_keyword')
                                ->label('Keyword (pisahkan koma, misal: first top up, first topup)')
                                ->hidden(fn (Get $get) => $get('type') !== 'keyword'),
                            TextInput::make('amount_min')
                                ->label('Jumlah Min')
                                ->numeric()
                                ->nullable()
                                ->hidden(fn (Get $get) => $get('type') !== 'range'),
                            TextInput::make('amount_max')
                                ->label('Jumlah Maks (kosong = tak terbatas)')
                                ->numeric()
                                ->nullable()
                                ->hidden(fn (Get $get) => $get('type') !== 'range'),
                            FileUpload::make('icon')
                                ->label('Icon')
                                ->image()
                                ->disk('public')
                                ->directory('icons/products'),
                        ])
                        ->addActionLabel('Tambah Rule Icon')
                        ->reorderable()
                        ->collapsible()
                        ->defaultItems(0),
                ])
                ->collapsible(),
        ]);
    }
}
