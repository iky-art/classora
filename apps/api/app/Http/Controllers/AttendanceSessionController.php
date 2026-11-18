<?php

namespace App\Http\Controllers;

use App\Http\Requests\OpenSessionRequest;
use App\Http\Requests\SaveRecordsRequest;
use App\Models\AttendanceSession;
use App\Models\Student;
use App\Services\Attendance\AttendanceService;
use App\Services\Attendance\ClassAccess;
use Illuminate\Http\Request;

class AttendanceSessionController extends Controller
{
    public function __construct(private AttendanceService $attendance, private ClassAccess $access)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', AttendanceSession::class);

        $query = AttendanceSession::with('class:id,name')->orderByDesc('session_date');
        $ids = $this->access->accessibleClassIds();
        if ($ids !== null) {
            $query->whereIn('class_id', $ids ?: [0]);
        }
        if ($classId = $request->query('class_id')) {
            $query->where('class_id', (int) $classId);
        }
        if ($from = $request->query('from')) {
            $query->whereDate('session_date', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('session_date', '<=', $to);
        }

        return $query->paginate(min((int) $request->query('per_page', 30), 100))->through(fn ($s) => $this->summary($s));
    }

    public function store(OpenSessionRequest $request)
    {
        $this->authorize('create', AttendanceSession::class);
        $classId = (int) $request->validated('class_id');
        abort_unless($this->access->canManage($classId), 403, 'Kamu bukan wali kelas atau guru pengampu kelas ini.');

        $session = $this->attendance->openSession($classId, $request->validated('session_date'), $request->validated('period_key') ?? 'daily', $request->user()->id);

        return response()->json($this->detail($session), $session->wasRecentlyCreated ? 201 : 200);
    }

    public function show(string $id)
    {
        $session = AttendanceSession::findOrFail($id);
        $this->authorize('view', $session);
        abort_unless($this->access->canManage($session->class_id), 403, 'Kamu tidak memiliki akses ke kelas ini.');

        return $this->detail($session);
    }

    public function records(SaveRecordsRequest $request, string $id)
    {
        $session = AttendanceSession::findOrFail($id);
        $this->authorize('update', $session);
        abort_unless($this->access->canManage($session->class_id), 403, 'Kamu tidak memiliki akses ke kelas ini.');

        // Mengubah catatan yang sudah ada = koreksi, butuh permission tersendiri (dicek di dalam transaksi).
        $stats = $this->attendance->saveRecords(
            $session, $request->validated('records'), $request->user()->id, $request->validated('reason'),
            'teacher', app(\App\Tenancy\TenantContext::class)->can('attendance.correct')
        );

        return ['data' => $stats] + $this->detail($session);
    }

    private function summary(AttendanceSession $s): array
    {
        return ['id' => $s->id, 'class' => ['id' => $s->class_id, 'name' => $s->class?->name], 'session_date' => $s->session_date->toDateString(), 'period_key' => $s->period_key];
    }

    private function detail(AttendanceSession $session): array
    {
        $date = $session->session_date->toDateString();
        $records = $session->records()->get()->keyBy('student_id');
        $students = Student::whereIn('id', $this->attendance->rosterStudentIds($session->class_id, $date))->orderBy('name')->get();

        return [
            'data' => $this->summary($session->loadMissing('class:id,name')),
            'roster' => $students->map(fn ($st) => [
                'student_id' => $st->id, 'name' => $st->name, 'nis' => $st->nis,
                'status' => $records[$st->id]->status ?? null, 'note' => $records[$st->id]->note ?? null,
            ])->values(),
        ];
    }
}
