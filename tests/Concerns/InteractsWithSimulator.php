<?php

namespace Tests\Concerns;

use App\Domain\GameProviders\Actions\StartDemoSession;
use App\Domain\GameProviders\Models\GameSession;
use App\Domain\GameProviders\Simulator\SimulatorSigner;
use App\Domain\GameProviders\Support\ProviderSecrets;
use App\Domain\Games\Models\Game;
use App\Domain\Games\Models\GameProvider;
use App\Models\User;
use Database\Seeders\LocalSimulatorCatalogueSeeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

trait InteractsWithSimulator
{
    protected function seedSimulator(): void
    {
        $this->seed(LocalSimulatorCatalogueSeeder::class);
    }

    protected function simulator(): GameProvider
    {
        return GameProvider::query()->where('slug', LocalSimulatorCatalogueSeeder::PROVIDER_SLUG)->firstOrFail();
    }

    protected function launchSession(User $player, string $slug = 'candy-crown'): GameSession
    {
        $game = Game::query()->where('slug', $slug)->firstOrFail();

        return app(StartDemoSession::class)($player, $game)['session'];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function event(GameSession $session, string $type, int $amount, array $overrides = []): array
    {
        return [
            'event_id' => 'evt_'.Str::lower(Str::random(12)),
            'type' => $type,
            'session_reference' => $session->session_reference,
            'round_id' => 'round-1',
            'amount' => $amount,
            'occurred_at' => Date::now()->toIso8601String(),
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>|string  $payload
     */
    protected function sendWebhook(array|string $payload, ?int $timestamp = null, ?string $secret = null): TestResponse
    {
        $body = is_string($payload) ? $payload : json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp ??= Date::now()->getTimestamp();
        $secret ??= app(ProviderSecrets::class)->webhookSecret($this->simulator());

        $headers = [];
        foreach (SimulatorSigner::headers($body, $timestamp, $secret) as $name => $value) {
            $headers[$name] = $value;
        }

        return $this->call(
            'POST',
            '/api/v1/webhooks/providers/'.LocalSimulatorCatalogueSeeder::PROVIDER_SLUG,
            server: $this->transformHeadersToServerVars($headers + ['Content-Type' => 'application/json', 'Accept' => 'application/json']),
            content: $body,
        );
    }
}
