<?php

namespace App\Domain\Audit;

use App\Domain\Audit\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Writes append-only audit records. Metadata keys that look like secrets or
 * credentials are redacted before storage.
 */
class AuditLogger
{
    private const REDACTED = '[redacted]';

    private const SENSITIVE_KEY_PATTERN = '/pass(word)?|secret|token|key|credential|authorization|cookie|card|iban|account_number/i';

    public function __construct(private readonly ?Request $request = null) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $action,
        ?Model $subject = null,
        ?Authenticatable $actor = null,
        ?string $reason = null,
        array $metadata = [],
    ): AuditLog {
        $actor ??= $this->request?->user();
        $actorId = $actor?->getAuthIdentifier();

        return AuditLog::query()->create([
            'actor_user_id' => is_string($actorId) ? $actorId : null,
            'action' => $action,
            'subject_type' => $subject !== null ? $subject->getMorphClass() : null,
            'subject_id' => $subject !== null ? (string) $subject->getKey() : null,
            'ip_address' => $this->request?->ip(),
            'user_agent' => $this->request !== null ? Str::limit((string) $this->request->userAgent(), 500, '') : null,
            'reason' => $reason,
            'metadata' => $this->redact($metadata),
        ]);
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && preg_match(self::SENSITIVE_KEY_PATTERN, $key) === 1) {
                $data[$key] = self::REDACTED;
            } elseif (is_array($value)) {
                $data[$key] = $this->redact($value);
            }
        }

        return $data;
    }
}
