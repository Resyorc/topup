<?php

namespace App\Filament\Admin\Resources\AiActions;

use App\Filament\Admin\Resources\AiActions\Pages\ListAiActions;
use App\Filament\Admin\Resources\AiActions\Pages\ViewAiAction;
use App\Filament\Admin\Resources\AiActions\Tables\AiActionsTable;
use App\Models\AiAction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AiActionResource extends Resource
{
    protected static ?string $model = AiAction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static UnitEnum|string|null $navigationGroup = 'AI Assistant';

    protected static ?string $navigationLabel = 'Approval Center';

    protected static ?int $navigationSort = 49;

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['Super Admin', 'Staff']);
    }

    public static function getBadge(): ?string
    {
        $count = AiAction::where('status', 'pending')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return AiActionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiActions::route('/'),
            'view'  => ViewAiAction::route('/{record}'),
        ];
    }
}
