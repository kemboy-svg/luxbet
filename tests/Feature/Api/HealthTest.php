<?php

namespace Tests\Feature\Api;

use App\Support\Health\HealthChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Tests\TestCase;

class HealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_returns_the_standard_envelope_when_dependencies_are_up(): void
    {
        $this->fakeHealth(database: true, redis: true);

        $response = $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.checks.database', 'ok')
            ->assertJsonPath('data.checks.redis', 'ok')
            ->assertJsonPath('data.environment_mode', 'demo')
            ->assertJsonPath('data.real_money_enabled', false);

        $requestId = $response->json('meta.request_id');

        $this->assertIsString($requestId);
        $this->assertTrue(Str::isUuid($requestId));
        $response->assertHeader('X-Request-Id', $requestId);
    }

    public function test_health_reports_degraded_with_503_when_a_dependency_is_down(): void
    {
        $this->fakeHealth(database: true, redis: false);

        $this->getJson('/api/v1/health')
            ->assertStatus(503)
            ->assertJsonPath('data.status', 'degraded')
            ->assertJsonPath('data.checks.redis', 'fail');
    }

    public function test_a_valid_inbound_request_id_is_reused_and_an_invalid_one_is_replaced(): void
    {
        $this->fakeHealth(database: true, redis: true);
        $id = (string) Str::uuid();

        $this->getJson('/api/v1/health', ['X-Request-Id' => $id])
            ->assertJsonPath('meta.request_id', $id)
            ->assertHeader('X-Request-Id', $id);

        $replaced = $this->getJson('/api/v1/health', ['X-Request-Id' => '<script>alert(1)</script>'])
            ->json('meta.request_id');

        $this->assertIsString($replaced);
        $this->assertTrue(Str::isUuid($replaced));
    }

    public function test_api_errors_use_the_error_envelope_without_debug_details(): void
    {
        config(['app.debug' => true]);

        $response = $this->getJson('/api/v1/does-not-exist')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'NOT_FOUND')
            ->assertJsonStructure(['error' => ['code', 'message', 'details'], 'meta' => ['request_id']]);

        $body = (string) $response->getContent();

        $this->assertStringNotContainsString('trace', $body);
        $this->assertStringNotContainsString('exception', $body);
        $this->assertStringNotContainsString('file', $body);
    }

    private function fakeHealth(bool $database, bool $redis): void
    {
        $this->mock(HealthChecker::class, function (MockInterface $mock) use ($database, $redis): void {
            $mock->shouldReceive('database')->andReturn($database);
            $mock->shouldReceive('redis')->andReturn($redis);
        });
    }
}
