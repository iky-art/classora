<?php

namespace App\Services\Attendance;

use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassEnrollment;
use App\Models\Semester;
use App\Support\Audit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function openSession(int $classId, string $date, string $periodKey, int $userId): AttendanceSession
    {
        $semesterId = Semester::whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->value('id');

        return AttendanceSession::firstOrCreate(
            ['class_id' => $classId, 'session_date' => $date, 'period_key' => $periodKey],
            ['semester_id' => $semesterId, 'created_by' => $userId]
        );
    }

    /** ID siswa yang terdaftar di kelas pada tanggal tertentu (berdasarkan riwayat enrollment). */
    public function rosterStudentIds(int $classId, string $date): Collection
    {
        return ClassEnrollment::where('class_id', $classId)
            ->whereDate('joined_on', '<=', $date)
            ->where(fn ($q) => $q->whereNull('left_on')->orWhereDate('left_on', '>=', $date))
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id);
    }

    /**
     * Simpan status resmi. Mengubah status yang sudah tercatat WAJIB disertai alasan,
     * dan menghasilkan baris koreksi + audit log. Semua dalam satu transaksi.
     *
     * @param array<int, array{student_id:int,status:string,note?:?string}> $records
     * @return array{created:int,corrected:int,unchanged:int}
     */
    public function saveRecords(AttendanceSession $session, array $records, int $userId, ?string $reason, string $source = 'teacher', bool $canCorrect = true): array
    {
        $date = $session->session_date->toDateString();
        $roster = $this->rosterStudentIds($session->class_id, $date)->all();

        foreach ($records as $i => $row) {
            if (! in_array((int) $row['student_id'], $roster, true)) {
                throw ValidationException::withMessages(["records.$i.student_id" => ['Siswa tidak terdaftar di kelas ini pada tanggal tersebut.']]);
            }
        }

        return DB::transaction(function () use ($session, $records, $userId, $reason, $source, $canCorrect) {
            $stats = ['created' => 0, 'corrected' => 0, 'unchanged' => 0];

            foreach ($records as $i => $row) {
                $existing = AttendanceRecord::where('session_id', $session->id)
                    ->where('student_id', $row['student_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $existing) {
                    AttendanceRecord::create([
                        'session_id' => $session->id,
                        'student_id' => $row['student_id'],
                        'status' => $row['status'],
                        'note' => $row['note'] ?? null,
                        'recorded_by' => $userId,
                        'source' => $source,
                    ]);
                    $stats['created']++;
                    continue;
                }

                if ($existing->status === $row['status']) {
                    $stats['unchanged']++;
                    continue;
                }

                abort_unless($canCorrect, 403, 'Kamu tidak boleh mengoreksi absensi yang sudah tercatat.');

                if (! $reason) {
                    throw ValidationException::withMessages(['reason' => ['Alasan koreksi wajib diisi untuk mengubah absensi yang sudah tercatat.']]);
                }

                AttendanceCorrection::create([
                    'record_id' => $existing->id,
                    'old_status' => $existing->status,
                    'new_status' => $row['status'],
                    'reason' => $reason,
                    'corrected_by' => $userId,
                ]);
                $existing->update(['status' => $row['status'], 'note' => $row['note'] ?? $existing->note, 'recorded_by' => $userId, 'source' => $source]);
                Audit::log('attendance.corrected', $existing, ['from' => $existing->getOriginal('status'), 'to' => $row['status'], 'reason' => $reason]);
                $stats['corrected']++;
            }

            Audit::log('attendance.saved', $session, $stats);

            return $stats;
        });
    }
}
