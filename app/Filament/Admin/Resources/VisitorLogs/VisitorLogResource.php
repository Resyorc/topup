<?php

namespace App\Filament\Admin\Resources\VisitorLogs;

use App\Filament\Admin\Clusters\MonitorCluster;
use App\Filament\Admin\Resources\VisitorLogs\Pages\ListVisitorLogs;
use App\Filament\Admin\Resources\VisitorLogs\Tables\VisitorLogsTable;
use App\Models\VisitorLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class VisitorLogResource extends Resource
{
    protected static ?string $model = VisitorLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $navigationLabel = 'Visitor Log';

    protected static ?string $title = 'Visitor Log';

    protected static ?string $cluster = MonitorCluster::class;

    protected static ?int $navigationSort = 10;

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
        return VisitorLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVisitorLogs::route('/'),
        ];
    }
}
