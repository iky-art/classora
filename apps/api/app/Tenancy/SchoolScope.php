<?php

namespace App\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/** Fail-closed: tanpa tenant context, query tenant tidak mengembalikan apa pun. */
class SchoolScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        if ($context->has()) {
            $builder->where($model->getTable().'.school_id', $context->schoolId());
        } else {
            $builder->whereRaw('1 = 0');
        }
    }
}
