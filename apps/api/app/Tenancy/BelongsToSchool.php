<?php

namespace App\Tenancy;

trait BelongsToSchool
{
    protected static function bootBelongsToSchool(): void
    {
        static::addGlobalScope(new SchoolScope());

        // school_id SELALU diambil dari tenant context, menimpa input apa pun.
        static::creating(function ($model) {
            $model->school_id = app(TenantContext::class)->schoolId();
        });
    }
}
