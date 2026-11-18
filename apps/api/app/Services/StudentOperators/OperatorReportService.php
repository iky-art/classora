<?php

namespace App\Services\StudentOperators;

use App\Models\AttendanceReportReview;
use App\Models\StudentAttendanceReport;
use App\Models\StudentOperatorAssignment;
use App\Models\StudentUserLink;
use App\Models\User;
use App\Services\Attendance\AttendanceService;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OperatorReportService
{
    public function __construct(private AttendanceService $attendance)
    {
    }

    public function studentIdFor(User $user): ?int
    {
        $id = StudentUserLink::where('user_id', $user->id)->value('student_id');

        return $id === null ? null : (int) $id;
    }

    /** Siswa hanya boleh melapor untuk penugasannya sendiri, aktif, dan pada tanggal dalam periode. */
    private function assertOwnActiveAssignment(User $user, StudentOperatorAssignment $assignment, string $date): void
    {
        $studentId = $this->studentIdFor($user);

        abort_unless($studentId && $assignment->student_id === $studentId, 403, 'Penugasan ini bukan milikmu.');
        abort_unless($assignment->isActiveOn($date), 403, 'Penugasan tidak aktif pada tanggal tersebut.');
    }

    private function validateItems(StudentOperatorAssignment $assignment, string $date, array $items): void
    {
        $roster = $this->attendance->rosterStudentIds($assignment->class_id, $date)->map(fn ($id) => (int) $id);
        $ids = collect($items)->pluck('student_id')->map(fn ($id) => (int) $id);

        if ($ids->diff($roster)->isNotEmpty()) {
            throw ValidationException::withMessages(['items' => ['Ada siswa yang tidak terdaftar di kelas ini.']]);
        }

        if ($assignment->mode === 'report_only') {
            if (collect($items)->contains(fn ($i) => $i['status'] === 'present')) {
                throw ValidationException::withMessages(['items' => ['Mode laporan hanya untuk siswa yang tidak hadir (sakit, izin, alpa).']]);
            }
        } elseif ($roster->diff($ids)->isNotEmpty()) {
            throw ValidationException::withMessages(['items' => ['Mode absensi penuh mewajibkan status untuk seluruh siswa kelas.']]);
        }
    }

    public function submit(User $user, array $data): StudentAttendanceReport
    {
        $assignment = StudentOperatorAssignment::findOrFail($data['assignment_id']);
        $date = $data['report_date'] ?? now()->toDateString();

        $this->assertOwnActiveAssignment($user, $assignment, $date);
        $this->validateItems($assignment, $date, $data['items']);

        return DB::transaction(function () use ($user, $assignment, $date, $data) {
            $existing = StudentAttendanceReport::where('assignment_id', $assignment->id)->where('report_date', $date)->lockForUpdate()->first();
            if ($existing) {
                throw ValidationException::withMessages(['report_date' => [
                    $existing->status === 'rejected'
                        ? 'Laporan tanggal ini ditolak. Gunakan kirim ulang (resubmit) untuk merevisinya.'
                        : 'Laporan untuk tanggal ini sudah dikirim.',
                ]]);
            }

            $report = StudentAttendanceReport::create([
                'assignment_id' => $assignment->id,
                'class_id' => $assignment->class_id,
                'report_date' => $date,
                'status' => 'pending',
                'revision' => 1,
                'submitted_by' => $user->id,
                'submitted_at' => now(),
            ]);
            $report->items()->createMany($this->itemRows($data['items']));
            Audit::log('operator_report.submitted', $report, ['items' => count($data['items'])]);

            return $report;
        });
    }

    public function resubmit(User $user, int $reportId, array $items): StudentAttendanceReport
    {
        return DB::transaction(function () use ($user, $reportId, $items) {
            $report = StudentAttendanceReport::lockForUpdate()->findOrFail($reportId);

            abort_unless($report->submitted_by === $user->id, 403, 'Laporan ini bukan milikmu.');
            abort_unless($report->status === 'rejected', 409, 'Hanya laporan yang ditolak yang dapat dikirim ulang.');

            $assignment = StudentOperatorAssignment::findOrFail($report->assignment_id);
            $date = $report->report_date->toDateString();
            $this->assertOwnActiveAssignment($user, $assignment, $date);
            $this->validateItems($assignment, $date, $items);

            $report->items()->delete();
            $report->items()->createMany($this->itemRows($items));
            $report->update(['status' => 'pending', 'revision' => $report->revision + 1, 'submitted_at' => now()]);
            Audit::log('operator_report.resubmitted', $report, ['revision' => $report->revision]);

            return $report;
        });
    }

    /**
     * Persetujuan: transaksi + kunci baris. Status resmi ditulis lewat AttendanceService
     * (termasuk koreksi/audit), bukan dengan menyalin mentah laporan siswa.
     *
     * @param array<int, array{student_id:int,status:string,note?:?string}> $overrides keputusan guru
     */
    public function approve(User $reviewer, int $reportId, array $overrides, ?string $note): StudentAttendanceReport
    {
        return DB::transaction(function () use ($reviewer, $reportId, $overrides, $note) {
            $report = StudentAttendanceReport::with('items')->lockForUpdate()->findOrFail($reportId);
            abort_unless($report->status === 'pending', 409, 'Laporan ini sudah diproses.');

            $assignment = StudentOperatorAssignment::findOrFail($report->assignment_id);
            $date = $report->report_date->toDateString();
            $roster = $this->attendance->rosterStudentIds($report->class_id, $date)->map(fn ($id) => (int) $id);

            $final = [];
            foreach ($report->items as $item) {
                $final[$item->student_id] = ['student_id' => $item->student_id, 'status' => $item->status, 'note' => $item->note];
            }
            foreach ($overrides as $o) {
                if (! $roster->contains((int) $o['student_id'])) {
                    throw ValidationException::withMessages(['overrides' => ['Ada siswa yang tidak terdaftar di kelas ini.']]);
                }
                $final[(int) $o['student_id']] = ['student_id' => (int) $o['student_id'], 'status' => $o['status'], 'note' => $o['note'] ?? null];
            }

            $session = $this->attendance->openSession($report->class_id, $date, 'daily', $reviewer->id);
            $reason = "Disetujui dari laporan petugas kelas #{$report->id}";

            $this->attendance->saveRecords($session, array_values($final), $reviewer->id, $reason, 'operator_approval');

            // Mode laporan: siswa yang tidak dilaporkan dianggap hadir, selama belum ada catatan resmi.
            if ($assignment->mode === 'report_only') {
                $present = $roster->reject(fn ($id) => isset($final[$id]))->map(fn ($id) => ['student_id' => $id, 'status' => 'present'])->values()->all();
                $already = $session->records()->pluck('student_id')->map(fn ($id) => (int) $id);
                $present = array_values(array_filter($present, fn ($r) => ! $already->contains($r['student_id'])));
                if ($present) {
                    $this->attendance->saveRecords($session, $present, $reviewer->id, $reason, 'operator_approval');
                }
            }

            $report->update(['status' => 'approved']);
            AttendanceReportReview::create([
                'report_id' => $report->id, 'reviewer_id' => $reviewer->id, 'decision' => 'approved',
                'note' => $note, 'revision' => $report->revision,
            ]);
            Audit::log('operator_report.approved', $report, ['session_id' => $session->id, 'overrides' => count($overrides)]);

            return $report;
        });
    }

    public function reject(User $reviewer, int $reportId, string $note): StudentAttendanceReport
    {
        return DB::transaction(function () use ($reviewer, $reportId, $note) {
            $report = StudentAttendanceReport::lockForUpdate()->findOrFail($reportId);
            abort_unless($report->status === 'pending', 409, 'Laporan ini sudah diproses.');

            $report->update(['status' => 'rejected']);
            AttendanceReportReview::create([
                'report_id' => $report->id, 'reviewer_id' => $reviewer->id, 'decision' => 'rejected',
                'note' => $note, 'revision' => $report->revision,
            ]);
            Audit::log('operator_report.rejected', $report, ['note' => $note]);

            return $report;
        });
    }

    private function itemRows(array $items): array
    {
        return array_map(fn ($i) => ['student_id' => $i['student_id'], 'status' => $i['status'], 'note' => $i['note'] ?? null], $items);
    }
}
