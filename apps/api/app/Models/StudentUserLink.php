<?php

namespace App\Models;

use App\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class StudentUserLink extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

}
