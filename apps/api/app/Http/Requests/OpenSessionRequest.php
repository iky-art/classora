<?php

namespace App\Http\Requests;

class OpenSessionRequest extends TenantRequest
{
    public function rules(): array
    {
        return [
            'class_id' => ['required', 'integer', $this->inSchool('classes')],
            'session_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'period_key' => ['nullable', 'string', 'max:30', 'regex:/^[a-z0-9-]+$/'],
        ];
    }
}
