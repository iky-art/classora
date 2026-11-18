<?php

namespace App\Http\Requests;

class UpdateStudentRequest extends StoreStudentRequest
{
    protected function ignoreId(): ?int
    {
        return (int) $this->route('student');
    }
}
