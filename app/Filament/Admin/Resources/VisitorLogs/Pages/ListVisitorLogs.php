<?php

namespace App\Filament\Admin\Resources\VisitorLogs\Pages;

use App\Filament\Admin\Resources\VisitorLogs\VisitorLogResource;
use Filament\Resources\Pages\ListRecords;

class ListVisitorLogs extends ListRecords
{
    protected static string $resource = VisitorLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
