<?php
declare(strict_types=1);

/** Referensi pilihan formulir layanan: jenis_layanan dan kategori_layanan. */
final class Referensi
{
    private const TABEL = [
        'jenis'    => ['tabel' => 'jenis_layanan',    'pk' => 'id_jenis',    'kolom' => 'jenis',    'label' => 'Jenis'],
        'kategori' => ['tabel' => 'kategori_layanan', 'pk' => 'id_kategori', 'kolom' => 'kategori', 'label' => 'Kategori'],
    ];

    private static function meta(string $t): array
    {
        if (!isset(self::TABEL[$t])) {
            throw new AturanBisnis('Referensi tidak dikenal.');
        }
        return self::TABEL[$t];
    }

    /** Daftar nama. $hanyaAktif=true untuk pilihan di formulir. @return string[] */
    public static function nama(string $t, bool $hanyaAktif = true): array
    {
        $m = self::meta($t);
        $w = $hanyaAktif ? 'WHERE aktif = 1' : '';
        return array_column(Database::all("SELECT nama FROM {$m['tabel']} $w ORDER BY {$m['pk']}"), 'nama');
    }

    /** Daftar lengkap beserta jumlah tiket yang memakainya. */
    public static function daftar(string $t): array
    {
        $m = self::meta($t);
        return Database::all(
            "SELECT r.*, (SELECT COUNT(*) FROM tiket_insiden x WHERE x.{$m['kolom']} = r.nama) AS dipakai
             FROM {$m['tabel']} r ORDER BY r.{$m['pk']}"
        );
    }

    private static function cek(string $nama): string
    {
        $nama = trim(preg_replace('/\s+/u', ' ', $nama) ?? '');
        if ($nama === '' || mb_strlen($nama) > 60) {
            throw new AturanBisnis('Nama wajib diisi (maksimal 60 karakter).');
        }
        return $nama;
    }

    public static function tambah(string $t, string $nama): void
    {
        $m = self::meta($t);
        $nama = self::cek($nama);
        if (Database::value("SELECT COUNT(*) FROM {$m['tabel']} WHERE nama = ?", [$nama])) {
            throw new AturanBisnis("{$m['label']} \"$nama\" sudah ada.");
        }
        Database::run("INSERT INTO {$m['tabel']} (nama) VALUES (?)", [$nama]);
    }

    public static function ubah(string $t, int $id, string $nama): void
    {
        $m = self::meta($t);
        $nama = self::cek($nama);
        if (!Database::value("SELECT COUNT(*) FROM {$m['tabel']} WHERE {$m['pk']} = ?", [$id])) {
            throw new AturanBisnis('Data tidak ditemukan.');
        }
        if (Database::value("SELECT COUNT(*) FROM {$m['tabel']} WHERE nama = ? AND {$m['pk']} <> ?", [$nama, $id])) {
            throw new AturanBisnis("{$m['label']} \"$nama\" sudah ada.");
        }
        Database::run("UPDATE {$m['tabel']} SET nama = ? WHERE {$m['pk']} = ?", [$nama, $id]); // tiket ikut berubah (ON UPDATE CASCADE)
    }

    public static function setAktif(string $t, int $id, bool $aktif): void
    {
        $m = self::meta($t);
        if (!$aktif && count(self::nama($t)) <= 1 && Database::value("SELECT aktif FROM {$m['tabel']} WHERE {$m['pk']} = ?", [$id])) {
            throw new AturanBisnis('Minimal harus ada satu pilihan yang aktif.');
        }
        Database::run("UPDATE {$m['tabel']} SET aktif = ? WHERE {$m['pk']} = ?", [$aktif ? 1 : 0, $id]);
    }

    public static function hapus(string $t, int $id): void
    {
        $m = self::meta($t);
        $r = Database::row("SELECT * FROM {$m['tabel']} WHERE {$m['pk']} = ?", [$id]);
        if (!$r) {
            throw new AturanBisnis('Data tidak ditemukan.');
        }
        if (Database::value("SELECT COUNT(*) FROM tiket_insiden WHERE {$m['kolom']} = ?", [$r['nama']])) {
            throw new AturanBisnis('Sudah dipakai pada tiket, tidak dapat dihapus. Nonaktifkan saja agar tidak muncul di formulir.');
        }
        if ($r['aktif'] && count(self::nama($t)) <= 1) {
            throw new AturanBisnis('Minimal harus ada satu pilihan yang aktif.');
        }
        Database::run("DELETE FROM {$m['tabel']} WHERE {$m['pk']} = ?", [$id]);
    }
}
