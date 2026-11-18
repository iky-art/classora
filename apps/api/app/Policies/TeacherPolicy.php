<?php

namespace App\Policies;

class TeacherPolicy extends TenantPolicy
{
    protected string $resource = 'teachers';
}
