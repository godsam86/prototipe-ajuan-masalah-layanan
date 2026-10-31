<?php
declare(strict_types=1);

/** Evaluasi manfaat layanan: agregasi dari tiket_insiden ke tabel ringkasan dashboard_manfaat. */
final class DashboardManfaat
{
    public const IND_PENYELESAIAN = 'Tingkat Penyelesaian Insiden';
    public const IND_WAKTU = 'Rata-rata Waktu Penanganan (jam)';
    public const IND_KEPUASAN = 'Kepuasan Pemustaka';
    public const IND_TARGET = 'Capaian Target Layanan';

    /**
     * Ringkasan KPI untuk tiket yang dibuat pada rentang [awal, akhir).
     * @return array{total:int,selesai:int,diproses:int,baru:int,penyelesaian:?float,rata_jam:?float,kepuasan:?float,jml_rating:int,target:?float}
     */
    public static function ringkasan(string $awal, string $akhir): array
    {
        $tH = max(1, Pengaturan::int('target_tinggi_jam', 24));
        $tS = max(1, Pengaturan::int('target_sedang_jam', 72));
        $tR = max(1, Pengaturan::int('target_rendah_jam', 168));
        $r = Database::row(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(status = 'Selesai'), 0) AS selesai,
                    COALESCE(SUM(status = 'Diproses'), 0) AS diproses,
                    COALESCE(SUM(status = 'Baru'), 0) AS baru,
                    AVG(CASE WHEN status = 'Selesai' AND tanggal_selesai IS NOT NULL
                             THEN TIMESTAMPDIFF(MINUTE, tanggal_dibuat, tanggal_selesai) END) / 60 AS rata_jam,
                    AVG(rating) AS kepuasan,
                    COUNT(rating) AS jml_rating,
                    COALESCE(SUM(CASE WHEN status = 'Selesai' AND tanggal_selesai IS NOT NULL
                             AND TIMESTAMPDIFF(MINUTE, tanggal_dibuat, tanggal_selesai) <=
                                 60 * (CASE urgensi WHEN 'Tinggi' THEN ? WHEN 'Sedang' THEN ? ELSE ? END)
                             THEN 1 ELSE 0 END), 0) AS sesuai_target
             FROM tiket_insiden WHERE tanggal_dibuat >= ? AND tanggal_dibuat < ?",
            [$tH, $tS, $tR, $awal, $akhir]
        );
        $total = (int)$r['total'];
        $selesai = (int)$r['selesai'];
        return [
            'total'        => $total,
            'selesai'      => $selesai,
            'diproses'     => (int)$r['diproses'],
            'baru'         => (int)$r['baru'],
            'penyelesaian' => $total > 0 ? round(100 * $selesai / $total, 2) : null,
            'rata_jam'     => $r['rata_jam'] !== null ? round((float)$r['rata_jam'], 2) : null,
            'kepuasan'     => $r['kepuasan'] !== null ? round((float)$r['kepuasan'], 2) : null,
            'jml_rating'   => (int)$r['jml_rating'],
            'target'       => $selesai > 0 ? round(100 * (int)$r['sesuai_target'] / $selesai, 2) : null,
        ];
    }

    /** Hitung ulang & simpan indikator bulanan (tabel dashboard_manfaat) untuk bulan dari tanggal tertentu. */
    public static function hitungBulan(string $tanggal): void
    {
        try {
            $t = strtotime($tanggal);
            if ($t === false) {
                return;
            }
            $awal = date('Y-m-01', $t);
            $akhir = date('Y-m-d', strtotime($awal . ' +1 month'));
            $k = self::ringkasan($awal, $akhir);
            $nilai = [
                self::IND_PENYELESAIAN => $k['penyelesaian'],
                self::IND_WAKTU        => $k['rata_jam'],
                self::IND_KEPUASAN     => $k['kepuasan'],
                self::IND_TARGET       => $k['target'],
            ];
            foreach ($nilai as $nama => $v) {
                if ($v === null) {
                    Database::run('DELETE FROM dashboard_manfaat WHERE nama_indikator = ? AND periode = ?', [$nama, $awal]);
                } else {
                    Database::run(
                        'INSERT INTO dashboard_manfaat (nama_indikator, nilai_capaian, periode) VALUES (?,?,?)
                         ON DUPLICATE KEY UPDATE nilai_capaian = VALUES(nilai_capaian)',
                        [$nama, $v, $awal]
                    );
                }
            }
        } catch (Throwable $e) {
            error_log('[INLISLite] gagal menghitung ringkasan manfaat: ' . $e->getMessage());
        }
    }

    /** Hitung ulang seluruh bulan yang memiliki tiket. */
    public static function hitungSemua(): int
    {
        $bulan = Database::all("SELECT DISTINCT DATE_FORMAT(tanggal_dibuat, '%Y-%m-01') AS b FROM tiket_insiden ORDER BY b");
        foreach ($bulan as $b) {
            self::hitungBulan($b['b']);
        }
        return count($bulan);
    }

    /** Tren bulanan tingkat penyelesaian dari tabel ringkasan. @return array<int,array{periode:string,nilai:float}> */
    public static function tren(string $awal, string $akhir, string $indikator = self::IND_PENYELESAIAN): array
    {
        return Database::all(
            'SELECT periode, nilai_capaian AS nilai FROM dashboard_manfaat WHERE nama_indikator = ? AND periode >= ? AND periode < ? ORDER BY periode',
            [$indikator, $awal, $akhir]
        );
    }

    public static function kategoriTerbanyak(string $awal, string $akhir, int $limit = 3): array
    {
        $limit = max(1, $limit);
        return Database::all(
            "SELECT kategori, COUNT(*) AS jumlah, SUM(status = 'Selesai') AS selesai FROM tiket_insiden
             WHERE tanggal_dibuat >= ? AND tanggal_dibuat < ? GROUP BY kategori ORDER BY jumlah DESC, kategori LIMIT $limit",
            [$awal, $akhir]
        );
    }

    /** Daftar tahun yang memiliki tiket (terbaru dulu); selalu memuat tahun berjalan. */
    public static function tahunTersedia(): array
    {
        $th = array_map('intval', array_column(Database::all('SELECT DISTINCT YEAR(tanggal_dibuat) AS y FROM tiket_insiden ORDER BY y DESC'), 'y'));
        $skrg = (int)date('Y');
        if (!in_array($skrg, $th, true)) {
            array_unshift($th, $skrg);
        }
        rsort($th);
        return $th;
    }
}
