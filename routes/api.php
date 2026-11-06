<?php

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
