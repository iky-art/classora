<?php

namespace App\Models;

use App\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolClass extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $table = 'classes';
    protected $guarded = ['id', 'school_id'];

    public function academicYear() { return $this->belongsTo(AcademicYear::class); }
    public function grade() { return $this->belongsTo(Grade::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function homeroomTeacher() { return $this->belongsTo(Teacher::class, 'homeroom_teacher_id'); }
    public function activeEnrollments() { return $this->hasMany(ClassEnrollment::class, 'class_id')->whereNull('left_on'); }
}
