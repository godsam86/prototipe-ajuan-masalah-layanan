<?php
declare(strict_types=1);

/** Autentikasi, sesi, dan hak akses berdasarkan peran. */
final class Auth
{
    private static ?array $user = null;
    private static bool $dimuat = false;

    public static function user(): ?array
    {
        if (self::$dimuat) {
            return self::$user;
        }
        self::$dimuat = true;
        $id = $_SESSION['uid'] ?? null;
        if (!$id) {
            return null;
        }
        $u = Database::row('SELECT id_user, nama, username, email, peran, status FROM users WHERE id_user = ?', [(int)$id]);
        if (!$u || $u['status'] !== 'Aktif') {
            // akun dihapus / dinonaktifkan saat sesi berjalan
            unset($_SESSION['uid']);
            return null;
        }
        self::$user = $u;
        return $u;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int)$u['id_user'] : null;
    }

    public static function peran(): ?string
    {
        $u = self::user();
        return $u ? $u['peran'] : null;
    }

    public static function beranda(?string $peran = null): string
    {
        $peran = $peran ?? self::peran();
        return match ($peran) {
            'Admin'     => 'admin/index.php',
            'Pimpinan'  => 'pimpinan/index.php',
            'Pemustaka' => 'pemustaka/index.php',
            default     => 'login.php',
        };
    }

    /** Wajib login dan (opsional) salah satu peran tertentu. */
    public static function wajib(array $peran = []): array
    {
        $u = self::user();
        if (!$u) {
            flash('warning', 'Silakan login terlebih dahulu.');
            redirect('login.php');
        }
        if ($peran && !in_array($u['peran'], $peran, true)) {
            http_response_code(403);
            echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Akses Ditolak</title></head>'
               . '<body style="font-family:sans-serif;padding:40px"><h2>Akses ditolak</h2>'
               . '<p>Anda tidak memiliki hak akses ke halaman ini.</p>'
               . '<p><a href="' . e(url(self::beranda())) . '">Kembali ke dashboard</a></p></body></html>';
            exit;
        }
        return $u;
    }

    /** @return array{0:bool,1:string} [berhasil, pesan] */
    public static function masuk(string $username, string $password): array
    {
        $username = trim($username);
        if ($username === '' || $password === '') {
            return [false, 'Username dan password wajib diisi.'];
        }
        if (mb_strlen($username) > 50 || strlen($password) > 200) {
            return [false, 'Username atau password salah.'];
        }
        if (self::terlaluBanyakGagal($username)) {
            return [false, 'Terlalu banyak percobaan login gagal. Silakan coba lagi dalam 15 menit.'];
        }

        $u = Database::row('SELECT * FROM users WHERE username = ?', [$username]);
        // verifikasi selalu dijalankan agar waktu respons relatif seragam
        $hash = $u ? $u['password'] : '$2y$10$jpS6IGo8bictkhC.SRrfMu0L98aVpX.Y5lHM8CWuAB.IGEfA.8qsi';
        $cocok = password_verify($password, $hash);

        if (!$u || !$cocok) {
            Log::catat('LOGIN_GAGAL', 'auth', null, 'Login gagal: ' . $username, $u ? (int)$u['id_user'] : null);
            return [false, 'Username atau password salah.'];
        }
        if ($u['status'] !== 'Aktif') {
            Log::catat('LOGIN_GAGAL', 'auth', null, 'Login gagal (akun nonaktif): ' . $username, (int)$u['id_user']);
            return [false, 'Akun Anda tidak aktif. Silakan hubungi administrator.'];
        }

        if (password_needs_rehash($u['password'], PASSWORD_DEFAULT)) {
            Database::run('UPDATE users SET password = ? WHERE id_user = ?', [password_hash($password, PASSWORD_DEFAULT), $u['id_user']]);
        }
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$u['id_user'];
        unset($_SESSION['_csrf']);
        Database::run('UPDATE users SET last_login = NOW() WHERE id_user = ?', [$u['id_user']]);
        self::$dimuat = false;
        self::$user = null;
        Log::catat('LOGIN', 'auth', null, 'Login berhasil: ' . $username, (int)$u['id_user']);
        return [true, 'OK'];
    }

    public static function keluar(): void
    {
        $u = self::user();
        if ($u) {
            Log::catat('LOGOUT', 'auth', null, 'Logout: ' . $u['username'], (int)$u['id_user']);
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool)$p['secure'], true);
        }
        session_destroy();
        self::$user = null;
        self::$dimuat = false;
    }

    private static function terlaluBanyakGagal(string $username): bool
    {
        $perUser = (int)Database::value(
            "SELECT COUNT(*) FROM log_aktivitas WHERE aksi = 'LOGIN_GAGAL' AND keterangan LIKE ? AND created_at > (NOW() - INTERVAL 15 MINUTE)",
            ['Login gagal%: ' . like_escape($username)]
        );
        if ($perUser >= 5) {
            return true;
        }
        $perIp = (int)Database::value(
            "SELECT COUNT(*) FROM log_aktivitas WHERE aksi = 'LOGIN_GAGAL' AND ip_address = ? AND created_at > (NOW() - INTERVAL 15 MINUTE)",
            [ip_klien()]
        );
        return $perIp >= 20;
    }
}
