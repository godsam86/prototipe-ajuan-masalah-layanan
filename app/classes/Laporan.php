<?php
declare(strict_types=1);

/** Kelas Laporan: pengolahan laporan dan statistik berdasarkan periode (tabel laporan_kinerja untuk arsip). */
final class Laporan
{
    /** Daftar bulan (YYYY-MM-01) dalam rentang [awal, akhir). */
    public static function bulanDalam(string $awal, string $akhir): array
    {
        $out = [];
        $t = strtotime($awal);
        $batas = strtotime($akhir);
        while ($t < $batas) {
            $out[] = date('Y-m-01', $t);
            $t = strtotime(date('Y-m-01', $t) . ' +1 month');
        }
        return $out;
    }

    public static function namaBulan(string $b): string
    {
        return substr(BULAN_ID[(int)date('n', strtotime($b))], 0, 3) . ' ' . date('y', strtotime($b));
    }

    public static function layanan(string $awal, string $akhir): array
    {
        $ring = DashboardManfaat::ringkasan($awal, $akhir);
        $kategori = Database::all(
            "SELECT kategori, COUNT(*) AS jumlah, SUM(status = 'Selesai') AS selesai,
                    AVG(CASE WHEN status = 'Selesai' AND tanggal_selesai IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, tanggal_dibuat, tanggal_selesai) END) / 60 AS rata_jam
             FROM tiket_insiden WHERE tanggal_dibuat >= ? AND tanggal_dibuat < ? GROUP BY kategori ORDER BY jumlah DESC",
            [$awal, $akhir]
        );
        $urgensi = Database::all(
            "SELECT urgensi, COUNT(*) AS jumlah, SUM(status = 'Selesai') AS selesai FROM tiket_insiden
             WHERE tanggal_dibuat >= ? AND tanggal_dibuat < ? GROUP BY urgensi ORDER BY FIELD(urgensi, 'Tinggi', 'Sedang', 'Rendah')",
            [$awal, $akhir]
        );
        $bulanData = [];
        foreach (Database::all(
            "SELECT DATE_FORMAT(tanggal_dibuat, '%Y-%m-01') AS b, COUNT(*) AS jumlah, SUM(status = 'Selesai') AS selesai
             FROM tiket_insiden WHERE tanggal_dibuat >= ? AND tanggal_dibuat < ? GROUP BY b",
            [$awal, $akhir]
        ) as $r) {
            $bulanData[$r['b']] = $r;
        }
        $perBulan = [];
        foreach (self::bulanDalam($awal, $akhir) as $b) {
            $perBulan[] = ['bulan' => $b, 'jumlah' => (int)($bulanData[$b]['jumlah'] ?? 0), 'selesai' => (int)($bulanData[$b]['selesai'] ?? 0)];
        }
        return ['ringkasan' => $ring, 'kategori' => $kategori, 'urgensi' => $urgensi, 'per_bulan' => $perBulan];
    }

    public static function sirkulasi(string $awal, string $akhir): array
    {
        $pinjam = [];
        foreach (Database::all(
            "SELECT DATE_FORMAT(tanggal_pinjam, '%Y-%m-01') AS b, COUNT(*) AS n FROM transaksi
             WHERE tanggal_pinjam >= ? AND tanggal_pinjam < ? GROUP BY b",
            [$awal, $akhir]
        ) as $r) {
            $pinjam[$r['b']] = (int)$r['n'];
        }
        $kembali = [];
        foreach (Database::all(
            "SELECT DATE_FORMAT(tanggal_kembali, '%Y-%m-01') AS b, COUNT(*) AS n, COALESCE(SUM(denda),0) AS denda FROM transaksi
             WHERE tanggal_kembali >= ? AND tanggal_kembali < ? GROUP BY b",
            [$awal, $akhir]
        ) as $r) {
            $kembali[$r['b']] = ['n' => (int)$r['n'], 'denda' => (int)$r['denda']];
        }
        $perBulan = [];
        foreach (self::bulanDalam($awal, $akhir) as $b) {
            $perBulan[] = [
                'bulan' => $b, 'pinjam' => $pinjam[$b] ?? 0,
                'kembali' => $kembali[$b]['n'] ?? 0, 'denda' => $kembali[$b]['denda'] ?? 0,
            ];
        }
        $top = Database::all(
            "SELECT b.judul, b.pengarang, COUNT(*) AS jumlah FROM transaksi t
             JOIN koleksi k ON k.id_koleksi = t.id_koleksi JOIN bibliografi b ON b.id_bibliografi = k.id_bibliografi
             WHERE t.tanggal_pinjam >= ? AND t.tanggal_pinjam < ? GROUP BY b.id_bibliografi, b.judul, b.pengarang
             ORDER BY jumlah DESC, b.judul LIMIT 10",
            [$awal, $akhir]
        );
        $kini = Database::row(
            "SELECT COUNT(*) AS aktif, COALESCE(SUM(tanggal_jatuh_tempo < CURDATE()), 0) AS terlambat FROM transaksi WHERE status = 'Dipinjam'"
        );
        return [
            'per_bulan' => $perBulan, 'top' => $top,
            'total_pinjam' => array_sum(array_column($perBulan, 'pinjam')),
            'total_kembali' => array_sum(array_column($perBulan, 'kembali')),
            'total_denda' => array_sum(array_column($perBulan, 'denda')),
            'aktif' => (int)$kini['aktif'], 'terlambat' => (int)$kini['terlambat'],
        ];
    }

    public static function koleksi(): array
    {
        return [
            'judul' => (int)Database::value('SELECT COUNT(*) FROM bibliografi'),
            'eksemplar' => Database::all("SELECT status, COUNT(*) AS jumlah FROM koleksi GROUP BY status ORDER BY FIELD(status,'Tersedia','Dipinjam','Rusak','Hilang')"),
            'bahan' => Database::all('SELECT jenis_bahan, COUNT(*) AS jumlah FROM bibliografi GROUP BY jenis_bahan ORDER BY jumlah DESC'),
            'anggota_status' => Database::all('SELECT status, COUNT(*) AS jumlah FROM anggota GROUP BY status ORDER BY status'),
            'anggota_jenis' => Database::all('SELECT jenis_anggota, COUNT(*) AS jumlah FROM anggota GROUP BY jenis_anggota ORDER BY jumlah DESC'),
        ];
    }

    /** Teks ringkasan laporan kinerja layanan untuk periode tertentu. */
    public static function teksKinerja(string $label, string $awal, string $akhir): string
    {
        $d = self::layanan($awal, $akhir);
        $k = $d['ringkasan'];
        $pct = fn($v) => $v === null ? '-' : angka($v, 1) . '%';
        $baris = [];
        $baris[] = 'LAPORAN KINERJA LAYANAN PEMUSTAKA';
        $baris[] = 'Periode: ' . $label;
        $baris[] = '';
        $baris[] = '1. Ringkasan indikator';
        $baris[] = '   - Jumlah tiket masuk           : ' . $k['total'] . ' (Selesai ' . $k['selesai'] . ', Diproses ' . $k['diproses'] . ', Baru ' . $k['baru'] . ')';
        $baris[] = '   - Tingkat penyelesaian insiden : ' . $pct($k['penyelesaian']);
        $baris[] = '   - Rata-rata waktu penanganan   : ' . durasi_jam($k['rata_jam']);
        $baris[] = '   - Kepuasan pemustaka           : ' . ($k['kepuasan'] === null ? '-' : angka($k['kepuasan'], 1) . ' / 5 (' . $k['jml_rating'] . ' penilaian)');
        $baris[] = '   - Capaian target layanan       : ' . $pct($k['target']);
        $baris[] = '';
        $baris[] = '2. Kategori keluhan';
        foreach ($d['kategori'] as $r) {
            $baris[] = '   - ' . label_kategori($r['kategori']) . ': ' . $r['jumlah'] . ' tiket (' . (int)$r['selesai'] . ' selesai)';
        }
        if (!$d['kategori']) {
            $baris[] = '   (tidak ada data)';
        }
        $baris[] = '';
        $baris[] = '3. Tren per bulan';
        foreach ($d['per_bulan'] as $r) {
            $baris[] = '   - ' . self::namaBulan($r['bulan']) . ': ' . $r['jumlah'] . ' tiket, ' . $r['selesai'] . ' selesai';
        }
        return implode("\n", $baris);
    }

    public static function simpanKinerja(int $idAdmin, string $periodeAwal, string $isi): int
    {
        Database::run('INSERT INTO laporan_kinerja (id_admin, periode, isi_laporan) VALUES (?,?,?)', [$idAdmin, $periodeAwal, $isi]);
        $id = Database::lastId();
        Log::catat('LAPORAN_SIMPAN', 'laporan', $id, 'Laporan kinerja periode ' . $periodeAwal, $idAdmin);
        return $id;
    }

    public static function daftarKinerja(int $limit = 20): array
    {
        $limit = max(1, $limit);
        return Database::all(
            "SELECT l.*, u.nama AS nama_admin FROM laporan_kinerja l LEFT JOIN users u ON u.id_user = l.id_admin
             ORDER BY l.created_at DESC, l.id_laporan DESC LIMIT $limit"
        );
    }
}
