<?php

namespace Tests\Support;

use App\Models\AcademicYear;
use App\Models\ClassEnrollment;
use App\Models\Grade;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolMembership;
use App\Models\Student;
use App\Models\StudentUserLink;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Role;
use App\Tenancy\TenantContext;
use Database\Seeders\PlatformSeeder;

trait CreatesTenants
{
    protected function seedPlatform(): void
    {
        $this->seed(PlatformSeeder::class);
    }

    protected function makeSchool(string $slug): School
    {
        return School::create(['name' => 'Sekolah '.$slug, 'slug' => $slug]);
    }

    /** @return array{0: User, 1: SchoolMembership} */
    protected function makeMember(School $school, string $role): array
    {
        $user = new User(['name' => $role.' '.$school->slug, 'email' => $role.'.'.uniqid().'@'.$school->slug.'.test', 'password' => 'password-12345']);
        $user->save();
        $membership = SchoolMembership::create(['school_id' => $school->id, 'user_id' => $user->id]);
        $membership->roles()->attach(Role::whereNull('school_id')->where('slug', $role)->firstOrFail()->id);

        return [$user, $membership];
    }

    protected function inTenant(School $school, callable $fn): mixed
    {
        return app(TenantContext::class)->runAs($school->id, $fn);
    }

    /** Sekolah dengan kelas, 3 siswa terdaftar, dan wali kelas. */
    protected function makeClassroom(School $school, SchoolMembership $teacherMembership): array
    {
        return $this->inTenant($school, function () use ($teacherMembership) {
            $year = AcademicYear::create(['name' => '2026/2027', 'starts_on' => '2026-07-01', 'ends_on' => '2027-06-30', 'is_active' => true]);
            $grade = Grade::create(['name' => 'X', 'level' => 10]);
            $teacher = Teacher::create(['name' => 'Bu Guru', 'membership_id' => $teacherMembership->id]);
            $class = SchoolClass::create(['academic_year_id' => $year->id, 'grade_id' => $grade->id, 'name' => 'X-A', 'homeroom_teacher_id' => $teacher->id]);

            $students = collect(['Ani', 'Budi', 'Citra'])->map(function ($name, $i) use ($class) {
                $s = Student::create(['nis' => 'N'.($i + 1), 'name' => $name, 'gender' => $i === 1 ? 'L' : 'P']);
                ClassEnrollment::create(['student_id' => $s->id, 'class_id' => $class->id, 'joined_on' => '2026-07-01']);

                return $s;
            });

            return [$class, $students];
        });
    }

    protected function linkStudentUser(School $school, Student $student, User $user): void
    {
        $this->inTenant($school, fn () => StudentUserLink::create(['student_id' => $student->id, 'user_id' => $user->id]));
    }
}
