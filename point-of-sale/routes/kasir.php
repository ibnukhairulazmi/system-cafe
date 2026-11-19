<?php

use App\Http\Controllers\Kasir\CheckoutController;
use App\Http\Controllers\Kasir\DeliveryOrderController;
use App\Http\Controllers\Kasir\PosController;
use App\Http\Controllers\Kasir\ReservationController;
use App\Http\Controllers\Kasir\ShiftController;
use App\Http\Controllers\Kasir\TransactionController;
use App\Http\Controllers\Kasir\TableController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:kasir'])
    ->prefix('kasir')
    ->name('kasir.')
    ->group(function () {
        Route::get('/shift', [ShiftController::class, 'index'])->name('shift.index');
        Route::post('/shift/open', [ShiftController::class, 'open'])->name('shift.open');
        Route::post('/shift/close', [ShiftController::class, 'close'])->name('shift.close');

        Route::middleware('shift.active')->group(function () {
            Route::get('/pos', [PosController::class, 'index'])->name('pos');
            Route::post('/checkout', CheckoutController::class)->name('checkout');
        });

        Route::resource('reservations', ReservationController::class)->only(['index', 'show']);
        Route::post('reservations/{reservation}/settle', [ReservationController::class, 'settle'])
            ->name('reservations.settle');
        Route::post('reservations/{reservation}/check-in', [ReservationController::class, 'checkIn'])
            ->name('reservations.check-in');
        Route::post('reservations/{reservation}/complete', [ReservationController::class, 'complete'])
            ->name('reservations.complete');

        Route::resource('delivery-orders', DeliveryOrderController::class)
            ->only(['index', 'show'])
            ->parameters(['delivery-orders' => 'deliveryOrder']);
        Route::patch('delivery-orders/{deliveryOrder}/ship', [DeliveryOrderController::class, 'ship'])
            ->name('delivery-orders.ship');
        Route::patch('delivery-orders/{deliveryOrder}/complete', [DeliveryOrderController::class, 'complete'])
            ->name('delivery-orders.complete');
        Route::post('delivery-orders/{deliveryOrder}/receive-payment', [DeliveryOrderController::class, 'receivePayment'])
            ->name('delivery-orders.receive-payment');

        Route::get('tables', [TableController::class, 'index'])->name('tables.index');
        Route::post('tables/{table}/occupy', [TableController::class, 'occupy'])->name('tables.occupy');
        Route::post('tables/{table}/release', [TableController::class, 'release'])->name('tables.release');
        Route::post('tables/reservations/{reservation}/check-in', [ReservationController::class, 'checkIn'])->name('tables.check-in');

        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('/transactions/{transaction}/receipt', [TransactionController::class, 'receipt'])->name('transactions.receipt');
        Route::post('/transactions/{transaction}/void-request', [TransactionController::class, 'requestVoid'])->name('transactions.void-request');
    });
