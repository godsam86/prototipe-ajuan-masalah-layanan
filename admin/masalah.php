<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/views/layout.php';

Auth::wajib(['Admin']);
$status = get('status');
$q = get('q');
$total = Masalah::hitung($status, $q);
$pg = paginasi($total, 10);
$daftar = Masalah::daftar($status, $q, $pg['per'], $pg['offset']);
$kb = Masalah::basisPengetahuan();

tampil_header('Manajemen Masalah', 'masalah', ['ikon' => 'ticket', 'crumb' => 'Penanganan Layanan > Manajemen Masalah']);
?>
<div class="box box-default">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-wrench"></i> Riwayat Masalah &amp; Akar Masalah</h3></div>
    <div class="box-body">
        <form method="get" class="form-inline" style="margin-bottom:14px">
            <input type="text" name="q" value="<?= e($q) ?>" class="form-control input-sm" placeholder="Cari no. tiket / akar masalah / solusi" style="min-width:250px">
            <select name="status" class="form-control input-sm"><option value="">Semua Status</option>
                <?php foreach (['Dianalisis', 'Selesai'] as $s): ?><option<?= selected($status, $s) ?>><?= e($s) ?></option><?php endforeach; ?></select>
            <button class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Filter</button>
            <a class="btn btn-default btn-sm" href="<?= e(url('admin/masalah.php')) ?>">Reset</a>
        </form>
        <div class="table-modern table-responsive">
            <table class="table" style="width:100%">
                <thead><tr><th>Tiket</th><th>Akar Masalah</th><th>Tindakan &amp; Pencegahan</th><th>Status</th><th style="width:100px">Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($daftar as $m): ?>
                    <tr>
                        <td><strong><?= e($m['nomor_tiket']) ?></strong><br><small class="text-muted"><?= e($m['kategori']) ?><br><?= e(tgl($m['tanggal_analisis'])) ?></small></td>
                        <td class="pre-wrap" style="max-width:300px"><?= e($m['akar_masalah']) ?></td>
                        <td class="pre-wrap" style="max-width:300px"><?= $m['tindakan_perbaikan'] ? '<b>Perbaikan:</b> ' . e($m['tindakan_perbaikan']) : '' ?><?= $m['langkah_pencegahan'] ? "\n<b>Pencegahan:</b> " . e($m['langkah_pencegahan']) : '' ?><?= !$m['tindakan_perbaikan'] && !$m['langkah_pencegahan'] ? '-' : '' ?></td>
                        <td><?= badge_status($m['status']) ?></td>
                        <td><a class="btn-action-outline" href="<?= e(url('admin/tiket_detail.php?id=' . (int)$m['id_tiket'])) ?>"><?= $m['status'] === 'Dianalisis' ? 'Tangani' : 'Lihat' ?></a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$daftar): ?><tr><td colspan="5" class="text-center text-muted">Belum ada data masalah.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= paginasi_html($pg) ?>
    </div>
</div>

<div class="box box-success">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-lightbulb-o"></i> Basis Pengetahuan (Solusi Terdokumentasi) &mdash; <?= count($kb) ?> solusi</h3></div>
    <div class="box-body">
        <?php foreach ($kb as $s): ?>
            <div style="padding:10px 0;border-bottom:1px dashed #ddd">
                <b><?= e($s['judul_solusi']) ?></b> <span class="label label-default"><?= e($s['nomor_tiket']) ?></span> <span class="label label-info"><?= e($s['kategori']) ?></span>
                <div class="pre-wrap" style="margin-top:4px"><?= e($s['deskripsi_solusi']) ?></div>
            </div>
        <?php endforeach; ?>
        <?php if (!$kb): ?><p class="text-muted" style="margin:0">Belum ada solusi terdokumentasi.</p><?php endif; ?>
    </div>
</div>
<?php tampil_footer(); ?>
