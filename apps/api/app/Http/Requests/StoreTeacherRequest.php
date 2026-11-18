<?php

namespace App\Http\Requests;

class StoreTeacherRequest extends TenantRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'employee_number' => ['nullable', 'string', 'max:40', $this->uniqueInSchool('teachers', 'employee_number')],
            'membership_id' => ['nullable', 'integer', $this->inSchool('school_memberships')],
        ];
    }
}
