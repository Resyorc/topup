<?php

namespace App\Filament\Admin\Resources\ErrorLogs;

use App\Filament\Admin\Clusters\MonitorCluster;
use App\Filament\Admin\Resources\ErrorLogs\Pages\ListErrorLogs;
use App\Filament\Admin\Resources\ErrorLogs\Tables\ErrorLogsTable;
use App\Models\ErrorLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class ErrorLogResource extends Resource
{
    protected static ?string $model = ErrorLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBugAnt;

    protected static ?string $navigationLabel = 'Error Log';

    protected static ?string $title = 'Error Log';

    protected static ?string $cluster = MonitorCluster::class;

    protected static ?int $navigationSort = 12;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('Super Admin');
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return ErrorLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListErrorLogs::route('/'),
        ];
    }
}
