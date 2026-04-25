<?php

namespace App\Filament\Admin\Resources\ProviderProducts;

use App\Filament\Admin\Resources\ProviderProducts\Pages\ListProviderProducts;
use App\Filament\Admin\Resources\ProviderProducts\Tables\ProviderProductsTable;
use App\Models\ProviderProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProviderProductResource extends Resource
{
    protected static ?string $model = ProviderProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Provider Products';

    protected static ?string $title = 'Provider Products';

    protected static UnitEnum|string|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 28;

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['Super Admin', 'Staff', 'CS']);
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
