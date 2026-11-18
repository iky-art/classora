<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nis' => $this->nis,
            'nisn' => $this->nisn,
            'name' => $this->name,
            'gender' => $this->gender,
            'status' => $this->status,
            'birth_date' => $this->birth_date?->toDateString(),
            'class' => $this->whenLoaded('activeEnrollment', fn () => $this->activeEnrollment?->class ? [
                'id' => $this->activeEnrollment->class->id,
                'name' => $this->activeEnrollment->class->name,
            ] : null),
        ];
    }
}
