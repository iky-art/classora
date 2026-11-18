<?php

namespace App\Tenancy;

use App\Models\School;
use App\Models\SchoolMembership;
use Illuminate\Support\Facades\DB;

/**
 * Konteks tenant per-request. Diisi HANYA oleh ResolveTenant (dari membership/domain
 * yang telah diotorisasi), tidak pernah dari school_id di body/query pengguna.
 */
final class TenantContext
{
    private ?int $schoolId = null;
    private ?School $school = null;
    private ?SchoolMembership $membership = null;
    private ?array $permissions = null;

    public function set(School $school, SchoolMembership $membership): void
    {
        $this->school = $school;
        $this->schoolId = $school->id;
        $this->membership = $membership;
        $this->permissions = null;
    }

    public function forget(): void
    {
        $this->school = null;
        $this->schoolId = null;
        $this->membership = null;
        $this->permissions = null;
    }

    public function has(): bool
    {
        return $this->schoolId !== null;
    }

    public function schoolId(): int
    {
        return $this->schoolId ?? throw new NoTenantException();
    }

    public function school(): School
    {
        return $this->school ?? throw new NoTenantException();
    }

    public function membership(): SchoolMembership
    {
        return $this->membership ?? throw new NoTenantException();
    }

    /** @return string[] */
    public function permissions(): array
    {
        if ($this->permissions === null) {
            $this->permissions = DB::table('membership_roles as mr')
                ->join('role_permissions as rp', 'rp.role_id', '=', 'mr.role_id')
                ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
                ->where('mr.membership_id', $this->membership()->id)
                ->distinct()
                ->pluck('p.slug')
                ->all();
        }

        return $this->permissions;
    }

    public function can(string $permission): bool
    {
        return $this->has() && $this->membership !== null && in_array($permission, $this->permissions(), true);
    }

    /** Menjalankan callback pada tenant tertentu (seeder, test, job). */
    public function runAs(int $schoolId, callable $callback): mixed
    {
        $previous = [$this->schoolId, $this->school, $this->membership, $this->permissions];
        $this->schoolId = $schoolId;
        $this->school = null;
        $this->membership = null;
        $this->permissions = null;

        try {
            return $callback();
        } finally {
            [$this->schoolId, $this->school, $this->membership, $this->permissions] = $previous;
        }
    }
}
