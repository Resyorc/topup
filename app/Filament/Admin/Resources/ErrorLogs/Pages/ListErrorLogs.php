<?php

namespace App\Filament\Admin\Resources\ErrorLogs\Pages;

use App\Filament\Admin\Resources\ErrorLogs\ErrorLogResource;
use App\Models\ErrorLog;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListErrorLogs extends ListRecords
{
    protected static string $resource = ErrorLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clear_all')
                ->label('Hapus Semua')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hapus semua error log?')
                ->modalDescription('Tindakan ini tidak bisa dibatalkan.')
                ->action(fn () => ErrorLog::truncate()),
        ];
    }
}
