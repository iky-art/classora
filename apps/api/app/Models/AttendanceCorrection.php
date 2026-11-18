<?php

namespace App\Models;

use App\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrection extends Model
{
    use BelongsToSchool;

    const UPDATED_AT = null;
    protected $guarded = ['id', 'school_id'];
}
