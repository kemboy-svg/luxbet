<?php

namespace App\Domain\Audit\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Append-only audit record. Create through App\Domain\Audit\AuditLogger.
 * Updates and deletes throw here and are rejected by a database trigger.
 *
 * @property string $id
 * @property string|null $actor_user_id
 * @property string $action
 * @property string|null $subject_type
 * @property string|null $subject_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $reason
 * @property array<string, mixed> $metadata
 * @property CarbonImmutable $created_at
 */
class AuditLog extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Audit logs are append-only.');
        });

        static::deleting(function (): void {
            throw new LogicException('Audit logs are append-only.');
        });
    }
}
