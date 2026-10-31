<?php
declare(strict_types=1);

/** Kelas Transaksi: sirkulasi (peminjaman, perpanjangan, pengembalian, denda). */
final class Transaksi
{
    private const SEL = "t.*, a.nomor_anggota, a.nama AS nama_anggota, k.barcode, b.judul,
        GREATEST(0, DATEDIFF(COALESCE(t.tanggal_kembali, CURDATE()), t.tanggal_jatuh_tempo)) AS hari_telat";
    private const DARI = 'FROM transaksi t
        JOIN anggota a ON a.id_anggota = t.id_anggota
        JOIN koleksi k ON k.id_koleksi = t.id_koleksi
        JOIN bibliografi b ON b.id_bibliografi = k.id_bibliografi';

    public static function dendaPerHari(): int
    {
        return Pengaturan::int('denda_per_hari', 500);
    }

    public static function aktif(string $q = ''): array
    {
        $w = "t.status = 'Dipinjam'";
        $p = [];
        if ($q !== '') {
            $like = '%' . like_escape($q) . '%';
            $w .= ' AND (a.nomor_anggota LIKE ? OR a.nama LIKE ? OR k.barcode LIKE ? OR b.judul LIKE ?)';
            $p = [$like, $like, $like, $like];
        }
        return Database::all('SELECT ' . self::SEL . ' ' . self::DARI . " WHERE $w ORDER BY t.tanggal_jatuh_tempo ASC, t.id_transaksi ASC", $p);
    }

    public static function hitungRiwayat(string $q): int
    {
        [$w, $p] = self::filterRiwayat($q);
        return (int)Database::value('SELECT COUNT(*) ' . self::DARI . " WHERE $w", $p);
    }

    private static function filterRiwayat(string $q): array
    {
        $w = "t.status = 'Dikembalikan'";
        $p = [];
        if ($q !== '') {
            $like = '%' . like_escape($q) . '%';
            $w .= ' AND (a.nomor_anggota LIKE ? OR a.nama LIKE ? OR k.barcode LIKE ? OR b.judul LIKE ?)';
            $p = [$like, $like, $like, $like];
        }
        return [$w, $p];
    }

    public static function riwayat(string $q, int $limit, int $offset): array
    {
        [$w, $p] = self::filterRiwayat($q);
        $limit = max(1, $limit);
        $offset = max(0, $offset);
        return Database::all(
            'SELECT ' . self::SEL . ' ' . self::DARI . " WHERE $w ORDER BY t.tanggal_kembali DESC, t.id_transaksi DESC LIMIT $limit OFFSET $offset",
            $p
        );
    }

    /** Peminjaman aktif milik satu anggota. */
    public static function aktifAnggota(int $idAnggota): array
    {
        return Database::all(
            'SELECT ' . self::SEL . ' ' . self::DARI . " WHERE t.status = 'Dipinjam' AND t.id_anggota = ? ORDER BY t.tanggal_jatuh_tempo",
            [$idAnggota]
        );
    }

    public static function pinjam(string $nomorAnggota, string $barcode, int $idPetugas): array
    {
        return Database::transaksi(function () use ($nomorAnggota, $barcode, $idPetugas): array {
            $a = Database::row('SELECT * FROM anggota WHERE nomor_anggota = ? FOR UPDATE', [$nomorAnggota]);
            if (!$a) {
                throw new AturanBisnis('Nomor anggota tidak ditemukan.');
            }
            if ($alasan = Anggota::alasanTidakBolehPinjam($a)) {
                throw new AturanBisnis('Transaksi ditolak: ' . $alasan);
            }
            $k = Database::row(
                'SELECT k.*, b.judul FROM koleksi k JOIN bibliografi b ON b.id_bibliografi = k.id_bibliografi WHERE k.barcode = ? FOR UPDATE',
                [$barcode]
            );
            if (!$k) {
                throw new AturanBisnis('Barcode koleksi tidak ditemukan.');
            }
            if ($k['status'] !== 'Tersedia') {
                throw new AturanBisnis('Transaksi ditolak: koleksi berstatus ' . $k['status'] . '.');
            }
            $aktif = (int)Database::value("SELECT COUNT(*) FROM transaksi WHERE id_anggota = ? AND status = 'Dipinjam'", [$a['id_anggota']]);
            $maks = Pengaturan::int('maks_pinjam', 3);
            if ($aktif >= $maks) {
                throw new AturanBisnis("Transaksi ditolak: anggota sudah meminjam $aktif koleksi (batas $maks).");
            }
            $telat = (int)Database::value(
                "SELECT COUNT(*) FROM transaksi WHERE id_anggota = ? AND status = 'Dipinjam' AND tanggal_jatuh_tempo < CURDATE()",
                [$a['id_anggota']]
            );
            if ($telat > 0) {
                throw new AturanBisnis('Transaksi ditolak: anggota masih memiliki pinjaman yang melewati jatuh tempo.');
            }
            $lama = max(1, Pengaturan::int('lama_pinjam_hari', 7));
            $pinjam = date('Y-m-d');
            $tempo = date('Y-m-d', strtotime("+$lama days"));
            Database::run(
                'INSERT INTO transaksi (id_anggota, id_koleksi, id_petugas, tanggal_pinjam, tanggal_jatuh_tempo) VALUES (?,?,?,?,?)',
                [$a['id_anggota'], $k['id_koleksi'], $idPetugas, $pinjam, $tempo]
            );
            $id = Database::lastId();
            Database::run("UPDATE koleksi SET status = 'Dipinjam' WHERE id_koleksi = ?", [$k['id_koleksi']]);
            Log::catat('PINJAM', 'sirkulasi', $id, "{$a['nomor_anggota']} meminjam {$k['barcode']}", $idPetugas);
            return ['id' => $id, 'judul' => $k['judul'], 'anggota' => $a['nama'], 'tempo' => $tempo];
        });
    }

