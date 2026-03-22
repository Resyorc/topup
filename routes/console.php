<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('digiflazz:sync-products')->everyFourHours();
Schedule::command('digiflazz:check-balance')->hourly();
Schedule::command('transactions:expire-pending')->everyMinute();
