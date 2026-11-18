<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/** Pemeriksaan fitur Pro berdasarkan langganan aktif sekolah (tanpa payment gateway di MVP). */
final class Entitlements
{
    public static function has(int $schoolId, string $feature): bool
    {
        $today = now()->toDateString();

        return DB::table('school_subscriptions as ss')
            ->join('plan_features as pf', 'pf.plan_id', '=', 'ss.plan_id')
            ->where('ss.school_id', $schoolId)
            ->where('ss.status', 'active')
            ->whereDate('ss.starts_on', '<=', $today)
            ->where(fn ($q) => $q->whereNull('ss.ends_on')->orWhereDate('ss.ends_on', '>=', $today))
            ->where('pf.feature', $feature)
            ->exists();
    }
}
