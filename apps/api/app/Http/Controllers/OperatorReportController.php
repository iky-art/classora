<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Http\Requests\SubmitReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\StudentAttendanceReport;
use App\Services\Attendance\ClassAccess;
use App\Services\StudentOperators\OperatorReportService;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;

class OperatorReportController extends Controller
{
    public function __construct(private OperatorReportService $service, private ClassAccess $access, private TenantContext $context)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', StudentAttendanceReport::class);

        $query = StudentAttendanceReport::with(['class:id,name', 'assignment'])->latest('id');
        if ($this->context->can('operator_reports.review')) {
            $ids = $this->access->accessibleClassIds();
            if ($ids !== null) {
                $query->whereIn('class_id', $ids ?: [0]);
            }
        } else {
            $query->where('submitted_by', $request->user()->id); // siswa hanya melihat laporannya sendiri
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return ReportResource::collection($query->paginate(30));
    }

    public function show(Request $request, string $id)
    {
        $report = StudentAttendanceReport::with(['class:id,name', 'assignment', 'items', 'reviews'])->findOrFail($id);
        $this->assertCanSee($request, $report);

        return new ReportResource($report);
    }

    public function store(SubmitReportRequest $request)
    {
        $report = $this->service->submit($request->user(), $request->validated());

        return (new ReportResource($report->load(['class:id,name', 'assignment', 'items'])))->response()->setStatusCode(201);
    }

    public function resubmit(SubmitReportRequest $request, string $id)
    {
        $report = $this->service->resubmit($request->user(), (int) $id, $request->validated('items'));

        return new ReportResource($report->load(['class:id,name', 'assignment', 'items']));
    }

    public function approve(ReviewRequest $request, string $id)
    {
        $report = $this->reviewable($request, $id);
        $report = $this->service->approve($request->user(), $report->id, $request->validated('overrides') ?? [], $request->validated('note'));

        return new ReportResource($report->load(['class:id,name', 'assignment', 'items', 'reviews']));
    }

    public function reject(ReviewRequest $request, string $id)
    {
        $report = $this->reviewable($request, $id);
        $report = $this->service->reject($request->user(), $report->id, $request->validated('note'));

        return new ReportResource($report->load(['class:id,name', 'assignment', 'items', 'reviews']));
    }

    private function reviewable(Request $request, string $id): StudentAttendanceReport
    {
        $report = StudentAttendanceReport::findOrFail($id);
        $this->authorize('review', $report); // permission + bukan pembuat laporan
        abort_unless($this->access->canManage($report->class_id), 403, 'Kamu bukan wali kelas atau guru pengampu kelas ini.');

        return $report;
    }

    private function assertCanSee(Request $request, StudentAttendanceReport $report): void
    {
        $isOwner = $report->submitted_by === $request->user()->id;
        $isReviewer = $this->context->can('operator_reports.review') && $this->access->canManage($report->class_id);
        abort_unless($isOwner || $isReviewer, 403);
    }
}
