# ERD

Semua tabel milik sekolah membawa `school_id`. Relasi antar-tabel sekolah memakai foreign key gabungan `(school_id, id)`.

## Platform, akses, dan paket

```mermaid
erDiagram
    schools ||--o{ school_memberships : memiliki
    users ||--o{ school_memberships : bergabung
    school_memberships }o--o{ roles : membership_roles
    roles }o--o{ permissions : role_permissions
    schools ||--o| school_branding : branding
    schools ||--o{ school_domains : domain
    schools ||--o{ school_subscriptions : langganan
    plans ||--o{ school_subscriptions : paket
    plans ||--o{ plan_features : fitur
    schools ||--o{ licenses : lisensi
```

## Akademik dan kehadiran

```mermaid
erDiagram
    academic_years ||--o{ semesters : memiliki
    academic_years ||--o{ classes : memiliki
    grades ||--o{ classes : tingkat
    departments ||--o{ classes : jurusan
    teachers ||--o{ classes : wali_kelas
    classes ||--o{ class_subjects : mapel
    subjects ||--o{ class_subjects : diajarkan
    class_subjects ||--o{ teacher_assignments : pengampu
    teachers ||--o{ teacher_assignments : mengajar
    students ||--o{ class_enrollments : riwayat_kelas
    classes ||--o{ class_enrollments : anggota
    students ||--o| student_user_links : akun
    students ||--o{ student_guardians : wali
    classes ||--o{ attendance_sessions : sesi
    attendance_sessions ||--o{ attendance_records : catatan
    students ||--o{ attendance_records : status
    attendance_records ||--o{ attendance_corrections : koreksi
```

## Petugas kelas

```mermaid
erDiagram
    classes ||--o{ student_operator_assignments : penugasan
    students ||--o{ student_operator_assignments : petugas
    student_operator_assignments ||--o{ student_attendance_reports : laporan
    student_attendance_reports ||--o{ student_attendance_report_items : butir
    student_attendance_reports ||--o{ attendance_report_reviews : keputusan
```

## Komunikasi dan sistem

`announcements`, `announcement_targets`, `notifications`, `report_exports`, `audit_logs`, `backup_logs`, `import_jobs`, `system_settings`, `public_share_links`. Lihat [DATABASE.md](../../docs/DATABASE.md).
