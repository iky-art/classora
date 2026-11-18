<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeacherRequest;
use App\Http\Resources\TeacherResource;
use App\Models\Teacher;
use App\Support\Audit;

class TeacherController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Teacher::class);

        return TeacherResource::collection(Teacher::orderBy('name')->paginate(50));
    }

    public function store(StoreTeacherRequest $request)
    {
        $this->authorize('create', Teacher::class);

        $teacher = Teacher::create($request->validated());
        Audit::log('teacher.created', $teacher);

        return (new TeacherResource($teacher))->response()->setStatusCode(201);
    }
}
