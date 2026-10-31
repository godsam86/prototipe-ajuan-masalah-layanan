<?php
declare(strict_types=1);

/** Kelas Masalah + basis pengetahuan: analisis akar masalah dan dokumentasi solusi. */
final class Masalah
{
    public static function cariTiket(int $idTiket): ?array
    {
        return Database::row(
            'SELECT m.*, bp.id_solusi, bp.judul_solusi, bp.deskripsi_solusi, u.nama AS nama_admin
             FROM masalah m LEFT JOIN basis_pengetahuan bp ON bp.id_masalah = m.id_masalah
             LEFT JOIN users u ON u.id_user = m.id_admin WHERE m.id_tiket = ?',
            [$idTiket]
        );
    }

    public static function daftar(string $status, string $q, int $limit, int $offset): array
    {
        [$w, $p] = self::filter($status, $q);
        $limit = max(1, $limit);
        $offset = max(0, $offset);
        return Database::all(
            "SELECT m.*, t.nomor_tiket, t.kategori, t.status AS status_tiket, bp.judul_solusi, bp.deskripsi_solusi, u.nama AS nama_admin
             FROM masalah m JOIN tiket_insiden t ON t.id_tiket = m.id_tiket
             LEFT JOIN basis_pengetahuan bp ON bp.id_masalah = m.id_masalah
             LEFT JOIN users u ON u.id_user = m.id_admin
             WHERE $w ORDER BY m.tanggal_analisis DESC LIMIT $limit OFFSET $offset",
            $p
        );
    }

    private static function filter(string $status, string $q): array
    {
        $w = ['1=1'];
        $p = [];
        if (in_array($status, ['Dianalisis', 'Selesai'], true)) {
            $w[] = 'm.status = ?';
            $p[] = $status;
        }
        if ($q !== '') {
            $like = '%' . like_escape($q) . '%';
            $w[] = '(t.nomor_tiket LIKE ? OR m.akar_masalah LIKE ? OR bp.judul_solusi LIKE ?)';
            array_push($p, $like, $like, $like);
        }
        return [implode(' AND ', $w), $p];
    }

    public static function hitung(string $status, string $q): int
    {
        [$w, $p] = self::filter($status, $q);
        return (int)Database::value(
            "SELECT COUNT(*) FROM masalah m JOIN tiket_insiden t ON t.id_tiket = m.id_tiket
             LEFT JOIN basis_pengetahuan bp ON bp.id_masalah = m.id_masalah WHERE $w",
            $p
        );
    }

    /** Solusi yang sudah terdokumentasi (untuk referensi penanganan tiket berikutnya). */
    public static function basisPengetahuan(): array
    {
        return Database::all(
            'SELECT bp.*, m.id_tiket, t.nomor_tiket, t.kategori FROM basis_pengetahuan bp
             JOIN masalah m ON m.id_masalah = bp.id_masalah JOIN tiket_insiden t ON t.id_tiket = m.id_tiket
             ORDER BY bp.created_at DESC, bp.id_solusi DESC'
        );
    }

    /** Eskalasi tiket menjadi masalah: catat akar masalah dan rencana perbaikan. */
    public static function eskalasi(int $idTiket, int $idAdmin, string $akar, string $tindakan, string $pencegahan): int
    {
        if (mb_strlen($akar) < 10) {
            throw new AturanBisnis('Analisis akar masalah wajib diisi (minimal 10 karakter).');
        }
        if (mb_strlen($akar) > 3000 || mb_strlen($tindakan) > 3000 || mb_strlen($pencegahan) > 3000) {
            throw new AturanBisnis('Setiap isian maksimal 3000 karakter.');
        }
        $hasil = Database::transaksi(function () use ($idTiket, $idAdmin, $akar, $tindakan, $pencegahan): array {
            if (Database::value('SELECT COUNT(*) FROM masalah WHERE id_tiket = ?', [$idTiket])) {
                throw new AturanBisnis('Tiket ini sudah pernah dieskalasi ke manajemen masalah.');
            }
            $t = PermintaanLayanan::tandaiEskalasi($idTiket, $idAdmin);
            Database::run(
                'INSERT INTO masalah (id_tiket, id_admin, akar_masalah, tindakan_perbaikan, langkah_pencegahan) VALUES (?,?,?,?,?)',
                [$idTiket, $idAdmin, $akar, $tindakan ?: null, $pencegahan ?: null]
            );
            $id = Database::lastId();
            Log::catat('MASALAH_BARU', 'masalah', $id, "Eskalasi {$t['nomor_tiket']} ke manajemen masalah", $idAdmin);
            return ['id' => $id, 'tgl' => $t['tanggal_dibuat']];
        });
        DashboardManfaat::hitungBulan($hasil['tgl']);
        return $hasil['id'];
    }

