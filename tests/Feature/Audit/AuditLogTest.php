<?php

namespace Tests\Feature\Audit;

use App\Domain\Audit\AuditLogger;
use App\Domain\Audit\Models\AuditLog;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_metadata_is_redacted(): void
    {
        $log = app(AuditLogger::class)->record('test.event', metadata: [
            'password' => 'hunter2',
            'api_token' => 'abc',
            'nested' => ['client_secret' => 'xyz', 'note' => 'kept'],
            'from' => 'active',
        ]);

        // jsonb does not preserve key order, so compare without it.
        $this->assertEquals([
            'password' => '[redacted]',
            'api_token' => '[redacted]',
            'nested' => ['client_secret' => '[redacted]', 'note' => 'kept'],
            'from' => 'active',
        ], $log->fresh()?->metadata);
    }

    public function test_the_model_refuses_updates_and_deletes(): void
    {
        $log = app(AuditLogger::class)->record('test.event');

        try {
            $log->forceFill(['action' => 'tampered'])->save();
            $this->fail('Updating an audit log should throw.');
        } catch (LogicException) {
            // expected
        }

        $this->expectException(LogicException::class);
        $log->delete();
    }

    public function test_the_database_refuses_updates(): void
    {
        app(AuditLogger::class)->record('test.event');

        $this->expectException(QueryException::class);

        DB::table('audit_logs')->update(['action' => 'tampered']);
    }

    public function test_the_database_refuses_deletes(): void
    {
        app(AuditLogger::class)->record('test.event');

        $this->expectException(QueryException::class);

        DB::table('audit_logs')->delete();
    }

    public function test_entries_survive_without_a_foreign_key_to_users(): void
    {
        $log = app(AuditLogger::class)->record('test.event');

        $this->assertInstanceOf(AuditLog::class, $log);
        $this->assertNull($log->actor_user_id);
    }
}
