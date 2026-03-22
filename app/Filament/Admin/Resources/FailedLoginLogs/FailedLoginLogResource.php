<?php

namespace App\Filament\Admin\Resources\FailedLoginLogs;

use App\Filament\Admin\Clusters\MonitorCluster;
use App\Filament\Admin\Resources\FailedLoginLogs\Pages\ListFailedLoginLogs;
use App\Filament\Admin\Resources\FailedLoginLogs\Tables\FailedLoginLogsTable;
use App\Models\FailedLoginLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class FailedLoginLogResource extends Resource
{
    protected static ?string $model = FailedLoginLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static ?string $navigationLabel = 'Failed Login';

    protected static ?string $title = 'Failed Login';

    protected static ?string $cluster = MonitorCluster::class;

    protected static ?int $navigationSort = 11;

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
        return FailedLoginLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFailedLoginLogs::route('/'),
        ];
    }
}
