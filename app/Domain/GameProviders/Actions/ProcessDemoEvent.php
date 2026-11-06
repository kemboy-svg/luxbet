<?php

namespace App\Domain\GameProviders\Actions;

use App\Domain\DemoWallet\Actions\RecordDemoTransaction;
use App\Domain\DemoWallet\Data\DemoTransactionRequest;
use App\Domain\DemoWallet\Enums\DemoTransactionSource;
use App\Domain\DemoWallet\Enums\DemoTransactionType;
use App\Domain\DemoWallet\Exceptions\DemoWalletException;
use App\Domain\GameProviders\Data\IngestResult;
use App\Domain\GameProviders\Data\VerifiedProviderEvent;
use App\Domain\GameProviders\Enums\DemoEventType;
use App\Domain\GameProviders\Enums\GameSessionStatus;
use App\Domain\GameProviders\Enums\WebhookProcessingStatus;
use App\Domain\GameProviders\Models\GameSession;
use App\Domain\GameProviders\Models\ProviderWebhookEvent;
use App\Domain\Games\Models\GameProvider;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Applies a verified provider event to the demo ledger.
 *
 * The wallet is always taken from our own session record, never from the
 * event. Bets need an open session; wins may arrive late or before their
 * bet (out-of-order delivery); refunds must match an earlier bet in the same
 * round. A rejected event changes no balance.
 */
class ProcessDemoEvent
{
    public function __construct(private readonly RecordDemoTransaction $record) {}

    public function __invoke(GameProvider $provider, VerifiedProviderEvent $event, ProviderWebhookEvent $receipt): IngestResult
    {
        $session = GameSession::query()
            ->with('game')
            ->where('provider_id', $provider->id)
            ->where('session_reference', $event->sessionReference)
            ->first();

        if ($session === null) {
            return $this->reject($receipt, 'unknown_session');
        }

        $receipt->forceFill(['game_session_id' => $session->id]);

        $refusal = $this->refusal($session, $event);

        if ($refusal !== null) {
            return $this->reject($receipt, $refusal);
        }

        try {
            $result = ($this->record)(new DemoTransactionRequest(
                walletId: $session->demo_wallet_id,
                type: $event->type->ledgerType(),
                amount: $event->amount,
                source: DemoTransactionSource::ProviderSandbox,
                description: Str::limit($session->game->name.' · round '.$event->roundReference, 240, ''),
                idempotencyKey: "provider-event:{$provider->id}:{$event->eventId}",
                externalReference: $this->roundKey($session, $event->roundReference),
            ));
        } catch (DemoWalletException $e) {
            return $this->reject($receipt, Str::limit('wallet_refused: '.$e->getMessage(), 250, ''));
        } catch (Throwable $e) {
            Log::error('Failed to apply a provider demo event.', ['exception' => $e::class, 'event_id' => $event->eventId]);
            $receipt->forceFill([
                'processing_status' => WebhookProcessingStatus::Failed,
                'failure_reason' => 'processing_error',
                'processed_at' => Date::now(),
            ])->save();

            return new IngestResult('failed', $receipt, reason: 'processing_error');
        }

        $receipt->forceFill([
            'processing_status' => WebhookProcessingStatus::Processed,
            'processed_at' => Date::now(),
        ])->save();

        return new IngestResult('processed', $receipt, $result->transaction);
    }

    private function refusal(GameSession $session, VerifiedProviderEvent $event): ?string
    {
        if ($session->status === GameSessionStatus::Failed || $session->status === GameSessionStatus::Created) {
            return 'session_not_launched';
        }

        if ($event->type === DemoEventType::Bet && ! $session->isOpen()) {
            return 'session_closed';
        }

        if ($event->type === DemoEventType::Refund) {
            $betTotal = $session->wallet->transactions()
                ->where('external_reference', $this->roundKey($session, $event->roundReference))
                ->where('type', DemoTransactionType::Bet->value)
                ->sum('amount_minor');

            $refunded = $session->wallet->transactions()
                ->where('external_reference', $this->roundKey($session, $event->roundReference))
                ->where('type', DemoTransactionType::Refund->value)
                ->sum('amount_minor');

            if ((int) $betTotal === 0) {
                return 'refund_without_bet';
            }

            if ((int) $refunded + $event->amount > (int) $betTotal) {
                return 'refund_exceeds_bet';
            }
        }

        return null;
    }

    private function roundKey(GameSession $session, string $round): string
    {
        return Str::limit($session->session_reference.':'.$round, 250, '');
    }

    private function reject(ProviderWebhookEvent $receipt, string $reason): IngestResult
    {
        $receipt->forceFill([
            'processing_status' => WebhookProcessingStatus::Rejected,
            'failure_reason' => $reason,
            'processed_at' => Date::now(),
        ])->save();

        return new IngestResult('rejected', $receipt, reason: $reason);
    }
}
