<?php

namespace App\Models;

use App\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AttendanceSession extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];
    protected $casts = ['session_date' => 'date:Y-m-d'];

    public function class() { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function records() { return $this->hasMany(AttendanceRecord::class, 'session_id'); }
}
