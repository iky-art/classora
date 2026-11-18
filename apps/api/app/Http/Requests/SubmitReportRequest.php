<?php

namespace App\Http\Requests;

class SubmitReportRequest extends TenantRequest
{
    public function rules(): array
    {
        return [
            'assignment_id' => [$this->route('id') ? 'sometimes' : 'required', 'integer', $this->inSchool('student_operator_assignments')],
            'report_date' => ['sometimes', 'date_format:Y-m-d', 'before_or_equal:today'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.student_id' => ['required', 'integer', 'distinct', $this->inSchool('students')],
            'items.*.status' => ['required', 'in:present,sick,excused,absent'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
