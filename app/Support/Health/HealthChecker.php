<?php

namespace App\Support\Health;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Lightweight dependency checks for the health endpoint. Failures are
 * logged with the exception class only, never connection details.
 */
class HealthChecker
{
    public function database(): bool
    {
        try {
            DB::connection()->select('select 1');

            return true;
        } catch (Throwable $e) {
            Log::error('Health check: database unavailable.', ['exception' => $e::class]);

            return false;
        }
    }

    public function redis(): bool
    {
        try {
            Redis::connection()->command('ping');

            return true;
        } catch (Throwable $e) {
            Log::error('Health check: Redis unavailable.', ['exception' => $e::class]);

            return false;
        }
    }
}
