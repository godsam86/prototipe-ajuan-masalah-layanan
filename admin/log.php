<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/views/layout.php';

Auth::wajib(['Admin']);
$q = get('q');
$aksi = get('aksi');
$modul = get('modul');
$w = ['1=1'];
$p = [];
if ($q !== '') {
    $like = '%' . like_escape($q) . '%';
    $w[] = '(l.keterangan LIKE ? OR u.username LIKE ? OR l.ip_address LIKE ?)';
    array_push($p, $like, $like, $like);
}
if ($aksi !== '') {
    $w[] = 'l.aksi = ?';
    $p[] = $aksi;
}
if ($modul !== '') {
    $w[] = 'l.modul = ?';
    $p[] = $modul;
}
$where = implode(' AND ', $w);
$total = (int)Database::value("SELECT COUNT(*) FROM log_aktivitas l LEFT JOIN users u ON u.id_user = l.id_user WHERE $where", $p);
$pg = paginasi($total, 20);
$rows = Database::all(
    "SELECT l.*, u.username FROM log_aktivitas l LEFT JOIN users u ON u.id_user = l.id_user
     WHERE $where ORDER BY l.id_log DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}",
    $p
);
$aksiList = array_column(Database::all('SELECT DISTINCT aksi FROM log_aktivitas ORDER BY aksi'), 'aksi');
$modulList = array_column(Database::all('SELECT DISTINCT modul FROM log_aktivitas ORDER BY modul'), 'modul');

tampil_header('Log Aktivitas', 'log', ['ikon' => 'clock-o', 'crumb' => 'Log Aktivitas']);
?>
<div class="box box-default">
    <div class="box-body">
        <form method="get" class="form-inline" style="margin-bottom:12px">
            <input type="text" name="q" value="<?= e($q) ?>" class="form-control input-sm" placeholder="Keterangan / username / IP">
            <select name="aksi" class="form-control input-sm"><option value="">Semua Aksi</option><?php foreach ($aksiList as $a): ?><option<?= selected($aksi, $a) ?>><?= e($a) ?></option><?php endforeach; ?></select>
            <select name="modul" class="form-control input-sm"><option value="">Semua Modul</option><?php foreach ($modulList as $a): ?><option<?= selected($modul, $a) ?>><?= e($a) ?></option><?php endforeach; ?></select>
            <button class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Filter</button>
        </form>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-condensed">
                <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Modul</th><th>Keterangan</th><th>IP</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr><td style="white-space:nowrap"><?= e(tgl_waktu($r['created_at'])) ?></td><td><?= e($r['username'] ?? '-') ?></td>
                        <td><span class="label label-<?= $r['aksi'] === 'LOGIN_GAGAL' ? 'danger' : 'info' ?>"><?= e($r['aksi']) ?></span></td>
                        <td><?= e($r['modul']) ?></td><td><?= e($r['keterangan']) ?></td><td><?= e($r['ip_address']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted">Tidak ada log.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="text-muted"><?= angka($total) ?> catatan</div>
        <?= paginasi_html($pg) ?>
    </div>
</div>
<?php tampil_footer();
