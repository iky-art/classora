<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolMembership extends Model
{
    protected $guarded = ['id'];

    public function school() { return $this->belongsTo(School::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function roles() { return $this->belongsToMany(Role::class, 'membership_roles', 'membership_id', 'role_id'); }
}
