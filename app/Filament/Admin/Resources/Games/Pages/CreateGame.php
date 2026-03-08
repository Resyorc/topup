<?php

namespace App\Filament\Admin\Resources\Games\Pages;

use App\Filament\Admin\Resources\Games\GameResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGame extends CreateRecord
{
    protected static string $resource = GameResource::class;
}
