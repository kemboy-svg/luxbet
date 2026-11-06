<?php

use App\Http\Controllers\Api\V1\DemoWalletController;
use App\Http\Controllers\Api\V1\GameController;
use App\Http\Controllers\Api\V1\GameSessionController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\ProviderWebhookController;
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

    Route::get('games', [GameController::class, 'index'])->name('api.v1.games.index');
    Route::get('games/categories', [GameController::class, 'categories'])->name('api.v1.games.categories');
    Route::get('games/{slug}', [GameController::class, 'show'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
        ->name('api.v1.games.show');
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

    Route::post('games/{slug}/demo-session', [GameSessionController::class, 'store'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
        ->middleware('throttle:20,1')
        ->name('api.v1.games.demo-session');

    Route::get('game-sessions', [GameSessionController::class, 'index'])->name('api.v1.game-sessions.index');
    Route::get('game-sessions/{session}', [GameSessionController::class, 'show'])->name('api.v1.game-sessions.show');
    Route::post('game-sessions/{session}/simulator-rounds', [GameSessionController::class, 'simulatorRound'])
        ->middleware('throttle:60,1')
        ->name('api.v1.game-sessions.simulator-rounds');
});

// Provider webhooks: no session or CSRF; authenticity comes from the
// provider's signature, checked before anything is processed.
Route::post('webhooks/providers/{provider:slug}', ProviderWebhookController::class)
    ->middleware('throttle:600,1')
    ->name('api.v1.webhooks.providers');
