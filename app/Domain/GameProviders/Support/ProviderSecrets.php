<?php

namespace App\Domain\GameProviders\Support;

use App\Domain\Compliance\Enums\EnvironmentMode;
use App\Domain\Games\Enums\ProviderEnvironment;
use App\Domain\Games\Models\GameProvider;
use RuntimeException;

/**
 * Resolves provider secrets from their references. Secrets live in
 * configuration backed by environment variables or a secrets manager
 * (config/services.php `game_providers`), never in the database.
 *
 * Production provider credentials are never loaded unless the deployment is
 * explicitly in production mode.
 */
class ProviderSecrets
{
    public function webhookSecret(GameProvider $provider): string
    {
        $this->assertMayLoad($provider);

        if ($provider->is_simulator) {
            // The simulator signs with a key derived from APP_KEY, so local
            // setups need no extra secret. It has no value outside this app.
            return hash_hmac('sha256', 'luxbet-simulator-webhook:'.$provider->id, (string) config('app.key'));
        }

        return $this->resolve($provider->webhook_signing_key_reference);
    }

    public function credentials(GameProvider $provider): string
    {
        $this->assertMayLoad($provider);

        return $this->resolve($provider->credentials_secret_reference);
    }

    private function assertMayLoad(GameProvider $provider): void
    {
        $mode = EnvironmentMode::fromConfig(config('luxbet.environment_mode'));

        if ($provider->environment === ProviderEnvironment::Production && $mode !== EnvironmentMode::Production) {
            throw new RuntimeException('Production provider credentials cannot be loaded outside production mode.');
        }
    }

    private function resolve(?string $reference): string
    {
        $secret = $reference !== null ? config('services.game_providers.'.$reference) : null;

        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException('The provider secret is not configured.');
        }

        return $secret;
    }
}
