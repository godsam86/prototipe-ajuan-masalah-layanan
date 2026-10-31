<?php
declare(strict_types=1);

/** Kelas Anggota: keanggotaan perpustakaan. */
final class Anggota
{
    public const JENIS = ['Umum', 'Pelajar', 'Mahasiswa', 'Pegawai'];
    public const STATUS = ['Aktif', 'Nonaktif'];

    public static function cari(int $id): ?array
    {
        return Database::row('SELECT a.*, u.username FROM anggota a LEFT JOIN users u ON u.id_user = a.id_user WHERE a.id_anggota = ?', [$id]);
    }

    public static function cariNomor(string $nomor): ?array
    {
        return Database::row('SELECT * FROM anggota WHERE nomor_anggota = ?', [$nomor]);
    }

    public static function cariUser(int $idUser): ?array
    {
        return Database::row('SELECT * FROM anggota WHERE id_user = ?', [$idUser]);
    }

    private static function filter(string $q, string $status): array
    {
        $w = ['1=1'];
        $p = [];
        if ($q !== '') {
            $like = '%' . like_escape($q) . '%';
            $w[] = '(a.nama LIKE ? OR a.nomor_anggota LIKE ? OR a.email LIKE ?)';
            array_push($p, $like, $like, $like);
        }
        if (in_array($status, self::STATUS, true)) {
            $w[] = 'a.status = ?';
            $p[] = $status;
        }
        return [implode(' AND ', $w), $p];
    }

    public static function hitung(string $q, string $status): int
    {
        [$w, $p] = self::filter($q, $status);
        return (int)Database::value("SELECT COUNT(*) FROM anggota a WHERE $w", $p);
    }

    public static function daftar(string $q, string $status, int $limit, int $offset): array
    {
        [$w, $p] = self::filter($q, $status);
        $limit = max(1, $limit);
        $offset = max(0, $offset);
        return Database::all(
            "SELECT a.*, u.username,
                    (SELECT COUNT(*) FROM transaksi t WHERE t.id_anggota = a.id_anggota AND t.status = 'Dipinjam') AS pinjaman_aktif
             FROM anggota a LEFT JOIN users u ON u.id_user = a.id_user
             WHERE $w ORDER BY a.nama LIMIT $limit OFFSET $offset",
            $p
        );
    }

    public static function nomorBerikut(): string
    {
        $tahun = date('Y');
        $maks = (int)Database::value(
            "SELECT MAX(CAST(SUBSTRING_INDEX(nomor_anggota, '-', -1) AS UNSIGNED)) FROM anggota WHERE nomor_anggota LIKE ?",
            ['SU-' . $tahun . '-%']
        );
        return sprintf('SU-%s-%05d', $tahun, $maks + 1);
    }

