<?php

use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\NotificationCountController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardRedirectController::class)->name('home');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');
});

Route::middleware('auth')->get('/notifications/counts', NotificationCountController::class)
    ->name('notifications.counts');

require __DIR__ . '/auth.php';
require __DIR__ . '/superadmin.php';
require __DIR__ . '/admin.php';
require __DIR__ . '/kasir.php';
