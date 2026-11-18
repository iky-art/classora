# API

Basis: `/api/v1`. Format JSON.

## Konvensi

**Autentikasi.** Cookie session Sanctum. Sebelum request mutasi, panggil `GET /sanctum/csrf-cookie`, lalu kirim cookie `XSRF-TOKEN` sebagai header `X-XSRF-TOKEN`. Request harus berasal dari domain di `SANCTUM_STATEFUL_DOMAINS`.

**Memilih sekolah.** Kirim header `X-School: <slug>`. Wajib bila pengguna punya lebih dari satu keanggotaan, dan harus cocok dengan keanggotaan aktif. Bila hanya satu keanggotaan, header boleh dikosongkan.

**Paginasi.** `?page=1&per_page=20` (maks. 100). Respons `{ "data": [...], "links": {...}, "meta": { current_page, last_page, per_page, total } }`.

**Format error**
```json
{ "error": { "code": "validation_failed", "message": "Data tidak valid.", "details": { "nis": ["The nis has already been taken."] } } }
```

| Status | `code` | Arti |
|---|---|---|
| 401 | `unauthenticated` | Belum masuk |
| 402 | `http_402` | Fitur membutuhkan paket Pro |
| 403 | `forbidden` / `no_tenant` | Tidak berizin / tidak punya akses ke sekolah itu |
| 404 | `not_found` | Tidak ada (termasuk data sekolah lain) |
| 409 | `http_409` | Konflik status (mis. laporan sudah diproses) |
| 422 | `validation_failed` | Validasi gagal |
| 429 | `http_429` | Terlalu banyak percobaan |

## Endpoint

| Method | Path | Izin | Keterangan |
|---|---|---|---|
| POST | `/auth/login` | publik | Dibatasi 5 percobaan per menit |
| POST | `/auth/logout` | login | |
| GET | `/auth/me` | login | Pengguna + keanggotaan sekolah |
| GET | `/auth/context` | tenant | Peran, izin, branding, penugasan petugas |
| GET, POST | `/schools` | Super Admin | POST membuat sekolah + admin pertama |
| GET | `/dashboard` | `dashboard.view` | Isi mengikuti izin |
| GET, POST | `/students` | `students.view` / `.manage` | Filter `q`, `status`, `class_id`; urut `name`, `nis`, `created_at` + `dir` |
| GET, PUT, DELETE | `/students/{id}` | `.view` / `.manage` | DELETE = arsip |
| GET, POST | `/teachers` | `teachers.view` / `.manage` | |
| GET, POST | `/classes` | `classes.view` / `.manage` | |
| GET | `/classes/{id}` | `classes.view` | Termasuk anggota aktif |
| POST | `/classes/{id}/enrollments` | `classes.manage` + `enrollments.manage` | Tambah atau pindahkan siswa |
| GET | `/reference/academic-years`, `/reference/grades` | tenant | Untuk dropdown |
| GET, POST | `/attendance/sessions` | `attendance.view` / `.record` | Dan akses ke kelas (wali atau pengampu) |
| GET | `/attendance/sessions/{id}` | `attendance.view` | Termasuk roster |
| POST | `/attendance/sessions/{id}/records` | `attendance.record` | Mengubah status lama butuh `reason` dan `attendance.correct` |
| GET, POST | `/student-operators/assignments` | `operators.assign` | Siswa hanya melihat miliknya |
| POST | `/student-operators/assignments/{id}/revoke` | `operators.assign` | |
| GET | `/student-operators/assignments/{id}/roster` | pemilik penugasan | Hanya selama penugasan aktif |
| GET, POST | `/student-operators/reports` | siswa terhubung (POST) | |
| GET | `/student-operators/reports/{id}` | pemilik atau pemeriksa | |
| POST | `/student-operators/reports/{id}/approve` | `operator_reports.review` | Bukan pembuat laporan; `overrides[]` opsional |
| POST | `/student-operators/reports/{id}/reject` | `operator_reports.review` | `note` wajib |
| POST | `/student-operators/reports/{id}/resubmit` | pemilik laporan | Hanya status `rejected` |
| GET | `/reports/attendance` | `reports.view` | `from`, `to` wajib; `class_id` opsional |
| GET, PUT | `/settings/branding` | `branding.manage` | PUT butuh fitur `white_label` |

## Contoh

**Menambah siswa**
```http
POST /api/v1/students
X-School: sekolah-contoh

{ "nis": "2026001", "nisn": null, "name": "Ani Wulandari", "gender": "P" }
```
```json
{ "data": { "id": 1, "nis": "2026001", "nisn": null, "name": "Ani Wulandari", "gender": "P", "status": "active", "birth_date": null } }
```
Field `school_id` di body, bila dikirim, diabaikan.

**Menyimpan absensi**
```http
POST /api/v1/attendance/sessions/12/records

{ "records": [ { "student_id": 1, "status": "present" }, { "student_id": 2, "status": "sick", "note": "surat dokter" } ] }
```
Respons berisi `data: { created, corrected, unchanged }` dan roster terbaru. Bila ada status yang berubah dari catatan sebelumnya, sertakan `"reason": "..."`.

**Petugas mengirim laporan (mode Report Only)**
```http
POST /api/v1/student-operators/reports

{ "assignment_id": 3, "report_date": "2026-11-18", "items": [ { "student_id": 2, "status": "absent", "note": "tanpa kabar" } ] }
```
Mode Report Only menolak status `present`. Mode Full Attendance mewajibkan seluruh siswa kelas.

**Guru menolak, petugas merevisi**
```http
POST /api/v1/student-operators/reports/7/reject
{ "note": "Budi hadir, cek lagi" }

POST /api/v1/student-operators/reports/7/resubmit
{ "items": [ { "student_id": 3, "status": "sick" } ] }
```

## Izin per peran

| Izin | Admin | Kepsek | Guru | Wali Kelas | Siswa |
|---|:-:|:-:|:-:|:-:|:-:|
| `dashboard.view` | ✓ | ✓ | ✓ | ✓ | ✓ |
| `students.view` | ✓ | ✓ | ✓ | ✓ | |
| `students.manage`, `teachers.manage`, `classes.manage`, `users.manage`, `settings.manage`, `branding.manage` | ✓ | | | | |
| `teachers.view` | ✓ | ✓ | | ✓ | |
| `classes.view` | ✓ | ✓ | ✓ | ✓ | |
| `enrollments.manage` | ✓ | | | ✓ | |
| `attendance.view`, `reports.view` | ✓ | ✓ | ✓ | ✓ | |
| `attendance.record`, `attendance.correct` | ✓ | | ✓ | ✓ | |
| `operators.assign`, `operator_reports.review` | ✓ | | ✓ | ✓ | |
| `attendance.manage_all` | ✓ | | | | |
| `audit.view`, `announcements.manage` | ✓ | ✓ | | | |

Guru dan Wali Kelas hanya mengelola kelas yang diampunya (sebagai wali kelas atau guru pengampu), kecuali punya `attendance.manage_all`.
