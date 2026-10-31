<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/views/layout.php';

$user = Auth::wajib(['Pemustaka']);
$anggota = Anggota::cariUser((int)$user['id_user']);
$tiket = PermintaanLayanan::ringkasStatus(['pelapor' => (int)$user['id_user']]);
$pinjaman = $anggota ? Transaksi::aktifAnggota((int)$anggota['id_anggota']) : [];
$populer = Bibliografi::populer(6);
$terbaru = Bibliografi::terbaru(6);

tampil_header('Dashboard Pemustaka', 'dashboard', [
    'ikon' => 'dashboard', 'crumb' => 'Dashboard',
    'sub' => 'Selamat datang, <strong>' . e($user['nama']) . '</strong>' . ($anggota ? ' &bull; No. Anggota: <strong>' . e($anggota['nomor_anggota']) . '</strong>' : ''),
]);
?>
<div class="row">
    <div class="col-lg-3 col-xs-6">
        <div class="small-box" style="background:#b72b2b;color:#fff;border-radius:4px;">
            <div class="inner"><h3><?= $tiket['total'] ?></h3><p>Tiket Saya</p></div>
            <div class="icon" style="opacity:.35"><i class="fa fa-ticket"></i></div>
            <a href="<?= e(url('pemustaka/riwayat.php')) ?>" class="small-box-footer" style="background:rgba(0,0,0,.15);color:#fff">Detail <i class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-xs-6">
        <div class="small-box" style="background:#e67e22;color:#fff;border-radius:4px;">
            <div class="inner"><h3><?= $tiket['baru'] + $tiket['diproses'] ?></h3><p>Sedang Ditangani</p></div>
            <div class="icon" style="opacity:.35"><i class="fa fa-wrench"></i></div>
            <a href="<?= e(url('pemustaka/riwayat.php?status=Diproses')) ?>" class="small-box-footer" style="background:rgba(0,0,0,.15);color:#fff">Detail <i class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-xs-6">
        <div class="small-box" style="background:#00965e;color:#fff;border-radius:4px;">
            <div class="inner"><h3><?= $tiket['selesai'] ?></h3><p>Tiket Selesai</p></div>
            <div class="icon" style="opacity:.35"><i class="fa fa-check-circle"></i></div>
            <a href="<?= e(url('pemustaka/riwayat.php?status=Selesai')) ?>" class="small-box-footer" style="background:rgba(0,0,0,.15);color:#fff">Detail <i class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-xs-6">
        <div class="small-box" style="background:#00b0e9;color:#fff;border-radius:4px;">
            <div class="inner"><h3><?= count($pinjaman) ?></h3><p>Buku Dipinjam</p></div>
            <div class="icon" style="opacity:.35"><i class="fa fa-book"></i></div>
            <a href="#pinjaman" class="small-box-footer" style="background:rgba(0,0,0,.15);color:#fff">Detail <i class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="box box-default">
    <div class="box-body" style="padding:15px 20px;">
        <a href="<?= e(url('pemustaka/layanan.php')) ?>" class="btn-blue-pill"><i class="fa fa-comment"></i> Ajukan Keluhan / Permintaan</a>
        <a href="<?= e(url('pemustaka/opac.php')) ?>" class="btn btn-default" style="border-radius:25px;padding:8px 22px;margin-left:8px"><i class="fa fa-search"></i> Cari Koleksi (OPAC)</a>
    </div>
</div>

<?php if ($anggota): ?>
<div class="box box-info" id="pinjaman">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-book"></i> Buku yang Sedang Saya Pinjam</h3></div>
    <div class="box-body table-responsive">
        <?php if (!$pinjaman): ?>
            <p class="text-muted" style="margin:0">Tidak ada peminjaman aktif.</p>
        <?php else: ?>
            <table class="table table-bordered table-striped" style="margin:0">
                <thead><tr><th>Judul</th><th>Barcode</th><th>Tgl Pinjam</th><th>Jatuh Tempo</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($pinjaman as $p): ?>
                    <tr>
                        <td><?= e($p['judul']) ?></td><td><code><?= e($p['barcode']) ?></code></td>
                        <td><?= e(tgl($p['tanggal_pinjam'])) ?></td><td><?= e(tgl($p['tanggal_jatuh_tempo'])) ?></td>
                        <td><?= (int)$p['hari_telat'] > 0
                            ? '<span class="label label-danger">Terlambat ' . (int)$p['hari_telat'] . ' hari</span>'
                            : '<span class="label label-success">Dipinjam</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php foreach ([['Koleksi Sering Dipinjam', 'fire text-orange', $populer], ['Koleksi Terbaru', 'book text-green', $terbaru]] as [$judul, $ikon, $daftar]): ?>
<div class="box box-default">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-<?= e($ikon) ?>"></i> <?= e(mb_strtoupper($judul)) ?></h3></div>
    <div class="box-body">
        <div class="row">
            <?php foreach ($daftar as $b): ?>
                <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6 book-card">
                    <a href="<?= e(url('detail.php?id=' . (int)$b['id_bibliografi'])) ?>">
                        <img class="book-cover" src="<?= e(Bibliografi::urlCover($b['cover'])) ?>" alt="<?= e($b['judul']) ?>" loading="lazy">
                        <p><?= e($b['judul']) ?></p>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php tampil_footer(); ?>
