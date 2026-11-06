<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\Models\AuditLog;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only audit trail (audit.view). Metadata was redacted when written.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'action' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9_.]+$/'],
            'actor' => ['nullable', 'string', 'max:191'],
        ]);
        $action = $validated['action'] ?? null;
        $actor = trim((string) ($validated['actor'] ?? ''));

        $logs = AuditLog::query()
            ->when($action, fn (Builder $query, string $action) => $query->where('action', 'like', addcslashes($action, '%_\\').'%'))
            ->when($actor !== '', fn (Builder $query) => $query->whereIn(
                'actor_user_id',
                User::query()->whereRaw('lower(email) = ?', [mb_strtolower($actor)])->select('id'),
            ))
            ->orderByDesc('created_at')
            ->paginate(40)
            ->withQueryString();

        $actorEmails = User::query()
            ->whereKey(collect($logs->items())->pluck('actor_user_id')->filter()->unique()->values()->all())
            ->pluck('email', 'id');

        return Inertia::render('admin/AuditLog', [
            'logs' => $logs->through(fn (AuditLog $log): array => [
                'id' => $log->id,
                'action' => $log->action,
                'actor' => $log->actor_user_id !== null ? ($actorEmails[$log->actor_user_id] ?? 'Deleted account') : 'System',
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
                'reason' => $log->reason,
                'metadata' => $log->metadata,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at->toIso8601String(),
            ]),
            'filters' => ['action' => $action, 'actor' => $actor],
        ]);
    }
}
