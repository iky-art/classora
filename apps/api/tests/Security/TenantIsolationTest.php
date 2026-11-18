<?php

namespace Tests\Security;

use App\Models\Grade;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use CreatesTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
    }

    public function test_user_only_sees_students_of_own_school(): void
    {
        $a = $this->makeSchool('a');
        $b = $this->makeSchool('b');
        [$adminA] = $this->makeMember($a, 'school_admin');
        $this->inTenant($a, fn () => Student::create(['nis' => '1', 'name' => 'Siswa A', 'gender' => 'L']));
        $this->inTenant($b, fn () => Student::create(['nis' => '1', 'name' => 'Siswa B', 'gender' => 'L']));

        Sanctum::actingAs($adminA);
        $names = collect($this->getJson('/api/v1/students')->assertOk()->json('data'))->pluck('name');

        $this->assertSame(['Siswa A'], $names->all());
    }

    public function test_cannot_read_or_modify_student_of_another_school(): void
    {
        $a = $this->makeSchool('a');
        $b = $this->makeSchool('b');
        [$adminA] = $this->makeMember($a, 'school_admin');
        $foreign = $this->inTenant($b, fn () => Student::create(['nis' => '9', 'name' => 'Siswa B', 'gender' => 'P']));

        Sanctum::actingAs($adminA);
        $this->getJson("/api/v1/students/{$foreign->id}")->assertNotFound();
        $this->putJson("/api/v1/students/{$foreign->id}", ['nis' => '9', 'name' => 'Diubah', 'gender' => 'P'])->assertNotFound();
        $this->deleteJson("/api/v1/students/{$foreign->id}")->assertNotFound();
        $this->assertSame('Siswa B', $this->inTenant($b, fn () => Student::find($foreign->id)->name));
    }

    public function test_school_id_in_request_body_is_ignored(): void
    {
        $a = $this->makeSchool('a');
        $b = $this->makeSchool('b');
        [$adminA] = $this->makeMember($a, 'school_admin');

        Sanctum::actingAs($adminA);
        $id = $this->postJson('/api/v1/students', ['nis' => '5', 'name' => 'Baru', 'gender' => 'L', 'school_id' => $b->id])
            ->assertCreated()->json('data.id');

        $this->assertSame($a->id, Student::withoutGlobalScopes()->find($id)->school_id);
    }

    public function test_cannot_switch_to_school_without_membership(): void
    {
        $a = $this->makeSchool('a');
        $this->makeSchool('b');
        [$adminA] = $this->makeMember($a, 'school_admin');

        Sanctum::actingAs($adminA);
        $this->getJson('/api/v1/students', ['X-School' => 'b'])->assertForbidden();
    }

    public function test_relations_across_schools_are_rejected(): void
    {
        $a = $this->makeSchool('a');
        $b = $this->makeSchool('b');
        [$adminA] = $this->makeMember($a, 'school_admin');
        $foreignGrade = $this->inTenant($b, fn () => Grade::create(['name' => 'X', 'level' => 10]));
        $ownGrade = $this->inTenant($a, fn () => Grade::create(['name' => 'X', 'level' => 10]));
        $year = $this->inTenant($a, fn () => \App\Models\AcademicYear::create(['name' => '2026', 'starts_on' => '2026-07-01', 'ends_on' => '2027-06-30']));

        Sanctum::actingAs($adminA);
        $this->postJson('/api/v1/classes', ['name' => 'X-A', 'academic_year_id' => $year->id, 'grade_id' => $foreignGrade->id])
            ->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');
        $this->postJson('/api/v1/classes', ['name' => 'X-A', 'academic_year_id' => $year->id, 'grade_id' => $ownGrade->id])->assertCreated();
    }

    public function test_role_without_permission_is_forbidden_by_backend(): void
    {
        $a = $this->makeSchool('a');
        [$teacher] = $this->makeMember($a, 'teacher');

        Sanctum::actingAs($teacher);
        $this->postJson('/api/v1/students', ['nis' => '1', 'name' => 'X', 'gender' => 'L'])->assertForbidden();
        $this->getJson('/api/v1/students')->assertOk();
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/students')->assertUnauthorized();
    }
}