    private static function pinjamanAktifBarcode(string $barcode): array
    {
        $t = Database::row(
            "SELECT t.*, k.barcode, b.judul, a.nama AS nama_anggota, a.nomor_anggota, a.status AS status_anggota, a.masa_berlaku
             FROM transaksi t JOIN koleksi k ON k.id_koleksi = t.id_koleksi
             JOIN bibliografi b ON b.id_bibliografi = k.id_bibliografi
             JOIN anggota a ON a.id_anggota = t.id_anggota
             WHERE k.barcode = ? AND t.status = 'Dipinjam' FOR UPDATE",
            [$barcode]
        );
        if (!$t) {
            throw new AturanBisnis('Tidak ada peminjaman aktif untuk barcode tersebut.');
        }
        return $t;
    }

    public static function kembali(string $barcode, int $idPetugas): array
    {
        return Database::transaksi(function () use ($barcode, $idPetugas): array {
            $t = self::pinjamanAktifBarcode($barcode);
            $hariIni = date('Y-m-d');
            $telat = max(0, (int)floor((strtotime($hariIni) - strtotime($t['tanggal_jatuh_tempo'])) / 86400));
            $denda = $telat * self::dendaPerHari();
            Database::run(
                "UPDATE transaksi SET status = 'Dikembalikan', tanggal_kembali = ?, denda = ? WHERE id_transaksi = ?",
                [$hariIni, $denda, $t['id_transaksi']]
            );
            Database::run("UPDATE koleksi SET status = 'Tersedia' WHERE id_koleksi = ? AND status = 'Dipinjam'", [$t['id_koleksi']]);
            Log::catat('KEMBALI', 'sirkulasi', (int)$t['id_transaksi'], "{$t['nomor_anggota']} mengembalikan {$t['barcode']}, denda $denda", $idPetugas);
            return ['judul' => $t['judul'], 'anggota' => $t['nama_anggota'], 'telat' => $telat, 'denda' => $denda];
        });
    }

    public static function perpanjang(string $barcode, int $idPetugas): array
    {
        return Database::transaksi(function () use ($barcode, $idPetugas): array {
            $t = self::pinjamanAktifBarcode($barcode);
            if ($t['status_anggota'] !== 'Aktif' || $t['masa_berlaku'] < date('Y-m-d')) {
                throw new AturanBisnis('Perpanjangan ditolak: keanggotaan tidak aktif / kedaluwarsa.');
            }
            $maks = Pengaturan::int('maks_perpanjang', 2);
            if ((int)$t['jumlah_perpanjang'] >= $maks) {
                throw new AturanBisnis("Perpanjangan ditolak: batas perpanjangan ($maks kali) sudah tercapai.");
            }
            if ($t['tanggal_jatuh_tempo'] < date('Y-m-d')) {
                throw new AturanBisnis('Perpanjangan ditolak: peminjaman sudah melewati jatuh tempo. Kembalikan koleksi terlebih dahulu.');
            }
            $lama = max(1, Pengaturan::int('lama_pinjam_hari', 7));
            $baru = date('Y-m-d', strtotime($t['tanggal_jatuh_tempo'] . " +$lama days"));
            Database::run(
                'UPDATE transaksi SET tanggal_jatuh_tempo = ?, jumlah_perpanjang = jumlah_perpanjang + 1 WHERE id_transaksi = ?',
                [$baru, $t['id_transaksi']]
            );
            Log::catat('PERPANJANG', 'sirkulasi', (int)$t['id_transaksi'], "{$t['nomor_anggota']} memperpanjang {$t['barcode']} s.d. $baru", $idPetugas);
            return ['judul' => $t['judul'], 'tempo' => $baru];
        });
    }
}
