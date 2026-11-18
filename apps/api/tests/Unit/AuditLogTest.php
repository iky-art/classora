<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_is_append_only(): void
    {
        $log = AuditLog::create(['action' => 'test']);

        $this->expectException(LogicException::class);
        $log->update(['action' => 'changed']);
    }
}
