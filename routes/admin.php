<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DemoWalletAdjustmentController;
use App\Http\Controllers\Admin\GameController;
use App\Http\Controllers\Admin\LaunchControlController;
use App\Http\Controllers\Admin\PlayerController;
use App\Http\Controllers\Admin\PlayerStatusController;
use App\Http\Controllers\Admin\ProviderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin (staff only) — mounted at /admin from routes/web.php
|--------------------------------------------------------------------------
|
| Every route requires: signed in, verified email, active account, a staff
| permission (admin.access) and confirmed two-factor authentication.
| Individual routes add their own permission checks.
|
| There is NO route that changes the real-money launch gate.
|
*/

Route::get('/', DashboardController::class)->name('dashboard');

Route::get('players', [PlayerController::class, 'index'])
    ->middleware('can:players.view')
    ->name('players.index');

Route::patch('players/{player}/status', PlayerStatusController::class)
    ->whereUuid('player')
    ->middleware('throttle:30,1')
    ->name('players.status.update');

Route::post('players/{player}/demo-wallet/adjustments', DemoWalletAdjustmentController::class)
    ->whereUuid('player')
    ->middleware('throttle:30,1')
    ->name('players.demo-wallet.adjustments.store');

Route::get('games', [GameController::class, 'index'])
    ->middleware('can:games.view')
    ->name('games.index');

Route::patch('games/{game:id}', [GameController::class, 'update'])
    ->whereUuid('game')
    ->middleware('throttle:60,1')
    ->name('games.update');

Route::get('providers', [ProviderController::class, 'index'])
    ->middleware('can:providers.view')
    ->name('providers.index');

Route::patch('providers/{provider}/status', [ProviderController::class, 'updateStatus'])
    ->whereUuid('provider')
    ->middleware('throttle:30,1')
    ->name('providers.status.update');

Route::get('launch-control', LaunchControlController::class)
    ->middleware('can:launch_gate.view')
    ->name('launch-control.show');
