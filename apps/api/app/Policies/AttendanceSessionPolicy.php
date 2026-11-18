<?php

namespace App\Policies;

use App\Models\User;

class AttendanceSessionPolicy extends TenantPolicy
{
    protected string $resource = 'attendance';

    public function create(User $user): bool { return $this->context->can('attendance.record'); }
    public function update(User $user, $model): bool { return $this->context->can('attendance.record'); }
}
