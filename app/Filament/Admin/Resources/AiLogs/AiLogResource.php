<?php

namespace App\Filament\Admin\Resources\AiLogs;

use App\Filament\Admin\Resources\AiLogs\Pages\ListAiLogs;
use App\Filament\Admin\Resources\AiLogs\Pages\ViewAiLog;
use App\Filament\Admin\Resources\AiLogs\Tables\AiLogsTable;
use App\Models\AiLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AiLogResource extends Resource
{
    protected static ?string $model = AiLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static UnitEnum|string|null $navigationGroup = 'AI Assistant';

    protected static ?string $navigationLabel = 'Audit Log AI';

    protected static ?int $navigationSort = 50;

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['Super Admin', 'Staff']);
    }

    public static function table(Table $table): Table
    {
        return AiLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiLogs::route('/'),
            'view'  => ViewAiLog::route('/{record}'),
        ];
    }
}
