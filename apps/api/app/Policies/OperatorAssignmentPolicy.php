<?php

namespace App\Policies;

use App\Models\User;

class OperatorAssignmentPolicy extends TenantPolicy
{
    protected string $resource = 'operators';

    public function viewAny(User $user): bool { return true; } // hasil difilter per peran di controller
    public function create(User $user): bool { return $this->context->can('operators.assign'); }
    public function update(User $user, $model): bool { return $this->context->can('operators.assign'); }
}
