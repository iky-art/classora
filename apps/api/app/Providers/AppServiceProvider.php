<?php

namespace App\Providers;

use App\Models\AttendanceSession;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAttendanceReport;
use App\Models\StudentOperatorAssignment;
use App\Models\Teacher;
use App\Policies\AttendanceSessionPolicy;
use App\Policies\OperatorAssignmentPolicy;
use App\Policies\OperatorReportPolicy;
use App\Policies\SchoolClassPolicy;
use App\Policies\StudentPolicy;
use App\Policies\TeacherPolicy;
use App\Tenancy\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(TenantContext::class);
    }

    public function boot(): void
    {
        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(Teacher::class, TeacherPolicy::class);
        Gate::policy(SchoolClass::class, SchoolClassPolicy::class);
        Gate::policy(AttendanceSession::class, AttendanceSessionPolicy::class);
        Gate::policy(StudentOperatorAssignment::class, OperatorAssignmentPolicy::class);
        Gate::policy(StudentAttendanceReport::class, OperatorReportPolicy::class);

        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(5)->by(strtolower((string) $r->input('email')).'|'.$r->ip()));
        RateLimiter::for('api', fn (Request $r) => Limit::perMinute(120)->by($r->user()?->id ?: $r->ip()));
    }
}
