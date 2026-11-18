<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolBranding;
use App\Models\SchoolMembership;
use App\Models\SchoolSubscription;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SchoolController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->is_super_admin, 403);

        return ['data' => School::orderBy('name')->get(['id', 'name', 'slug', 'status'])];
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->is_super_admin, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9-]+$/', 'unique:schools,slug'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', Password::min(12)->letters()->numbers()],
        ]);

        $school = DB::transaction(function () use ($data) {
            $school = School::create(['name' => $data['name'], 'slug' => $data['slug']]);
            SchoolBranding::create(['school_id' => $school->id]);
            SchoolSubscription::create([
                'school_id' => $school->id, 'plan_id' => Plan::where('code', 'community')->firstOrFail()->id,
                'status' => 'active', 'starts_on' => now()->toDateString(),
            ]);

            $admin = new User(['name' => $data['admin_name'], 'email' => $data['admin_email'], 'password' => $data['admin_password']]);
            $admin->save();
            $membership = SchoolMembership::create(['school_id' => $school->id, 'user_id' => $admin->id]);
            $membership->roles()->attach(Role::whereNull('school_id')->where('slug', 'school_admin')->firstOrFail()->id);

            Audit::log('school.created', $school, ['admin_user_id' => $admin->id]);

            return $school;
        });

        return response()->json(['data' => $school->only(['id', 'name', 'slug', 'status'])], 201);
    }
}
