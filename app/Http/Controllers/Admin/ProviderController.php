<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\AuditLogger;
use App\Domain\Games\Models\GameProvider;
use App\Domain\Identity\Authorization\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateProviderStatusRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Provider status for staff. Never shows URLs, credentials or secret
 * references, and offers no way to switch a provider to production.
 */
class ProviderController extends Controller
{
    public function index(Request $request): Response
    {
        $providers = GameProvider::query()
            ->withCount('games')
            ->orderBy('name')
            ->get()
            ->map(fn (GameProvider $provider): array => [
                'id' => $provider->id,
                'name' => $provider->name,
                'environment' => $provider->environment->value,
                'status' => $provider->status->value,
                'supports_demo' => $provider->supports_demo,
                'is_simulator' => $provider->is_simulator,
                'can_serve_demo' => $provider->canServeDemo(),
                'games_count' => (int) $provider->getAttribute('games_count'),
                'credentials_configured' => $provider->credentials_secret_reference !== null,
            ])
            ->all();

        return Inertia::render('admin/providers/Index', [
            'providers' => $providers,
            'canManage' => $request->user()?->can(Permission::ProvidersManage->value) === true,
        ]);
    }

    public function updateStatus(UpdateProviderStatusRequest $request, GameProvider $provider, AuditLogger $audit): RedirectResponse
    {
        $from = $provider->status;
        $to = $request->status();

        if ($from !== $to) {
            DB::transaction(function () use ($provider, $from, $to, $request, $audit): void {
                $provider->forceFill(['status' => $to])->save();

                $audit->record('provider.status_changed', subject: $provider, reason: trim($request->string('reason')->toString()), metadata: [
                    'from' => $from->value,
                    'to' => $to->value,
                ]);
            });
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Provider status updated.']);

        return back();
    }
}
