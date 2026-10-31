<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

Auth::wajib(['Admin', 'Pimpinan']);
$tahun = (int)get('tahun', date('Y'));
if ($tahun < 2000 || $tahun > 2100) {
    $tahun = (int)date('Y');
}
$periode = get('periode', 'tahun');
if (!preg_match('/^(tahun|q[1-4]|[1-9]|1[0-2])$/', $periode)) {
    $periode = 'tahun';
}
[$awal, $akhir, $label] = rentang_periode($tahun, $periode);
$k = DashboardManfaat::ringkasan($awal, $akhir);

/** Cegah injeksi rumus pada spreadsheet. */
function csv_aman($v): string
{
    $s = (string)($v ?? '');
    return ($s !== '' && strpbrk($s[0], "=+-@\t\r") !== false) ? "'" . $s : $s;
}

Log::catat('UNDUH', 'laporan', null, 'Unduh laporan kinerja ' . $label);
$nama = 'laporan_kinerja_' . preg_replace('/[^a-z0-9]+/', '_', strtolower($label)) . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nama . '"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['LAPORAN KINERJA LAYANAN PEMUSTAKA'], ';');
fputcsv($out, ['Periode', $label], ';');
fputcsv($out, ['Jumlah tiket', $k['total']], ';');
fputcsv($out, ['Tingkat penyelesaian (%)', $k['penyelesaian'] ?? '-'], ';');
fputcsv($out, ['Rata-rata waktu penanganan', durasi_jam($k['rata_jam'])], ';');
fputcsv($out, ['Kepuasan pemustaka (1-5)', $k['kepuasan'] ?? '-'], ';');
fputcsv($out, ['Capaian target layanan (%)', $k['target'] ?? '-'], ';');
fputcsv($out, [], ';');
fputcsv($out, ['No. Tiket', 'Tanggal Dikirim', 'Pelapor', 'Jenis', 'Kategori', 'Urgensi', 'Status', 'Tanggal Selesai', 'Durasi (jam)', 'Penilaian'], ';');
$st = Database::pdo()->prepare(
    "SELECT t.nomor_tiket, t.tanggal_dibuat, p.nama, t.jenis, t.kategori, t.urgensi, t.status, t.tanggal_selesai,
            CASE WHEN t.tanggal_selesai IS NOT NULL THEN ROUND(TIMESTAMPDIFF(MINUTE, t.tanggal_dibuat, t.tanggal_selesai) / 60, 1) END AS jam, t.rating
     FROM tiket_insiden t JOIN users p ON p.id_user = t.id_pelapor
     WHERE t.tanggal_dibuat >= ? AND t.tanggal_dibuat < ? ORDER BY t.tanggal_dibuat"
);
$st->execute([$awal, $akhir]);
while ($r = $st->fetch(PDO::FETCH_NUM)) {
    fputcsv($out, array_map('csv_aman', $r), ';');
}
fclose($out);
