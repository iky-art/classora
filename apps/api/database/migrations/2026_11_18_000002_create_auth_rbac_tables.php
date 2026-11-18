<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->timestamp('email_verified_at')->nullable();
            $t->string('password');
            $t->boolean('is_super_admin')->default(false);
            $t->string('status', 20)->default('active');
            $t->timestamp('last_login_at')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('email')->primary();
            $t->string('token');
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->longText('payload');
            $t->integer('last_activity')->index();
        });

        Schema::create('school_memberships', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('school_id');
            $t->unsignedBigInteger('user_id');
            $t->string('status', 20)->default('active');
            $t->timestamps();
            $t->unique(['school_id', 'user_id']);
            $t->unique(['school_id', 'id']);
            $t->foreign('school_id')->references('id')->on('schools')->restrictOnDelete();
            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('school_id')->nullable(); // null = role sistem
            $t->string('slug', 60);
            $t->string('name');
            $t->boolean('is_system')->default(true);
            $t->timestamps();
            $t->unique(['school_id', 'slug']);
            $t->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
        });

        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 80)->unique();
            $t->string('description')->nullable();
        });

        Schema::create('role_permissions', function (Blueprint $t) {
            $t->unsignedBigInteger('role_id');
            $t->unsignedBigInteger('permission_id');
            $t->primary(['role_id', 'permission_id']);
            $t->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $t->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
        });

        Schema::create('membership_roles', function (Blueprint $t) {
            $t->unsignedBigInteger('membership_id');
            $t->unsignedBigInteger('role_id');
            $t->primary(['membership_id', 'role_id']);
            $t->foreign('membership_id')->references('id')->on('school_memberships')->cascadeOnDelete();
            $t->foreign('role_id')->references('id')->on('roles')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        foreach (['membership_roles', 'role_permissions', 'permissions', 'roles', 'school_memberships', 'sessions', 'password_reset_tokens', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
