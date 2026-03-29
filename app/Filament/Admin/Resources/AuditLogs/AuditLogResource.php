<?php

namespace App\Filament\Admin\Resources\AuditLogs;

use App\Filament\Admin\Clusters\MonitorCluster;
use App\Filament\Admin\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Admin\Resources\AuditLogs\Tables\AuditLogsTable;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Audit Log';

    protected static ?string $pluralLabel = 'Audit Logs';

    protected static ?int $navigationSort = 10;

    protected static ?string $cluster = MonitorCluster::class;

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['Super Admin', 'Staff']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return AuditLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }
}
