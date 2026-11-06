<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Compliance\LaunchGate\LaunchGate;
use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use App\Support\Health\HealthChecker;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __invoke(HealthChecker $checker, LaunchGate $gate): JsonResponse
    {
        $checks = [
            'database' => $checker->database() ? 'ok' : 'fail',
            'redis' => $checker->redis() ? 'ok' : 'fail',
        ];

        $healthy = ! in_array('fail', $checks, true);
        $status = $gate->status();

        return ApiResponse::success([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'environment_mode' => $status->environmentMode->value,
            'real_money_enabled' => $status->realMoneyEnabled,
        ], $healthy ? 200 : 503);
    }
}
