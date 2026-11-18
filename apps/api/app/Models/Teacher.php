<?php

namespace App\Models;

use App\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $guarded = ['id', 'school_id'];

    public function assignments() { return $this->hasMany(TeacherAssignment::class); }
}