    private static function tanggalValid(string $t): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $t);
        return $d && $d->format('Y-m-d') === $t;
    }

    public static function validasi(array $d, ?int $id): array
    {
        $err = [];
        if ($d['nomor_anggota'] === '' || mb_strlen($d['nomor_anggota']) > 30) {
            $err['nomor_anggota'] = 'Nomor anggota wajib diisi (maksimal 30 karakter).';
        } elseif (Database::value('SELECT COUNT(*) FROM anggota WHERE nomor_anggota = ? AND id_anggota <> ?', [$d['nomor_anggota'], $id ?? 0])) {
            $err['nomor_anggota'] = 'Nomor anggota sudah terdaftar.';
        }
        if ($d['nama'] === '' || mb_strlen($d['nama']) > 100) {
            $err['nama'] = 'Nama wajib diisi (maksimal 100 karakter).';
        }
        if (!in_array($d['jenis_anggota'], self::JENIS, true)) {
            $err['jenis_anggota'] = 'Jenis anggota tidak valid.';
        }
        if ($d['jenis_kelamin'] !== '' && !in_array($d['jenis_kelamin'], ['Laki-laki', 'Perempuan'], true)) {
            $err['jenis_kelamin'] = 'Jenis kelamin tidak valid.';
        }
        if ($d['email'] !== '' && (!filter_var($d['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($d['email']) > 100)) {
            $err['email'] = 'Format email tidak valid.';
        }
        if ($d['telepon'] !== '' && !preg_match('/^[0-9+\-\s()]{5,20}$/', $d['telepon'])) {
            $err['telepon'] = 'Nomor telepon tidak valid.';
        }
        if (mb_strlen($d['alamat']) > 255) {
            $err['alamat'] = 'Alamat maksimal 255 karakter.';
        }
        if (!self::tanggalValid($d['tanggal_daftar'])) {
            $err['tanggal_daftar'] = 'Tanggal daftar tidak valid.';
        }
        if (!self::tanggalValid($d['masa_berlaku'])) {
            $err['masa_berlaku'] = 'Masa berlaku tidak valid.';
        } elseif (!isset($err['tanggal_daftar']) && $d['masa_berlaku'] < $d['tanggal_daftar']) {
            $err['masa_berlaku'] = 'Masa berlaku tidak boleh sebelum tanggal daftar.';
        }
        if (!in_array($d['status'], self::STATUS, true)) {
            $err['status'] = 'Status tidak valid.';
        }
        return $err;
    }

    public static function simpan(array $d, ?int $id): int
    {
        $v = [
            $d['nomor_anggota'], $d['nama'], $d['jenis_anggota'], $d['jenis_kelamin'] ?: null,
            $d['alamat'] ?: null, $d['telepon'] ?: null, $d['email'] ?: null,
            $d['tanggal_daftar'], $d['masa_berlaku'], $d['status'],
        ];
        if ($id === null) {
            Database::run(
                'INSERT INTO anggota (nomor_anggota, nama, jenis_anggota, jenis_kelamin, alamat, telepon, email, tanggal_daftar, masa_berlaku, status)
                 VALUES (?,?,?,?,?,?,?,?,?,?)',
                $v
            );
            return Database::lastId();
        }
        $v[] = $id;
        Database::run(
            'UPDATE anggota SET nomor_anggota=?, nama=?, jenis_anggota=?, jenis_kelamin=?, alamat=?, telepon=?, email=?,
                    tanggal_daftar=?, masa_berlaku=?, status=? WHERE id_anggota=?',
            $v
        );
        return $id;
    }

    /**
     * Hapus anggota secara permanen beserta akun login, riwayat peminjaman, dan tiket keluhannya.
     * Ditolak hanya bila masih ada buku yang sedang dipinjam.
     */
    public static function hapus(int $id): void
    {
        $a = self::cari($id);
        if (!$a) {
            throw new AturanBisnis('Anggota tidak ditemukan.');
        }
        $aktif = (int)Database::value("SELECT COUNT(*) FROM transaksi WHERE id_anggota = ? AND status = 'Dipinjam'", [$id]);
        if ($aktif > 0) {
            throw new AturanBisnis("Anggota masih meminjam $aktif buku. Proses pengembalian di menu Sirkulasi terlebih dahulu, atau ubah statusnya menjadi Nonaktif.");
        }
        $idUser = $a['id_user'] ? (int)$a['id_user'] : null;
        Database::transaksi(function () use ($id, $idUser) {
            Database::run('DELETE FROM transaksi WHERE id_anggota = ?', [$id]);
            Database::run('DELETE FROM anggota WHERE id_anggota = ?', [$id]);
            if ($idUser) {
                Database::run("DELETE FROM tiket_insiden WHERE id_pelapor = ?", [$idUser]);
                Database::run("DELETE FROM users WHERE id_user = ? AND peran = 'Pemustaka'", [$idUser]);
            }
        });
        if ($idUser) {
            DashboardManfaat::hitungSemua();
        }
    }

    /** Anggota boleh meminjam? Mengembalikan pesan alasan penolakan atau null. */
    public static function alasanTidakBolehPinjam(array $a): ?string
    {
        if ($a['status'] !== 'Aktif') {
            return 'Keanggotaan tidak aktif.';
        }
        if ($a['masa_berlaku'] < date('Y-m-d')) {
            return 'Masa berlaku keanggotaan sudah habis (' . tgl($a['masa_berlaku']) . ').';
        }
        return null;
    }
}
