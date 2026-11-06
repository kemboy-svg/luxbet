<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreflightCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $setVariables = [];

    protected function tearDown(): void
    {
        foreach ($this->setVariables as $name) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);
        }

        parent::tearDown();
    }

    private function setEnv(string $name, string $value): void
    {
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
        $this->setVariables[] = $name;
    }

    private function hardenedConfig(): void
    {
        config([
            'app.debug' => false,
            'app.url' => 'https://staging.luxbet.test',
            'session.secure' => true,
            'luxbet.environment_mode' => 'staging',
        ]);
    }

    public function test_a_hardened_deployment_passes(): void
    {
        $this->hardenedConfig();

        $this->artisan('luxbet:preflight')
            ->expectsOutputToContain('Preflight passed')
            ->assertSuccessful();
    }

    public function test_payment_credentials_fail_the_release_without_printing_values(): void
    {
        $this->hardenedConfig();
        $this->setEnv('STRIPE_SECRET', 'sk_test_do_not_print');

        $this->artisan('luxbet:preflight')
            ->expectsOutputToContain('STRIPE_SECRET')
            ->doesntExpectOutputToContain('sk_test_do_not_print')
            ->assertFailed();
    }

    public function test_live_secrets_fail_outside_production_mode(): void
    {
        $this->hardenedConfig();
        $this->setEnv('PROVIDER_LIVE_API_KEY', 'live-key');

        $this->artisan('luxbet:preflight')->assertFailed();
    }

    public function test_debug_mode_and_plain_http_fail(): void
    {
        $this->hardenedConfig();
        config(['app.debug' => true, 'app.url' => 'http://staging.luxbet.test']);

        $this->artisan('luxbet:preflight')->assertFailed();
    }

    public function test_local_mode_skips_deployment_only_checks(): void
    {
        config(['app.debug' => true, 'app.url' => 'http://localhost']);

        $this->artisan('luxbet:preflight --allow-local')->assertSuccessful();
    }
}
