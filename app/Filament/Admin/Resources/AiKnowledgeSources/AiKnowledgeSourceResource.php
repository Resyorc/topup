<?php

namespace App\Filament\Admin\Resources\AiKnowledgeSources;

use App\Filament\Admin\Resources\AiKnowledgeSources\Pages\CreateAiKnowledgeSource;
use App\Filament\Admin\Resources\AiKnowledgeSources\Pages\EditAiKnowledgeSource;
use App\Filament\Admin\Resources\AiKnowledgeSources\Pages\ListAiKnowledgeSources;
use App\Filament\Admin\Resources\AiKnowledgeSources\Schemas\AiKnowledgeSourceForm;
use App\Filament\Admin\Resources\AiKnowledgeSources\Tables\AiKnowledgeSourcesTable;
use App\Models\AiKnowledgeSource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AiKnowledgeSourceResource extends Resource
{
    protected static ?string $model = AiKnowledgeSource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static UnitEnum|string|null $navigationGroup = 'AI Assistant';

    protected static ?string $navigationLabel = 'Knowledge Base';

    protected static ?int $navigationSort = 51;

    protected static ?string $recordTitleAttribute = 'title';

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['Super Admin', 'Staff']);
    }

    public static function form(Schema $schema): Schema
    {
        return AiKnowledgeSourceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AiKnowledgeSourcesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListAiKnowledgeSources::route('/'),
            'create' => CreateAiKnowledgeSource::route('/create'),
            'edit'   => EditAiKnowledgeSource::route('/{record}/edit'),
        ];
    }
}
