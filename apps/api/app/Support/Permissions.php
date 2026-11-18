<?php

namespace App\Support;

final class Permissions
{
    public const ALL = [
        'dashboard.view' => 'Melihat dashboard',
        'students.view' => 'Melihat data siswa',
        'students.manage' => 'Mengelola data siswa',
        'teachers.view' => 'Melihat data guru',
        'teachers.manage' => 'Mengelola data guru',
        'classes.view' => 'Melihat kelas',
        'classes.manage' => 'Mengelola kelas',
        'enrollments.manage' => 'Mengelola anggota kelas',
        'attendance.view' => 'Melihat absensi',
        'attendance.record' => 'Mengisi absensi',
        'attendance.correct' => 'Mengoreksi absensi',
        'attendance.manage_all' => 'Mengelola absensi semua kelas',
        'operators.assign' => 'Menunjuk petugas kelas',
        'operator_reports.review' => 'Memeriksa laporan petugas kelas',
        'reports.view' => 'Melihat rekap dan laporan',
        'announcements.manage' => 'Mengelola pengumuman',
        'users.manage' => 'Mengelola pengguna dan akses',
        'settings.manage' => 'Mengelola pengaturan sekolah',
        'branding.manage' => 'Mengelola branding',
        'audit.view' => 'Melihat audit log',
    ];

    /** @return array<string, array{name: string, permissions: string[]}> */
    public static function roles(): array
    {
        $teacher = [
            'dashboard.view', 'students.view', 'classes.view', 'attendance.view', 'attendance.record',
            'attendance.correct', 'operators.assign', 'operator_reports.review', 'reports.view',
        ];

        return [
            'school_admin' => ['name' => 'Admin Sekolah', 'permissions' => array_keys(self::ALL)],
            'headmaster' => ['name' => 'Kepala Sekolah', 'permissions' => [
                'dashboard.view', 'students.view', 'teachers.view', 'classes.view', 'attendance.view',
                'reports.view', 'audit.view', 'announcements.manage',
            ]],
            'teacher' => ['name' => 'Guru', 'permissions' => $teacher],
            'homeroom_teacher' => ['name' => 'Wali Kelas', 'permissions' => array_merge($teacher, ['teachers.view', 'enrollments.manage'])],
            'student' => ['name' => 'Siswa', 'permissions' => ['dashboard.view']],
            'student_operator' => ['name' => 'Petugas Kelas', 'permissions' => ['dashboard.view']],
        ];
    }

    public const FEATURES = ['white_label', 'advanced_analytics', 'custom_reports', 'advanced_audit', 'automated_backup', 'integrations'];
}
