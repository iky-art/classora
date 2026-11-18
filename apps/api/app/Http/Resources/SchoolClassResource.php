<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolClassResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'academic_year' => $this->whenLoaded('academicYear', fn () => $this->academicYear?->name),
            'grade' => $this->whenLoaded('grade', fn () => $this->grade?->name),
            'department' => $this->whenLoaded('department', fn () => $this->department?->name),
            'homeroom_teacher' => $this->whenLoaded('homeroomTeacher', fn () => $this->homeroomTeacher?->name),
            'students_count' => $this->whenCounted('activeEnrollments'),
        ];
    }
}
