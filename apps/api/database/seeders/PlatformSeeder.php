<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Database\Seeder;

/** Data sistem (permission, role bawaan, paket). Tidak membuat akun default apa pun. */
class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Permissions::ALL as $slug => $description) {
            Permission::updateOrCreate(['slug' => $slug], ['description' => $description]);
        }

        foreach (Permissions::roles() as $slug => $def) {
            $role = Role::updateOrCreate(['school_id' => null, 'slug' => $slug], ['name' => $def['name'], 'is_system' => true]);
            $role->permissions()->sync(Permission::whereIn('slug', $def['permissions'])->pluck('id'));
        }

        Plan::updateOrCreate(['code' => 'community'], ['name' => 'Community (Gratis)']);
        $pro = Plan::updateOrCreate(['code' => 'pro'], ['name' => 'Pro']);
        foreach (Permissions::FEATURES as $feature) {
            PlanFeature::firstOrCreate(['plan_id' => $pro->id, 'feature' => $feature]);
        }
    }
}
