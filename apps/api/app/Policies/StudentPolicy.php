<?php

namespace App\Policies;

class StudentPolicy extends TenantPolicy
{
    protected string $resource = 'students';
}
