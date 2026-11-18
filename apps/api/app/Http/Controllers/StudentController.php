<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Support\Audit;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Student::class);

        $sorts = ['name', 'nis', 'created_at'];
        $sort = in_array($request->query('sort'), $sorts, true) ? $request->query('sort') : 'name';
        $dir = $request->query('dir') === 'desc' ? 'desc' : 'asc';

        $query = Student::query()->with('activeEnrollment.class');

        if ($q = trim((string) $request->query('q'))) {
            $like = addcslashes($q, '%_\\');
            $query->where(fn ($w) => $w->where('name', 'like', "%{$like}%")->orWhere('nis', 'like', "{$like}%")->orWhere('nisn', 'like', "{$like}%"));
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($classId = $request->query('class_id')) {
            $query->whereHas('activeEnrollment', fn ($e) => $e->where('class_id', (int) $classId));
        }

        return StudentResource::collection($query->orderBy($sort, $dir)->paginate(min((int) $request->query('per_page', 20), 100)));
    }

    public function store(StoreStudentRequest $request)
    {
        $this->authorize('create', Student::class);

        $student = Student::create($request->validated());
        Audit::log('student.created', $student);

        return (new StudentResource($student))->response()->setStatusCode(201);
    }

    public function show(string $id)
    {
        $student = Student::with('activeEnrollment.class')->findOrFail($id);
        $this->authorize('view', $student);

        return new StudentResource($student);
    }

    public function update(UpdateStudentRequest $request, string $id)
    {
        $student = Student::findOrFail($id);
        $this->authorize('update', $student);

        $student->update($request->validated());
        Audit::log('student.updated', $student);

        return new StudentResource($student->load('activeEnrollment.class'));
    }

    /** Arsip (soft delete): riwayat kelas dan absensi tetap utuh. */
    public function destroy(string $id)
    {
        $student = Student::findOrFail($id);
        $this->authorize('delete', $student);

        $student->delete();
        Audit::log('student.archived', $student);

        return response()->noContent();
    }
}
