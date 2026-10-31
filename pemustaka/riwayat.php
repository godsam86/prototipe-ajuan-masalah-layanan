<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/views/layout.php';

$user = Auth::wajib(['Pemustaka']);
$status = get('status');
$filter = ['pelapor' => (int)$user['id_user'], 'status' => $status, 'urut_baru' => true];
$ring = PermintaanLayanan::ringkasStatus(['pelapor' => (int)$user['id_user']]);
$total = PermintaanLayanan::hitung($filter);
$pg = paginasi($total, 10);
$daftar = PermintaanLayanan::daftar($filter, $pg['per'], $pg['offset']);

tampil_header('Riwayat & Status Tiket', 'layanan_riwayat', ['ikon' => 'comment', 'crumb' => 'Layanan Pemustaka > Riwayat']);
?>
<div class="box box-default">
    <div class="box-body">
        <form method="get" class="form-inline" style="margin-bottom:14px">
            <label>Status:&nbsp;</label>
            <select name="status" class="form-control input-sm" onchange="this.form.submit()">
                <option value="">Semua (<?= $ring['total'] ?>)</option>
                <?php foreach (PermintaanLayanan::STATUS as $s): ?>
                    <option value="<?= e($s) ?>"<?= selected($status, $s) ?>><?= e($s) ?> (<?= $ring[strtolower($s)] ?>)</option>
                <?php endforeach; ?>
            </select>
            <a href="<?= e(url('pemustaka/layanan.php')) ?>" class="btn btn-primary btn-sm pull-right"><i class="fa fa-plus"></i> Ajukan Baru</a>
        </form>
        <div class="table-modern table-responsive">
            <table class="table" style="width:100%">
                <thead><tr><th>No. Tiket</th><th>Tanggal</th><th>Kategori</th><th>Urgensi</th><th>Status</th><th style="width:110px">Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($daftar as $t): ?>
                    <tr>
                        <td><strong><?= e($t['nomor_tiket']) ?></strong></td>
                        <td><?= e(tgl_waktu($t['tanggal_dibuat'])) ?></td>
                        <td><?= e($t['kategori']) ?><br><small class="text-muted"><?= e($t['jenis']) ?></small></td>
                        <td><?= badge_urgensi($t['urgensi']) ?></td>
                        <td><?= badge_status($t['status']) ?>
                            <?php if ($t['status'] === 'Selesai' && $t['rating'] === null): ?><br><small class="text-orange">Belum dinilai</small><?php endif; ?></td>
                        <td><a class="btn-action-outline" href="<?= e(url('pemustaka/tiket.php?id=' . (int)$t['id_tiket'])) ?>">Lihat</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$daftar): ?><tr><td colspan="6" class="text-center text-muted">Belum ada tiket.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= paginasi_html($pg) ?>
    </div>
</div>
<?php tampil_footer(); ?>
