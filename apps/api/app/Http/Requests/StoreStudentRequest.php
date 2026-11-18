<?php

namespace App\Http\Requests;

class StoreStudentRequest extends TenantRequest
{
    protected function ignoreId(): ?int
    {
        return null;
    }

    public function rules(): array
    {
        $ignore = $this->ignoreId();

        return [
            'nis' => ['required', 'string', 'max:30', $this->uniqueInSchool('students', 'nis', $ignore)],
            'nisn' => ['nullable', 'string', 'max:20', $this->uniqueInSchool('students', 'nisn', $ignore)],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:L,P'],
            'status' => ['sometimes', 'in:active,inactive,graduated,moved'],
            'birth_date' => ['nullable', 'date'],
        ];
    }
}
