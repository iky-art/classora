<?php

namespace Tests\Feature;

use App\Models\AttendanceCorrection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use CreatesTenants, RefreshDatabase;

    public function test_record_then_correct_requires_reason_and_writes_correction(): void
    {
        $this->seedPlatform();
        $school = $this->makeSchool('a');
        [$teacher, $membership] = $this->makeMember($school, 'homeroom_teacher');
        [$class, $students] = $this->makeClassroom($school, $membership);
        $today = now()->toDateString();

        Sanctum::actingAs($teacher);
        $session = $this->postJson('/api/v1/attendance/sessions', ['class_id' => $class->id, 'session_date' => $today])->assertCreated()->json('data.id');
        $this->assertCount(3, $this->getJson("/api/v1/attendance/sessions/$session")->json('roster'));

        $payload = ['records' => $students->map(fn ($s) => ['student_id' => $s->id, 'status' => 'present'])->all()];
        $this->postJson("/api/v1/attendance/sessions/$session/records", $payload)->assertOk()->assertJsonPath('data.created', 3);

        $payload['records'][0]['status'] = 'sick';
        $this->postJson("/api/v1/attendance/sessions/$session/records", $payload)->assertStatus(422);
        $this->postJson("/api/v1/attendance/sessions/$session/records", $payload + ['reason' => 'Surat dokter'])->assertOk()->assertJsonPath('data.corrected', 1);

        $this->inTenant($school, fn () => $this->assertSame(1, AttendanceCorrection::count()));
    }

    public function test_teacher_of_other_class_cannot_record(): void
    {
        $this->seedPlatform();
        $school = $this->makeSchool('a');
        [, $homeroom] = $this->makeMember($school, 'homeroom_teacher');
        [$class] = $this->makeClassroom($school, $homeroom);
        [$other] = $this->makeMember($school, 'teacher');

        Sanctum::actingAs($other);
        $this->postJson('/api/v1/attendance/sessions', ['class_id' => $class->id, 'session_date' => now()->toDateString()])->assertForbidden();
    }
}
