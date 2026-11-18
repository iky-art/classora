<?php

namespace App\Models;

use App\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];
    protected $casts = ['published_at' => 'datetime'];
}
