<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require APP_ROOT . '/app/views/layout.php';

$user = Auth::wajib(['Pemustaka', 'Admin']);
$b = Bibliografi::cari((int)get('id'));
if (!$b) {
    flash('danger', 'Data koleksi tidak ditemukan.');
    redirect($user['peran'] === 'Admin' ? 'admin/bibliografi.php' : 'pemustaka/katalog.php');
}
$eks = Bibliografi::eksemplar((int)$b['id_bibliografi']);
$kembali = $user['peran'] === 'Admin' ? 'admin/bibliografi.php' : 'pemustaka/katalog.php';

tampil_header('Detail Koleksi', $user['peran'] === 'Admin' ? 'bibliografi' : 'katalog', ['ikon' => 'book', 'crumb' => 'Katalog > Detail']);
?>
<div class="box box-default">
    <div class="box-body" style="padding:20px">
        <div class="row">
            <div class="col-sm-3 text-center"><img class="book-cover" style="width:170px;height:250px" src="<?= e(Bibliografi::urlCover($b['cover'])) ?>" alt=""></div>
            <div class="col-sm-9">
                <h3 style="margin-top:0"><?= e($b['judul']) ?></h3>
                <table class="table table-condensed" style="background:transparent">
                    <tr><td width="22%"><b>Pengarang</b></td><td><?= e($b['pengarang']) ?></td></tr>
                    <tr><td><b>Penerbit</b></td><td><?= e($b['penerbit'] ?: '-') ?></td></tr>
                    <tr><td><b>Kota / Tahun</b></td><td><?= e(($b['kota_terbit'] ?: '-') . ' / ' . ($b['tahun_terbit'] ?: '-')) ?></td></tr>
                    <tr><td><b>ISBN / ISSN</b></td><td><?= e($b['isbn'] ?: '-') ?></td></tr>
                    <tr><td><b>Subjek</b></td><td><?= e($b['subjek'] ?: '-') ?></td></tr>
                    <tr><td><b>Nomor Panggil</b></td><td><span class="label label-default"><?= e($b['nomor_panggil'] ?: '-') ?></span></td></tr>
                    <tr><td><b>Jenis Bahan</b></td><td><?= e($b['jenis_bahan']) ?></td></tr>
                    <tr><td><b>Deskripsi Fisik</b></td><td><?= e($b['deskripsi_fisik'] ?: '-') ?></td></tr>
                    <tr><td><b>Ketersediaan</b></td><td><b><?= (int)$b['tersedia'] ?> dari <?= (int)$b['total_eks'] ?> eksemplar</b> tersedia</td></tr>
                </table>
            </div>
        </div>
        <h4 style="margin-top:20px"><i class="fa fa-list"></i> Informasi Eksemplar</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>No. Barcode</th><th>Lokasi</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($eks as $k): ?>
                    <tr><td><code><?= e($k['barcode']) ?></code></td><td><?= e($k['lokasi']) ?></td>
                        <td><span class="label label-<?= $k['status'] === 'Tersedia' ? 'success' : ($k['status'] === 'Dipinjam' ? 'warning' : 'danger') ?>"><?= e($k['status']) ?></span></td></tr>
                <?php endforeach; ?>
                <?php if (!$eks): ?><tr><td colspan="3" class="text-center text-muted">Belum ada eksemplar.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <a href="<?= e(url($kembali)) ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali</a>
    </div>
</div>
<?php tampil_footer(); ?>
