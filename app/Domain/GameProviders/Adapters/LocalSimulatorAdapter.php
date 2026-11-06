<?php

namespace App\Domain\GameProviders\Adapters;

use App\Domain\GameProviders\Contracts\GameProviderAdapter;
use App\Domain\GameProviders\Data\LaunchResult;
use App\Domain\GameProviders\Data\ProviderHealth;
use App\Domain\GameProviders\Data\VerifiedProviderEvent;
use App\Domain\GameProviders\Enums\DemoEventType;
use App\Domain\GameProviders\Exceptions\InvalidProviderWebhook;
use App\Domain\GameProviders\Exceptions\ProviderUnavailable;
use App\Domain\GameProviders\Models\GameSession;
use App\Domain\GameProviders\Simulator\SimulatorSigner;
use App\Domain\GameProviders\Support\ProviderSecrets;
use App\Domain\Games\Models\GameProvider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

/**
 * Adapter for the LuxBet local simulator. Clearly labelled as a simulator,
 * never a certified casino game. It launches a signed page inside this app
 * and signs its events with LuxBet's own HMAC scheme.
 */
class LocalSimulatorAdapter implements GameProviderAdapter
{
    public function __construct(private readonly ProviderSecrets $secrets) {}

    public function createDemoSession(GameSession $session, string $returnUrl): LaunchResult
    {
        if (! config('luxbet.providers.simulator.available')) {
            throw ProviderUnavailable::because('The simulator is unavailable.');
        }

        return new LaunchResult(
            launchUrl: URL::temporarySignedRoute('simulator.play', $session->expires_at, [
                'session' => $session->session_reference,
                'return' => $returnUrl,
            ]),
            providerSessionReference: 'sim-'.Str::lower(Str::random(24)),
        );
    }

    public function verifyWebhook(GameProvider $provider, string $body, array $headers): VerifiedProviderEvent
    {
        $timestamp = $headers[SimulatorSigner::TIMESTAMP_HEADER] ?? '';
        $signature = $headers[SimulatorSigner::SIGNATURE_HEADER] ?? '';

        if (! ctype_digit($timestamp) || $signature === '') {
            throw InvalidProviderWebhook::signature();
        }

        $expected = SimulatorSigner::sign($body, (int) $timestamp, $this->secrets->webhookSecret($provider));

        if (! hash_equals($expected, $signature)) {
            throw InvalidProviderWebhook::signature();
        }

        $window = (int) config('luxbet.providers.webhook.replay_window_seconds');

        if (abs(Date::now()->getTimestamp() - (int) $timestamp) > $window) {
            throw InvalidProviderWebhook::stale();
        }

        return $this->parse($body);
    }

    public function health(GameProvider $provider): ProviderHealth
    {
        return config('luxbet.providers.simulator.available')
            ? new ProviderHealth(true, 0, 'Local simulator running in this application.')
            : new ProviderHealth(false, null, 'Simulator switched off (LUXBET_SIMULATOR_AVAILABLE=false).');
    }

    private function parse(string $body): VerifiedProviderEvent
    {
        try {
            $data = json_decode($body, true, 4, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw InvalidProviderWebhook::schema('body is not valid JSON');
        }

        if (! is_array($data)) {
            throw InvalidProviderWebhook::schema('body must be an object');
        }

        $eventId = $data['event_id'] ?? null;
        $type = is_string($data['type'] ?? null) ? DemoEventType::tryFrom($data['type']) : null;
        $session = $data['session_reference'] ?? null;
        $round = $data['round_id'] ?? null;
        $amount = $data['amount'] ?? null;

        if (! is_string($eventId) || preg_match('/^[A-Za-z0-9_.:\-]{1,128}$/', $eventId) !== 1) {
            throw InvalidProviderWebhook::schema('event_id');
        }

        if ($type === null) {
            throw InvalidProviderWebhook::schema('unsupported event type (only demo events are accepted)');
        }

        if (! is_string($session) || ! is_string($round) || $session === '' || $round === '' || strlen($round) > 100) {
            throw InvalidProviderWebhook::schema('session_reference or round_id');
        }

        if (! is_int($amount) || $amount <= 0 || $amount > (int) config('luxbet.demo_wallet.max_transaction')) {
            throw InvalidProviderWebhook::schema('amount');
        }

        try {
            $occurredAt = CarbonImmutable::parse(is_string($data['occurred_at'] ?? null) ? $data['occurred_at'] : 'invalid');
        } catch (Throwable) {
            throw InvalidProviderWebhook::schema('occurred_at');
        }

        return new VerifiedProviderEvent($eventId, $type, $session, $round, $amount, $occurredAt, hash('sha256', $body));
    }
}
