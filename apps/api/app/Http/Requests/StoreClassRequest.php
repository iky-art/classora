<?php

namespace App\Http\Requests;

class StoreClassRequest extends TenantRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'academic_year_id' => ['required', 'integer', $this->inSchool('academic_years')],
            'grade_id' => ['required', 'integer', $this->inSchool('grades')],
            'department_id' => ['nullable', 'integer', $this->inSchool('departments')],
            'homeroom_teacher_id' => ['nullable', 'integer', $this->inSchool('teachers')],
        ];
    }
}
