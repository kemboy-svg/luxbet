<?php

use App\Http\Controllers\Games\GameController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Player\DemoWalletController;
use App\Http\Controllers\Player\FavouriteController;
use App\Http\Controllers\Player\PlayController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('games', [GameController::class, 'index'])->name('games.index');
Route::get('games/{slug}', [GameController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('games.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('demo-wallet', [DemoWalletController::class, 'show'])->name('demo-wallet.show');
    Route::post('demo-wallet/reset', [DemoWalletController::class, 'reset'])
        ->middleware('throttle:10,1')
        ->name('demo-wallet.reset');

    Route::post('games/{slug}/favourite', [FavouriteController::class, 'store'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
        ->middleware('throttle:60,1')
        ->name('games.favourite.store');
    Route::delete('games/{slug}/favourite', [FavouriteController::class, 'destroy'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
        ->middleware('throttle:60,1')
        ->name('games.favourite.destroy');

    Route::post('games/{slug}/play', [PlayController::class, 'start'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
        ->middleware('throttle:20,1')
        ->name('games.play');

    // Local simulator game screen: only reachable through the signed launch URL.
    Route::get('play/{session}', [PlayController::class, 'show'])
        ->middleware('signed')
        ->name('simulator.play');

    Route::post('play/{session}/end', [PlayController::class, 'end'])->name('simulator.end');
});

require __DIR__.'/settings.php';

Route::middleware(['auth', 'verified', 'can:admin.access', 'staff.mfa'])
    ->prefix('admin')
    ->name('admin.')
    ->group(__DIR__.'/admin.php');
