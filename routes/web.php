<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\CoinHistoryController;
use App\Http\Controllers\CoinTopupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileAvatarController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\UserTransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/reseller', [HomeController::class, 'reseller'])->name('reseller');

Route::get('/order/{slug}', [App\Http\Controllers\GameController::class, 'show'])->name('game.detail');
Route::get('/invoice', [App\Http\Controllers\InvoiceController::class, 'show'])->name('invoice');
Route::get('/invoice/data', [App\Http\Controllers\InvoiceController::class, 'data'])->middleware('throttle:10,1')->name('invoice.data');
Route::get('/invoice/by-phone', [App\Http\Controllers\InvoiceController::class, 'searchByPhone'])->middleware('throttle:10,1')->name('invoice.by-phone');
Route::get('/blog', [App\Http\Controllers\ArticleController::class, 'index'])->name('blog');
Route::get('/blog/{slug}', [App\Http\Controllers\ArticleController::class, 'show'])->name('blog.show');

Route::inertia('/syarat-ketentuan', 'syarat-ketentuan')->name('terms');
Route::inertia('/kebijakan-privasi', 'kebijakan-privasi')->name('privacy');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/transactions', [UserTransactionController::class, 'index'])->name('dashboard.transactions');
    Route::inertia('dashboard/settings', 'user/settings')->name('dashboard.settings');
    Route::post('dashboard/settings/avatar', [ProfileAvatarController::class, 'update'])->name('dashboard.settings.avatar');
    Route::delete('dashboard/settings/avatar', [ProfileAvatarController::class, 'destroy'])->name('dashboard.settings.avatar.destroy');
    Route::get('dashboard/api-credentials', [App\Http\Controllers\ApiCredentialController::class, 'index'])->name('dashboard.api-credentials');
    Route::post('dashboard/api-credentials/regenerate', [App\Http\Controllers\ApiCredentialController::class, 'regenerate'])->name('dashboard.api-credentials.regenerate');

    // Fitur coin — butuh verifikasi email
    Route::middleware('verified')->group(function () {
        Route::get('dashboard/topup-saldo', [CoinTopupController::class, 'index'])->name('dashboard.coin-topups.index');
        Route::post('dashboard/topup-saldo', [CoinTopupController::class, 'store'])->name('dashboard.coin-topups.store');
        Route::get('dashboard/coin-history', [CoinHistoryController::class, 'index'])->name('dashboard.coin-history');

    });
});

require __DIR__.'/settings.php';
