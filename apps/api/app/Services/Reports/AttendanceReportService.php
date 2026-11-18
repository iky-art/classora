<?php

namespace App\Services\Reports;

use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

class AttendanceReportService
{
    public function __construct(private TenantContext $context)
    {
    }

    /**
     * Rekap kehadiran per siswa. Query mentah melewati global scope, jadi school_id
     * dibatasi secara eksplisit pada setiap tabel yang di-join.
     *
     * @param int[]|null $classIds null = semua kelas
     */
    public function recap(string $from, string $to, ?int $classId, ?array $classIds): array
    {
        $schoolId = $this->context->schoolId();

        $query = DB::table('attendance_records as r')
            ->join('attendance_sessions as s', 's.id', '=', 'r.session_id')
            ->join('students as st', 'st.id', '=', 'r.student_id')
            ->where('r.school_id', $schoolId)
            ->where('s.school_id', $schoolId)
            ->where('st.school_id', $schoolId)
            ->whereBetween('s.session_date', [$from, $to]);

        if ($classId) {
            $query->where('s.class_id', $classId);
        }
        if ($classIds !== null) {
            $query->whereIn('s.class_id', $classIds ?: [0]);
        }

        return $query->groupBy('st.id', 'st.name', 'st.nis')
            ->orderBy('st.name')
            ->select([
                'st.id as student_id', 'st.name', 'st.nis',
                DB::raw("SUM(CASE WHEN r.status = 'present' THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN r.status = 'sick' THEN 1 ELSE 0 END) as sick"),
                DB::raw("SUM(CASE WHEN r.status = 'excused' THEN 1 ELSE 0 END) as excused"),
                DB::raw("SUM(CASE WHEN r.status = 'absent' THEN 1 ELSE 0 END) as absent"),
                DB::raw('COUNT(*) as total'),
            ])
            ->get()
            ->map(fn ($row) => [
                'student_id' => (int) $row->student_id, 'name' => $row->name, 'nis' => $row->nis,
                'present' => (int) $row->present, 'sick' => (int) $row->sick,
                'excused' => (int) $row->excused, 'absent' => (int) $row->absent, 'total' => (int) $row->total,
            ])->all();
    }
}
