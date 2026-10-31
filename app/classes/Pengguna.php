<?php
declare(strict_types=1);

/** Kelas Pengguna: akun login (tabel users). */
final class Pengguna
{
    public const PERAN = ['Admin', 'Pemustaka', 'Pimpinan'];
    public const STATUS = ['Aktif', 'Nonaktif'];

    public static function cari(int $id): ?array
    {
        return Database::row(
            'SELECT u.*, a.id_anggota, a.nomor_anggota FROM users u LEFT JOIN anggota a ON a.id_user = u.id_user WHERE u.id_user = ?',
            [$id]
        );
    }

    private static function filter(string $q, string $peran, string $status): array
    {
        $w = ['1=1'];
        $p = [];
        if ($q !== '') {
            $like = '%' . like_escape($q) . '%';
            $w[] = '(u.nama LIKE ? OR u.username LIKE ? OR u.email LIKE ?)';
            array_push($p, $like, $like, $like);
        }
        if (in_array($peran, self::PERAN, true)) {
            $w[] = 'u.peran = ?';
            $p[] = $peran;
        }
        if (in_array($status, self::STATUS, true)) {
            $w[] = 'u.status = ?';
            $p[] = $status;
        }
        return [implode(' AND ', $w), $p];
    }

    public static function hitung(string $q = '', string $peran = '', string $status = ''): int
    {
        [$w, $p] = self::filter($q, $peran, $status);
        return (int)Database::value("SELECT COUNT(*) FROM users u WHERE $w", $p);
    }

    public static function daftar(string $q, string $peran, string $status, int $limit, int $offset): array
    {
        [$w, $p] = self::filter($q, $peran, $status);
        $limit = max(1, $limit);
        $offset = max(0, $offset);
        return Database::all(
            "SELECT u.id_user, u.nama, u.username, u.email, u.peran, u.status, u.last_login, a.nomor_anggota
             FROM users u LEFT JOIN anggota a ON a.id_user = u.id_user
             WHERE $w ORDER BY u.peran, u.nama LIMIT $limit OFFSET $offset",
            $p
        );
    }

    /** Anggota yang belum punya akun (untuk ditautkan ke akun Pemustaka). */
    public static function anggotaTanpaAkun(?int $termasukUser = null): array
    {
        if ($termasukUser) {
            return Database::all(
                'SELECT id_anggota, nomor_anggota, nama FROM anggota WHERE id_user IS NULL OR id_user = ? ORDER BY nama',
                [$termasukUser]
            );
        }
        return Database::all('SELECT id_anggota, nomor_anggota, nama FROM anggota WHERE id_user IS NULL ORDER BY nama');
    }

