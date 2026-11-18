<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function tenant(Blueprint $t): void
    {
        $t->id();
        $t->unsignedBigInteger('school_id');
        $t->foreign('school_id')->references('id')->on('schools')->restrictOnDelete();
    }

    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name', 40);
            $t->date('starts_on');
            $t->date('ends_on');
            $t->boolean('is_active')->default(false);
            $t->timestamps();
            $t->unique(['school_id', 'name']);
            $t->unique(['school_id', 'id']);
        });

        Schema::create('semesters', function (Blueprint $t) {
            $this->tenant($t);
            $t->unsignedBigInteger('academic_year_id');
            $t->string('name', 40);
            $t->date('starts_on');
            $t->date('ends_on');
            $t->boolean('is_active')->default(false);
            $t->timestamps();
            $t->unique(['school_id', 'academic_year_id', 'name']);
            $t->unique(['school_id', 'id']);
            $t->foreign(['school_id', 'academic_year_id'])->references(['school_id', 'id'])->on('academic_years')->restrictOnDelete();
        });

        Schema::create('grades', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name', 20);
            $t->unsignedTinyInteger('level');
            $t->timestamps();
            $t->unique(['school_id', 'name']);
            $t->unique(['school_id', 'id']);
        });

        Schema::create('departments', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('code', 20);
            $t->string('name');
            $t->timestamps();
            $t->unique(['school_id', 'code']);
            $t->unique(['school_id', 'id']);
        });

        Schema::create('subjects', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('code', 30);
            $t->string('name');
            $t->timestamps();
            $t->unique(['school_id', 'code']);
            $t->unique(['school_id', 'id']);
        });

        Schema::create('teachers', function (Blueprint $t) {
            $this->tenant($t);
            $t->unsignedBigInteger('membership_id')->nullable();
            $t->string('employee_number', 40)->nullable();
            $t->string('name');
            $t->string('status', 20)->default('active');
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['school_id', 'employee_number']);
            $t->unique(['school_id', 'id']);
            $t->foreign(['school_id', 'membership_id'])->references(['school_id', 'id'])->on('school_memberships')->restrictOnDelete();
        });

        Schema::create('students', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('nis', 30);
            $t->string('nisn', 20)->nullable();
            $t->string('name');
            $t->enum('gender', ['L', 'P']);
            $t->string('status', 20)->default('active');
            $t->date('birth_date')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['school_id', 'nis']);
            $t->unique(['school_id', 'nisn']);
            $t->unique(['school_id', 'id']);
            $t->index(['school_id', 'name']);
        });

        Schema::create('classes', function (Blueprint $t) {
            $this->tenant($t);
            $t->unsignedBigInteger('academic_year_id');
            $t->unsignedBigInteger('grade_id');
            $t->unsignedBigInteger('department_id')->nullable();
            $t->string('name', 60);
            $t->unsignedBigInteger('homeroom_teacher_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['school_id', 'academic_year_id', 'name']);
            $t->unique(['school_id', 'id']);
            $t->foreign(['school_id', 'academic_year_id'])->references(['school_id', 'id'])->on('academic_years')->restrictOnDelete();
            $t->foreign(['school_id', 'grade_id'])->references(['school_id', 'id'])->on('grades')->restrictOnDelete();
            $t->foreign(['school_id', 'department_id'])->references(['school_id', 'id'])->on('departments')->restrictOnDelete();
            $t->foreign(['school_id', 'homeroom_teacher_id'])->references(['school_id', 'id'])->on('teachers')->restrictOnDelete();
        });

        Schema::create('class_subjects', function (Blueprint $t) {
            $this->tenant($t);
            $t->unsignedBigInteger('class_id');
            $t->unsignedBigInteger('subject_id');
            $t->timestamps();
            $t->unique(['school_id', 'class_id', 'subject_id']);
            $t->unique(['school_id', 'id']);
            $t->foreign(['school_id', 'class_id'])->references(['school_id', 'id'])->on('classes')->restrictOnDelete();
            $t->foreign(['school_id', 'subject_id'])->references(['school_id', 'id'])->on('subjects')->restrictOnDelete();
        });

        Schema::create('teacher_assignments', function (Blueprint $t) {
            $this->tenant($t);
            $t->unsignedBigInteger('class_subject_id');
            $t->unsignedBigInteger('teacher_id');
            $t->timestamps();
            $t->unique(['school_id', 'class_subject_id', 'teacher_id']);
            $t->foreign(['school_id', 'class_subject_id'])->references(['school_id', 'id'])->on('class_subjects')->restrictOnDelete();
            $t->foreign(['school_id', 'teacher_id'])->references(['school_id', 'id'])->on('teachers')->restrictOnDelete();
        });

        // Riwayat kelas: baris tidak pernah dihapus saat siswa pindah, hanya left_on yang diisi.
        Schema::create('class_enrollments', function (Blueprint $t) {
            $this->tenant($t);
            $t->unsignedBigInteger('student_id');
            $t->unsignedBigInteger('class_id');
            $t->date('joined_on');
            $t->date('left_on')->nullable();
            $t->string('left_reason', 120)->nullable();
            $t->unsignedBigInteger('active_student_id')->nullable()
                ->virtualAs('CASE WHEN left_on IS NULL THEN student_id END');
            $t->timestamps();
            $t->unique('active_student_id'); // satu siswa hanya boleh punya satu enrollment aktif
            $t->index(['school_id', 'class_id', 'left_on']);
            $t->index(['school_id', 'student_id']);
            $t->unique(['school_id', 'id']);
            $t->foreign(['school_id', 'student_id'])->references(['school_id', 'id'])->on('students')->restrictOnDelete();
            $t->foreign(['school_id', 'class_id'])->references(['school_id', 'id'])->on('classes')->restrictOnDelete();
        });

        Schema::create('student_guardians', function (Blueprint $t) {
            $this->tenant($t);
            $t->unsignedBigInteger('student_id');
            $t->string('name');
            $t->string('relation', 40);
            $t->string('phone', 30)->nullable();
            $t->timestamps();
            $t->foreign(['school_id', 'student_id'])->references(['school_id', 'id'])->on('students')->restrictOnDelete();
        });

        Schema::create('student_user_links', function (Blueprint $t) {
            $this->tenant($t);
            $t->unsignedBigInteger('student_id')->unique();
            $t->unsignedBigInteger('user_id');
            $t->timestamps();
            $t->unique(['school_id', 'user_id']);
            $t->foreign(['school_id', 'student_id'])->references(['school_id', 'id'])->on('students')->restrictOnDelete();
            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        foreach (['student_user_links', 'student_guardians', 'class_enrollments', 'teacher_assignments', 'class_subjects', 'classes', 'students', 'teachers', 'subjects', 'departments', 'grades', 'semesters', 'academic_years'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
