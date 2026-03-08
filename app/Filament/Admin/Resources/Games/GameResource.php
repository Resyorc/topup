<?php

namespace App\Filament\Admin\Resources\Games;

use App\Filament\Admin\Resources\Games\Pages\CreateGame;
use App\Filament\Admin\Resources\Games\Pages\EditGame;
use App\Filament\Admin\Resources\Games\Pages\ListGames;
use App\Filament\Admin\Resources\Games\Schemas\GameForm;
use App\Filament\Admin\Resources\Games\Tables\GamesTable;
use App\Filament\Admin\Clusters\CatalogCluster;
use App\Models\Game;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GameResource extends Resource
{
    protected static ?string $model = Game::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $cluster = CatalogCluster::class;

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['Super Admin', 'Admin Operasional']);
    }

    public static function form(Schema $schema): Schema
    {
        return GameForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GamesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGames::route('/'),
            'create' => CreateGame::route('/create'),
            'edit' => EditGame::route('/{record}/edit'),
        ];
    }
}
