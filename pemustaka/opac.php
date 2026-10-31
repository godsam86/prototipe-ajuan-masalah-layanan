<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/views/layout.php';

Auth::wajib(['Pemustaka']);
$q = get('q');
$ruas = get('ruas', 'semua');
if (!isset(Bibliografi::RUAS[$ruas])) {
    $ruas = 'semua';
}
$bahan = get('bahan');
$dicari = isset($_GET['q']);
$total = $dicari ? Bibliografi::hitung($q, $ruas, $bahan) : 0;
$pg = paginasi($total, 10);
$hasil = $dicari ? Bibliografi::daftar($q, $ruas, $bahan, $pg['per'], $pg['offset']) : [];

tampil_header('Pencarian Koleksi (OPAC)', 'opac', ['ikon' => 'search', 'crumb' => 'Online Public Access Catalog']);
?>
<div class="box box-default">
    <div class="box-body" style="padding:18px 20px">
        <form method="get" action="<?= e(url('pemustaka/opac.php')) ?>">
            <div class="row">
                <div class="col-sm-4"><div class="form-group"><input type="text" class="form-control" name="q" value="<?= e($q) ?>" placeholder="Kata kunci" maxlength="100" autofocus></div></div>
                <div class="col-sm-3"><div class="form-group">
                    <select class="form-control" name="ruas"><?php foreach (Bibliografi::RUAS as $k => $v): ?><option value="<?= e($k) ?>"<?= selected($ruas, $k) ?>><?= e($v) ?></option><?php endforeach; ?></select>
                </div></div>
                <div class="col-sm-3"><div class="form-group">
                    <select class="form-control" name="bahan"><option value="">Semua Bahan</option>
                        <?php foreach (Bibliografi::BAHAN as $b): ?><option value="<?= e($b) ?>"<?= selected($bahan, $b) ?>><?= e($b) ?></option><?php endforeach; ?></select>
                </div></div>
                <div class="col-sm-2"><button class="btn btn-success btn-block" type="submit"><i class="fa fa-search"></i> Cari</button></div>
            </div>
        </form>
    </div>
</div>
<?php if ($dicari): ?>
<div class="box box-default">
    <div class="box-body" style="padding:20px 15px">
        <p>Menampilkan <b><?= angka($total) ?></b> hasil<?= $q !== '' ? ' untuk &ldquo;<b>' . e($q) . '</b>&rdquo;' : '' ?></p>
        <?php foreach ($hasil as $i => $b): ?>
            <div class="book-item">
                <div class="row">
                    <div class="col-sm-1 text-center"><b><?= $pg['offset'] + $i + 1 ?></b></div>
                    <div class="col-sm-2 text-center">
                        <a href="<?= e(url('detail.php?id=' . (int)$b['id_bibliografi'])) ?>"><img class="book-cover" src="<?= e(Bibliografi::urlCover($b['cover'])) ?>" alt="" loading="lazy"></a>
                    </div>
                    <div class="col-sm-9">
                        <table class="table2" style="width:100%;background:transparent">
                            <tr><th colspan="2" style="font-size:16px;padding-bottom:6px"><a href="<?= e(url('detail.php?id=' . (int)$b['id_bibliografi'])) ?>"><?= sorot($b['judul'], $q) ?></a></th></tr>
                            <tr><td width="22%"><b>Jenis Bahan</b></td><td><?= e($b['jenis_bahan']) ?></td></tr>
                            <tr><td><b>Pengarang</b></td><td><?= sorot($b['pengarang'], $q) ?></td></tr>
                            <tr><td><b>Penerbitan</b></td><td><?= e(trim(($b['kota_terbit'] ? $b['kota_terbit'] . ' : ' : '') . $b['penerbit'] . ', ' . $b['tahun_terbit'], ' ,:')) ?></td></tr>
                            <tr><td><b>Subjek</b></td><td><?= sorot((string)$b['subjek'], $q) ?></td></tr>
                            <tr><td><b>Ketersediaan</b></td><td><b><?= (int)$b['tersedia'] ?> dari <?= (int)$b['total_eks'] ?> eksemplar</b> tersedia</td></tr>
                            <tr><td><b>Nomor Panggil</b></td><td><span class="label label-default"><?= e($b['nomor_panggil'] ?: '-') ?></span></td></tr>
                            <tr><td><b>Aksi</b></td><td style="padding-top:6px"><a href="<?= e(url('detail.php?id=' . (int)$b['id_bibliografi'])) ?>" class="btn btn-primary btn-xs"><i class="fa fa-eye"></i> Detail Bibliografi</a></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$hasil): ?>
            <div class="alert alert-warning"><i class="fa fa-info-circle"></i> Koleksi tidak ditemukan. Silakan ubah kata kunci atau filter pencarian.</div>
        <?php endif; ?>
        <div class="text-center"><?= paginasi_html($pg) ?></div>
    </div>
</div>
<?php endif; ?>
<?php tampil_footer(); ?>
