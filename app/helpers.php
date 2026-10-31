<?php
declare(strict_types=1);

/* ------------------------------------------------------------------ */
/*  Helper umum                                                        */
/* ------------------------------------------------------------------ */

function e($v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function redirect(string $path): void
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}

/** Nilai POST sebagai string yang sudah di-trim. */
function post(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function get(string $key, string $default = ''): string
{
    $v = $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Nilai untuk mengisi ulang form: POST jika ada, jika tidak gunakan default. */
function val(string $key, $default = ''): string
{
    if (is_post() && isset($_POST[$key]) && is_string($_POST[$key])) {
        return e($_POST[$key]);
    }
    return e($default);
}

function selected($current, $option): string
{
    return (string)$current === (string)$option ? ' selected' : '';
}

function ip_klien(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

/* ------------------------------------------------------------------ */
/*  Flash message                                                      */
/* ------------------------------------------------------------------ */

function flash(string $type, string $msg): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'msg' => $msg];
}

/** @return array<int,array{type:string,msg:string}> */
function flash_ambil(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

/* ------------------------------------------------------------------ */
/*  CSRF                                                               */
/* ------------------------------------------------------------------ */

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** Hentikan permintaan POST jika token CSRF tidak valid. */
function csrf_cek(): void
{
    $t = $_POST['_csrf'] ?? '';
    if (!is_string($t) || empty($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'], $t)) {
        http_response_code(419);
        echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Sesi Berakhir</title></head>'
           . '<body style="font-family:sans-serif;padding:40px"><h2>Sesi formulir tidak valid atau telah berakhir</h2>'
           . '<p>Silakan kembali, muat ulang halaman, lalu kirim ulang formulir.</p>'
           . '<p><a href="' . e(url('')) . '">Kembali</a></p></body></html>';
        exit;
    }
}

/* ------------------------------------------------------------------ */
/*  Format                                                             */
/* ------------------------------------------------------------------ */

const BULAN_ID = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

function tgl(?string $d, bool $pendek = true): string
{
    if (!$d || $d === '0000-00-00') {
        return '-';
    }
    $t = strtotime($d);
    if ($t === false) {
        return '-';
    }
    $b = BULAN_ID[(int)date('n', $t)];
    return date('j', $t) . ' ' . ($pendek ? substr($b, 0, 3) : $b) . ' ' . date('Y', $t);
}

function tgl_waktu(?string $d): string
{
    if (!$d) {
        return '-';
    }
    $t = strtotime($d);
    return $t === false ? '-' : tgl($d) . ', ' . date('H:i', $t);
}

function rupiah($n): string
{
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}

function angka($n, int $desimal = 0): string
{
    return number_format((float)$n, $desimal, ',', '.');
}

/** Format durasi (jam, desimal) menjadi teks, mis. "1,4 hari" atau "6,5 jam". */
function durasi_jam($jam): string
{
    if ($jam === null || $jam === '') {
        return '-';
    }
    $jam = (float)$jam;
    if ($jam >= 24) {
        return angka($jam / 24, 1) . ' hari';
    }
    return angka($jam, 1) . ' jam';
}

function badge_status(string $status): string
{
    $map = [
        'Baru' => 'baru', 'Diproses' => 'diproses', 'Selesai' => 'selesai', 'Dianalisis' => 'diproses',
    ];
    $c = $map[$status] ?? 'selesai';
    return '<span class="status-badge ' . $c . '">' . e($status) . '</span>';
}

function badge_urgensi(string $u): string
{
    $c = ['Tinggi' => 'danger', 'Sedang' => 'warning', 'Rendah' => 'default'][$u] ?? 'default';
    return '<span class="label label-' . $c . '">' . e($u) . '</span>';
}

function label_kategori(string $k): string
{
    return $k;
}

function bintang(?float $nilai): string
{
    if ($nilai === null) {
        return '-';
    }
    return angka($nilai, 1) . ' / 5';
}

/* ------------------------------------------------------------------ */
/*  Paginasi                                                           */
/* ------------------------------------------------------------------ */

/** @return array{page:int,pages:int,offset:int,per:int} */
function paginasi(int $total, int $per = 10): array
{
    $pages = max(1, (int)ceil($total / $per));
    $page = (int)get('page', '1');
    $page = max(1, min($page, $pages));
    return ['page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $per, 'per' => $per];
}

function paginasi_html(array $p): string
{
    if ($p['pages'] <= 1) {
        return '';
    }
    $q = $_GET;
    $link = function (int $n, string $label, bool $active = false, bool $disabled = false) use ($q): string {
        $q['page'] = $n;
        $href = $disabled ? '#' : '?' . http_build_query($q);
        $cls = ($active ? 'active ' : '') . ($disabled ? 'disabled' : '');
        return '<li class="' . trim($cls) . '"><a href="' . e($href) . '">' . $label . '</a></li>';
    };
    $h = '<ul class="pagination pagination-sm" style="margin:10px 0 0">';
    $h .= $link(max(1, $p['page'] - 1), '&laquo;', false, $p['page'] <= 1);
    $start = max(1, $p['page'] - 3);
    $end = min($p['pages'], $p['page'] + 3);
    for ($i = $start; $i <= $end; $i++) {
        $h .= $link($i, (string)$i, $i === $p['page']);
    }
    $h .= $link(min($p['pages'], $p['page'] + 1), '&raquo;', false, $p['page'] >= $p['pages']);
    return $h . '</ul>';
}

/** Escape karakter khusus LIKE. */
function like_escape(string $s): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $s);
}

/* ------------------------------------------------------------------ */
/*  Periode laporan                                                    */
/* ------------------------------------------------------------------ */

/**
 * Ubah pilihan tahun + periode menjadi rentang tanggal.
 * periode: 'tahun' | 'q1'..'q4' | '1'..'12'
 * @return array{0:string,1:string,2:string} [awal, akhir(eksklusif), label]
 */
function rentang_periode(int $tahun, string $periode): array
{
    if (preg_match('/^q([1-4])$/', $periode, $m)) {
        $q = (int)$m[1];
        $awal = sprintf('%04d-%02d-01', $tahun, ($q - 1) * 3 + 1);
        $akhir = date('Y-m-d', strtotime($awal . ' +3 months'));
        $rom = [1 => 'I', 'II', 'III', 'IV'];
        return [$awal, $akhir, 'Triwulan ' . $rom[$q] . ' ' . $tahun];
    }
    if (ctype_digit($periode) && (int)$periode >= 1 && (int)$periode <= 12) {
        $m = (int)$periode;
        $awal = sprintf('%04d-%02d-01', $tahun, $m);
        $akhir = date('Y-m-d', strtotime($awal . ' +1 month'));
        return [$awal, $akhir, BULAN_ID[$m] . ' ' . $tahun];
    }
    return [sprintf('%04d-01-01', $tahun), sprintf('%04d-01-01', $tahun + 1), 'Tahun ' . $tahun];
}

function opsi_periode(string $pilih): string
{
    $o = ['tahun' => 'Setahun penuh', 'q1' => 'Triwulan I', 'q2' => 'Triwulan II', 'q3' => 'Triwulan III', 'q4' => 'Triwulan IV'];
    for ($i = 1; $i <= 12; $i++) {
        $o[(string)$i] = BULAN_ID[$i];
    }
    $h = '';
    foreach ($o as $k => $v) {
        $h .= '<option value="' . e($k) . '"' . selected($pilih, $k) . '>' . e($v) . '</option>';
    }
    return $h;
}

/** Escape teks lalu sorot kata kunci (aman dari XSS). */
function sorot(string $teks, string $kata): string
{
    $aman = e($teks);
    $kata = trim($kata);
    if ($kata === '' || mb_strlen($kata) < 2) {
        return $aman;
    }
    $pola = '/(' . preg_quote(e($kata), '/') . ')/iu';
    return preg_replace($pola, '<b class="highlight-match">$1</b>', $aman) ?? $aman;
}

/** Tampilkan pesan galat validasi di bawah field. */
function galat(array $err, string $key): string
{
    return isset($err[$key]) ? '<span class="help-block">' . e($err[$key]) . '</span>' : '';
}

function kelas_galat(array $err, string $key): string
{
    return isset($err[$key]) ? ' has-error' : '';
}
