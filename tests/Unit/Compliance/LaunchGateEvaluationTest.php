<?php

namespace Tests\Unit\Compliance;

use App\Domain\Compliance\Enums\EnvironmentMode;
use App\Domain\Compliance\LaunchGate\LaunchGate;
use App\Domain\Compliance\Models\LaunchControl;
use Carbon\CarbonImmutable;
use Tests\TestCase;

/**
 * Pure evaluation of the launch-gate rules, using unsaved records so we can
 * describe states the database itself would refuse to store.
 */
class LaunchGateEvaluationTest extends TestCase
{
    private CarbonImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = CarbonImmutable::parse('2026-11-05 12:00:00', 'UTC');
    }

    public function test_even_a_complete_approval_record_stays_closed_without_the_multi_person_workflow(): void
    {
        $reasons = $this->gate()->evaluate($this->approvedRecord(), true, EnvironmentMode::Production, $this->now);

        $this->assertSame([LaunchGate::REASON_MULTI_PERSON_WORKFLOW_MISSING], $reasons);
    }

    public function test_configuration_lock_keeps_the_gate_closed(): void
    {
        $reasons = $this->gate()->evaluate($this->approvedRecord(), false, EnvironmentMode::Production, $this->now);

        $this->assertContains(LaunchGate::REASON_CONFIG_LOCKED, $reasons);
    }

    public function test_missing_record_keeps_the_gate_closed(): void
    {
        $reasons = $this->gate()->evaluate(null, true, EnvironmentMode::Production, $this->now);

        $this->assertContains(LaunchGate::REASON_RECORD_MISSING, $reasons);
    }

    public function test_expired_review_keeps_the_gate_closed(): void
    {
        $record = $this->approvedRecord(['review_due_at' => $this->now->subDay()]);

        $reasons = $this->gate()->evaluate($record, true, EnvironmentMode::Production, $this->now);

        $this->assertContains(LaunchGate::REASON_REVIEW_EXPIRED, $reasons);
    }

    public function test_approval_dated_in_the_future_keeps_the_gate_closed(): void
    {
        $record = $this->approvedRecord(['approved_at' => $this->now->addDay()]);

        $reasons = $this->gate()->evaluate($record, true, EnvironmentMode::Production, $this->now);

        $this->assertContains(LaunchGate::REASON_APPROVED_AT_IN_FUTURE, $reasons);
    }

    public function test_missing_approval_evidence_keeps_the_gate_closed(): void
    {
        $record = $this->approvedRecord([
            'approval_reference' => null,
            'approval_document_path' => '',
            'approved_by' => null,
            'change_reason' => null,
        ]);

        $reasons = $this->gate()->evaluate($record, true, EnvironmentMode::Production, $this->now);

        $this->assertContains(LaunchGate::REASON_APPROVAL_REFERENCE_MISSING, $reasons);
        $this->assertContains(LaunchGate::REASON_APPROVAL_DOCUMENT_MISSING, $reasons);
        $this->assertContains(LaunchGate::REASON_APPROVER_MISSING, $reasons);
        $this->assertContains(LaunchGate::REASON_CHANGE_REASON_MISSING, $reasons);
    }

    public function test_malformed_jurisdictions_keep_the_gate_closed(): void
    {
        foreach ([[], ['mu'], ['MUS'], [123]] as $jurisdictions) {
            $record = $this->approvedRecord(['approved_jurisdictions' => $jurisdictions]);

            $reasons = $this->gate()->evaluate($record, true, EnvironmentMode::Production, $this->now);

            $this->assertTrue(
                in_array(LaunchGate::REASON_NO_JURISDICTIONS, $reasons, true)
                    || in_array(LaunchGate::REASON_INVALID_JURISDICTIONS, $reasons, true),
                'Jurisdictions '.json_encode($jurisdictions).' should keep the gate closed.',
            );
        }
    }

    public function test_non_production_or_mismatched_mode_keeps_the_gate_closed(): void
    {
        $demoRecord = $this->approvedRecord(['environment_mode' => EnvironmentMode::Demo]);

        $this->assertContains(
            LaunchGate::REASON_NOT_PRODUCTION,
            $this->gate()->evaluate($demoRecord, true, EnvironmentMode::Production, $this->now),
        );

        $this->assertContains(
            LaunchGate::REASON_MODE_MISMATCH,
            $this->gate()->evaluate($this->approvedRecord(), true, EnvironmentMode::Staging, $this->now),
        );
    }

    public function test_malformed_record_data_keeps_the_gate_closed(): void
    {
        $record = new LaunchControl;
        $record->setRawAttributes([
            'id' => 1,
            'real_money_enabled' => true,
            'environment_mode' => 'casino-royale',
            'approved_jurisdictions' => '{not json',
            'version' => 1,
        ]);

        $reasons = $this->gate()->evaluate($record, true, EnvironmentMode::Production, $this->now);

        $this->assertContains(LaunchGate::REASON_RECORD_UNREADABLE, $reasons);
    }

    public function test_unknown_configured_mode_falls_back_to_demo(): void
    {
        $this->assertSame(EnvironmentMode::Demo, EnvironmentMode::fromConfig('live'));
        $this->assertSame(EnvironmentMode::Demo, EnvironmentMode::fromConfig(null));
        $this->assertSame(EnvironmentMode::Production, EnvironmentMode::fromConfig('production'));
    }

    private function gate(): LaunchGate
    {
        return $this->app->make(LaunchGate::class);
    }

    /**
     * An in-memory record with every approval field filled in. Never saved:
     * the database and the model both refuse real_money_enabled = true.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function approvedRecord(array $overrides = []): LaunchControl
    {
        $record = new LaunchControl;
        $record->setRawAttributes(['id' => 1, 'version' => 1]);

        $record->forceFill([
            'real_money_enabled' => true,
            'environment_mode' => EnvironmentMode::Production,
            'approved_jurisdictions' => ['MU'],
            'approval_reference' => 'TEST-REF-001',
            'approval_document_path' => 'approvals/test-ref-001.pdf',
            'approved_by' => 42,
            'approved_at' => $this->now->subMonth(),
            'review_due_at' => $this->now->addMonths(6),
            'change_reason' => 'Fictional test fixture — not a real approval.',
            ...$overrides,
        ]);

        return $record;
    }
}
