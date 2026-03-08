<?php

namespace App\Filament\Admin\Resources\BroadcastMessages\Pages;

use App\Filament\Admin\Resources\BroadcastMessages\BroadcastMessageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBroadcastMessage extends EditRecord
{
    protected static string $resource = BroadcastMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
