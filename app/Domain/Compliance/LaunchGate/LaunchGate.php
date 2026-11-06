<?php

namespace App\Domain\Compliance\LaunchGate;

use App\Domain\Compliance\Enums\EnvironmentMode;
use App\Domain\Compliance\Models\LaunchControl;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Server-side enforcement point for the real-money launch gate.
 *
 * The gate fails closed: it reports "enabled" only when EVERY condition
 * below holds, and any missing, malformed, expired, unreadable or
 * inconsistent data keeps it disabled. In this release the configuration
 * lock and the absence of a multi-person approval workflow mean it can
 * never report enabled.
 */
class LaunchGate
{
    public const REASON_CONFIG_LOCKED = 'config_locked';

    public const REASON_MULTI_PERSON_WORKFLOW_MISSING = 'multi_person_approval_workflow_not_implemented';

    public const REASON_RECORD_MISSING = 'record_missing';

    public const REASON_RECORD_UNREADABLE = 'record_unreadable';

    public const REASON_RECORD_FLAG_OFF = 'record_flag_off';

    public const REASON_NOT_PRODUCTION = 'environment_not_production';

    public const REASON_MODE_MISMATCH = 'environment_mode_mismatch';

    public const REASON_NO_JURISDICTIONS = 'no_approved_jurisdictions';

    public const REASON_INVALID_JURISDICTIONS = 'invalid_approved_jurisdictions';

    public const REASON_APPROVAL_REFERENCE_MISSING = 'approval_reference_missing';

    public const REASON_APPROVAL_DOCUMENT_MISSING = 'approval_document_missing';

    public const REASON_APPROVER_MISSING = 'approver_missing';

    public const REASON_APPROVED_AT_MISSING = 'approved_at_missing';

    public const REASON_APPROVED_AT_IN_FUTURE = 'approved_at_in_future';

    public const REASON_REVIEW_DATE_MISSING = 'review_due_at_missing';

    public const REASON_REVIEW_EXPIRED = 'review_expired';

    public const REASON_CHANGE_REASON_MISSING = 'change_reason_missing';

    public const REASON_INVALID_VERSION = 'invalid_version';

    public function __construct(private readonly Config $config) {}

    public function isRealMoneyEnabled(): bool
    {
        return $this->status()->realMoneyEnabled;
    }

    public function status(): LaunchGateStatus
    {
        $mode = EnvironmentMode::fromConfig($this->config->get('luxbet.environment_mode'));
        $configAllows = $this->config->get('luxbet.real_money.enabled') === true;

        try {
            $record = LaunchControl::current();
        } catch (Throwable $e) {
            Log::warning('Launch-control record could not be read; real-money gate stays closed.', [
                'exception' => $e::class,
            ]);

            return new LaunchGateStatus(
                realMoneyEnabled: false,
                blockingReasons: array_values(array_unique([
                    ...$this->configReasons($configAllows),
                    self::REASON_RECORD_UNREADABLE,
                ])),
                environmentMode: $mode,
                recordPresent: false,
                recordVersion: null,
                reviewDueAt: null,
            );
        }

        $reasons = $this->evaluate($record, $configAllows, $mode, Date::now());

        return new LaunchGateStatus(
            realMoneyEnabled: $reasons === [],
            blockingReasons: $reasons,
            environmentMode: $mode,
            recordPresent: $record !== null,
            recordVersion: $this->safeVersion($record),
            reviewDueAt: $this->safeReviewDate($record),
        );
    }

    /**
     * Pure evaluation of every condition. Returns the reasons the gate is
     * closed; an empty list is the only "enabled" outcome.
     *
     * @return list<string>
     */
    public function evaluate(?LaunchControl $record, bool $configAllows, EnvironmentMode $configuredMode, CarbonInterface $now): array
    {
        $reasons = $this->configReasons($configAllows);

        if ($record === null) {
            $reasons[] = self::REASON_RECORD_MISSING;

            return $reasons;
        }

        try {
            if ($record->real_money_enabled !== true) {
                $reasons[] = self::REASON_RECORD_FLAG_OFF;
            }

            $recordMode = $record->environment_mode;

            if ($recordMode !== EnvironmentMode::Production || $configuredMode !== EnvironmentMode::Production) {
                $reasons[] = self::REASON_NOT_PRODUCTION;
            }

            if ($recordMode !== $configuredMode) {
                $reasons[] = self::REASON_MODE_MISMATCH;
            }

            $reasons = [...$reasons, ...$this->jurisdictionReasons($record->approved_jurisdictions)];

            if (blank($record->approval_reference)) {
                $reasons[] = self::REASON_APPROVAL_REFERENCE_MISSING;
            }

            if (blank($record->approval_document_path)) {
                $reasons[] = self::REASON_APPROVAL_DOCUMENT_MISSING;
            }

            if ($record->approved_by === null) {
                $reasons[] = self::REASON_APPROVER_MISSING;
            }

            if ($record->approved_at === null) {
                $reasons[] = self::REASON_APPROVED_AT_MISSING;
            } elseif ($record->approved_at->greaterThan($now)) {
                $reasons[] = self::REASON_APPROVED_AT_IN_FUTURE;
            }

            if ($record->review_due_at === null) {
                $reasons[] = self::REASON_REVIEW_DATE_MISSING;
            } elseif ($record->review_due_at->lessThanOrEqualTo($now)) {
                $reasons[] = self::REASON_REVIEW_EXPIRED;
            }

            if (blank($record->change_reason)) {
                $reasons[] = self::REASON_CHANGE_REASON_MISSING;
            }

            if ($record->version < 1) {
                $reasons[] = self::REASON_INVALID_VERSION;
            }
        } catch (Throwable) {
            // Malformed data (bad enum value, unparseable dates or JSON) keeps the gate closed.
            $reasons[] = self::REASON_RECORD_UNREADABLE;
        }

        return array_values(array_unique($reasons));
    }

    /**
     * @return list<string>
     */
    private function configReasons(bool $configAllows): array
    {
        $reasons = [self::REASON_MULTI_PERSON_WORKFLOW_MISSING];

        if (! $configAllows) {
            array_unshift($reasons, self::REASON_CONFIG_LOCKED);
        }

        return $reasons;
    }

    /**
     * @return list<string>
     */
    private function jurisdictionReasons(mixed $jurisdictions): array
    {
        if (! is_array($jurisdictions) || $jurisdictions === []) {
            return [self::REASON_NO_JURISDICTIONS];
        }

        foreach ($jurisdictions as $code) {
            if (! is_string($code) || preg_match('/^[A-Z]{2}$/', $code) !== 1) {
                return [self::REASON_INVALID_JURISDICTIONS];
            }
        }

        return [];
    }

    private function safeVersion(?LaunchControl $record): ?int
    {
        try {
            return $record?->version;
        } catch (Throwable) { // @phpstan-ignore catch.neverThrown (casting a corrupt row can throw; the gate must still fail closed)
            return null;
        }
    }

    private function safeReviewDate(?LaunchControl $record): ?CarbonImmutable
    {
        try {
            return $record?->review_due_at;
        } catch (Throwable) { // @phpstan-ignore catch.neverThrown (as above)
            return null;
        }
    }
}
