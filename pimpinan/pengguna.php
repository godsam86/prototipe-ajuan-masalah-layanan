<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/views/layout.php';

Auth::wajib(['Pimpinan']);
$q = get('q');
$peran = get('peran');
$total = Pengguna::hitung($q, $peran, '');
$pg = paginasi($total, 15);
$daftar = Pengguna::daftar($q, $peran, '', $pg['per'], $pg['offset']);

tampil_header('Data Pengguna (Akses Terbatas)', 'pengguna', ['ikon' => 'user', 'crumb' => 'Pimpinan > Data Pengguna', 'sub' => 'Pimpinan hanya dapat melihat data pengguna. Pengelolaan akun dilakukan oleh Admin.']);
?>
<div class="box box-default">
    <div class="box-body">
        <form method="get" class="form-inline" style="margin-bottom:14px">
            <input type="text" name="q" value="<?= e($q) ?>" class="form-control input-sm" placeholder="Cari nama / username">
            <select name="peran" class="form-control input-sm"><option value="">Semua Peran</option>
                <?php foreach (Pengguna::PERAN as $p): ?><option<?= selected($peran, $p) ?>><?= e($p) ?></option><?php endforeach; ?></select>
            <button class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Cari</button>
        </form>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Nama</th><th>Username</th><th>Peran</th><th>Status</th><th>Login Terakhir</th></tr></thead>
                <tbody>
                <?php foreach ($daftar as $u): ?>
                    <tr><td><?= e($u['nama']) ?></td><td><?= e($u['username']) ?></td><td><?= e($u['peran']) ?></td>
                        <td><span class="label label-<?= $u['status'] === 'Aktif' ? 'success' : 'default' ?>"><?= e($u['status']) ?></span></td>
                        <td><?= e(tgl_waktu($u['last_login'])) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$daftar): ?><tr><td colspan="5" class="text-center text-muted">Data tidak ditemukan.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= paginasi_html($pg) ?>
    </div>
</div>
<?php tampil_footer(); ?>
