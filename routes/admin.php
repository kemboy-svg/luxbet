<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DemoTransactionController;
use App\Http\Controllers\Admin\DemoWalletAdjustmentController;
use App\Http\Controllers\Admin\GameController;
use App\Http\Controllers\Admin\LaunchControlController;
use App\Http\Controllers\Admin\PlayerController;
use App\Http\Controllers\Admin\PlayerStatusController;
use App\Http\Controllers\Admin\ProviderController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\WebhookEventController;
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

Route::get('players/{player}', [PlayerController::class, 'show'])
    ->whereUuid('player')
    ->middleware('can:players.view')
    ->name('players.show');

Route::patch('players/{player}/status', PlayerStatusController::class)
    ->whereUuid('player')
    ->middleware('throttle:30,1,players.status.update')
    ->name('players.status.update');

Route::post('players/{player}/demo-wallet/adjustments', DemoWalletAdjustmentController::class)
    ->whereUuid('player')
    ->middleware('throttle:30,1,players.demo-wallet.adjustments.store')
    ->name('players.demo-wallet.adjustments.store');

Route::get('games', [GameController::class, 'index'])
    ->middleware('can:games.view')
    ->name('games.index');

Route::patch('games/{game:id}', [GameController::class, 'update'])
    ->whereUuid('game')
    ->middleware('throttle:60,1,games.update')
    ->name('games.update');

Route::get('providers', [ProviderController::class, 'index'])
    ->middleware('can:providers.view')
    ->name('providers.index');

Route::patch('providers/{provider}/status', [ProviderController::class, 'updateStatus'])
    ->whereUuid('provider')
    ->middleware('throttle:30,1,providers.status.update')
    ->name('providers.status.update');

Route::get('launch-control', LaunchControlController::class)
    ->middleware('can:launch_gate.view')
    ->name('launch-control.show');

Route::get('demo-transactions', [DemoTransactionController::class, 'index'])
    ->middleware('can:demo_wallet.view')
    ->name('demo-transactions.index');

Route::get('provider-events', [WebhookEventController::class, 'index'])
    ->middleware('can:providers.view')
    ->name('provider-events.index');

Route::get('audit-log', [AuditLogController::class, 'index'])
    ->middleware('can:audit.view')
    ->name('audit-log.index');

Route::get('system-health', SystemHealthController::class)
    ->middleware('can:reports.view')
    ->name('system-health');

// Staff management: re-confirm the password before changing who has access.
Route::middleware(['can:staff.manage', 'password.confirm'])->group(function () {
    Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
    Route::post('staff', [StaffController::class, 'store'])
        ->middleware('throttle:10,1,staff.store')
        ->name('staff.store');
    Route::post('staff/{staff}/roles', [StaffController::class, 'grant'])
        ->whereUuid('staff')
        ->middleware('throttle:30,1,staff.roles.store')
        ->name('staff.roles.store');
    Route::delete('staff/{staff}/roles', [StaffController::class, 'revoke'])
        ->whereUuid('staff')
        ->middleware('throttle:30,1,staff.roles.destroy')
        ->name('staff.roles.destroy');
});
