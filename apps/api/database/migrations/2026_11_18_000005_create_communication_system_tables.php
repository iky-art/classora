<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function tenant(Blueprint $t): void
    {
        $t->id();
        $t->unsignedBigInteger('school_id');
        $t->foreign('school_id')->references('id')->on('schools')->restrictOnDelete();
    }

    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $t) {
            $this->tenant($t);
            $t->unsignedBigInteger('class_id')->nullable();
            $t->string('title');
            $t->text('body');
            $t->string('status', 20)->default('draft');
            $t->timestamp('published_at')->nullable();
            $t->unsignedBigInteger('created_by');
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->foreign(['school_id', 'class_id'])->references(['school_id', 'id'])->on('classes')->restrictOnDelete();
            $t->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('announcement_targets', function (Blueprint $t) {
            $this->tenant($t);
            $t->unsignedBigInteger('announcement_id');
            $t->string('target_type', 30); // all | role | class
            $t->string('target_value', 60)->nullable();
            $t->foreign(['school_id', 'announcement_id'])->references(['school_id', 'id'])->on('announcements')->cascadeOnDelete();
        });

        Schema::create('notifications', function (Blueprint $t) {
            $this->tenant($t);
            $t->unsignedBigInteger('user_id');
            $t->string('type', 60);
            $t->json('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
            $t->index(['school_id', 'user_id', 'read_at']);
            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('report_exports', function (Blueprint $t) {
            $this->tenant($t);
            $t->unsignedBigInteger('user_id');
            $t->string('type', 40);
            $t->string('format', 10);
            $t->json('params')->nullable();
            $t->string('file_path')->nullable();
            $t->string('status', 20)->default('done');
            $t->timestamps();
            $t->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('school_id')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('action', 80);
            $t->string('subject_type', 80)->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->json('meta')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->nullable();
            $t->index(['school_id', 'created_at']);
            $t->index(['subject_type', 'subject_id']);
        });

        Schema::create('backup_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('school_id')->nullable();
            $t->string('type', 20);
            $t->string('status', 20);
            $t->string('path')->nullable();
            $t->unsignedBigInteger('size_bytes')->nullable();
            $t->timestamps();
        });

        Schema::create('import_jobs', function (Blueprint $t) {
            $this->tenant($t);
            $t->unsignedBigInteger('user_id');
            $t->string('type', 30);
            $t->string('status', 20)->default('pending');
            $t->json('summary')->nullable();
            $t->timestamps();
            $t->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('system_settings', function (Blueprint $t) {
            $t->string('key', 120)->primary();
            $t->json('value')->nullable();
            $t->timestamps();
        });

        Schema::create('public_share_links', function (Blueprint $t) {
            $this->tenant($t);
            $t->char('token_hash', 64)->unique();
            $t->string('scope', 40);
            $t->timestamp('expires_at')->nullable();
            $t->timestamp('revoked_at')->nullable();
            $t->unsignedBigInteger('created_by');
            $t->timestamps();
            $t->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
        });

        // Audit log append-only di level database (MariaDB/MySQL).
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::unprepared("CREATE TRIGGER audit_logs_no_update BEFORE UPDATE ON audit_logs FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_logs is append-only'");
            DB::unprepared("CREATE TRIGGER audit_logs_no_delete BEFORE DELETE ON audit_logs FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_logs is append-only'");
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::unprepared('DROP TRIGGER IF EXISTS audit_logs_no_update');
            DB::unprepared('DROP TRIGGER IF EXISTS audit_logs_no_delete');
        }
        foreach (['public_share_links', 'system_settings', 'import_jobs', 'backup_logs', 'audit_logs', 'report_exports', 'notifications', 'announcement_targets', 'announcements'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
