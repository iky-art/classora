<?php

namespace App\Http\Requests;

class StoreAssignmentRequest extends TenantRequest
{
    public function rules(): array
    {
        return [
            'class_id' => ['required', 'integer', $this->inSchool('classes')],
            'student_id' => ['required', 'integer', $this->inSchool('students')],
            'mode' => ['required', 'in:report_only,full_attendance'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
        ];
    }
}
