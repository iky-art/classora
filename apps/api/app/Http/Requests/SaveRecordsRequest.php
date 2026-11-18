<?php

namespace App\Http\Requests;

class SaveRecordsRequest extends TenantRequest
{
    public function rules(): array
    {
        return [
            'records' => ['required', 'array', 'min:1', 'max:200'],
            'records.*.student_id' => ['required', 'integer', 'distinct', $this->inSchool('students')],
            'records.*.status' => ['required', 'in:present,sick,excused,absent'],
            'records.*.note' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:255'], // wajib bila mengubah status yang sudah tercatat
        ];
    }
}
