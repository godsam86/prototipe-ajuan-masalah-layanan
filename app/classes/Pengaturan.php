<?php
declare(strict_types=1);

/** Konfigurasi aplikasi yang disimpan di tabel pengaturan. */
final class Pengaturan
{
    private static ?array $cache = null;

    public static function semua(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            foreach (Database::all('SELECT kunci, nilai FROM pengaturan') as $r) {
                self::$cache[$r['kunci']] = $r['nilai'];
            }
        }
        return self::$cache;
    }

    public static function ambil(string $kunci, string $default = ''): string
    {
        $s = self::semua();
        return $s[$kunci] ?? $default;
    }

    public static function int(string $kunci, int $default = 0): int
    {
        $v = self::ambil($kunci, (string)$default);
        return is_numeric($v) ? (int)$v : $default;
    }

    public static function simpan(string $kunci, string $nilai): void
    {
        Database::run(
            'INSERT INTO pengaturan (kunci, nilai) VALUES (?, ?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)',
            [$kunci, $nilai]
        );
        self::$cache = null;
    }
}
