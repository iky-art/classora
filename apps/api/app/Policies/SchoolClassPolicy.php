<?php

namespace App\Policies;

class SchoolClassPolicy extends TenantPolicy
{
    protected string $resource = 'classes';
}
