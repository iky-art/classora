<?php

namespace App\Policies;

use App\Models\StudentAttendanceReport;
use App\Models\User;

class OperatorReportPolicy extends TenantPolicy
{
    protected string $resource = 'operator_reports';

    public function viewAny(User $user): bool { return true; } // difilter per peran di controller

    /** Pemeriksa harus punya permission, dan tidak boleh pembuat laporan itu sendiri. */
    public function review(User $user, StudentAttendanceReport $report): bool
    {
        return $this->context->can('operator_reports.review') && $report->submitted_by !== $user->id;
    }
}
