<?php

namespace App\Filament\Admin\Resources\BlockedIps\Pages;

use App\Filament\Admin\Resources\BlockedIps\BlockedIpResource;
use App\Models\BlockedIp;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListBlockedIps extends ListRecords
{
    protected static string $resource = BlockedIpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('blockIp')
                ->label('Blokir IP')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->form([
                    TextInput::make('ip')
                        ->label('IP Address')
                        ->required()
                        ->placeholder('103.x.x.x'),

                    TextInput::make('reason')
                        ->label('Alasan')
                        ->required()
                        ->placeholder('Aktivitas mencurigakan'),

                    DateTimePicker::make('blocked_until')
                        ->label('Blokir sampai (kosongkan = permanen)')
                        ->nullable(),
                ])
                ->action(function (array $data) {
                    BlockedIp::block(
                        ip: $data['ip'],
                        reason: $data['reason'],
                        until: $data['blocked_until'] ? \Carbon\Carbon::parse($data['blocked_until']) : null,
                        auto: false,
                    );

                    Notification::make()
                        ->title('IP berhasil diblokir')
                        ->success()
                        ->send();
                }),
        ];
    }
}
