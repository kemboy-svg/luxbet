<?php

namespace Tests\Feature\Compliance;

use App\Domain\Compliance\Enums\EnvironmentMode;
use App\Domain\Compliance\Exceptions\RealMoneyLockedException;
use App\Domain\Compliance\LaunchGate\LaunchGate;
use App\Domain\Compliance\Models\LaunchControl;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Required launch-gate tests (instructions.md §11.6) that apply to the
 * foundation milestone. Role-based cases (support agent, super admin) are
 * added with roles and permissions in Milestone 2.
 */
class LaunchGateTest extends TestCase
{
    use RefreshDatabase;

    /** §11.6 (1) */
    public function test_a_new_installation_has_real_money_disabled(): void
    {
        $record = LaunchControl::current();

        $this->assertNotNull($record);
        $this->assertFalse($record->real_money_enabled);
        $this->assertSame(EnvironmentMode::Demo, $record->environment_mode);
        $this->assertSame(1, $record->version);
        $this->assertFalse(config('luxbet.real_money.enabled'));

        $status = app(LaunchGate::class)->status();

        $this->assertFalse($status->realMoneyEnabled);
        $this->assertContains(LaunchGate::REASON_CONFIG_LOCKED, $status->blockingReasons);
    }

    /** §11.6 (2) */
    public function test_missing_launch_control_data_keeps_real_money_disabled(): void
    {
        DB::table('launch_control')->delete();

        $status = app(LaunchGate::class)->status();

        $this->assertFalse($status->realMoneyEnabled);
        $this->assertFalse($status->recordPresent);
        $this->assertContains(LaunchGate::REASON_RECORD_MISSING, $status->blockingReasons);
    }

    public function test_the_database_refuses_to_store_real_money_enabled(): void
    {
        $this->expectException(QueryException::class);

        DB::table('launch_control')->where('id', 1)->update(['real_money_enabled' => true]);
    }

    public function test_the_database_allows_only_one_launch_control_record(): void
    {
        $this->expectException(QueryException::class);

        DB::table('launch_control')->insert(['id' => 2, 'environment_mode' => 'demo', 'version' => 1]);
    }

    public function test_application_code_cannot_enable_real_money_mode(): void
    {
        $record = LaunchControl::current();
        $this->assertNotNull($record);

        $this->expectException(RealMoneyLockedException::class);

        $record->forceFill(['real_money_enabled' => true])->save();
    }

    public function test_application_code_cannot_delete_the_launch_control_record(): void
    {
        $record = LaunchControl::current();
        $this->assertNotNull($record);

        $this->expectException(RealMoneyLockedException::class);

        $record->delete();
    }

    public function test_a_failing_launch_control_read_keeps_real_money_disabled(): void
    {
        DB::statement('ALTER TABLE launch_control RENAME TO launch_control_unavailable');

        $status = app(LaunchGate::class)->status();

        $this->assertFalse($status->realMoneyEnabled);
        $this->assertContains(LaunchGate::REASON_RECORD_UNREADABLE, $status->blockingReasons);
    }

    /** §11.6 (3) */
    public function test_a_player_cannot_call_a_real_money_api_route(): void
    {
        Route::middleware(['api', 'real-money'])
            ->post('api/v1/__test/real-money-action', fn () => response()->json(['reached' => true]));

        $this->actingAs(User::factory()->create())
            ->postJson('/api/v1/__test/real-money-action')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'REAL_MONEY_DISABLED')
            ->assertJsonMissingPath('reached');
    }

    public function test_a_player_cannot_reach_a_real_money_web_route(): void
    {
        Route::middleware(['web', 'real-money'])
            ->post('__test/real-money-action', fn () => 'reached');

        $this->actingAs(User::factory()->create())
            ->post('/__test/real-money-action')
            ->assertForbidden();
    }

    /** §11.6 (3), (6) — no deposit, withdrawal or cash-out endpoints exist */
    public function test_no_deposit_withdrawal_or_cash_out_endpoints_exist(): void
    {
        foreach (['deposit', 'deposits', 'withdraw', 'withdrawals', 'cashout', 'cash-out', 'payments'] as $path) {
            $this->postJson("/api/v1/{$path}")
                ->assertNotFound()
                ->assertJsonPath('error.code', 'NOT_FOUND');
        }

        $offending = collect(Route::getRoutes()->getRoutes())
            ->map(fn (RoutingRoute $route): string => $route->uri())
            ->filter(fn (string $uri): bool => preg_match('/deposit|withdraw|cash-?out|payout/i', $uri) === 1)
            ->values()
            ->all();

        $this->assertSame([], $offending, 'Real-money endpoints must not exist in the demo release.');
    }

    /** §11.6 (4), (5) — no route can change launch control */
    public function test_no_route_can_modify_launch_control(): void
    {
        $writable = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RoutingRoute $route): bool => preg_match('/launch|real-?money|gate/i', $route->uri()) === 1)
            ->filter(fn (RoutingRoute $route): bool => array_diff($route->methods(), ['GET', 'HEAD']) !== [])
            ->map(fn (RoutingRoute $route): string => implode('|', $route->methods()).' '.$route->uri())
            ->values()
            ->all();

        $this->assertSame([], $writable);
    }

    public function test_the_launch_gate_command_reports_a_closed_gate(): void
    {
        $this->artisan('luxbet:launch-gate')
            ->expectsOutputToContain(LaunchGate::REASON_CONFIG_LOCKED)
            ->assertSuccessful();
    }

    /** §11.6 (10) */
    public function test_every_page_shows_the_demo_only_notice(): void
    {
        $this->withoutVite();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('DEMO MODE — NO REAL-MONEY GAMBLING')
            ->assertInertia(fn (Assert $page) => $page
                ->where('luxbet.realMoneyEnabled', false)
                ->where('luxbet.environmentMode', 'demo')
                ->where('luxbet.demoNotice.title', 'DEMO MODE — NO REAL-MONEY GAMBLING'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('DEMO MODE — NO REAL-MONEY GAMBLING');
    }
}
