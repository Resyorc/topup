<?php

namespace App\Filament\Admin\Resources\ProviderProducts\Tables;

use App\Models\Game;
use App\Models\Product;
use App\Models\ProviderProduct;
use App\Services\ProviderCatalogImportService;
use App\Services\TopupPriceService;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class ProviderProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('provider_sku')
                    ->label('Provider SKU')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('product_name')
                    ->label('Nama Provider')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('brand')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('product.name')
                    ->label('Mapped Product')
                    ->searchable()
                    ->placeholder('Belum di-associate')
                    ->wrap(),

                TextColumn::make('product.game.name')
                    ->label('Game')
                    ->badge()
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Harga Modal')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                TextColumn::make('priority')
                    ->label('Prioritas')
                    ->badge()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('seller_name')
                    ->label('Seller')
                    ->searchable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('product.game_id')
                    ->label('Game')
                    ->relationship('product.game', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label('Status Provider')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),

                TernaryFilter::make('product_id')
                    ->label('Association')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('product_id'),
                        false: fn ($query) => $query->whereNull('product_id'),
                        blank: fn ($query) => $query,
                    )
                    ->trueLabel('Sudah terhubung')
                    ->falseLabel('Belum terhubung'),

                SelectFilter::make('brand')
                    ->label('Brand Provider')
                    ->options(fn (): array => ProviderProduct::query()
                        ->whereNotNull('brand')
                        ->select('brand')
                        ->distinct()
                        ->orderBy('brand')
                        ->pluck('brand', 'brand')
                        ->all())
                    ->searchable(),
            ])
            ->recordActions([])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('create_products_from_provider')
                        ->label('Buat Produk')
                        ->icon('heroicon-o-plus-circle')
                        ->color('primary')
                        ->form([
                            Select::make('game_id')
                                ->label('Game Tujuan')
                                ->options(fn (): array => Game::query()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->required(),
                            TextInput::make('margin_flat')
                                ->label('Margin per Produk')
                                ->helperText('Dipakai untuk semua produk baru yang dibuat dari pilihan SKU ini.')
                                ->numeric()
                                ->minValue(0)
                                ->default(1500)
                                ->prefix('Rp')
                                ->required(),
                            TextInput::make('group')
                                ->label('Grup Produk')
                                ->placeholder('Contoh: Diamonds, Membership, Weekly Pass')
                                ->maxLength(100),
                            TextInput::make('priority')
                                ->label('Prioritas Seller')
                                ->helperText('Angka lebih kecil dipilih lebih dulu. Jika sama, harga modal termurah dipakai.')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(999)
                                ->default(100)
                                ->required(),
                            Toggle::make('merge_same_name')
                                ->label('Gabungkan SKU dengan nama produk yang sama')
                                ->helperText('Aktifkan agar beberapa seller untuk nominal yang sama menjadi alternatif pada 1 produk.')
                                ->default(true),
                            Toggle::make('skip_mapped')
                                ->label('Lewati SKU yang sudah terhubung')
                                ->default(true),
                        ])
                        ->action(function (array $data, Collection $records): void {
                            $result = app(ProviderCatalogImportService::class)->import($records, $data);

                            Notification::make()
                                ->title('Import produk selesai')
                                ->body("{$result['created']} produk dibuat, {$result['reused']} produk existing dipakai, {$result['mapped']} SKU dipetakan, {$result['skipped']} SKU dilewati.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => auth()->user()->hasAnyRole(['Super Admin', 'Staff'])),

                    BulkAction::make('map_to_product')
                        ->label('Map ke Produk')
                        ->icon('heroicon-o-link')
                        ->color('success')
                        ->form([
                            Select::make('product_id')
                                ->label('Produk Nuvelo')
                                ->searchable()
                                ->options(fn (): array => Product::query()
                                    ->with('game')
                                    ->orderBy('name')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn (Product $product) => [
                                        $product->id => self::productOptionLabel($product),
                                    ])
                                    ->all())
                                ->getSearchResultsUsing(fn (string $search): array => Product::query()
                                    ->with('game')
                                    ->where(function ($query) use ($search) {
                                        $query->where('name', 'like', "%{$search}%")
                                            ->orWhereHas('game', fn ($gameQuery) => $gameQuery->where('name', 'like', "%{$search}%"));
                                    })
                                    ->orderBy('name')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn (Product $product) => [
                                        $product->id => self::productOptionLabel($product),
                                    ])
                                    ->all())
                                ->getOptionLabelUsing(fn ($value): ?string => ($product = Product::with('game')->find($value))
                                    ? self::productOptionLabel($product)
                                    : null)
                                ->required(),
                            TextInput::make('priority')
                                ->label('Prioritas Seller')
                                ->helperText('Angka lebih kecil dipilih lebih dulu. Jika sama, harga modal termurah dipakai.')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(999)
                                ->default(100)
                                ->required(),
                        ])
                        ->action(function (array $data, Collection $records): void {
                            $productId = (int) $data['product_id'];
                            $priority = (int) $data['priority'];

                            ProviderProduct::whereKey($records->pluck('id'))->update([
                                'product_id' => $productId,
                                'priority' => $priority,
                            ]);

                            if ($product = Product::find($productId)) {
                                app(TopupPriceService::class)->refreshProductPricing($product);
                            }

                            Notification::make()
                                ->title('Provider SKU berhasil dipetakan')
                                ->body($records->count().' SKU menjadi alternatif untuk produk yang sama.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => auth()->user()->hasAnyRole(['Super Admin', 'Staff'])),

                    BulkAction::make('unmap_from_product')
                        ->label('Lepas Mapping')
                        ->icon('heroicon-o-link-slash')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $productIds = $records->pluck('product_id')->filter()->unique()->values();

                            ProviderProduct::whereKey($records->pluck('id'))->update([
                                'product_id' => null,
                            ]);

                            Product::whereKey($productIds)->get()->each(
                                fn (Product $product) => app(TopupPriceService::class)->refreshProductPricing($product)
                            );

                            Notification::make()
                                ->title('Mapping provider SKU dilepas')
                                ->body($records->count().' SKU tidak lagi terhubung ke produk Nuvelo.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => auth()->user()->hasAnyRole(['Super Admin', 'Staff'])),
                ]),
            ]);
    }

    private static function productOptionLabel(Product $product): string
    {
        $gameName = $product->game?->name ?? 'Tanpa Game';

        return "{$gameName} - {$product->name}";
    }
}