    /** Perbarui analisis masalah yang belum selesai. */
    public static function perbarui(int $idMasalah, int $idAdmin, string $akar, string $tindakan, string $pencegahan): void
    {
        if (mb_strlen($akar) < 10) {
            throw new AturanBisnis('Analisis akar masalah wajib diisi (minimal 10 karakter).');
        }
        if (mb_strlen($akar) > 3000 || mb_strlen($tindakan) > 3000 || mb_strlen($pencegahan) > 3000) {
            throw new AturanBisnis('Setiap isian maksimal 3000 karakter.');
        }
        $n = Database::run(
            "UPDATE masalah SET akar_masalah = ?, tindakan_perbaikan = ?, langkah_pencegahan = ?, id_admin = ? WHERE id_masalah = ? AND status = 'Dianalisis'",
            [$akar, $tindakan ?: null, $pencegahan ?: null, $idAdmin, $idMasalah]
        );
        if ($n < 1) {
            throw new AturanBisnis('Masalah tidak ditemukan atau sudah selesai.');
        }
        Log::catat('MASALAH_UBAH', 'masalah', $idMasalah, 'Analisis masalah diperbarui', $idAdmin);
    }

    /** Selesaikan masalah: dokumentasikan solusi ke basis pengetahuan dan tutup tiket. */
    public static function selesaikan(int $idMasalah, int $idAdmin, string $judul, string $deskripsi): void
    {
        if ($judul === '' || mb_strlen($judul) > 150) {
            throw new AturanBisnis('Judul solusi wajib diisi (maksimal 150 karakter).');
        }
        if (mb_strlen($deskripsi) < 10 || mb_strlen($deskripsi) > 3000) {
            throw new AturanBisnis('Uraian solusi wajib diisi (10-3000 karakter).');
        }
        $tgl = Database::transaksi(function () use ($idMasalah, $idAdmin, $judul, $deskripsi): string {
            $m = Database::row('SELECT * FROM masalah WHERE id_masalah = ? FOR UPDATE', [$idMasalah]);
            if (!$m) {
                throw new AturanBisnis('Masalah tidak ditemukan.');
            }
            if ($m['status'] === 'Selesai') {
                throw new AturanBisnis('Masalah ini sudah selesai.');
            }
            Database::run('INSERT INTO basis_pengetahuan (id_masalah, judul_solusi, deskripsi_solusi) VALUES (?,?,?)', [$idMasalah, $judul, $deskripsi]);
            Database::run("UPDATE masalah SET status = 'Selesai', id_admin = ? WHERE id_masalah = ?", [$idAdmin, $idMasalah]);
            $t = PermintaanLayanan::selesaikanDariMasalah(
                (int)$m['id_tiket'],
                $idAdmin,
                "Diselesaikan melalui manajemen masalah. Solusi: $judul\n$deskripsi"
            );
            Log::catat('MASALAH_SELESAI', 'masalah', $idMasalah, "Solusi \"$judul\" dicatat ke basis pengetahuan", $idAdmin);
            return $t['tanggal_dibuat'];
        });
        DashboardManfaat::hitungBulan($tgl);
    }
}
