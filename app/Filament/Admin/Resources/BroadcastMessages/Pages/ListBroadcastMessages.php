<?php

namespace App\Filament\Admin\Resources\BroadcastMessages\Pages;

use App\Filament\Admin\Resources\BroadcastMessages\BroadcastMessageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBroadcastMessages extends ListRecords
{
    protected static string $resource = BroadcastMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
