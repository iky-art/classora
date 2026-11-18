<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;

final class Audit
{
    public static function log(string $action, ?Model $subject = null, array $meta = []): void
    {
        $context = app(TenantContext::class);

        AuditLog::create([
            'school_id' => $context->has() ? $context->schoolId() : null,
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'meta' => $meta ?: null,
            'ip' => request()?->ip(),
        ]);
    }
}
