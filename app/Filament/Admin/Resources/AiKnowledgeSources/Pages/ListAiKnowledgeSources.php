<?php

namespace App\Filament\Admin\Resources\AiKnowledgeSources\Pages;

use App\Filament\Admin\Resources\AiKnowledgeSources\AiKnowledgeSourceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAiKnowledgeSources extends ListRecords
{
    protected static string $resource = AiKnowledgeSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
