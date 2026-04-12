<?php

namespace App\Filament\Admin\Pages;

use App\Models\Setting;
use App\Services\DigiflazzService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Throwable;
use UnitEnum;

class DigiflazzBalance extends Page
{
    protected string $view = 'filament.admin.pages.digiflazz-balance';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Saldo Digiflazz';

    protected static ?string $title = 'Saldo Digiflazz';

    protected static ?int $navigationSort = 5;

    protected static UnitEnum|string|null $navigationGroup = 'Operasional';

    public ?float $balance = null;

    public bool $loading = false;

    public ?string $errorMessage = null;

    public float $threshold = 100000;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('Super Admin');
    }

    public function mount(): void
    {
        $this->threshold = (float) Setting::get('digiflazz_low_balance_threshold', 100000);
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
            Action::make('setThreshold')
                ->label('Set Threshold Alert')
                ->icon('heroicon-o-bell-alert')
                ->color('warning')
                ->form([
                    TextInput::make('threshold')
                        ->label('Batas minimum saldo (Rp)')
                        ->numeric()
                        ->minValue(0)
                        ->default(fn () => $this->threshold)
                        ->helperText('Alert akan dikirim ke email admin jika saldo di bawah angka ini.')
                        ->required(),
                ])
                ->action(function (array $data) {
                    Setting::set('digiflazz_low_balance_threshold', $data['threshold']);
                    $this->threshold = (float) $data['threshold'];

                    Notification::make()
                        ->title('Threshold berhasil disimpan')
                        ->success()
                        ->send();
                }),

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
