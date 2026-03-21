<?php

use App\Http\Controllers\Api\CancelTransactionController;
use App\Http\Controllers\Api\GameSearchController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\DigiflazzCallbackController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\TripayCallbackController;
use App\Http\Controllers\Api\UsernameCheckController;
use App\Http\Controllers\Api\ValidateVoucherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Checkout API
Route::middleware(['web', 'throttle:10,1'])->group(function () {
    Route::post('/checkout', [CheckoutController::class, 'store']);
    Route::post('/calculate-fee', [CheckoutController::class, 'calculateFee']);
});

Route::middleware('throttle:30,1')->post('/check-username', [UsernameCheckController::class, 'check']);

// Game Search Autocomplete
Route::middleware('throttle:60,1')->get('/search', GameSearchController::class);

// Review
Route::middleware(['web', 'throttle:5,1'])->group(function () {
    Route::post('/review', [ReviewController::class, 'store']);
    Route::post('/review/check', [ReviewController::class, 'check']);
});

// Validate Voucher
Route::middleware(['web', 'throttle:20,1'])->post('/validate-voucher', ValidateVoucherController::class);

// Cancel Transaction
Route::middleware(['web', 'throttle:10,1'])->post('/cancel', [CancelTransactionController::class, 'cancel']);

// Live Chat (Nova AI Assistant)
Route::middleware(['web', 'throttle:20,1'])->post('/chat', [ChatController::class, 'send']);

// Webhook / Callbacks Integration
Route::post('/callback/tripay', [TripayCallbackController::class, 'handle'])->middleware('throttle:60,1');
Route::post('/callback/digiflazz', [DigiflazzCallbackController::class, 'handle'])->middleware('throttle:60,1');
