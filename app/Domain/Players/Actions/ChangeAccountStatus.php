<?php

namespace App\Domain\Players\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Identity\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The only way account status changes. Every change is audited with a
 * reason, and leaving `active` revokes remember-me tokens so the user is
 * signed out everywhere (EnsureAccountIsActive ends live sessions).
 */
class ChangeAccountStatus
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function __invoke(User $user, UserStatus $status, string $reason, ?User $actor = null): User
    {
        $from = $user->status;

        if ($from === $status) {
            return $user;
        }

        return DB::transaction(function () use ($user, $status, $from, $reason, $actor): User {
            $user->forceFill(['status' => $status]);

            if (! $status->canSignIn()) {
                $user->forceFill(['remember_token' => Str::random(60)]);
            }

            $user->save();

            $this->audit->record('account.status_changed', subject: $user, actor: $actor, reason: $reason, metadata: [
                'from' => $from->value,
                'to' => $status->value,
            ]);

            return $user;
        });
    }
}
