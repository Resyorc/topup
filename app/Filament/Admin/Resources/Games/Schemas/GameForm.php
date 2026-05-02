<?php

namespace App\Filament\Admin\Resources\Games\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class GameForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Tabs')
                ->tabs([
                    Tab::make('Informasi Utama')
                        ->icon('heroicon-o-information-circle')
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
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->disk('public')
                                ->directory('games')
                                ->fetchFileInformation(false)
                                ->imagePreviewHeight('120')
                                ->maxParallelUploads(1)
                                ->maxSize(2048)
                                ->orientImagesFromExif(false),
                            FileUpload::make('thumbnail')
                                ->image()
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->disk('public')
                                ->directory('games/thumbnails')
                                ->fetchFileInformation(false)
                                ->imagePreviewHeight('120')
                                ->maxParallelUploads(1)
                                ->maxSize(2048)
                                ->orientImagesFromExif(false),
                            Toggle::make('is_active')
                                ->label('Status Aktif')
                                ->required(),
                        ])
                        ->columns(2),

                    Tab::make('Kategori & Region')
                        ->icon('heroicon-o-rectangle-group')
                        ->schema([
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
                        ]),

                    Tab::make('Tampilan & Input')
                        ->icon('heroicon-o-paint-brush')
                        ->schema([
                            Section::make('Icon Produk')
                                ->description('Atur icon produk berdasarkan grup atau rentang jumlah. Rules diproses berurutan — rule pertama yang cocok yang dipakai. Kosongkan jika ingin pakai icon default game.')
                                ->schema([
                                    Repeater::make('icon_rules')
                                        ->label('')
                                        ->schema([
                                            Select::make('type')
                                                ->label('Tipe Rule')
                                                ->options([
                                                    'group' => 'Nama Grup/Kategori',
                                                    'range' => 'Rentang Jumlah',
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
                                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                                ->disk('public')
                                                ->directory('icons/products')
                                                ->fetchFileInformation(false)
                                                ->imagePreviewHeight('64')
                                                ->itemPanelAspectRatio('1:1')
                                                ->maxParallelUploads(1)
                                                ->maxSize(512)
                                                ->orientImagesFromExif(false)
                                                ->panelLayout('compact')
                                                ->automaticallyResizeImagesMode('contain')
                                                ->automaticallyResizeImagesToWidth('256')
                                                ->automaticallyResizeImagesToHeight('256'),
                                        ])
                                        ->addActionLabel('Tambah Rule Icon')
                                        ->reorderable()
                                        ->collapsible()
                                        ->collapsed()
                                        ->itemLabel(function (array $state): ?string {
                                            return match ($state['type'] ?? null) {
                                                'group' => filled($state['match_group'] ?? null) ? 'Grup: '.$state['match_group'] : 'Rule grup',
                                                'range' => 'Range: '.($state['amount_min'] ?? '0').' - '.($state['amount_max'] ?? 'tanpa batas'),
                                                'keyword' => filled($state['match_keyword'] ?? null) ? 'Keyword: '.$state['match_keyword'] : 'Rule keyword',
                                                default => null,
                                            };
                                        })
                                        ->defaultItems(0),
                                ])
                                ->collapsible(),

                            Section::make('Field Input Transaksi')
                                ->description('Buat custom field untuk input ID (misal: User ID, Server ID).')
                                ->schema([
                                    Repeater::make('input_fields')
                                        ->label('')
                                        ->schema([
                                            TextInput::make('name')
                                                ->label('Field Name (key)')
                                                ->required()
                                                ->placeholder('contoh: user_id'),
                                            TextInput::make('label')
                                                ->label('Label')
                                                ->required()
                                                ->placeholder('contoh: User ID'),
                                            Select::make('type')
                                                ->label('Tipe Input')
                                                ->options([
                                                    'text' => 'Text',
                                                    'number' => 'Number',
                                                ])
                                                ->default('text')
                                                ->required(),
                                            TextInput::make('placeholder')
                                                ->label('Placeholder')
                                                ->nullable()
                                                ->placeholder('contoh: Masukkan User ID Anda'),
                                            Toggle::make('is_required')
                                                ->label('Wajib Diisi')
                                                ->default(true),
                                            Toggle::make('half_width')
                                                ->label('Setengah Lebar (2 kolom)')
                                                ->helperText('Aktifkan agar field tampil berdampingan dengan field lain.')
                                                ->default(false),
                                        ])
                                        ->columns(2)
                                        ->addActionLabel('Tambah Field Input')
                                        ->reorderable()
                                        ->collapsible()
                                        ->defaultItems(0),
                                ])
                                ->collapsible(),
                        ]),

                    Tab::make('Panduan Pembelian')
                        ->icon('heroicon-o-book-open')
                        ->schema([
                            FileUpload::make('guide_image')
                                ->label('Gambar Panduan')
                                ->image()
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->disk('public')
                                ->directory('games/guides')
                                ->fetchFileInformation(false)
                                ->imagePreviewHeight('120')
                                ->maxParallelUploads(1)
                                ->maxSize(2048)
                                ->orientImagesFromExif(false),
                            RichEditor::make('guide_content')
                                ->label('Instruksi Panduan')
                                ->toolbarButtons([
                                    'bold',
                                    'italic',
                                    'link',
                                    'bulletList',
                                    'orderedList',
                                    'redo',
                                    'undo',
                                ])
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
