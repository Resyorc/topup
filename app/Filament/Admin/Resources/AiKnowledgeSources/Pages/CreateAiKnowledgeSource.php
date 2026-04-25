<?php

namespace App\Filament\Admin\Resources\AiKnowledgeSources\Pages;

use App\Filament\Admin\Resources\AiKnowledgeSources\AiKnowledgeSourceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAiKnowledgeSource extends CreateRecord
{
    protected static string $resource = AiKnowledgeSourceResource::class;
}
