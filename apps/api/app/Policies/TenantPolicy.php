<?php

namespace App\Policies;

use App\Models\User;
use App\Tenancy\TenantContext;

/** Semua keputusan akses berasal dari permission membership aktif di tenant saat ini. */
abstract class TenantPolicy
{
    protected string $resource;

    public function __construct(protected TenantContext $context)
    {
    }

    public function viewAny(User $user): bool { return $this->context->can($this->resource.'.view'); }
    public function view(User $user, $model): bool { return $this->context->can($this->resource.'.view'); }
    public function create(User $user): bool { return $this->context->can($this->resource.'.manage'); }
    public function update(User $user, $model): bool { return $this->context->can($this->resource.'.manage'); }
    public function delete(User $user, $model): bool { return $this->context->can($this->resource.'.manage'); }
}
