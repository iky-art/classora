<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug', 80)->unique();
            $t->string('npsn', 20)->nullable();
            $t->string('status', 20)->default('active');
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('school_settings', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('school_id');
            $t->string('key', 120);
            $t->json('value')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'key']);
            $t->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
        });

        Schema::create('school_branding', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('school_id')->unique();
            $t->string('app_name')->nullable();
            $t->string('logo_path')->nullable();
            $t->string('favicon_path')->nullable();
            $t->char('primary_color', 7)->nullable();
            $t->string('login_title')->nullable();
            $t->string('login_message', 500)->nullable();
            $t->string('report_header', 500)->nullable();
            $t->string('public_page_title')->nullable();
            $t->timestamps();
            $t->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
        });

        Schema::create('school_domains', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('school_id');
            $t->string('domain')->unique();
            $t->boolean('verified')->default(false);
            $t->timestamps();
            $t->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
        });

        Schema::create('plans', function (Blueprint $t) {
            $t->id();
            $t->string('code', 40)->unique();
            $t->string('name');
            $t->timestamps();
        });

        Schema::create('plan_features', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('plan_id');
            $t->string('feature', 60);
            $t->unique(['plan_id', 'feature']);
            $t->foreign('plan_id')->references('id')->on('plans')->cascadeOnDelete();
        });

        Schema::create('school_subscriptions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('school_id');
            $t->unsignedBigInteger('plan_id');
            $t->string('status', 20)->default('active');
            $t->date('starts_on');
            $t->date('ends_on')->nullable();
            $t->timestamps();
            $t->index(['school_id', 'status']);
            $t->foreign('school_id')->references('id')->on('schools')->restrictOnDelete();
            $t->foreign('plan_id')->references('id')->on('plans')->restrictOnDelete();
        });

        Schema::create('licenses', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('school_id');
            $t->unsignedBigInteger('plan_id');
            $t->char('license_key_hash', 64)->unique(); // hanya hash, kunci asli tidak disimpan
            $t->timestamp('issued_at')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamp('revoked_at')->nullable();
            $t->timestamps();
            $t->foreign('school_id')->references('id')->on('schools')->restrictOnDelete();
            $t->foreign('plan_id')->references('id')->on('plans')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        foreach (['licenses', 'school_subscriptions', 'plan_features', 'plans', 'school_domains', 'school_branding', 'school_settings', 'schools'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
