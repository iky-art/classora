<?php

namespace App\Models;

use App\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public function student() { return $this->belongsTo(Student::class); }
    public function session() { return $this->belongsTo(AttendanceSession::class, 'session_id'); }
}
