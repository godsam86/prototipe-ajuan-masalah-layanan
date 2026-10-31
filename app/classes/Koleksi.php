<?php
declare(strict_types=1);

/** Kelas Koleksi: eksemplar bahan pustaka. */
final class Koleksi
{
    public const STATUS = ['Tersedia', 'Dipinjam', 'Rusak', 'Hilang'];
    /** Status yang boleh diatur manual oleh admin (Dipinjam hanya diatur oleh sirkulasi). */
    public const STATUS_MANUAL = ['Tersedia', 'Rusak', 'Hilang'];

    public static function cari(int $id): ?array
    {
        return Database::row(
            'SELECT k.*, b.judul, b.pengarang FROM koleksi k JOIN bibliografi b ON b.id_bibliografi = k.id_bibliografi WHERE k.id_koleksi = ?',
            [$id]
        );
    }

    private static function filter(string $q, string $status, int $idBib): array
    {
        $w = ['1=1'];
        $p = [];
        if ($q !== '') {
            $like = '%' . like_escape($q) . '%';
            $w[] = '(k.barcode LIKE ? OR b.judul LIKE ? OR k.lokasi LIKE ?)';
            array_push($p, $like, $like, $like);
        }
        if (in_array($status, self::STATUS, true)) {
            $w[] = 'k.status = ?';
            $p[] = $status;
        }
        if ($idBib > 0) {
            $w[] = 'k.id_bibliografi = ?';
            $p[] = $idBib;
        }
        return [implode(' AND ', $w), $p];
    }

    public static function hitung(string $q, string $status, int $idBib = 0): int
    {
        [$w, $p] = self::filter($q, $status, $idBib);
        return (int)Database::value("SELECT COUNT(*) FROM koleksi k JOIN bibliografi b ON b.id_bibliografi = k.id_bibliografi WHERE $w", $p);
    }

    public static function daftar(string $q, string $status, int $idBib, int $limit, int $offset): array
    {
        [$w, $p] = self::filter($q, $status, $idBib);
        $limit = max(1, $limit);
        $offset = max(0, $offset);
        return Database::all(
            "SELECT k.*, b.judul, b.pengarang FROM koleksi k JOIN bibliografi b ON b.id_bibliografi = k.id_bibliografi
             WHERE $w ORDER BY b.judul, k.barcode LIMIT $limit OFFSET $offset",
            $p
        );
    }

    public static function barcodeBerikut(int $idBib): string
    {
        $n = (int)Database::value('SELECT COUNT(*) FROM koleksi WHERE id_bibliografi = ?', [$idBib]) + 1;
        do {
            $kode = sprintf('B%05d-%d', $idBib, $n++);
        } while (Database::value('SELECT COUNT(*) FROM koleksi WHERE barcode = ?', [$kode]));
        return $kode;
    }

    public static function validasi(array $d, ?int $id, ?array $lama = null): array
    {
        $err = [];
        if ((int)$d['id_bibliografi'] <= 0 || !Bibliografi::cari((int)$d['id_bibliografi'])) {
            $err['id_bibliografi'] = 'Pilih bibliografi yang valid.';
        }
        if ($d['barcode'] === '' || !preg_match('/^[A-Za-z0-9._\/-]{1,40}$/', $d['barcode'])) {
            $err['barcode'] = 'Barcode wajib diisi (maks. 40 karakter: huruf, angka, titik, garis, atau garis miring).';
        } elseif (Database::value('SELECT COUNT(*) FROM koleksi WHERE barcode = ? AND id_koleksi <> ?', [$d['barcode'], $id ?? 0])) {
            $err['barcode'] = 'Nomor barcode sudah dipakai koleksi lain.';
        }
        if ($d['lokasi'] === '' || mb_strlen($d['lokasi']) > 100) {
            $err['lokasi'] = 'Lokasi wajib diisi (maksimal 100 karakter).';
        }
        $dipinjam = $lama && $lama['status'] === 'Dipinjam';
        if (!$dipinjam && !in_array($d['status'], self::STATUS_MANUAL, true)) {
            $err['status'] = 'Status tidak valid.';
        }
        if ($d['tanggal_pengadaan'] !== '') {
            $t = DateTime::createFromFormat('Y-m-d', $d['tanggal_pengadaan']);
            if (!$t || $t->format('Y-m-d') !== $d['tanggal_pengadaan']) {
                $err['tanggal_pengadaan'] = 'Tanggal pengadaan tidak valid.';
            }
        }
        return $err;
    }

    public static function simpan(array $d, ?int $id, ?array $lama = null): int
    {
        $tgl = $d['tanggal_pengadaan'] === '' ? null : $d['tanggal_pengadaan'];
        if ($id === null) {
            Database::run(
                'INSERT INTO koleksi (id_bibliografi, barcode, lokasi, status, tanggal_pengadaan) VALUES (?,?,?,?,?)',
                [(int)$d['id_bibliografi'], $d['barcode'], $d['lokasi'], $d['status'], $tgl]
            );
            return Database::lastId();
        }
        // eksemplar yang sedang dipinjam: status tidak boleh diubah manual
        $status = ($lama && $lama['status'] === 'Dipinjam') ? 'Dipinjam' : $d['status'];
        Database::run(
            'UPDATE koleksi SET id_bibliografi=?, barcode=?, lokasi=?, status=?, tanggal_pengadaan=? WHERE id_koleksi=?',
            [(int)$d['id_bibliografi'], $d['barcode'], $d['lokasi'], $status, $tgl, $id]
        );
        return $id;
    }

    public static function hapus(int $id): void
    {
        $k = self::cari($id);
        if (!$k) {
            throw new AturanBisnis('Koleksi tidak ditemukan.');
        }
        if (Database::value('SELECT COUNT(*) FROM transaksi WHERE id_koleksi = ?', [$id])) {
            throw new AturanBisnis('Eksemplar memiliki riwayat transaksi sirkulasi dan tidak dapat dihapus. Ubah statusnya menjadi Rusak/Hilang.');
        }
        Database::run('DELETE FROM koleksi WHERE id_koleksi = ?', [$id]);
    }
}
