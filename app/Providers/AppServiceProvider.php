<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\CoinTopup;
use App\Models\Game;
use App\Models\Transaction;
use App\Observers\BannerObserver;
use App\Observers\CoinTopupObserver;
use App\Observers\GameObserver;
use App\Observers\TransactionObserver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Transaction::observe(TransactionObserver::class);
        CoinTopup::observe(CoinTopupObserver::class);
        Game::observe(GameObserver::class);
        Banner::observe(BannerObserver::class);
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(8)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
