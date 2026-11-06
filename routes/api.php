<?php

use App\Http\Controllers\Api\V1\DemoWalletController;
use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — mounted at /api/v1 (see bootstrap/app.php)
|--------------------------------------------------------------------------
|
| Do NOT add /deposit, /withdraw, /cashout or any real-money wallet routes.
| Any route that would ever touch real money must use the `real-money`
| middleware, which denies every request in this release.
|
*/

Route::middleware('throttle:api')->group(function () {
    Route::get('health', HealthController::class)->name('api.v1.health');
});

// Browser-session API: secure HTTP-only cookies, CSRF on state changes.
Route::middleware(['api.session', 'auth', 'verified', 'throttle:api'])->group(function () {
    Route::get('demo-wallets', [DemoWalletController::class, 'index'])->name('api.v1.demo-wallets.index');

    Route::get('demo-wallets/{wallet}/transactions', [DemoWalletController::class, 'transactions'])
        ->whereUuid('wallet')
        ->name('api.v1.demo-wallets.transactions');

    Route::post('demo-wallets/{wallet}/reset', [DemoWalletController::class, 'reset'])
        ->whereUuid('wallet')
        ->middleware('throttle:10,1')
        ->name('api.v1.demo-wallets.reset');
});
