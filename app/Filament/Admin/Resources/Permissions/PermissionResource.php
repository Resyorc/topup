<?php

namespace App\Filament\Admin\Resources\Permissions;

use App\Filament\Admin\Clusters\Settings\SettingsCluster;
use App\Filament\Admin\Resources\Permissions\Pages\CreatePermission;
use App\Filament\Admin\Resources\Permissions\Pages\EditPermission;
use App\Filament\Admin\Resources\Permissions\Pages\ListPermissions;
use App\Filament\Admin\Resources\Permissions\Schemas\PermissionForm;
use App\Filament\Admin\Resources\Permissions\Tables\PermissionsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'Permissions';

    protected static ?string $pluralLabel = 'Permissions';

    protected static ?int $navigationSort = 3;

    protected static ?string $cluster = SettingsCluster::class;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('Super Admin');
    }

    public static function form(Schema $schema): Schema
    {
        return PermissionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PermissionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermissions::route('/'),
            'create' => CreatePermission::route('/create'),
            'edit' => EditPermission::route('/{record}/edit'),
        ];
    }
}
