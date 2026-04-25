<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Widgets\DashboardStats;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use App\Filament\Admin\Widgets\TopGamesWidget;
use App\Filament\Admin\Widgets\TransactionChart;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Admin\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Models\Setting;
use App\Filament\Admin\Widgets\OrderStatusChart;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $logoPath = app()->runningInConsole() 
            ? null 
            : cache()->rememberForever('site_logo', function () {
                return Setting::where('key', 'web_logo')->value('value');
            });
        $adminDomain = config('app.admin_domain');
        $adminPath = config('app.admin_path');
        $appScheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';
        $homeUrl = $adminDomain
            ? sprintf('%s://%s/%s', $appScheme, $adminDomain, ltrim((string) $adminPath, '/'))
            : url('/'.ltrim((string) $adminPath, '/'));

        return $panel
            ->id('admin')
            ->domain($adminDomain)
            ->path($adminPath)
            ->brandLogo(asset('storage/' . $logoPath))
            ->colors([
                'primary' => Color::Purple,
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')

            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->login()
            ->homeUrl(rtrim($homeUrl, '/'))
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->widgets([
                // DashboardStats::class,
                // TransactionChart::class,
                // OrderStatusChart::class,
                // TopGamesWidget::class,
                // AccountWidget::class,
                // FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
