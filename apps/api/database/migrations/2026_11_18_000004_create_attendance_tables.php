<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const STATUS = ['present', 'sick', 'excused', 'absent'];

    private function tenant(Blueprint $t): void
    {
        $t->id();
        $t->unsignedBigInteger('school_id');
        $t->foreign('school_id')->references('id')->on('schools')->restrictOnDelete();
    }

    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $t) {
            $this->tenant($t);
            $t->unsignedBigInteger('class_id');
            $t->unsignedBigInteger('semester_id')->nullable();
            $t->date('session_date');
            $t->string('period_key', 30)->default('daily'); // 'daily' atau label sesi, mis. 'jam-1'
            $t->unsignedBigInteger('created_by');
            $t->timestamps();
            $t->unique(['school_id', 'class_id', 'session_date', 'period_key'], 'att_sessions_class_date_period_unique');
            $t->unique(['school_id', 'id']);
            $t->index(['school_id', 'session_date']);
            $t->foreign(['school_id', 'class_id'])->references(['school_id', 'id'])->on('classes')->restrictOnDelete();
            $t->foreign(['school_id', 'semester_id'])->references(['school_id', 'id'])->on('semesters')->restrictOnDelete();
            $t->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('attendance_records', function (Blueprint $t) {
            $this->tenant($t);
            $t->unsignedBigInteger('session_id');
            $t->unsignedBigInteger('student_id');
            $t->enum('status', self::STATUS);
            $t->string('note', 255)->nullable();
            $t->unsignedBigInteger('recorded_by');
            $t->string('source', 30)->default('teacher'); // teacher | operator_approval
            $t->timestamps();
            $t->unique(['session_id', 'student_id']);
            $t->unique(['school_id', 'id']);
            $t->index(['school_id', 'student_id']);
            $t->foreign(['school_id', 'session_id'])->references(['school_id', 'id'])->on('attendance_sessions')->restrictOnDelete();
            $t->foreign(['school_id', 'student_id'])->references(['school_id', 'id'])->on('students')->restrictOnDelete();
            $t->foreign('recorded_by')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('attendance_corrections', function (Blueprint $t) {
            $this->tenant($t);
            $t->unsignedBigInteger('record_id');
            $t->enum('old_status', self::STATUS);
            $t->enum('new_status', self::STATUS);
            $t->string('reason', 255);
            $t->unsignedBigInteger('corrected_by');
            $t->timestamp('created_at')->nullable();
            $t->foreign(['school_id', 'record_id'])->references(['school_id', 'id'])->on('attendance_records')->restrictOnDelete();
            $t->foreign('corrected_by')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('student_operator_assignments', function (Blueprint $t) {
            $this->tenant($t);
            $t->unsignedBigInteger('class_id');
            $t->unsignedBigInteger('student_id');
            $t->unsignedBigInteger('assigned_by');
            $t->enum('mode', ['report_only', 'full_attendance']);
            $t->date('starts_on');
            $t->date('ends_on');
            $t->string('status', 20)->default('active'); // active | revoked
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->index(['school_id', 'student_id', 'status']);
            $t->foreign(['school_id', 'class_id'])->references(['school_id', 'id'])->on('classes')->restrictOnDelete();
            $t->foreign(['school_id', 'student_id'])->references(['school_id', 'id'])->on('students')->restrictOnDelete();
            $t->foreign('assigned_by')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('student_attendance_reports', function (Blueprint $t) {
            $this->tenant($t);
            $t->unsignedBigInteger('assignment_id');
            $t->unsignedBigInteger('class_id');
            $t->date('report_date');
            $t->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $t->unsignedInteger('revision')->default(1);
            $t->unsignedBigInteger('submitted_by');
            $t->timestamp('submitted_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'assignment_id', 'report_date'], 'att_reports_assignment_date_unique');
            $t->unique(['school_id', 'id']);
            $t->index(['school_id', 'class_id', 'status']);
            $t->foreign(['school_id', 'assignment_id'])->references(['school_id', 'id'])->on('student_operator_assignments')->restrictOnDelete();
            $t->foreign(['school_id', 'class_id'])->references(['school_id', 'id'])->on('classes')->restrictOnDelete();
            $t->foreign('submitted_by')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('student_attendance_report_items', function (Blueprint $t) {
            $this->tenant($t);
            $t->unsignedBigInteger('report_id');
            $t->unsignedBigInteger('student_id');
            $t->enum('status', self::STATUS);
            $t->string('note', 255)->nullable();
            $t->timestamps();
            $t->unique(['report_id', 'student_id']);
            $t->foreign(['school_id', 'report_id'])->references(['school_id', 'id'])->on('student_attendance_reports')->cascadeOnDelete();
            $t->foreign(['school_id', 'student_id'])->references(['school_id', 'id'])->on('students')->restrictOnDelete();
        });

        Schema::create('attendance_report_reviews', function (Blueprint $t) {
            $this->tenant($t);
            $t->unsignedBigInteger('report_id');
            $t->unsignedBigInteger('reviewer_id');
            $t->enum('decision', ['approved', 'rejected']);
            $t->string('note', 500)->nullable();
            $t->unsignedInteger('revision');
            $t->timestamp('created_at')->nullable();
            $t->foreign(['school_id', 'report_id'])->references(['school_id', 'id'])->on('student_attendance_reports')->restrictOnDelete();
            $t->foreign('reviewer_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        foreach (['attendance_report_reviews', 'student_attendance_report_items', 'student_attendance_reports', 'student_operator_assignments', 'attendance_corrections', 'attendance_records', 'attendance_sessions'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
