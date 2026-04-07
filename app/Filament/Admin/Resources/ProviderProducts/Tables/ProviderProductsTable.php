<?php

namespace App\Filament\Admin\Resources\ProviderProducts\Tables;

use App\Models\Product;
use App\Models\ProviderProduct;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ProviderProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('provider_name')
                    ->label('Provider')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                TextColumn::make('provider_sku')
                    ->label('SKU Code')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('brand')
                    ->label('Brand')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('product_name')
                    ->label('Nama Produk (Provider)')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('seller_name')
                    ->label('Seller')
                    ->searchable()
                    ->badge()
                    ->color('warning'),

                TextColumn::make('price')
                    ->label('Harga Modal')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                TextColumn::make('product.name')
                    ->label('Produk Nuvelo')
                    ->placeholder('— belum dipetakan —')
                    ->color(fn ($record) => $record->product_id ? 'success' : 'gray'),
            ])
            ->defaultSort('brand')
            ->filters([
                SelectFilter::make('provider_name')
                    ->label('Provider')
                    ->options([
                        'digiflazz' => 'Digiflazz',
                        // others later
                    ]),

                SelectFilter::make('brand')
                    ->label('Brand')
                    ->options(fn () => ProviderProduct::whereNotNull('brand')
                        ->distinct()
                        ->orderBy('brand')
                        ->pluck('brand', 'brand')
                    )
                    ->searchable(),

                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),

                TernaryFilter::make('mapped')
                    ->label('Pemetaan Produk')
                    ->trueLabel('Sudah dipetakan')
                    ->falseLabel('Belum dipetakan')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('product_id'),
                        false: fn ($query) => $query->whereNull('product_id'),
                    ),
            ])
            ->bulkActions([
                \Filament\Actions\BulkAction::make('importAsNewProducts')
                    ->label('Impor sbg Produk Baru')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->visible(fn () => auth()->user()->hasAnyRole(['Super Admin']))
                    ->requiresConfirmation()
                    ->form([
                        Select::make('game_id')
                            ->label('Ke Game')
                            ->options(\App\Models\Game::pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->helperText('Semua SKU yang dipilih akan dibuatkan produknya di game ini.'),
                    ])
                    ->action(function (Collection $records, array $data) {
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record->product_id) continue;

                            $product = Product::create([
                                'game_id' => $data['game_id'],
                                'name' => $record->product_name,
                                'price_cost' => $record->price,
                                'is_available' => $record->is_active,
                            ]);
                            
                            $record->update(['product_id' => $product->id]);
                            $count++;
                        }
                        
                        Notification::make()
                            ->title("Berhasil mengimpor {$count} produk")
                            ->success()
                            ->send();
                    })
            ])
            ->recordActions([
                \Filament\Actions\Action::make('assignToProduct')
                    ->label(fn ($record) => $record->product_id ? 'Ganti Produk' : 'Petakan ke Produk')
                    ->icon('heroicon-o-link')
                    ->color(fn ($record) => $record->product_id ? 'gray' : 'primary')
                    ->visible(fn () => auth()->user()->hasAnyRole(['Super Admin', 'Staff']))
                    ->form([
                        Select::make('product_id')
                            ->label('Pilih Produk Nuvelo')
                            ->options(fn () => Product::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->placeholder('— Lepas pemetaan —')
                            ->helperText('Pilih produk pengguna akhir untuk SKU ini. Karena 1 Produk kini bisa punya banyak SKU (Multi-Seller), harga produk otomatis dihitung dari Seller termurah yg aktif saat Sinkronisasi berjalan.'),
                    ])
                    ->fillForm(fn ($record) => ['product_id' => $record->product_id])
                    ->action(function ($record, array $data) {
                        $newProductId = $data['product_id'] ?: null;
                        $record->update(['product_id' => $newProductId]);

                        Notification::make()
                            ->title($newProductId ? 'Pemetaan Multi-Seller ditambah/diubah!' : 'Pemetaan produk dilepas')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
