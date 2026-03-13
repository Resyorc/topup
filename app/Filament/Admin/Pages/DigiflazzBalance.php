<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Clusters\MonitorCluster;
use App\Services\DigiflazzService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Throwable;

class DigiflazzBalance extends Page
{
    protected string $view = 'filament.admin.pages.digiflazz-balance';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Saldo Digiflazz';

    protected static ?string $title = 'Saldo Digiflazz';

    protected static ?int $navigationSort = 5;

    protected static ?string $cluster = MonitorCluster::class;

    public ?float $balance = null;

    public bool $loading = false;

    public ?string $errorMessage = null;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('Super Admin');
    }

    public function mount(): void
    {
        $this->fetchBalance();
    }

    public function fetchBalance(): void
    {
        $this->errorMessage = null;

        try {
            $data = app(DigiflazzService::class)->checkBalance();
            $this->balance = $data['deposit'] ?? null;
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();
            $this->balance = null;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Saldo')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->fetchBalance();

                    if ($this->errorMessage) {
                        Notification::make()
                            ->title('Gagal mengambil saldo')
                            ->body($this->errorMessage)
                            ->danger()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Saldo berhasil diperbarui')
                            ->success()
                            ->send();
                    }
                }),
        ];
    }
}
