<?php

namespace App\Filament\Admin\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class LoyaltySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.admin.pages.loyalty-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static ?string $navigationLabel = 'Program Loyalitas';

    protected static ?string $title = 'Pengaturan Program Loyalitas';

    protected static ?int $navigationSort = 10;

    protected static UnitEnum|string|null $navigationGroup = 'Marketing & Konten';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('Super Admin');
    }

    public function mount(): void
    {
        $this->form->fill([
            'rate_percent' => (float) Setting::get('loyalty_rate_percent', config('services.loyalty.rate_percent', 1)),
            'min_amount'   => (int) Setting::get('loyalty_min_amount', config('services.loyalty.min_amount', 5000)),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Konfigurasi Reward')
                    ->description('Atur persentase cashback dan minimum transaksi untuk program loyalitas Krysta Coin.')
                    ->schema([
                        TextInput::make('rate_percent')
                            ->label('Persentase Reward (%)')
                            ->helperText('Contoh: isi 1 berarti transaksi Rp 10.000 mendapat 100 Krysta Coin.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.1)
                            ->suffix('%')
                            ->required(),

                        TextInput::make('min_amount')
                            ->label('Minimum Transaksi (Rp)')
                            ->helperText('Transaksi di bawah nominal ini tidak mendapat reward.')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Rp')
                            ->required(),
                    ])
                    ->columns(2),

            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('loyalty_rate_percent', $data['rate_percent']);
        Setting::set('loyalty_min_amount', $data['min_amount']);

        Notification::make()
            ->title('Pengaturan tersimpan')
            ->body('Konfigurasi program loyalitas berhasil diperbarui.')
            ->success()
            ->send();
    }
}
