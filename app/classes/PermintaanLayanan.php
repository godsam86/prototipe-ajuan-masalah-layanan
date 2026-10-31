<?php
declare(strict_types=1);

/**
 * Kelas PermintaanLayanan (tabel tiket_insiden).
 * Alur: Pemustaka mengajukan keluhan -> Admin menangani (Baru -> Diproses -> Selesai)
 * -> hasil penanganan menjadi bahan evaluasi manfaat layanan bagi Pimpinan.
 */
final class PermintaanLayanan
{
    public const URGENSI = ['Rendah', 'Sedang', 'Tinggi'];
    public const STATUS = ['Baru', 'Diproses', 'Selesai'];

    private const SEL = "t.*, p.nama AS nama_pelapor, p.username AS username_pelapor, ad.nama AS nama_admin,
        an.nomor_anggota,
        CASE WHEN t.tanggal_selesai IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, t.tanggal_dibuat, t.tanggal_selesai) / 60 END AS durasi_jam";
    private const DARI = 'FROM tiket_insiden t
        JOIN users p ON p.id_user = t.id_pelapor
        LEFT JOIN users ad ON ad.id_user = t.id_admin
        LEFT JOIN anggota an ON an.id_user = t.id_pelapor';

    /* ------------------------------ pembacaan ------------------------------ */

    public static function cari(int $id): ?array
    {
        return Database::row('SELECT ' . self::SEL . ' ' . self::DARI . ' WHERE t.id_tiket = ?', [$id]);
    }

    public static function riwayat(int $idTiket): array
    {
        return Database::all(
            'SELECT r.*, u.nama AS nama_user, u.peran FROM tiket_riwayat r LEFT JOIN users u ON u.id_user = r.id_user
             WHERE r.id_tiket = ? ORDER BY r.created_at, r.id_riwayat',
            [$idTiket]
        );
    }

    private static function filter(array $f): array
    {
        $w = ['1=1'];
        $p = [];
        if (!empty($f['pelapor'])) {
            $w[] = 't.id_pelapor = ?';
            $p[] = (int)$f['pelapor'];
        }
        if (!empty($f['status']) && in_array($f['status'], self::STATUS, true)) {
            $w[] = 't.status = ?';
            $p[] = $f['status'];
        }
        if (!empty($f['urgensi']) && in_array($f['urgensi'], self::URGENSI, true)) {
            $w[] = 't.urgensi = ?';
            $p[] = $f['urgensi'];
        }
        if (!empty($f['kategori']) && in_array($f['kategori'], Referensi::nama('kategori', false), true)) {
            $w[] = 't.kategori = ?';
            $p[] = $f['kategori'];
        }
        if (!empty($f['q'])) {
            $like = '%' . like_escape((string)$f['q']) . '%';
            $w[] = '(t.nomor_tiket LIKE ? OR p.nama LIKE ? OR t.deskripsi LIKE ?)';
            array_push($p, $like, $like, $like);
        }
        return [implode(' AND ', $w), $p];
    }

    public static function hitung(array $f = []): int
    {
        [$w, $p] = self::filter($f);
        return (int)Database::value('SELECT COUNT(*) ' . self::DARI . " WHERE $w", $p);
    }

    public static function daftar(array $f, int $limit, int $offset): array
    {
        [$w, $p] = self::filter($f);
        $limit = max(1, $limit);
        $offset = max(0, $offset);
        // prioritas: belum selesai dulu, urgensi tinggi dulu, lalu terbaru
        $order = "FIELD(t.status, 'Baru', 'Diproses', 'Selesai'), FIELD(t.urgensi, 'Tinggi', 'Sedang', 'Rendah'), t.tanggal_dibuat DESC";
        if (!empty($f['urut_baru'])) {
            $order = 't.tanggal_dibuat DESC, t.id_tiket DESC';
        }
        return Database::all('SELECT ' . self::SEL . ' ' . self::DARI . " WHERE $w ORDER BY $order LIMIT $limit OFFSET $offset", $p);
    }

    /** Hitungan per status untuk kartu ringkasan. */
    public static function ringkasStatus(array $f = []): array
    {
        [$w, $p] = self::filter($f);
        $r = Database::row(
            "SELECT COUNT(*) AS total, COALESCE(SUM(t.status='Baru'),0) AS baru, COALESCE(SUM(t.status='Diproses'),0) AS diproses,
                    COALESCE(SUM(t.status='Selesai'),0) AS selesai " . self::DARI . " WHERE $w",
            $p
        );
        return array_map('intval', $r ?: ['total' => 0, 'baru' => 0, 'diproses' => 0, 'selesai' => 0]);
    }

    /* ----------------------------- pengajuan ------------------------------- */

    public static function validasi(array $d): array
    {
        $err = [];
        if (!in_array($d['jenis'], Referensi::nama('jenis'), true)) {
            $err['jenis'] = 'Pilih jenis pengajuan.';
        }
        if (!in_array($d['kategori'], Referensi::nama('kategori'), true)) {
            $err['kategori'] = 'Pilih kategori keluhan.';
        }
        if (!in_array($d['urgensi'], self::URGENSI, true)) {
            $err['urgensi'] = 'Pilih tingkat urgensi.';
        }
        $len = mb_strlen($d['deskripsi']);
        if ($len < 10) {
            $err['deskripsi'] = 'Deskripsi minimal 10 karakter agar kendala dapat dipahami.';
        } elseif ($len > 3000) {
            $err['deskripsi'] = 'Deskripsi maksimal 3000 karakter.';
        }
        if ($d['kontak'] !== '' && (mb_strlen($d['kontak']) > 100
            || (!filter_var($d['kontak'], FILTER_VALIDATE_EMAIL) && !preg_match('/^[0-9+\-\s()]{5,20}$/', $d['kontak'])))) {
            $err['kontak'] = 'Kontak harus berupa email atau nomor telepon yang valid.';
        }
        return $err;
    }

    /** @return array{id:int,nomor:string} */
    public static function buat(int $idPelapor, array $d): array
    {
        $hasil = Database::transaksi(function () use ($idPelapor, $d): array {
            Database::run(
                "INSERT INTO tiket_insiden (nomor_tiket, id_pelapor, jenis, kategori, urgensi, deskripsi, kontak, status, tanggal_dibuat)
                 VALUES (?,?,?,?,?,?,?, 'Baru', NOW())",
                ['TMP' . bin2hex(random_bytes(8)), $idPelapor, $d['jenis'], $d['kategori'], $d['urgensi'], $d['deskripsi'], $d['kontak'] ?: null]
            );
            $id = Database::lastId();
            $nomor = sprintf('TK-%04d', $id);
            Database::run('UPDATE tiket_insiden SET nomor_tiket = ? WHERE id_tiket = ?', [$nomor, $id]);
            Database::run(
                "INSERT INTO tiket_riwayat (id_tiket, id_user, status_lama, status_baru, catatan) VALUES (?,?,NULL,'Baru','Tiket dibuat oleh pemustaka.')",
                [$id, $idPelapor]
            );
            Log::catat('TIKET_BARU', 'tiket', $id, "Pengajuan $nomor ({$d['kategori']}, urgensi {$d['urgensi']})", $idPelapor);
            return ['id' => $id, 'nomor' => $nomor];
        });
        DashboardManfaat::hitungBulan(date('Y-m-d'));
        return $hasil;
    }

    /* --------------------------- penanganan admin -------------------------- */

    /**
     * Ubah status tiket dengan pencatatan riwayat. Dijalankan dalam transaksi pemanggil
     * atau transaksi sendiri bila belum ada.
     */
    private static function ubahStatus(int $idTiket, string $statusBaru, int $idAdmin, string $catatan, ?string $penanganan = null): array
    {
        $t = Database::row('SELECT * FROM tiket_insiden WHERE id_tiket = ? FOR UPDATE', [$idTiket]);
        if (!$t) {
            throw new AturanBisnis('Tiket tidak ditemukan.');
        }
        if ($t['status'] === 'Selesai') {
            throw new AturanBisnis('Tiket ' . $t['nomor_tiket'] . ' sudah selesai dan tidak dapat diubah lagi.');
        }
        if ($statusBaru === 'Diproses' && $t['status'] !== 'Baru') {
            throw new AturanBisnis('Tiket sudah dalam penanganan.');
        }
        $set = ['status = ?', 'id_admin = COALESCE(id_admin, ?)'];
        $par = [$statusBaru, $idAdmin];
        if ($t['tanggal_diproses'] === null) {
            $set[] = 'tanggal_diproses = NOW()';
        }
        if ($statusBaru === 'Selesai') {
            $set[] = 'tanggal_selesai = NOW()';
            $set[] = 'penanganan = ?';
            $par[] = $penanganan;
        }
        $par[] = $idTiket;
        Database::run('UPDATE tiket_insiden SET ' . implode(', ', $set) . ' WHERE id_tiket = ?', $par);
        Database::run(
            'INSERT INTO tiket_riwayat (id_tiket, id_user, status_lama, status_baru, catatan) VALUES (?,?,?,?,?)',
            [$idTiket, $idAdmin, $t['status'], $statusBaru, $catatan]
        );
        Log::catat('TIKET_STATUS', 'tiket', $idTiket, "{$t['nomor_tiket']}: {$t['status']} -> $statusBaru", $idAdmin);
        return $t;
    }

    /** Admin mengambil tiket baru untuk ditangani (Baru -> Diproses). */
    public static function proses(int $idTiket, int $idAdmin): void
    {
        $t = Database::transaksi(fn() => self::ubahStatus($idTiket, 'Diproses', $idAdmin, 'Tiket diperiksa dan mulai ditangani oleh petugas.'));
        DashboardManfaat::hitungBulan($t['tanggal_dibuat']);
    }

    /** Solusi standar tersedia -> terapkan dan selesaikan tiket. */
    public static function selesaikanStandar(int $idTiket, int $idAdmin, string $catatan): void
    {
        if (mb_strlen($catatan) < 5) {
            throw new AturanBisnis('Catatan penanganan wajib diisi (minimal 5 karakter).');
        }
        if (mb_strlen($catatan) > 3000) {
            throw new AturanBisnis('Catatan penanganan maksimal 3000 karakter.');
        }
        $t = Database::transaksi(function () use ($idTiket, $idAdmin, $catatan) {
            if (Database::value('SELECT COUNT(*) FROM masalah WHERE id_tiket = ? AND status = ?', [$idTiket, 'Dianalisis'])) {
                throw new AturanBisnis('Tiket sedang dieskalasi ke manajemen masalah. Selesaikan melalui dokumentasi solusi masalah.');
            }
            return self::ubahStatus($idTiket, 'Selesai', $idAdmin, $catatan, $catatan);
        });
        DashboardManfaat::hitungBulan($t['tanggal_dibuat']);
    }

    /** Dipanggil oleh Masalah::selesaikan() di dalam transaksinya. */
    public static function selesaikanDariMasalah(int $idTiket, int $idAdmin, string $catatan): array
    {
        return self::ubahStatus($idTiket, 'Selesai', $idAdmin, $catatan, $catatan);
    }

    /** Dipanggil oleh Masalah::eskalasi() di dalam transaksinya. */
    public static function tandaiEskalasi(int $idTiket, int $idAdmin): array
    {
        $t = Database::row('SELECT * FROM tiket_insiden WHERE id_tiket = ? FOR UPDATE', [$idTiket]);
        if (!$t) {
            throw new AturanBisnis('Tiket tidak ditemukan.');
        }
        if ($t['status'] === 'Selesai') {
            throw new AturanBisnis('Tiket sudah selesai dan tidak dapat dieskalasi.');
        }
        if ($t['status'] === 'Baru') {
            self::ubahStatus($idTiket, 'Diproses', $idAdmin, 'Tiket dieskalasi ke manajemen masalah untuk analisis akar masalah.');
        } else {
            Database::run(
                'INSERT INTO tiket_riwayat (id_tiket, id_user, status_lama, status_baru, catatan) VALUES (?,?,?,?,?)',
                [$idTiket, $idAdmin, 'Diproses', 'Diproses', 'Tiket dieskalasi ke manajemen masalah untuk analisis akar masalah.']
            );
            Database::run('UPDATE tiket_insiden SET id_admin = COALESCE(id_admin, ?) WHERE id_tiket = ?', [$idAdmin, $idTiket]);
        }
        return $t;
    }

    /* ------------------------------- penilaian ----------------------------- */

    public static function beriRating(int $idTiket, int $idPelapor, int $rating, string $ulasan): void
    {
        if ($rating < 1 || $rating > 5) {
            throw new AturanBisnis('Pilih nilai kepuasan 1 sampai 5.');
        }
        if (mb_strlen($ulasan) > 255) {
            throw new AturanBisnis('Ulasan maksimal 255 karakter.');
        }
        $t = Database::transaksi(function () use ($idTiket, $idPelapor, $rating, $ulasan) {
            $t = Database::row('SELECT * FROM tiket_insiden WHERE id_tiket = ? AND id_pelapor = ? FOR UPDATE', [$idTiket, $idPelapor]);
            if (!$t) {
                throw new AturanBisnis('Tiket tidak ditemukan.');
            }
            if ($t['status'] !== 'Selesai') {
                throw new AturanBisnis('Penilaian hanya dapat diberikan setelah tiket selesai.');
            }
            if ($t['rating'] !== null) {
                throw new AturanBisnis('Anda sudah memberikan penilaian untuk tiket ini.');
            }
            Database::run('UPDATE tiket_insiden SET rating = ?, ulasan = ? WHERE id_tiket = ?', [$rating, $ulasan ?: null, $idTiket]);
            Log::catat('TIKET_RATING', 'tiket', $idTiket, "Penilaian $rating/5 untuk {$t['nomor_tiket']}", $idPelapor);
            return $t;
        });
        DashboardManfaat::hitungBulan($t['tanggal_dibuat']);
    }
}
