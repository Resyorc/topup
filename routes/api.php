<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\TripayCallbackController;
use App\Http\Controllers\Api\DigiflazzCallbackController;
use App\Http\Controllers\Api\UsernameCheckController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Checkout API
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/checkout', [CheckoutController::class, 'store']);
    Route::post('/calculate-fee', [CheckoutController::class, 'calculateFee']);
});

Route::middleware('throttle:30,1')->post('/check-username', [UsernameCheckController::class, 'check']);

// Webhook / Callbacks Integration
Route::post('/callback/tripay', [TripayCallbackController::class, 'handle'])->middleware('throttle:60,1');;
Route::post('/callback/digiflazz', [DigiflazzCallbackController::class, 'handle'])->middleware('throttle:60,1');;
