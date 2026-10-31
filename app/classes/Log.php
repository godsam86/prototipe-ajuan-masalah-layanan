<?php
declare(strict_types=1);

/** Pencatatan log aktivitas (tabel log_aktivitas). */
final class Log
{
    public static function catat(string $aksi, string $modul, ?int $idReferensi = null, ?string $keterangan = null, ?int $idUser = null): void
    {
        try {
            if ($idUser === null) {
                $u = Auth::user();
                $idUser = $u ? (int)$u['id_user'] : null;
            }
            Database::run(
                'INSERT INTO log_aktivitas (id_user, aksi, modul, id_referensi, keterangan, ip_address, user_agent) VALUES (?,?,?,?,?,?,?)',
                [
                    $idUser,
                    mb_substr($aksi, 0, 30),
                    mb_substr($modul, 0, 30),
                    $idReferensi,
                    $keterangan === null ? null : mb_substr($keterangan, 0, 255),
                    ip_klien(),
                    mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                ]
            );
        } catch (Throwable $e) {
            error_log('[INLISLite] gagal mencatat log: ' . $e->getMessage());
        }
    }
}
