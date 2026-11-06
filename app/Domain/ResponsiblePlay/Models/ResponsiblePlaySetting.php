<?php

namespace App\Domain\ResponsiblePlay\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $user_id
 * @property int|null $demo_session_reminder_minutes
 * @property bool $marketing_opt_in
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class ResponsiblePlaySetting extends Model
{
    use HasUuids;

    public const REMINDER_OPTIONS = [15, 30, 45, 60, 90];

    public const DEFAULT_REMINDER_MINUTES = 45;

    /**
     * @var list<string>
     */
    protected $fillable = ['demo_session_reminder_minutes', 'marketing_opt_in'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'demo_session_reminder_minutes' => self::DEFAULT_REMINDER_MINUTES,
        'marketing_opt_in' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'demo_session_reminder_minutes' => 'integer',
            'marketing_opt_in' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The user's settings, or unsaved defaults when they have none yet.
     */
    public static function forUser(User $user): self
    {
        return self::query()->firstOrNew(['user_id' => $user->id]);
    }
}