    /** @return array<string,string> kolom => pesan galat */
    public static function validasi(array $d, ?int $id): array
    {
        $err = [];
        if ($d['nama'] === '' || mb_strlen($d['nama']) > 100) {
            $err['nama'] = 'Nama wajib diisi (maksimal 100 karakter).';
        }
        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $d['username'])) {
            $err['username'] = 'Username 3-50 karakter: huruf, angka, titik, garis bawah, atau strip.';
        } elseif (Database::value('SELECT COUNT(*) FROM users WHERE username = ? AND id_user <> ?', [$d['username'], $id ?? 0])) {
            $err['username'] = 'Username sudah dipakai akun lain.';
        }
        if ($d['email'] !== '') {
            if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($d['email']) > 100) {
                $err['email'] = 'Format email tidak valid.';
            } elseif (Database::value('SELECT COUNT(*) FROM users WHERE email = ? AND id_user <> ?', [$d['email'], $id ?? 0])) {
                $err['email'] = 'Email sudah dipakai akun lain.';
            }
        }
        if (!in_array($d['peran'], self::PERAN, true)) {
            $err['peran'] = 'Peran tidak valid.';
        }
        if (!in_array($d['status'], self::STATUS, true)) {
            $err['status'] = 'Status tidak valid.';
        }
        if ($id === null || $d['password'] !== '') {
            if (mb_strlen($d['password']) < 8) {
                $err['password'] = 'Password minimal 8 karakter.';
            } elseif ($d['password'] !== $d['password2']) {
                $err['password2'] = 'Konfirmasi password tidak sama.';
            }
        }
        if ($d['peran'] === 'Pemustaka') {
            $ida = (int)$d['id_anggota'];
            if ($ida <= 0) {
                $err['id_anggota'] = 'Akun Pemustaka harus ditautkan ke data anggota.';
            } else {
                $a = Database::row('SELECT id_user FROM anggota WHERE id_anggota = ?', [$ida]);
                if (!$a) {
                    $err['id_anggota'] = 'Data anggota tidak ditemukan.';
                } elseif ($a['id_user'] !== null && (int)$a['id_user'] !== (int)($id ?? 0)) {
                    $err['id_anggota'] = 'Anggota tersebut sudah memiliki akun.';
                }
            }
        }
        // jaga agar selalu ada Admin aktif
        if ($id !== null) {
            $lama = Database::row('SELECT peran, status FROM users WHERE id_user = ?', [$id]);
            if ($lama && $lama['peran'] === 'Admin' && $lama['status'] === 'Aktif'
                && ($d['peran'] !== 'Admin' || $d['status'] !== 'Aktif')
                && self::jumlahAdminAktif($id) < 1) {
                $err['peran'] = 'Harus ada minimal satu akun Admin yang aktif.';
            }
            if ($id === Auth::id() && ($d['peran'] !== $lama['peran'] || $d['status'] !== 'Aktif')) {
                $err['peran'] = 'Anda tidak dapat mengubah peran atau menonaktifkan akun sendiri.';
            }
        }
        return $err;
    }

    /** Jumlah Admin aktif selain id tertentu. */
    public static function jumlahAdminAktif(int $kecuali = 0): int
    {
        return (int)Database::value("SELECT COUNT(*) FROM users WHERE peran = 'Admin' AND status = 'Aktif' AND id_user <> ?", [$kecuali]);
    }

    public static function simpan(array $d, ?int $id): int
    {
        return Database::transaksi(function () use ($d, $id): int {
            $email = $d['email'] === '' ? null : $d['email'];
            if ($id === null) {
                Database::run(
                    'INSERT INTO users (nama, username, email, password, peran, status) VALUES (?,?,?,?,?,?)',
                    [$d['nama'], $d['username'], $email, password_hash($d['password'], PASSWORD_DEFAULT), $d['peran'], $d['status']]
                );
                $id = Database::lastId();
            } else {
                Database::run(
                    'UPDATE users SET nama = ?, username = ?, email = ?, peran = ?, status = ? WHERE id_user = ?',
                    [$d['nama'], $d['username'], $email, $d['peran'], $d['status'], $id]
                );
                if ($d['password'] !== '') {
                    Database::run('UPDATE users SET password = ? WHERE id_user = ?', [password_hash($d['password'], PASSWORD_DEFAULT), $id]);
                }
            }
            // tautan ke anggota
            Database::run('UPDATE anggota SET id_user = NULL WHERE id_user = ?', [$id]);
            if ($d['peran'] === 'Pemustaka') {
                $n = Database::run('UPDATE anggota SET id_user = ? WHERE id_anggota = ? AND id_user IS NULL', [$id, (int)$d['id_anggota']]);
                if ($n !== 1) {
                    throw new AturanBisnis('Anggota tersebut sudah memiliki akun.');
                }
            }
            return $id;
        });
    }

    public static function ubahStatus(int $id, string $status): void
    {
        if (!in_array($status, self::STATUS, true)) {
            throw new AturanBisnis('Status tidak valid.');
        }
        $u = self::cari($id);
        if (!$u) {
            throw new AturanBisnis('Pengguna tidak ditemukan.');
        }
        if ($status === 'Nonaktif') {
            if ($id === Auth::id()) {
                throw new AturanBisnis('Anda tidak dapat menonaktifkan akun sendiri.');
            }
            if ($u['peran'] === 'Admin' && $u['status'] === 'Aktif' && self::jumlahAdminAktif($id) < 1) {
                throw new AturanBisnis('Harus ada minimal satu akun Admin yang aktif.');
            }
        }
        Database::run('UPDATE users SET status = ? WHERE id_user = ?', [$status, $id]);
    }

    /** Ubah profil sendiri (nama, email) */
    public static function ubahProfil(int $id, string $nama, string $email): array
    {
        $err = [];
        if ($nama === '' || mb_strlen($nama) > 100) {
            $err['nama'] = 'Nama wajib diisi (maksimal 100 karakter).';
        }
        if ($email !== '') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
                $err['email'] = 'Format email tidak valid.';
            } elseif (Database::value('SELECT COUNT(*) FROM users WHERE email = ? AND id_user <> ?', [$email, $id])) {
                $err['email'] = 'Email sudah dipakai akun lain.';
            }
        }
        if (!$err) {
            Database::run('UPDATE users SET nama = ?, email = ? WHERE id_user = ?', [$nama, $email === '' ? null : $email, $id]);
        }
        return $err;
    }

    public static function ubahPassword(int $id, string $lama, string $baru, string $baru2): array
    {
        $err = [];
        $hash = (string)Database::value('SELECT password FROM users WHERE id_user = ?', [$id]);
        if (!password_verify($lama, $hash)) {
            $err['lama'] = 'Password lama salah.';
        }
        if (mb_strlen($baru) < 8) {
            $err['baru'] = 'Password baru minimal 8 karakter.';
        } elseif ($baru !== $baru2) {
            $err['baru2'] = 'Konfirmasi password tidak sama.';
        }
        if (!$err) {
            Database::run('UPDATE users SET password = ? WHERE id_user = ?', [password_hash($baru, PASSWORD_DEFAULT), $id]);
        }
        return $err;
    }

    /**
     * Hapus akun. Akun Pemustaka dihapus bersama data anggota, riwayat peminjaman, dan tiketnya.
     * Admin/Pimpinan: akun dihapus, jejak log dan penanganan tiket tetap tersimpan tanpa nama petugas.
     */
    public static function hapus(int $id, int $idPelaksana): void
    {
        $u = self::cari($id);
        if (!$u) {
            throw new AturanBisnis('Pengguna tidak ditemukan.');
        }
        if ($id === $idPelaksana) {
            throw new AturanBisnis('Anda tidak dapat menghapus akun yang sedang Anda pakai.');
        }
        if ($u['peran'] === 'Admin'
            && (int)Database::value("SELECT COUNT(*) FROM users WHERE peran = 'Admin' AND status = 'Aktif' AND id_user <> ?", [$id]) === 0) {
            throw new AturanBisnis('Akun Admin aktif terakhir tidak dapat dihapus.');
        }
        if ($u['peran'] === 'Pemustaka' && $u['id_anggota']) {
            Anggota::hapus((int)$u['id_anggota']);
            return;
        }
        Database::transaksi(function () use ($id, $u) {
            if ($u['peran'] === 'Pemustaka') {
                Database::run('DELETE FROM tiket_insiden WHERE id_pelapor = ?', [$id]);
            }
            Database::run('DELETE FROM users WHERE id_user = ?', [$id]);
        });
        if ($u['peran'] === 'Pemustaka') {
            DashboardManfaat::hitungSemua();
        }
    }
}
