<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolDomain extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['verified' => 'bool'];
}
