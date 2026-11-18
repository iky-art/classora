<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolBranding;
use App\Models\SchoolMembership;
use App\Models\SchoolSubscription;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SchoolCommand extends Command
{
    protected $signature = 'classora:school {name} {slug} {admin_name} {admin_email} {admin_password}';
    protected $description = 'Membuat sekolah baru beserta akun Admin Sekolah-nya.';

    public function handle(): int
    {
        $slug = $this->argument('slug');
        $email = $this->argument('admin_email');

        if (! preg_match('/^[a-z0-9-]+$/', $slug)) {
            $this->error('Slug hanya boleh huruf kecil, angka, dan tanda hubung.');
            return self::FAILURE;
        }
        if (strlen($this->argument('admin_password')) < 12) {
            $this->error('Kata sandi admin minimal 12 karakter.');
            return self::FAILURE;
        }
        if (School::where('slug', $slug)->exists() || User::where('email', $email)->exists()) {
            $this->error('Slug atau email sudah dipakai.');
            return self::FAILURE;
        }

        DB::transaction(function () use ($slug, $email) {
            $school = School::create(['name' => $this->argument('name'), 'slug' => $slug]);
            SchoolBranding::create(['school_id' => $school->id]);
            SchoolSubscription::create([
                'school_id' => $school->id,
                'plan_id' => Plan::where('code', 'community')->firstOrFail()->id,
                'status' => 'active',
                'starts_on' => now()->toDateString(),
            ]);

            $admin = new User(['name' => $this->argument('admin_name'), 'email' => $email, 'password' => $this->argument('admin_password')]);
            $admin->save();
            $membership = SchoolMembership::create(['school_id' => $school->id, 'user_id' => $admin->id]);
            $membership->roles()->attach(Role::whereNull('school_id')->where('slug', 'school_admin')->firstOrFail()->id);
        });

        $this->info("Sekolah '{$this->argument('name')}' dibuat. Admin: {$email}");

        return self::SUCCESS;
    }
}
