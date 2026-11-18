<?php

namespace App\Models;

use App\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class StudentAttendanceReport extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];
    protected $casts = ['report_date' => 'date:Y-m-d', 'submitted_at' => 'datetime'];

    public function items() { return $this->hasMany(StudentAttendanceReportItem::class, 'report_id'); }
    public function assignment() { return $this->belongsTo(StudentOperatorAssignment::class, 'assignment_id'); }
    public function class() { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function reviews() { return $this->hasMany(AttendanceReportReview::class, 'report_id'); }
}
