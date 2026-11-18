<?php

namespace App\Models;

use App\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];
    protected $casts = ['starts_on' => 'date:Y-m-d', 'ends_on' => 'date:Y-m-d', 'is_active' => 'bool'];
}
