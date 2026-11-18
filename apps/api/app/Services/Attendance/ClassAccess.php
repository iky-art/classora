<?php

namespace App\Services\Attendance;

use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Tenancy\TenantContext;

/** Kelas mana yang boleh dikelola pengguna: semua (permission), atau wali kelas / guru pengampu. */
class ClassAccess
{
    public function __construct(private TenantContext $context)
    {
    }

    public function teacher(): ?Teacher
    {
        return Teacher::where('membership_id', $this->context->membership()->id)->first();
    }

    /** @return int[]|null null = semua kelas */
    public function accessibleClassIds(): ?array
    {
        if ($this->context->can('attendance.manage_all')) {
            return null;
        }

        $teacher = $this->teacher();
        if (! $teacher) {
            return [];
        }

        $homeroom = SchoolClass::where('homeroom_teacher_id', $teacher->id)->pluck('id');
        $taught = ClassSubject::whereIn('id', TeacherAssignment::where('teacher_id', $teacher->id)->select('class_subject_id'))->pluck('class_id');

        return $homeroom->merge($taught)->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    public function canManage(int $classId): bool
    {
        $ids = $this->accessibleClassIds();

        return $ids === null || in_array($classId, $ids, true);
    }
}
