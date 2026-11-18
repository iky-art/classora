<?php

namespace App\Http\Controllers;

use App\Models\SchoolBranding;
use App\Models\SchoolMembership;
use App\Models\StudentOperatorAssignment;
use App\Models\StudentUserLink;
use App\Support\Entitlements;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);

        abort_unless($request->hasSession(), 400, 'Permintaan harus berasal dari domain frontend yang terdaftar di SANCTUM_STATEFUL_DOMAINS.');

        if (! Auth::guard('web')->attempt([...$credentials, 'status' => 'active'])) {
            throw ValidationException::withMessages(['email' => ['Email atau kata sandi salah.']]);
        }

        $request->session()->regenerate();
        $request->user('web')->forceFill(['last_login_at' => now()])->save();

        return $this->me($request);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $memberships = SchoolMembership::with(['school:id,name,slug', 'roles:id,slug,name'])
            ->where('user_id', $user->id)->where('status', 'active')->get();

        return ['data' => [
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'is_super_admin' => $user->is_super_admin],
            'memberships' => $memberships->map(fn ($m) => [
                'school' => ['id' => $m->school->id, 'name' => $m->school->name, 'slug' => $m->school->slug],
                'roles' => $m->roles->pluck('slug'),
            ])->values(),
        ]];
    }

    /** Izin, peran, branding, dan penugasan petugas kelas pada sekolah aktif. */
    public function context(Request $request, TenantContext $context)
    {
        $school = $context->school();
        $branding = Entitlements::has($school->id, 'white_label')
            ? SchoolBranding::where('school_id', $school->id)->first()?->only(['app_name', 'primary_color', 'login_title', 'login_message', 'report_header'])
            : null;

        $studentId = StudentUserLink::where('user_id', $request->user()->id)->value('student_id');
        $today = now()->toDateString();
        $assignments = $studentId
            ? StudentOperatorAssignment::with('class:id,name')->where('student_id', $studentId)->where('status', 'active')
                ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->get()
                ->map(fn ($a) => ['id' => $a->id, 'class' => ['id' => $a->class->id, 'name' => $a->class->name], 'mode' => $a->mode,
                    'starts_on' => $a->starts_on->toDateString(), 'ends_on' => $a->ends_on->toDateString()])
            : collect();

        return ['data' => [
            'school' => ['id' => $school->id, 'name' => $school->name, 'slug' => $school->slug],
            'roles' => $context->membership()->roles()->pluck('slug'),
            'permissions' => $context->permissions(),
            'branding' => $branding,
            'operator_assignments' => $assignments->values(),
        ]];
    }
}
