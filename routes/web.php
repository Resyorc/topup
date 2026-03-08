<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

use App\Http\Controllers\HomeController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/order/{slug}', [App\Http\Controllers\GameController::class, 'show'])->name('game.detail');
Route::get('/invoice', [App\Http\Controllers\InvoiceController::class, 'show'])->name('invoice');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::inertia('dashboard/transactions', 'user/transactions')->name('dashboard.transactions');
    Route::inertia('dashboard/settings', 'user/settings')->name('dashboard.settings');
});

require __DIR__.'/settings.php';
