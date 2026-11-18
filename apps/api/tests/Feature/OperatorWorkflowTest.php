<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

class OperatorWorkflowTest extends TestCase
{
    use CreatesTenants, RefreshDatabase;

    private function setupScenario(): array
    {
        $this->seedPlatform();
        $school = $this->makeSchool('a');
        [$teacher, $tm] = $this->makeMember($school, 'homeroom_teacher');
        [$class, $students] = $this->makeClassroom($school, $tm);
        [$studentUser] = $this->makeMember($school, 'student');
        $this->linkStudentUser($school, $students[0], $studentUser);

        return [$school, $teacher, $studentUser, $class, $students];
    }

    private function assign($teacher, $class, $student, string $mode = 'report_only'): int
    {
        Sanctum::actingAs($teacher);

        return $this->postJson('/api/v1/student-operators/assignments', [
            'class_id' => $class->id, 'student_id' => $student->id, 'mode' => $mode,
            'starts_on' => now()->subDay()->toDateString(), 'ends_on' => now()->addDay()->toDateString(),
        ])->assertCreated()->json('data.id');
    }

    public function test_full_report_only_flow_with_approval_writes_official_attendance_and_audit(): void
    {
        [$school, $teacher, $studentUser, $class, $students] = $this->setupScenario();
        $assignmentId = $this->assign($teacher, $class, $students[0]);
        $today = now()->toDateString();

        Sanctum::actingAs($studentUser);
        $reportId = $this->postJson('/api/v1/student-operators/reports', [
            'assignment_id' => $assignmentId, 'report_date' => $today,
            'items' => [['student_id' => $students[1]->id, 'status' => 'absent', 'note' => 'tanpa kabar']],
        ])->assertCreated()->assertJsonPath('data.status', 'pending')->json('data.id');

        // Siswa tidak bisa menyetujui laporannya sendiri maupun mengirim dua kali.
        $this->postJson("/api/v1/student-operators/reports/$reportId/approve")->assertForbidden();
        $this->postJson('/api/v1/student-operators/reports', [
            'assignment_id' => $assignmentId, 'report_date' => $today,
            'items' => [['student_id' => $students[1]->id, 'status' => 'absent']],
        ])->assertStatus(422);

        Sanctum::actingAs($teacher);
        $this->postJson("/api/v1/student-operators/reports/$reportId/approve")->assertOk()->assertJsonPath('data.status', 'approved');
        $this->postJson("/api/v1/student-operators/reports/$reportId/approve")->assertStatus(409);

        $this->inTenant($school, function () use ($students) {
            $records = AttendanceRecord::pluck('status', 'student_id');
            $this->assertSame('absent', $records[$students[1]->id]);
            $this->assertSame('present', $records[$students[0]->id]);
            $this->assertSame('present', $records[$students[2]->id]);
            $this->assertTrue(AuditLog::where('action', 'operator_report.approved')->exists());
        });
    }

    public function test_rejected_report_can_be_resubmitted_then_approved(): void
    {
        [, $teacher, $studentUser, $class, $students] = $this->setupScenario();
        $assignmentId = $this->assign($teacher, $class, $students[0]);
        $yesterday = now()->subDay()->toDateString();

        Sanctum::actingAs($studentUser);
        $reportId = $this->postJson('/api/v1/student-operators/reports', [
            'assignment_id' => $assignmentId, 'report_date' => $yesterday,
            'items' => [['student_id' => $students[1]->id, 'status' => 'absent']],
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs($teacher);
        $this->postJson("/api/v1/student-operators/reports/$reportId/reject")->assertStatus(422); // catatan wajib
        $this->postJson("/api/v1/student-operators/reports/$reportId/reject", ['note' => 'Budi hadir, cek lagi'])->assertOk()->assertJsonPath('data.status', 'rejected');

        Sanctum::actingAs($studentUser);
        $this->postJson("/api/v1/student-operators/reports/$reportId/resubmit", [
            'items' => [['student_id' => $students[2]->id, 'status' => 'sick']],
        ])->assertOk()->assertJsonPath('data.status', 'pending')->assertJsonPath('data.revision', 2);

        Sanctum::actingAs($teacher);
        $this->postJson("/api/v1/student-operators/reports/$reportId/approve")->assertOk();
    }

    public function test_student_cannot_assign_and_unlinked_student_cannot_submit(): void
    {
        [$school, $teacher, $studentUser, $class, $students] = $this->setupScenario();
        $assignmentId = $this->assign($teacher, $class, $students[0]);
        [$otherStudentUser] = $this->makeMember($school, 'student');

        Sanctum::actingAs($studentUser);
        $this->postJson('/api/v1/student-operators/assignments', [
            'class_id' => $class->id, 'student_id' => $students[0]->id, 'mode' => 'full_attendance',
            'starts_on' => now()->toDateString(), 'ends_on' => now()->toDateString(),
        ])->assertForbidden();

        Sanctum::actingAs($studentUser);
        $this->getJson("/api/v1/student-operators/assignments/$assignmentId/roster")->assertOk()->assertJsonCount(3, 'data');

        Sanctum::actingAs($otherStudentUser);
        $this->getJson("/api/v1/student-operators/assignments/$assignmentId/roster")->assertForbidden();
        $this->postJson('/api/v1/student-operators/reports', [
            'assignment_id' => $assignmentId, 'report_date' => now()->toDateString(),
            'items' => [['student_id' => $students[1]->id, 'status' => 'absent']],
        ])->assertForbidden();
    }

    public function test_full_mode_requires_every_student(): void
    {
        [, $teacher, $studentUser, $class, $students] = $this->setupScenario();
        $assignmentId = $this->assign($teacher, $class, $students[0], 'full_attendance');

        Sanctum::actingAs($studentUser);
        $this->postJson('/api/v1/student-operators/reports', [
            'assignment_id' => $assignmentId, 'report_date' => now()->toDateString(),
            'items' => [['student_id' => $students[0]->id, 'status' => 'present']],
        ])->assertStatus(422);

        $this->postJson('/api/v1/student-operators/reports', [
            'assignment_id' => $assignmentId, 'report_date' => now()->toDateString(),
            'items' => $students->map(fn ($s) => ['student_id' => $s->id, 'status' => 'present'])->all(),
        ])->assertCreated();
    }
}
