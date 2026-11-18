<?php

namespace App\Models;

use App\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    use BelongsToSchool;

    protected $table = 'notifications';
    protected $guarded = ['id', 'school_id'];
    protected $casts = ['data' => 'array', 'read_at' => 'datetime'];
}
