<?php

use App\Http\Controllers\AttendanceReportController;
use App\Http\Controllers\AttendanceSessionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OperatorAssignmentController;
use App\Http\Controllers\OperatorReportController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Platform (Super Admin)
    Route::get('/schools', [SchoolController::class, 'index']);
    Route::post('/schools', [SchoolController::class, 'store']);

    // Semua rute di bawah memerlukan konteks sekolah (tenant) yang tervalidasi.
    Route::middleware('tenant')->group(function () {
        Route::get('/auth/context', [AuthController::class, 'context']);
        Route::get('/dashboard', DashboardController::class);

        Route::apiResource('students', StudentController::class);

        Route::get('/teachers', [TeacherController::class, 'index']);
        Route::post('/teachers', [TeacherController::class, 'store']);

        Route::get('/classes', [ClassController::class, 'index']);
        Route::post('/classes', [ClassController::class, 'store']);
        Route::get('/classes/{id}', [ClassController::class, 'show'])->whereNumber('id');
        Route::post('/classes/{id}/enrollments', [ClassController::class, 'enroll'])->whereNumber('id');
        Route::get('/reference/academic-years', [ClassController::class, 'academicYears']);
        Route::get('/reference/grades', [ClassController::class, 'grades']);

        Route::get('/attendance/sessions', [AttendanceSessionController::class, 'index']);
        Route::post('/attendance/sessions', [AttendanceSessionController::class, 'store']);
        Route::get('/attendance/sessions/{id}', [AttendanceSessionController::class, 'show'])->whereNumber('id');
        Route::post('/attendance/sessions/{id}/records', [AttendanceSessionController::class, 'records'])->whereNumber('id');

        Route::prefix('student-operators')->group(function () {
            Route::get('/assignments', [OperatorAssignmentController::class, 'index']);
            Route::post('/assignments', [OperatorAssignmentController::class, 'store']);
            Route::get('/assignments/{id}/roster', [OperatorAssignmentController::class, 'roster'])->whereNumber('id');
            Route::post('/assignments/{id}/revoke', [OperatorAssignmentController::class, 'revoke'])->whereNumber('id');

            Route::get('/reports', [OperatorReportController::class, 'index']);
            Route::post('/reports', [OperatorReportController::class, 'store']);
            Route::get('/reports/{id}', [OperatorReportController::class, 'show'])->whereNumber('id');
            Route::post('/reports/{id}/approve', [OperatorReportController::class, 'approve'])->whereNumber('id');
            Route::post('/reports/{id}/reject', [OperatorReportController::class, 'reject'])->whereNumber('id');
            Route::post('/reports/{id}/resubmit', [OperatorReportController::class, 'resubmit'])->whereNumber('id');
        });

        Route::get('/reports/attendance', [AttendanceReportController::class, 'index']);

        Route::get('/settings/branding', [BrandingController::class, 'show']);
        Route::put('/settings/branding', [BrandingController::class, 'update']);
    });
});
