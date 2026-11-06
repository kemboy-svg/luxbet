<?php

namespace App\Domain\GameProviders\Contracts;

use App\Domain\GameProviders\Data\LaunchResult;
use App\Domain\GameProviders\Data\ProviderHealth;
use App\Domain\GameProviders\Data\VerifiedProviderEvent;
use App\Domain\GameProviders\Exceptions\InvalidProviderWebhook;
use App\Domain\GameProviders\Exceptions\ProviderUnavailable;
use App\Domain\GameProviders\Models\GameSession;
use App\Domain\Games\Models\GameProvider;

/**
 * One implementation per provider. Request and response formats must come
 * from the provider's official documentation; nothing here may be invented
 * for a real provider. Only sandbox endpoints may be used in demo mode.
 */
interface GameProviderAdapter
{
    /**
     * Ask the provider for a demo launch URL for an already created session.
     *
     * @throws ProviderUnavailable when the provider cannot be reached or refuses
     */
    public function createDemoSession(GameSession $session, string $returnUrl): LaunchResult;

    /**
     * Verify a raw webhook request and turn it into a typed event.
     *
     * @param  array<string, string>  $headers  lower-cased header names
     *
     * @throws InvalidProviderWebhook when the signature, timestamp or schema is invalid
     */
    public function verifyWebhook(GameProvider $provider, string $body, array $headers): VerifiedProviderEvent;

    public function health(GameProvider $provider): ProviderHealth;
}
