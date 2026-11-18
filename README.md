<p align="center">
  <img src="assets/brand/classora-logo-reference.png" alt="Classora" width="360">
</p>

<h3 align="center">Kelola Sekolah, Lebih Mudah.</h3>

<p align="center">
Platform manajemen sekolah dan kelas yang multi-tenant, modular, dan dirancang untuk dipasang sendiri (self-hosted).
</p>

---

## Ringkasan

Classora memungkinkan banyak sekolah mengelola siswa, guru, kelas, absensi, dan laporan dalam **satu instalasi**. Setiap sekolah terisolasi penuh: data, pengguna, tahun ajaran, dan branding milik sendiri.

Fitur khas: **Petugas Kelas**, yaitu siswa yang ditunjuk guru untuk melaporkan kehadiran kelasnya. Guru tetap memegang keputusan akhir. Setiap persetujuan berjalan dalam satu transaksi database dan tercatat di audit log.

## Status proyek

> **Tahap awal (pra-rilis).** Fondasi dan alur inti sudah berjalan, tetapi banyak modul belum dibuat. Jangan dipakai di produksi sebelum ditinjau.

**Sudah diverifikasi**

| Hal | Hasil |
|---|---|
| Test backend (PHPUnit, PHP 8.5.12, SQLite) | 14 test lulus, 55 assertion |
| Migrasi 45 tabel di MariaDB 13.0.2 | Berhasil |
| Seeder role, permission, dan paket | Berhasil |
| Frontend berjalan dan login ke API (sesi + CSRF) | Berhasil dicoba manual |

**Belum diverifikasi:** test frontend (Vitest), `tsc`, dan `vite build`; alur UI penuh untuk guru, siswa, dan petugas kelas; ekspor Excel/PDF; tampilan di berbagai ukuran layar.

## Fitur

| Modul | Backend | UI |
|---|:---:|:---:|
| Autentikasi (cookie session Sanctum), pilih sekolah | ✅ | ✅ |
| Multi-tenant, RBAC (permission + policy) | ✅ | ✅ |
| Siswa (CRUD, arsip, pencarian) | ✅ | ✅ |
| Kelas, anggota, perpindahan dengan riwayat | ✅ | ✅ |
| Absensi harian/sesi, koreksi beralasan, audit | ✅ | ✅ |
| Petugas Kelas (Report Only / Full Attendance) | ✅ | ✅ |
| Rekap kehadiran, ekspor Excel dan PDF (A4 landscape) | ✅ | ✅ |
| Branding / White Label (paket Pro) | ✅ | ✅ |
| Guru (daftar dan tambah) | ✅ | ❌ |
| Pengumuman dan notifikasi | tabel saja | ❌ |
| Manajemen pengguna dan role | ❌ | ❌ |
| Tahun ajaran, semester, tingkat, jurusan | tabel saja | ❌ |
| Import Excel, backup/restore, audit log viewer, unggah logo | ❌ | ❌ |

## Teknologi

| Lapisan | Pilihan |
|---|---|
| Frontend | React 19, Vite, TypeScript, Tailwind CSS 4, React Router, TanStack Query, Zod |
| Backend | PHP 8.3–8.5, Laravel 13, REST API, Sanctum |
| Database | MariaDB (InnoDB, utf8mb4, foreign key gabungan per sekolah) |
| Ekspor | ExcelJS, jsPDF (di browser) |
| Paket | pnpm (monorepo), Composer |

## Struktur repositori

```
classora/
├── apps/
│   ├── web/          SPA React
│   └── api/          Laravel REST API
├── packages/
│   ├── design-tokens/  palet dan token CSS
│   ├── shared-types/   tipe TypeScript bersama
│   └── api-contracts/  skema Zod
├── database/         ERD dan dokumentasi
├── docs/             dokumentasi
├── infrastructure/   Nginx, Apache, PHP, backup
└── assets/brand/     referensi logo
```

## Mulai cepat

Kebutuhan: PHP ≥ 8.3 (disarankan 8.5) dengan `pdo_mysql`, Composer 2, MariaDB 10.6+, Node 22, pnpm.

```bash
git clone https://github.com/iky-art/classora.git && cd classora

# Backend
cd apps/api
composer install
cp .env.example .env
php artisan key:generate
php artisan test                 # test memakai SQLite in-memory
php artisan classora:install     # database, migrasi, seeder, Super Admin
php artisan classora:school "Nama Sekolah" slug-sekolah "Nama Admin" admin@sekolah.test "sandi-min-12-karakter"
php artisan serve                # http://127.0.0.1:8000

# Frontend (terminal lain, dari root repo)
pnpm install
pnpm dev:web                     # http://localhost:5173 (proxy ke :8000)
```

Masuk sebagai **admin sekolah**, bukan Super Admin. Super Admin hanya mengelola platform dan tidak punya akses ke data sekolah.

Panduan lengkap termasuk Termux dan produksi: [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).

## Peran

| Peran | Cakupan utama |
|---|---|
| Super Admin | Platform: membuat sekolah |
| Admin Sekolah | Seluruh pengaturan dan data sekolah |
| Kepala Sekolah | Melihat data, laporan, audit |
| Guru | Melihat siswa/kelas, mengisi dan mengoreksi absensi, menunjuk dan memeriksa petugas kelas |
| Wali Kelas | Guru + anggota kelas |
| Siswa | Dashboard; menu Petugas Kelas hanya bila ada penugasan aktif |

Hak akses ditentukan di backend. Menu di frontend hanya kosmetik. Rinciannya di [docs/SECURITY.md](docs/SECURITY.md).

## Dokumentasi

| Dokumen | Isi |
|---|---|
| [PRODUCT](docs/PRODUCT.md) | Visi, peran, modul, aturan bisnis |
| [ARCHITECTURE](docs/ARCHITECTURE.md) | Arsitektur, tenant isolation, alur utama, risiko |
| [DATABASE](docs/DATABASE.md) | Tabel, integritas, [ERD](database/documentation/ERD.md) |
| [API](docs/API.md) | Endpoint, izin, contoh request dan response |
| [SECURITY](docs/SECURITY.md) | Model keamanan dan batasannya |
| [DEPLOYMENT](docs/DEPLOYMENT.md) | Instalasi server dan Termux |
| [CONTRIBUTING](docs/CONTRIBUTING.md) | Aturan berkontribusi |

## Peta jalan

1. Manajemen tahun ajaran, semester, tingkat, jurusan, dan guru di UI.
2. Manajemen pengguna, role, dan membership.
3. Pengumuman dan notifikasi.
4. Import siswa dari Excel.
5. Backup dan restore, audit log viewer, unggah logo dan favicon.
6. Ekspor sisi server untuk laporan besar.
7. Reset kata sandi, verifikasi email, 2FA.

**Catatan yang diketahui:** endpoint pendaftaran anggota kelas saat ini meminta `classes.manage` sekaligus `enrollments.manage`, sehingga wali kelas (yang hanya punya yang kedua) belum bisa memakainya.

## Lisensi

[MIT](LICENSE)
