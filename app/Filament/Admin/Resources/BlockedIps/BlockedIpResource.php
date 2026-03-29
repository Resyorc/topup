<?php

namespace App\Filament\Admin\Resources\BlockedIps;

use App\Filament\Admin\Clusters\MonitorCluster;
use App\Filament\Admin\Resources\BlockedIps\Pages\ListBlockedIps;
use App\Filament\Admin\Resources\BlockedIps\Tables\BlockedIpsTable;
use App\Models\BlockedIp;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class BlockedIpResource extends Resource
{
    protected static ?string $model = BlockedIp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    protected static ?string $navigationLabel = 'IP Diblokir';

    protected static ?string $title = 'IP Diblokir';

    protected static ?string $cluster = MonitorCluster::class;

    protected static ?int $navigationSort = 12;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['Super Admin', 'Staff']);
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return BlockedIpsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlockedIps::route('/'),
        ];
    }
}
