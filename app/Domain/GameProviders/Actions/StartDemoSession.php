<?php

namespace App\Domain\GameProviders\Actions;

use App\Domain\DemoWallet\Actions\EnsureDemoWallet;
use App\Domain\GameProviders\Enums\GameSessionStatus;
use App\Domain\GameProviders\Exceptions\DemoSessionRefused;
use App\Domain\GameProviders\Exceptions\ProviderUnavailable;
use App\Domain\GameProviders\Models\GameSession;
use App\Domain\GameProviders\Support\LaunchUrlGuard;
use App\Domain\GameProviders\Support\ProviderAdapterRegistry;
use App\Domain\GameProviders\Support\ReturnUrlGuard;
use App\Domain\Games\Models\Game;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * The game launch flow (instructions.md §7):
 * account active → game enabled with demo → provider serving demo in
 * sandbox → short-lived session → launch URL from the adapter → URL checked
 * against the allowlist. Any failure leaves no usable session and charges
 * nothing.
 */
class StartDemoSession
{
    public function __construct(
        private readonly ProviderAdapterRegistry $adapters,
        private readonly EnsureDemoWallet $ensureWallet,
        private readonly LaunchUrlGuard $launchUrls,
        private readonly ReturnUrlGuard $returnUrls,
    ) {}

    /**
     * @return array{session: GameSession, launch_url: string}
     */
    public function __invoke(User $user, Game $game, ?string $returnUrl = null): array
    {
        $game->loadMissing('provider');

        if (! $user->isActive()) {
            throw new DemoSessionRefused('This account is not active.');
        }

        if (! $game->isPlayable()) {
            throw new DemoSessionRefused('This game cannot be played in demo mode right now.');
        }

        $provider = $game->provider;

        if (! $this->adapters->has($provider)) {
            throw new DemoSessionRefused('This game cannot be played in demo mode right now.');
        }

        $wallet = ($this->ensureWallet)($user);

        if (! $wallet->isActive()) {
            throw new DemoSessionRefused('Your demo wallet is not active.');
        }

        $session = new GameSession;
        $session->forceFill([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'provider_id' => $provider->id,
            'demo_wallet_id' => $wallet->id,
            'session_reference' => 'gs_'.Str::lower(Str::random(32)),
            'mode' => 'demo',
            'status' => GameSessionStatus::Created,
            'expires_at' => Date::now()->addMinutes((int) config('luxbet.providers.session_minutes')),
        ])->save();

        try {
            $result = $this->adapters->for($provider)->createDemoSession($session, $this->returnUrls->resolve($returnUrl));
            $this->launchUrls->assertAllowed($result->launchUrl);
        } catch (ProviderUnavailable|DemoSessionRefused $e) {
            $this->fail($session, $e->getMessage());

            throw new DemoSessionRefused($e instanceof ProviderUnavailable
                ? 'The game provider is not responding. Please try again in a few minutes.'
                : 'This game could not be started safely. Please try again later.');
        } catch (Throwable $e) {
            Log::error('Unexpected error starting a demo session.', ['exception' => $e::class, 'session' => $session->session_reference]);
            $this->fail($session, 'Unexpected error');

            throw new DemoSessionRefused('This game could not be started. Please try again later.');
        }

        $session->forceFill([
            'status' => GameSessionStatus::Launched,
            'launched_at' => Date::now(),
            'provider_session_reference' => $result->providerSessionReference,
        ])->save();

        return ['session' => $session, 'launch_url' => $result->launchUrl];
    }

    private function fail(GameSession $session, string $reason): void
    {
        $session->forceFill([
            'status' => GameSessionStatus::Failed,
            'failure_reason' => Str::limit($reason, 250, ''),
            'ended_at' => Date::now(),
        ])->save();
    }
}
