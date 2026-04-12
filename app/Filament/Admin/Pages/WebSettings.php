<?php

namespace App\Filament\Admin\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use UnitEnum;

class WebSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.admin.pages.web-settings';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'Konfigurasi Web';

    protected static ?string $title = 'Konfigurasi Sistem & Web';

    protected static ?int $navigationSort = 11;

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('Super Admin');
    }

    public function mount(): void
    {
        $this->form->fill([
            // Tab 1
            'web_logo'             => Setting::get('web_logo', null),
            'web_favicon'          => Setting::get('web_favicon', null),

            // Tab 2
            'seo_title'            => Setting::get('seo_title', 'Nuvelo - Top Up Games Cepat & Murah'),
            'seo_description'      => Setting::get('seo_description', 'Layanan top up game tercepat, termurah dan terpercaya.'),
            'seo_keywords'         => Setting::get('seo_keywords', 'topup game, diamond ml, uc pubg'),
            'seo_og_image'         => Setting::get('seo_og_image', null),
            'sitemap_url'          => Setting::get('sitemap_url', url('/sitemap.xml')),

            // Tab 3
            'wa_bubble_enabled'    => (bool) Setting::get('wa_bubble_enabled', false),
            'wa_bubble_number'     => Setting::get('wa_bubble_number', ''),
            'wa_bubble_message'    => Setting::get('wa_bubble_message', 'Halo CS Nuvelo, saya butuh bantuan.'),
            'footer_links'         => json_decode(Setting::get('footer_links', '[]'), true),
            'sosmed_instagram'     => Setting::get('sosmed_instagram', ''),
            'sosmed_tiktok'        => Setting::get('sosmed_tiktok', ''),

            // Tab 4 — Global Pricing
            'pricing_pct_guest'    => (float) Setting::get('pricing_pct_guest',    4.0),
            'pricing_pct_bronze'   => (float) Setting::get('pricing_pct_bronze',   3.0),
            'pricing_pct_silver'   => (float) Setting::get('pricing_pct_silver',   2.0),
            'pricing_pct_gold'     => (float) Setting::get('pricing_pct_gold',     1.0),
            'pricing_pct_platinum' => (float) Setting::get('pricing_pct_platinum', 0.5),

            // Tab 5
            'enable_turnstile'     => (bool) Setting::get('enable_turnstile', false),
            'turnstile_site_key'   => Setting::get('turnstile_site_key', ''),
            'turnstile_secret_key' => Setting::get('turnstile_secret_key', ''),
            'otp_provider'         => Setting::get('otp_provider', 'fonnte'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Pengaturan')
                    ->tabs([
                        Tabs\Tab::make('Umum & Tampilan')
                            ->icon('heroicon-o-paint-brush')
                            ->schema([
                                FileUpload::make('web_logo')
                                    ->label('Logo Website')
                                    ->image()
                                    ->directory('settings')
                                    ->disk('public'),
                                FileUpload::make('web_favicon')
                                    ->label('Favicon')
                                    ->image()
                                    ->directory('settings')
                                    ->disk('public'),
                            ])->columns(2),

                        Tabs\Tab::make('SEO & Meta')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                TextInput::make('seo_title')
                                    ->label('Judul SEO (Gelar Tab)')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Textarea::make('seo_description')
                                    ->label('Meta Deskripsi')
                                    ->rows(3)
                                    ->columnSpanFull(),
                                TextInput::make('seo_keywords')
                                    ->label('Kata Kunci (Keywords)')
                                    ->helperText('Pisahkan dengan koma. Contoh: topup, mlbb, murah')
                                    ->columnSpanFull(),
                                FileUpload::make('seo_og_image')
                                    ->label('Gambar OpenGraph (Preview Social Media)')
                                    ->image()
                                    ->directory('settings')
                                    ->disk('public'),
                                TextInput::make('sitemap_url')
                                    ->label('URL Sitemap')
                                    ->url()
                                    ->columnSpan(2),
                            ])->columns(2),

                        Tabs\Tab::make('Widget & Footer')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Toggle::make('wa_bubble_enabled')
                                    ->label('Aktifkan Widget Chat WA')
                                    ->helperText('Menampilkan tombol WhatsApp melayang di pojok kanan bawah layar pengunjung.')
                                    ->columnSpanFull(),
                                TextInput::make('wa_bubble_number')
                                    ->label('Nomor WhatsApp CS')
                                    ->helperText('Gunakan kode negara (tanpa +), misal: 628123456789')
                                    ->numeric(),
                                Textarea::make('wa_bubble_message')
                                    ->label('Pesan Awal Otomatis')
                                    ->rows(2)
                                    ->columnSpanFull(),

                                \Filament\Schemas\Components\Fieldset::make('Tautan Sosial Media')
                                    ->schema([
                                        TextInput::make('sosmed_instagram')
                                            ->label('Link Instagram')
                                            ->url()
                                            ->placeholder('https://instagram.com/...'),
                                        TextInput::make('sosmed_tiktok')
                                            ->label('Link TikTok')
                                            ->url()
                                            ->placeholder('https://tiktok.com/@...'),
                                    ])->columns(2),

                                Repeater::make('footer_links')
                                    ->label('Tautan Bagian Bawah (Footer Links)')
                                    ->schema([
                                        TextInput::make('label')->required()->label('Label'),
                                        TextInput::make('url')->required()->label('Tautan (URL)')->url(),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->addActionLabel('Tambah Tautan'),
                            ])->columns(2),

                        Tabs\Tab::make('Global Pricing')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                \Filament\Schemas\Components\Section::make('Persentase Margin per Tier')
                                    ->description('Margin dihitung otomatis: Margin Flat = price_cost × %. Nilai dibulatkan ke kelipatan Rp 50, minimum Rp 50.')
                                    ->schema([
                                        TextInput::make('pricing_pct_guest')
                                            ->label('Guest (%)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->step(0.1)
                                            ->suffix('%')
                                            ->default(4.0),
                                        TextInput::make('pricing_pct_bronze')
                                            ->label('Bronze (%)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->step(0.1)
                                            ->suffix('%')
                                            ->default(3.0),
                                        TextInput::make('pricing_pct_silver')
                                            ->label('Silver (%)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->step(0.1)
                                            ->suffix('%')
                                            ->default(2.0),
                                        TextInput::make('pricing_pct_gold')
                                            ->label('Gold (%)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->step(0.1)
                                            ->suffix('%')
                                            ->default(1.0),
                                        TextInput::make('pricing_pct_platinum')
                                            ->label('Platinum (%)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->step(0.1)
                                            ->suffix('%')
                                            ->default(0.5),
                                    ])->columns(5),
                            ]),

                        Tabs\Tab::make('Keamanan & OTP')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Select::make('otp_provider')
                                    ->label('Penyedia Layanan OTP')
                                    ->options([
                                        'fonnte' => 'Fonnte (WhatsApp)',
                                        'mpwa'   => 'MPWA (WhatsApp)',
                                    ])
                                    ->required()
                                    ->columnSpanFull(),

                                Toggle::make('enable_turnstile')
                                    ->label('Aktifkan Proteksi Cloudflare Turnstile')
                                    ->columnSpanFull(),
                                TextInput::make('turnstile_site_key')
                                    ->label('Site Key'),
                                TextInput::make('turnstile_secret_key')
                                    ->label('Secret Key')
                                    ->password(),
                            ])->columns(2),
                    ])
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            if ($key === 'footer_links') {
                $value = json_encode($value ?? []);
            }
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            Setting::set($key, $value);
        }

        Notification::make()
            ->title('Konfigurasi web tersimpan')
            ->body('Seluruh perubahan konfigurasi berhasil diteruskan ke database.')
            ->success()
            ->send();
    }

    public function applyGlobalPricing(): void
    {
        // Simpan terlebih dahulu agar persentase terbaru sudah ada di DB
        $this->save();

        Artisan::call('products:recalculate-margins');

        Notification::make()
            ->title('Global Pricing Diterapkan!')
            ->body('Semua harga produk telah dihitung ulang berdasarkan persentase margin yang baru.')
            ->success()
            ->send();
    }
}
