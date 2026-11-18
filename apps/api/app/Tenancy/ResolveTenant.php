<?php

namespace App\Tenancy;

use App\Models\SchoolDomain;
use App\Models\SchoolMembership;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Urutan: domain terverifikasi -> header X-School (slug) -> satu-satunya membership.
 * Apa pun sumbernya, hasilnya WAJIB cocok dengan membership aktif milik pengguna.
 */
class ResolveTenant
{
    public function __construct(private TenantContext $context)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $this->context->forget();

        $user = $request->user();
        abort_unless($user, 401, 'Silakan masuk terlebih dahulu.');

        $memberships = SchoolMembership::with('school')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->get();

        $membership = null;
        $domainSchoolId = SchoolDomain::where('domain', $request->getHost())->where('verified', true)->value('school_id');

        if ($domainSchoolId) {
            $membership = $memberships->firstWhere('school_id', $domainSchoolId);
        } elseif ($slug = $request->header('X-School')) {
            $membership = $memberships->first(fn ($m) => $m->school?->slug === $slug);
        } elseif ($memberships->count() === 1) {
            $membership = $memberships->first();
        }

        if (! $membership || $membership->school?->status !== 'active') {
            throw new NoTenantException('Kamu tidak memiliki akses aktif ke sekolah yang diminta.');
        }

        $this->context->set($membership->school, $membership);

        return $next($request);
    }
}
