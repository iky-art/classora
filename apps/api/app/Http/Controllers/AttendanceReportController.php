<?php

namespace App\Http\Controllers;

use App\Services\Attendance\ClassAccess;
use App\Services\Reports\AttendanceReportService;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;

class AttendanceReportController extends Controller
{
    public function index(Request $request, TenantContext $context, ClassAccess $access, AttendanceReportService $reports)
    {
        abort_unless($context->can('reports.view'), 403);

        $v = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'class_id' => ['nullable', 'integer'],
        ]);

        $classId = isset($v['class_id']) ? (int) $v['class_id'] : null;
        if ($classId) {
            abort_unless($access->canManage($classId) || $context->can('attendance.view') && $access->accessibleClassIds() === null, 403);
        }

        return ['data' => $reports->recap($v['from'], $v['to'], $classId, $access->accessibleClassIds())];
    }
}
