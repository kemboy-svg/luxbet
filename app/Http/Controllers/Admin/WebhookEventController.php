<?php

namespace App\Http\Controllers\Admin;

use App\Domain\GameProviders\Enums\WebhookProcessingStatus;
use App\Domain\GameProviders\Models\ProviderWebhookEvent;
use App\Domain\Games\Models\GameProvider;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only log of provider events (providers.view). Shows metadata only:
 * raw payloads are never stored, just their hash.
 */
class WebhookEventController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(WebhookProcessingStatus::class)],
            'provider' => ['nullable', 'uuid'],
        ]);
        $status = $validated['status'] ?? null;
        $provider = $validated['provider'] ?? null;

        $events = ProviderWebhookEvent::query()
            ->with(['provider', 'gameSession'])
            ->when($status, fn ($query, string $status) => $query->where('processing_status', $status))
            ->when($provider, fn ($query, string $provider) => $query->where('provider_id', $provider))
            ->orderByDesc('received_at')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (ProviderWebhookEvent $event): array => [
                'id' => $event->id,
                'provider' => $event->provider->name,
                'event_id' => $event->event_id,
                'event_type' => $event->event_type,
                'session_reference' => $event->gameSession?->session_reference,
                'round_reference' => $event->round_reference,
                'amount' => $event->amount,
                'status' => $event->processing_status->value,
                'failure_reason' => $event->failure_reason,
                'received_at' => $event->received_at->toIso8601String(),
                'processed_at' => $event->processed_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/WebhookEvents', [
            'events' => $events,
            'filters' => ['status' => $status, 'provider' => $provider],
            'statuses' => array_map(fn (WebhookProcessingStatus $s): string => $s->value, WebhookProcessingStatus::cases()),
            'providers' => GameProvider::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (GameProvider $p): array => ['id' => $p->id, 'name' => $p->name])
                ->all(),
        ]);
    }
}
