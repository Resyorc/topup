<?php

namespace App\Filament\Admin\Resources\FailedLoginLogs\Pages;

use App\Filament\Admin\Resources\FailedLoginLogs\FailedLoginLogResource;
use Filament\Resources\Pages\ListRecords;

class ListFailedLoginLogs extends ListRecords
{
    protected static string $resource = FailedLoginLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
