<?php

use App\Http\Controllers\Player\DemoWalletController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('demo-wallet', [DemoWalletController::class, 'show'])->name('demo-wallet.show');
    Route::post('demo-wallet/reset', [DemoWalletController::class, 'reset'])
        ->middleware('throttle:10,1')
        ->name('demo-wallet.reset');
});

require __DIR__.'/settings.php';

Route::middleware(['auth', 'verified', 'can:admin.access', 'staff.mfa'])
    ->prefix('admin')
    ->name('admin.')
    ->group(__DIR__.'/admin.php');
