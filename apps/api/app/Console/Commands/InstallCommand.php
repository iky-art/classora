<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\PlatformSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PDO;
use Throwable;

class InstallCommand extends Command
{
    protected $signature = 'classora:install';
    protected $description = 'Memeriksa sistem, mengatur database, migrasi, dan membuat akun Super Admin awal.';

    public function handle(): int
    {
        $this->info('Classora — instalasi');

        if (! $this->checkRequirements()) {
            return self::FAILURE;
        }

        $url = $this->ask('URL aplikasi (API)', 'http://localhost:8000');
        $frontend = $this->ask('Domain frontend untuk Sanctum (tanpa skema, mis. app.sekolah.sch.id)', 'localhost:5173');
        $host = $this->ask('Host database', '127.0.0.1');
        $port = $this->ask('Port database', '3306');
        $name = $this->ask('Nama database', 'classora');
        $user = $this->ask('User database', 'classora');
        $pass = (string) $this->ask('Password database');

        try {
            new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } catch (Throwable $e) {
            $this->error('Koneksi database gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->writeEnv([
            'APP_URL' => $url, 'DB_CONNECTION' => 'mariadb', 'DB_HOST' => $host, 'DB_PORT' => $port,
            'DB_DATABASE' => $name, 'DB_USERNAME' => $user, 'DB_PASSWORD' => $pass,
            'SANCTUM_STATEFUL_DOMAINS' => $frontend, 'SESSION_DRIVER' => 'database',
        ]);

        Artisan::call('key:generate', ['--force' => true], $this->output);
        $this->call('config:clear');
        $this->call('migrate', ['--force' => true]);
        $this->call('db:seed', ['--class' => PlatformSeeder::class, '--force' => true]);
        $this->call('storage:link');

        $this->createSuperAdmin();
        $this->info('Selesai. Buat sekolah pertama lewat POST /api/v1/schools (Super Admin).');

        return self::SUCCESS;
    }

    private function checkRequirements(): bool
    {
        $ok = true;
        if (version_compare(PHP_VERSION, '8.3.0', '<')) {
            $this->error('PHP 8.3+ diperlukan (terdeteksi '.PHP_VERSION.').');
            $ok = false;
        }
        foreach (['pdo_mysql', 'mbstring', 'openssl', 'fileinfo', 'ctype', 'json', 'tokenizer', 'xml'] as $ext) {
            if (! extension_loaded($ext)) {
                $this->error("Ekstensi PHP belum aktif: $ext");
                $ok = false;
            }
        }
        foreach ([storage_path(), base_path('bootstrap/cache')] as $dir) {
            if (! is_writable($dir)) {
                $this->error("Folder tidak dapat ditulis: $dir");
                $ok = false;
            }
        }

        return $ok;
    }

    private function createSuperAdmin(): void
    {
        if (User::where('is_super_admin', true)->exists()) {
            $this->warn('Super Admin sudah ada, dilewati.');

            return;
        }

        $email = $this->ask('Email Super Admin');
        $name = $this->ask('Nama Super Admin', 'Super Admin');
        $password = (string) $this->ask('Kata sandi (min. 12 karakter, kosongkan untuk dibuat acak)');
        $generated = false;
        if ($password === '') {
            $password = Str::password(20);
            $generated = true;
        }
        if (strlen($password) < 12) {
            $this->error('Kata sandi minimal 12 karakter.');
            $this->createSuperAdmin();

            return;
        }

        $admin = new User(['name' => $name, 'email' => $email, 'password' => $password]);
        $admin->is_super_admin = true;
        $admin->save();

        if ($generated) {
            $this->warn("Kata sandi acak (hanya ditampilkan sekali, segera ganti): $password");
        }
    }

    private function writeEnv(array $values): void
    {
        $path = base_path('.env');
        $env = is_file($path) ? file_get_contents($path) : '';
        foreach ($values as $key => $value) {
            $line = $key.'='.(preg_match('/[\s#"\']/', (string) $value) ? '"'.addcslashes((string) $value, '"\\').'"' : $value);
            $env = preg_match("/^{$key}=.*$/m", $env)
                ? preg_replace("/^{$key}=.*$/m", str_replace('\\', '\\\\', $line), $env)
                : rtrim($env)."\n".$line."\n";
        }
        file_put_contents($path, $env);
    }
}
