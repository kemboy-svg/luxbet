<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Compliance\LaunchGate\LaunchGate;
use App\Domain\GameProviders\Enums\GameSessionStatus;
use App\Domain\GameProviders\Enums\WebhookProcessingStatus;
use App\Domain\GameProviders\Models\GameSession;
use App\Domain\GameProviders\Models\ProviderWebhookEvent;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Operational checks for staff with reports.view. Reports pass/warn/fail
 * only; it never shows connection strings, hosts or credentials.
 */
class SystemHealthController extends Controller
{
    public function __invoke(LaunchGate $gate): Response
    {
        $checks = [
            $this->check('Database', 'PostgreSQL connection', function (): array {
                DB::select('select 1');

                return ['ok', 'Connected'];
            }),
            $this->check('Cache', 'Read and write through the cache store', function (): array {
                $key = 'health:'.Str::random(12);
                Cache::put($key, 'ok', 10);
                $value = Cache::pull($key);

                return $value === 'ok' ? ['ok', 'Working'] : ['fail', 'Value did not round-trip'];
            }),
            $this->check('Queue', 'Failed jobs', function (): array {
                if (! Schema::hasTable('failed_jobs')) {
                    return ['warn', 'failed_jobs table missing'];
                }
                $failed = DB::table('failed_jobs')->count();

                return [$failed === 0 ? 'ok' : 'warn', $failed.' failed '.Str::plural('job', $failed)];
            }),
            $this->check('Provider events', 'Received but not processed after 5 minutes', function (): array {
                $stuck = ProviderWebhookEvent::query()
                    ->where('processing_status', WebhookProcessingStatus::Received->value)
                    ->where('received_at', '<', Date::now()->subMinutes(5))
                    ->count();

                return [$stuck === 0 ? 'ok' : 'warn', $stuck.' waiting'];
            }),
            $this->check('Session expiry', 'Launched sessions past their expiry (scheduler running?)', function (): array {
                $overdue = GameSession::query()
                    ->where('status', GameSessionStatus::Launched->value)
                    ->where('expires_at', '<', Date::now()->subMinutes(10))
                    ->count();

                return [$overdue === 0 ? 'ok' : 'warn', $overdue.' overdue'];
            }),
            $this->check('Real-money launch gate', 'Must be disabled', function () use ($gate): array {
                return $gate->isRealMoneyEnabled()
                    ? ['fail', 'Enabled — this build does not support real money']
                    : ['ok', 'Disabled'];
            }),
        ];

        return Inertia::render('admin/SystemHealth', [
            'checks' => $checks,
            'environment' => [
                'mode' => (string) config('luxbet.environment_mode'),
                'app_env' => (string) config('app.env'),
                'debug' => (bool) config('app.debug'),
            ],
            'checked_at' => Date::now()->toIso8601String(),
        ]);
    }

    /**
     * @param  callable(): array{0: string, 1: string}  $probe
     * @return array{name: string, description: string, status: string, detail: string}
     */
    private function check(string $name, string $description, callable $probe): array
    {
        try {
            [$status, $detail] = $probe();
        } catch (Throwable $e) {
            report($e);
            [$status, $detail] = ['fail', 'Check failed ('.class_basename($e).')'];
        }

        return compact('name', 'description', 'status', 'detail');
    }
}
