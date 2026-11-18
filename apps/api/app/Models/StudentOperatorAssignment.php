<?php

namespace App\Models;

use App\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class StudentOperatorAssignment extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];
    protected $casts = ['starts_on' => 'date:Y-m-d', 'ends_on' => 'date:Y-m-d'];

    public function class() { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function student() { return $this->belongsTo(Student::class); }

    public function isActiveOn(string $date): bool
    {
        return $this->status === 'active'
            && $this->starts_on->toDateString() <= $date
            && $this->ends_on->toDateString() >= $date;
    }
}
