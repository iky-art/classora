# Database

MariaDB 10.6+ (diuji di 13.0.2), InnoDB, utf8mb4. Kunci primer `BIGINT UNSIGNED`. Diagram: [ERD](../database/documentation/ERD.md).

## Tabel (45)

| Kelompok | Tabel |
|---|---|
| Platform | `schools`, `school_settings`, `school_branding`, `school_domains`, `plans`, `plan_features`, `school_subscriptions`, `licenses` |
| Autentikasi dan RBAC | `users`, `school_memberships`, `roles`, `permissions`, `role_permissions`, `membership_roles`, `sessions`, `password_reset_tokens` |
| Akademik | `academic_years`, `semesters`, `grades`, `departments`, `classes`, `subjects`, `class_subjects` |
| Siswa dan guru | `students`, `teachers`, `class_enrollments`, `teacher_assignments`, `student_guardians`, `student_user_links` |
| Absensi | `attendance_sessions`, `attendance_records`, `attendance_corrections` |
| Petugas kelas | `student_operator_assignments`, `student_attendance_reports`, `student_attendance_report_items`, `attendance_report_reviews` |
| Komunikasi | `announcements`, `announcement_targets`, `notifications`, `report_exports` |
| Sistem | `audit_logs`, `backup_logs`, `import_jobs`, `system_settings`, `public_share_links` |

## Jaminan integritas

| Jaminan | Mekanisme |
|---|---|
| Tidak ada relasi lintas sekolah | Unik `(school_id, id)` di induk + foreign key gabungan di anak |
| Satu enrollment aktif per siswa | Kolom virtual `active_student_id` (`left_on IS NULL` → `student_id`) bersifat unik |
| Riwayat kelas tidak hilang | Pindah kelas mengisi `left_on` dan membuat baris baru |
| Satu catatan per siswa per sesi | Unik `(session_id, student_id)` |
| Satu sesi per kelas, tanggal, periode | Unik `(school_id, class_id, session_date, period_key)` |
| Satu laporan petugas per penugasan per tanggal | Unik `(school_id, assignment_id, report_date)` |
| Perubahan absensi terlacak | `attendance_corrections` menyimpan status lama, baru, alasan, pelaku |
| Audit tidak bisa diubah | Model menolak update/delete; trigger MariaDB `BEFORE UPDATE/DELETE` memicu `SIGNAL` |
| Kunci lisensi tidak bocor | Hanya hash (`license_key_hash`) |
| Statistik tidak menjadi sumber data | Rekap selalu dihitung dari `attendance_records` |

Pengarsipan (*soft delete*): `schools`, `students`, `teachers`, `classes`.

## Catatan teknis

- Nama indeks otomatis Laravel yang melebihi 64 karakter ditolak MariaDB. Dua indeks panjang diberi nama eksplisit (`att_sessions_class_date_period_unique`, `att_reports_assignment_date_unique`). Beri nama eksplisit pada indeks panjang berikutnya.
- Test memakai SQLite in-memory. Trigger audit hanya dibuat di MariaDB/MySQL.
- Migrasi, model, dan seeder ada di `apps/api/database` dan `apps/api/app/Models`.
