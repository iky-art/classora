<?php

namespace App\Models;

use App\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $guarded = ['id', 'school_id'];
    protected $casts = ['birth_date' => 'date:Y-m-d'];

    public function enrollments() { return $this->hasMany(ClassEnrollment::class); }
    public function activeEnrollment() { return $this->hasOne(ClassEnrollment::class)->whereNull('left_on'); }
    public function userLink() { return $this->hasOne(StudentUserLink::class); }
}
