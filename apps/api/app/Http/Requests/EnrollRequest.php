<?php

namespace App\Http\Requests;

class EnrollRequest extends TenantRequest
{
    public function rules(): array
    {
        return [
            'student_ids' => ['required', 'array', 'min:1', 'max:200'],
            'student_ids.*' => ['integer', 'distinct', $this->inSchool('students')],
            'joined_on' => ['required', 'date'],
        ];
    }
}
