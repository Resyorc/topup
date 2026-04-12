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
            'rate_percent'             => (float) Setting::get('loyalty_rate_percent', config('services.loyalty.rate_percent', 1)),
            'min_amount'               => (int) Setting::get('loyalty_min_amount', config('services.loyalty.min_amount', 5000)),
            'tier_multiplier_bronze'   => (float) Setting::get('tier_multiplier_bronze', 1.0),
            'tier_multiplier_silver'   => (float) Setting::get('tier_multiplier_silver', 1.25),
            'tier_multiplier_gold'     => (float) Setting::get('tier_multiplier_gold', 1.5),
            'tier_multiplier_platinum' => (float) Setting::get('tier_multiplier_platinum', 2.0),

            // Harga upgrade membership
            'membership_price_silver'   => (int) Setting::get('membership_price_silver', 25_000),
            'membership_price_gold'     => (int) Setting::get('membership_price_gold', 75_000),
            'membership_price_platinum' => (int) Setting::get('membership_price_platinum', 150_000),
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

                Section::make('Multiplier Coins per Tier')
                    ->description('Coins yang didapat user akan dikalikan nilai ini sesuai tier mereka.')
                    ->schema([
                        TextInput::make('tier_multiplier_bronze')
                            ->label('🥉 Bronze')
                            ->numeric()->minValue(0.1)->step(0.05)->suffix('x')->required(),
                        TextInput::make('tier_multiplier_silver')
                            ->label('🥈 Silver')
                            ->numeric()->minValue(0.1)->step(0.05)->suffix('x')->required(),
                        TextInput::make('tier_multiplier_gold')
                            ->label('🥇 Gold')
                            ->numeric()->minValue(0.1)->step(0.05)->suffix('x')->required(),
                        TextInput::make('tier_multiplier_platinum')
                            ->label('💎 Platinum')
                            ->numeric()->minValue(0.1)->step(0.05)->suffix('x')->required(),
                    ])
                    ->columns(4),

                Section::make('Harga Upgrade Membership (One-Time)')
                    ->description('User membayar sekali untuk langsung naik ke tier yang dipilih secara permanen.')
                    ->schema([
                        TextInput::make('membership_price_silver')
                            ->label('🥈 Upgrade ke Silver')
                            ->numeric()->minValue(0)->prefix('Rp')->required(),
                        TextInput::make('membership_price_gold')
                            ->label('🥇 Upgrade ke Gold')
                            ->numeric()->minValue(0)->prefix('Rp')->required(),
                        TextInput::make('membership_price_platinum')
                            ->label('💎 Upgrade ke Platinum')
                            ->numeric()->minValue(0)->prefix('Rp')->required(),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('loyalty_rate_percent', $data['rate_percent']);
        Setting::set('loyalty_min_amount', $data['min_amount']);
        Setting::set('tier_multiplier_bronze', $data['tier_multiplier_bronze']);
        Setting::set('tier_multiplier_silver', $data['tier_multiplier_silver']);
        Setting::set('tier_multiplier_gold', $data['tier_multiplier_gold']);
        Setting::set('tier_multiplier_platinum', $data['tier_multiplier_platinum']);
        Setting::set('membership_price_silver', $data['membership_price_silver']);
        Setting::set('membership_price_gold', $data['membership_price_gold']);
        Setting::set('membership_price_platinum', $data['membership_price_platinum']);

        Notification::make()
            ->title('Pengaturan tersimpan')
            ->body('Konfigurasi program loyalitas berhasil diperbarui.')
            ->success()
            ->send();
    }
}
