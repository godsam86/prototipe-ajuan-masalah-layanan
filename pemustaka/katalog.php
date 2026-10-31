<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/views/layout.php';

Auth::wajib(['Pemustaka']);
$bahan = get('bahan');
$total = Bibliografi::hitung('', 'semua', $bahan);
$pg = paginasi($total, 12);
$daftar = Bibliografi::daftar('', 'semua', $bahan, $pg['per'], $pg['offset']);

tampil_header('Katalog Koleksi', 'katalog', ['ikon' => 'book', 'crumb' => 'Katalog > Daftar Koleksi']);
?>
<div class="box box-info">
    <div class="box-header with-border">
        <form method="get" class="form-inline">
            <label>Jenis bahan:&nbsp;</label>
            <select name="bahan" class="form-control input-sm" onchange="this.form.submit()">
                <option value="">Semua</option>
                <?php foreach (Bibliografi::BAHAN as $b): ?><option value="<?= e($b) ?>"<?= selected($bahan, $b) ?>><?= e($b) ?></option><?php endforeach; ?>
            </select>
            <span class="text-muted" style="margin-left:12px"><?= angka($total) ?> judul</span>
        </form>
    </div>
    <div class="box-body">
        <?php foreach ($daftar as $b): ?>
            <div class="book-item">
                <div class="row">
                    <div class="col-sm-2 text-center">
                        <a href="<?= e(url('detail.php?id=' . (int)$b['id_bibliografi'])) ?>"><img class="book-cover" src="<?= e(Bibliografi::urlCover($b['cover'])) ?>" alt="" loading="lazy"></a>
                    </div>
                    <div class="col-sm-10">
                        <table class="table2" style="width:100%;background:transparent">
                            <tr><th colspan="2" style="font-size:16px;padding-bottom:6px"><a href="<?= e(url('detail.php?id=' . (int)$b['id_bibliografi'])) ?>"><?= e($b['judul']) ?></a></th></tr>
                            <tr><td width="22%"><b>Pengarang</b></td><td><?= e($b['pengarang']) ?></td></tr>
                            <tr><td><b>Penerbitan</b></td><td><?= e(trim(($b['kota_terbit'] ? $b['kota_terbit'] . ' : ' : '') . $b['penerbit'] . ', ' . $b['tahun_terbit'], ' ,:')) ?></td></tr>
                            <tr><td><b>Jenis Bahan</b></td><td><?= e($b['jenis_bahan']) ?></td></tr>
                            <tr><td><b>Ketersediaan</b></td><td><b><?= (int)$b['tersedia'] ?> dari <?= (int)$b['total_eks'] ?> eksemplar</b> tersedia</td></tr>
                            <tr><td><b>Nomor Panggil</b></td><td><span class="label label-default"><?= e($b['nomor_panggil'] ?: '-') ?></span></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$daftar): ?><div class="alert alert-warning">Data koleksi tidak ditemukan.</div><?php endif; ?>
        <div class="text-center"><?= paginasi_html($pg) ?></div>
    </div>
</div>
<?php tampil_footer(); ?>
