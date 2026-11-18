# Deployment

Classora dipasang sendiri oleh sekolah. Docker tidak diwajibkan.

## Kebutuhan

| Komponen | Versi |
|---|---|
| OS | Linux (Termux untuk pengembangan di Android) |
| PHP | ≥ 8.3, disarankan 8.5. Ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `ctype`, `json`, `tokenizer`, `xml` |
| Composer | 2.x |
| MariaDB | 10.6+ (diuji 13.0.2) |
| Node.js dan pnpm | Node 22, untuk membangun frontend |
| Web server | Nginx atau Apache |
| HTTPS | Wajib di produksi |

## Instalasi server

```bash
git clone https://github.com/iky-art/classora.git /var/www/classora
cd /var/www/classora

# Database (di MariaDB)
#   CREATE DATABASE classora CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
#   CREATE USER 'classora'@'localhost' IDENTIFIED BY '<sandi-kuat>';
#   GRANT ALL ON classora.* TO 'classora'@'localhost';

# Backend
cd apps/api
composer install --no-dev -o
cp .env.example .env
php artisan classora:install          # cek sistem, .env, migrasi, seeder, Super Admin
php artisan classora:school "Nama Sekolah" slug-sekolah "Nama Admin" admin@sekolah.sch.id "sandi-min-12-karakter"

# Frontend
cd ../..
pnpm install && pnpm build:web        # hasil: apps/web/dist
```

Konfigurasi web server: [`infrastructure/nginx/classora.conf`](../infrastructure/nginx/classora.conf) atau [`infrastructure/apache/classora.conf`](../infrastructure/apache/classora.conf). SPA dan API sebaiknya satu domain (`/api` dan `/sanctum` diteruskan ke PHP-FPM).

Variabel `.env` penting:

| Variabel | Nilai produksi |
|---|---|
| `APP_ENV` / `APP_DEBUG` | `production` / `false` |
| `APP_URL` | URL publik |
| `DB_CONNECTION` | `mariadb` |
| `SESSION_DRIVER` | `database` |
| `SESSION_SECURE_COOKIE` | `true` |
| `SANCTUM_STATEFUL_DOMAINS` | domain frontend, tanpa skema |

## Tugas terjadwal dan backup

```cron
* * * * * cd /var/www/classora/apps/api && php artisan schedule:run >> /dev/null 2>&1
0 2 * * * DB_NAME=classora DB_USER=classora DB_PASS=... /var/www/classora/infrastructure/backup/backup.sh
```
Salin hasil backup ke luar server dan uji pemulihan secara berkala.

## HTTPS

```bash
certbot --nginx -d classora.contoh.sch.id
```

## Pengembangan di Termux (Android)

```bash
pkg install nodejs php composer mariadb git -y
corepack enable                       # atau: npm i -g pnpm

mysql_install_db                      # sekali saja
mariadbd-safe &                       # tunggu beberapa detik
mariadb -u root                       # lalu buat database dan user (lihat di atas)
```

Lalu jalankan langkah *Instalasi server* di folder proyek. Catatan khusus Termux:

- Keyboard Android sering tidak bisa mengetik di input yang disembunyikan. Karena itu installer menampilkan sandi saat diketik.
- Buat Super Admin dari prompt biasa bila perlu:
  ```bash
  php artisan tinker --execute='$u = new App\Models\User(["name"=>"Admin","email"=>"admin@contoh.test","password"=>"SANDI-MIN-12-KARAKTER"]); $u->is_super_admin = true; $u->save();'
  history -c
  ```
- Jalankan dua sesi Termux: `php artisan serve` (di `apps/api`) dan `pnpm dev:web` (di root).
- Buka `http://localhost:5173`, masuk sebagai **admin sekolah**.

## Pemecahan masalah

| Gejala | Penyebab dan solusi |
|---|---|
| `Identifier name '...' is too long` saat migrasi | Nama indeks > 64 karakter. Beri nama eksplisit pada indeks itu. |
| `Access denied ... (using password: NO)` | Konfigurasi lama masih dipakai. Jalankan ulang installer atau `php artisan config:clear`. |
| Setelah migrasi gagal, tabel "sudah ada" | MariaDB tidak membatalkan DDL parsial. Jalankan `php artisan migrate:fresh --force` pada database kosong. |
| "Akunmu belum memiliki akses aktif" | Masuk dengan akun Super Admin. Gunakan akun admin sekolah. |
| Login gagal "domain frontend yang terdaftar" | Tambahkan domain/port frontend ke `SANCTUM_STATEFUL_DOMAINS`. |
