<?php

namespace App\Models;

use App\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ClassEnrollment extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];
    protected $casts = ['joined_on' => 'date:Y-m-d', 'left_on' => 'date:Y-m-d'];

    public function student() { return $this->belongsTo(Student::class); }
    public function class() { return $this->belongsTo(SchoolClass::class, 'class_id'); }
}
