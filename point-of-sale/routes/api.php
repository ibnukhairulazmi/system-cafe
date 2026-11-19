<?php

use App\Http\Controllers\Api\DeliveryOrderController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\MidtransWebhookController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\QrisPaymentController;

Route::prefix('public')->group(function () {
    Route::get('menu', [PublicController::class, 'menu']);
    Route::get('site', [PublicController::class, 'site']);
    Route::post('delivery-orders', [DeliveryOrderController::class, 'store'])
        ->middleware('throttle:reservations');
    Route::get('reservations/availability', [ReservationController::class, 'availability']);
    Route::post('reservations', [ReservationController::class, 'store'])
        ->middleware('throttle:10,1');
    Route::get('reservations/{code}', [ReservationController::class, 'show']);
    
    Route::post('payments/midtrans/webhook', [MidtransWebhookController::class, 'handle']);

    Route::get('/payments/config', [QrisPaymentController::class, 'config']);
    Route::post('/payments/qris', [QrisPaymentController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/payments/qris/{code}/status', [QrisPaymentController::class, 'status'])->middleware('throttle:60,1');
    Route::post('/payments/qris/simulate', [QrisPaymentController::class, 'simulate']);
});
