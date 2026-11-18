<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAttendanceReport;
use App\Models\Teacher;
use App\Services\Attendance\ClassAccess;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;

/** Isi dashboard bergantung pada permission; peran tanpa izin tidak menerima angkanya. */
class DashboardController extends Controller
{
    public function __invoke(Request $request, TenantContext $context, ClassAccess $access)
    {
        abort_unless($context->can('dashboard.view'), 403);

        $data = [];
        if ($context->can('students.view')) {
            $data['students'] = Student::where('status', 'active')->count();
        }
        if ($context->can('teachers.view')) {
            $data['teachers'] = Teacher::where('status', 'active')->count();
        }
        if ($context->can('classes.view')) {
            $data['classes'] = SchoolClass::count();
        }
        if ($context->can('attendance.view')) {
            $today = now()->toDateString();
            $ids = $access->accessibleClassIds();
            $rows = AttendanceRecord::whereHas('session', function ($q) use ($today, $ids) {
                $q->whereDate('session_date', $today);
                if ($ids !== null) {
                    $q->whereIn('class_id', $ids ?: [0]);
                }
            })->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

            $data['attendance_today'] = collect(['present', 'sick', 'excused', 'absent'])->mapWithKeys(fn ($s) => [$s => (int) ($rows[$s] ?? 0)]);
        }
        if ($context->can('operator_reports.review')) {
            $ids = $access->accessibleClassIds();
            $data['pending_reports'] = StudentAttendanceReport::where('status', 'pending')
                ->when($ids !== null, fn ($q) => $q->whereIn('class_id', $ids ?: [0]))->count();
        }
        if ($context->can('audit.view')) {
            $data['recent_activity'] = AuditLog::where('school_id', $context->schoolId())->latest('id')->limit(10)
                ->get(['action', 'subject_type', 'subject_id', 'created_at']);
        }

        return ['data' => $data];
    }
}
