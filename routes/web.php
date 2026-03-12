<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CoinTopupController;
use App\Http\Controllers\UserTransactionController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/order/{slug}', [App\Http\Controllers\GameController::class, 'show'])->name('game.detail');
Route::get('/invoice', [App\Http\Controllers\InvoiceController::class, 'show'])->name('invoice');
Route::get('/invoice/data', [App\Http\Controllers\InvoiceController::class, 'data'])->name('invoice.data');
Route::inertia('/syarat-ketentuan', 'syarat-ketentuan')->name('terms');
Route::inertia('/kebijakan-privasi', 'kebijakan-privasi')->name('privacy');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/topup-saldo', [CoinTopupController::class, 'index'])->name('dashboard.coin-topups.index');
    Route::post('dashboard/topup-saldo', [CoinTopupController::class, 'store'])->name('dashboard.coin-topups.store');
    Route::get('dashboard/transactions', [UserTransactionController::class, 'index'])->name('dashboard.transactions');
    Route::inertia('dashboard/settings', 'user/settings')->name('dashboard.settings');
});

require __DIR__.'/settings.php';
