<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssignmentRequest;
use App\Models\ClassEnrollment;
use App\Models\Student;
use App\Models\StudentOperatorAssignment;
use App\Services\Attendance\AttendanceService;
use App\Services\StudentOperators\OperatorReportService;
use App\Models\StudentUserLink;
use App\Services\Attendance\ClassAccess;
use App\Support\Audit;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OperatorAssignmentController extends Controller
{
    public function __construct(private ClassAccess $access, private TenantContext $context)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', StudentOperatorAssignment::class);

        $query = StudentOperatorAssignment::with(['class:id,name', 'student:id,name,nis'])->latest('id');
        if ($this->context->can('operators.assign')) {
            $ids = $this->access->accessibleClassIds();
            if ($ids !== null) {
                $query->whereIn('class_id', $ids ?: [0]);
            }
        } else {
            // Siswa hanya melihat penugasannya sendiri.
            $studentId = StudentUserLink::where('user_id', $request->user()->id)->value('student_id');
            $query->where('student_id', $studentId ?: 0);
        }

        return ['data' => $query->limit(100)->get()->map(fn ($a) => [
            'id' => $a->id, 'class' => ['id' => $a->class_id, 'name' => $a->class?->name],
            'student' => ['id' => $a->student_id, 'name' => $a->student?->name, 'nis' => $a->student?->nis],
            'mode' => $a->mode, 'starts_on' => $a->starts_on->toDateString(), 'ends_on' => $a->ends_on->toDateString(), 'status' => $a->status,
        ])];
    }

    public function store(StoreAssignmentRequest $request)
    {
        $this->authorize('create', StudentOperatorAssignment::class);
        $v = $request->validated();
        abort_unless($this->access->canManage((int) $v['class_id']), 403, 'Kamu bukan wali kelas atau guru pengampu kelas ini.');

        $enrolled = ClassEnrollment::where('class_id', $v['class_id'])->where('student_id', $v['student_id'])->whereNull('left_on')->exists();
        if (! $enrolled) {
            throw ValidationException::withMessages(['student_id' => ['Siswa tidak terdaftar aktif di kelas ini.']]);
        }

        $link = StudentUserLink::where('student_id', $v['student_id'])->first();
        if (! $link) {
            throw ValidationException::withMessages(['student_id' => ['Siswa belum memiliki akun pengguna yang terhubung.']]);
        }
        if ($link->user_id === $request->user()->id) {
            throw ValidationException::withMessages(['student_id' => ['Siswa tidak dapat menunjuk dirinya sendiri.']]);
        }

        $overlap = StudentOperatorAssignment::where('student_id', $v['student_id'])->where('class_id', $v['class_id'])->where('status', 'active')
            ->whereDate('starts_on', '<=', $v['ends_on'])->whereDate('ends_on', '>=', $v['starts_on'])->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['starts_on' => ['Siswa sudah memiliki penugasan aktif yang tumpang tindih.']]);
        }

        $assignment = StudentOperatorAssignment::create($v + ['assigned_by' => $request->user()->id, 'status' => 'active']);
        Audit::log('operator_assignment.created', $assignment, ['mode' => $v['mode']]);

        return response()->json(['data' => ['id' => $assignment->id]], 201);
    }

    public function revoke(string $id)
    {
        $assignment = StudentOperatorAssignment::findOrFail($id);
        $this->authorize('update', $assignment);
        abort_unless($this->access->canManage($assignment->class_id), 403);

        $assignment->update(['status' => 'revoked']);
        Audit::log('operator_assignment.revoked', $assignment);

        return ['data' => ['id' => $assignment->id, 'status' => 'revoked']];
    }

    /** Daftar siswa kelas untuk petugas: hanya pemilik penugasan, hanya selama penugasan aktif. */
    public function roster(Request $request, string $id, OperatorReportService $reports, AttendanceService $attendance)
    {
        $assignment = StudentOperatorAssignment::findOrFail($id);
        $today = now()->toDateString();
        abort_unless($reports->studentIdFor($request->user()) === (int) $assignment->student_id && $assignment->isActiveOn($today), 403);

        return ['data' => Student::whereIn('id', $attendance->rosterStudentIds($assignment->class_id, $today))->orderBy('name')->get(['id', 'name', 'nis'])];
    }
}
