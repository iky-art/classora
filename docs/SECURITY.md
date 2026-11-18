# Keamanan

Dokumen ini menjelaskan apa yang sudah diterapkan **dan** apa yang belum. Lapor kerentanan secara privat kepada pemilik repositori, jangan lewat issue publik.

## Yang sudah diterapkan

| Area | Penerapan |
|---|---|
| Autentikasi | Sanctum cookie session; session diregenerasi saat login; batas 5 percobaan login per menit per email+IP; kata sandi di-hash (`hashed` cast) |
| Otorisasi | Policy dan permission di backend untuk setiap endpoint. Frontend hanya menyembunyikan menu. |
| Isolasi tenant | Lihat [ARCHITECTURE](ARCHITECTURE.md): penentuan sekolah dari keanggotaan, scope fail-closed, validasi relasi per sekolah, foreign key gabungan |
| Validasi input | Form Request pada semua endpoint tulis; Eloquent dan query builder berparameter |
| Aturan Petugas Kelas | Tidak bisa menunjuk atau menyetujui diri sendiri; pemeriksa bukan pembuat laporan; siswa hanya mengakses penugasannya |
| Audit | `audit_logs` append-only di model dan di database (trigger); koreksi absensi dan keputusan tercatat |
| Integritas persetujuan | Transaksi dengan penguncian baris; laporan yang sudah diproses menolak aksi kedua (409) |
| Rahasia | Tidak ada akun default. `.env` tidak masuk repositori. Kunci lisensi hanya berupa hash. |
| Format error | Tidak membocorkan nama model atau stack trace pada respons API |

Diuji otomatis: `apps/api/tests/Security/TenantIsolationTest.php` (7 skenario) dan alur Petugas Kelas di `tests/Feature`.

## Yang belum ada

- Unggah file (logo, favicon) belum ada, sehingga validasi unggah juga belum.
- Reset kata sandi, verifikasi email, 2FA.
- Daftar sesi aktif dan pencabutan sesi.
- Backup terenkripsi otomatis. `infrastructure/backup/backup.sh` hanya contoh dasar.
- Pembatasan laju untuk endpoint selain login dan batas umum 120 permintaan per menit.
- Penetration test dan tinjauan keamanan independen.

## Praktik wajib untuk pemasang

1. HTTPS, dan `SESSION_SECURE_COOKIE=true` di produksi.
2. `APP_ENV=production`, `APP_DEBUG=false`.
3. Kata sandi database kuat; user database hanya punya hak pada database `classora`.
4. Kata sandi Super Admin minimal 12 karakter.
5. Simpan backup di luar server dan uji pemulihannya.
6. Perbarui dependensi (`composer update`, `pnpm update`) secara berkala.

## Catatan

- Installer menampilkan input sandi secara terbuka karena beberapa keyboard Android tidak bisa mengetik di input tersembunyi. Pastikan tidak ada yang melihat layarmu, dan bersihkan riwayat shell setelahnya.
- Jangan pernah membagikan isi `.env`.
