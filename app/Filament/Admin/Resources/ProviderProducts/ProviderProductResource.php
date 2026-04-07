<?php

namespace App\Filament\Admin\Resources\ProviderProducts;

use App\Filament\Admin\Clusters\CatalogCluster;
use App\Filament\Admin\Resources\ProviderProducts\Pages\ListProviderProducts;
use App\Filament\Admin\Resources\ProviderProducts\Tables\ProviderProductsTable;
use App\Models\ProviderProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProviderProductResource extends Resource
{
    protected static ?string $model = ProviderProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'Provider SKU';

    protected static ?string $modelLabel = 'SKU';

    protected static ?string $pluralModelLabel = 'Provider SKU';

    protected static ?int $navigationSort = 10;

    protected static ?string $cluster = CatalogCluster::class;

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['Super Admin', 'Staff']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return ProviderProductsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProviderProducts::route('/'),
        ];
    }
}
