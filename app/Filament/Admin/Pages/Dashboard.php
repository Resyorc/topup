<?php

namespace App\Filament\Admin\Pages;

class Dashboard extends \Filament\Pages\Dashboard
{
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['Super Admin', 'Staff', 'CS']);
    }

    public function getColumns(): int | array
    {
        return 2;
    }
}
