<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnrollRequest;
use App\Http\Requests\StoreClassRequest;
use App\Http\Resources\SchoolClassResource;
use App\Http\Resources\StudentResource;
use App\Models\AcademicYear;
use App\Models\ClassEnrollment;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClassController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', SchoolClass::class);

        $query = SchoolClass::with(['academicYear', 'grade', 'department', 'homeroomTeacher'])->withCount('activeEnrollments')->orderBy('name');
        if ($year = $request->query('academic_year_id')) {
            $query->where('academic_year_id', (int) $year);
        }

        return SchoolClassResource::collection($query->paginate(min((int) $request->query('per_page', 50), 100)));
    }

    public function store(StoreClassRequest $request)
    {
        $this->authorize('create', SchoolClass::class);

        $class = SchoolClass::create($request->validated());
        Audit::log('class.created', $class);

        return (new SchoolClassResource($class->load(['academicYear', 'grade', 'department', 'homeroomTeacher'])))->response()->setStatusCode(201);
    }

    public function show(string $id)
    {
        $class = SchoolClass::with(['academicYear', 'grade', 'department', 'homeroomTeacher'])->findOrFail($id);
        $this->authorize('view', $class);

        $members = Student::whereIn('id', $class->activeEnrollments()->select('student_id'))->orderBy('name')->get();

        return [
            'data' => (new SchoolClassResource($class))->resolve(),
            'members' => StudentResource::collection($members)->resolve(),
        ];
    }

    /** Memindahkan siswa menutup enrollment lama (left_on) dan membuat yang baru; riwayat tidak dihapus. */
    public function enroll(EnrollRequest $request, string $id)
    {
        $class = SchoolClass::findOrFail($id);
        $this->authorize('update', $class);
        abort_unless(app(\App\Tenancy\TenantContext::class)->can('enrollments.manage'), 403);

        $joined = $request->validated('joined_on');
        $moved = 0;
        $added = 0;

        DB::transaction(function () use ($request, $class, $joined, &$moved, &$added) {
            foreach ($request->validated('student_ids') as $studentId) {
                $student = Student::findOrFail($studentId);
                if ($student->status !== 'active') {
                    throw ValidationException::withMessages(['student_ids' => ["Siswa {$student->name} tidak berstatus aktif."]]);
                }

                $current = ClassEnrollment::where('student_id', $studentId)->whereNull('left_on')->lockForUpdate()->first();
                if ($current && $current->class_id === $class->id) {
                    continue;
                }
                if ($current) {
                    $current->update(['left_on' => $joined, 'left_reason' => 'pindah kelas']);
                    $moved++;
                }

                ClassEnrollment::create(['student_id' => $studentId, 'class_id' => $class->id, 'joined_on' => $joined]);
                $added++;
            }
            Audit::log('class.enrolled', $class, ['added' => $added, 'moved' => $moved]);
        });

        return ['data' => ['added' => $added, 'moved' => $moved]];
    }

    public function academicYears()
    {
        return ['data' => AcademicYear::orderByDesc('starts_on')->get(['id', 'name', 'is_active'])];
    }

    public function grades()
    {
        return ['data' => Grade::orderBy('level')->get(['id', 'name', 'level'])];
    }
}
