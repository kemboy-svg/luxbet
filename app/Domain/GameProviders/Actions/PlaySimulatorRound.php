<?php

namespace App\Domain\GameProviders\Actions;

use App\Domain\GameProviders\Data\IngestResult;
use App\Domain\GameProviders\Enums\DemoEventType;
use App\Domain\GameProviders\Exceptions\DemoSessionRefused;
use App\Domain\GameProviders\Models\GameSession;
use App\Domain\GameProviders\Simulator\SimulatorOutcome;
use App\Domain\GameProviders\Simulator\SimulatorRoundEngine;
use App\Domain\GameProviders\Simulator\SimulatorSigner;
use App\Domain\GameProviders\Support\ProviderSecrets;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

/**
 * Plays one round on the LOCAL SIMULATOR. It behaves like a remote
 * provider: it produces signed demo.bet / demo.win events and submits them
 * through the same ingestion path as real webhooks, so signature checks,
 * receipts and idempotency are exercised on every round.
 */
class PlaySimulatorRound
{
    public function __construct(
        private readonly SimulatorRoundEngine $engine,
        private readonly IngestProviderWebhook $ingest,
        private readonly ProviderSecrets $secrets,
    ) {}

    /**
     * @return array{round_id: string, outcome: SimulatorOutcome, balance: int}
     */
    public function __invoke(GameSession $session, int $stake): array
    {
        $session->loadMissing(['game', 'provider', 'wallet']);

        if (! $session->provider->is_simulator) {
            throw new DemoSessionRefused('This session is not on the local simulator.');
        }

        if (! $session->isOpen()) {
            throw new DemoSessionRefused('This demo session has ended. Start a new one from the game page.');
        }

        /** @var list<int> $stakes */
        $stakes = config('luxbet.providers.simulator.stakes');

        if (! in_array($stake, $stakes, true)) {
            throw new DemoSessionRefused('Choose one of the available stakes.');
        }

        $outcome = $this->engine->play($session->game->category, $stake, $session->game->code);
        $roundId = 'r_'.Str::lower(Str::random(16));

        $bet = $this->emit($session, DemoEventType::Bet, $roundId, $stake);

        if ($bet->status !== 'processed') {
            throw new DemoSessionRefused(str_contains((string) $bet->reason, 'not enough')
                ? 'Not enough demo credits for that stake. You can reset your credits from the Demo wallet page.'
                : 'This round could not be played. Please try again.');
        }

        if ($outcome->payout > 0) {
            $this->emit($session, DemoEventType::Win, $roundId, $outcome->payout);
        }

        return [
            'round_id' => $roundId,
            'outcome' => $outcome,
            'balance' => $session->wallet->refresh()->balance_minor,
        ];
    }

    private function emit(GameSession $session, DemoEventType $type, string $roundId, int $amount): IngestResult
    {
        $body = json_encode([
            'event_id' => 'sim_'.Str::lower((string) Str::uuid()),
            'type' => $type->value,
            'session_reference' => $session->session_reference,
            'round_id' => $roundId,
            'amount' => $amount,
            'occurred_at' => Date::now()->toIso8601String(),
        ], JSON_THROW_ON_ERROR);

        $headers = SimulatorSigner::headers($body, Date::now()->getTimestamp(), $this->secrets->webhookSecret($session->provider));

        return ($this->ingest)($session->provider, $body, $headers);
    }
}
