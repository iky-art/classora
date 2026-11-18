<?php

namespace App\Http\Requests;

class ReviewRequest extends TenantRequest
{
    public function rules(): array
    {
        $rejecting = str_ends_with($this->path(), '/reject');

        return [
            'note' => [$rejecting ? 'required' : 'nullable', 'string', 'max:500'],
            'overrides' => ['sometimes', 'array', 'max:200'],
            'overrides.*.student_id' => ['required', 'integer', 'distinct', $this->inSchool('students')],
            'overrides.*.status' => ['required', 'in:present,sick,excused,absent'],
            'overrides.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
