<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    // Throttled to curb brute-force / credential-stuffing on the open endpoints.
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:6,1');

    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

/*
|--------------------------------------------------------------------------
| Orders & Payments (JWT protected)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->group(function () {
    Route::apiResource('orders', OrderController::class);

    // Payments nested under an order.
    Route::get('orders/{order}/payments', [PaymentController::class, 'indexForOrder']);
    Route::post('orders/{order}/payments', [PaymentController::class, 'store']);

    // All payments across the user's orders.
    Route::get('payments', [PaymentController::class, 'index']);
});
