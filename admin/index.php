<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/views/layout.php';

Auth::wajib(['Admin']);
$judul = (int)Database::value('SELECT COUNT(*) FROM bibliografi');
$eks = (int)Database::value('SELECT COUNT(*) FROM koleksi');
$ang = (int)Database::value("SELECT COUNT(*) FROM anggota WHERE status = 'Aktif'");
$pinjam = (int)Database::value("SELECT COUNT(*) FROM transaksi WHERE status = 'Dipinjam'");
$ring = PermintaanLayanan::ringkasStatus();
$terbaru = PermintaanLayanan::daftar(['urut_baru' => true], 5, 0);
$trx = Transaksi::aktif();
$trx = array_slice($trx, 0, 6);
$bulanData = Laporan::sirkulasi(date('Y-m-01', strtotime('-5 months')), date('Y-m-d', strtotime('first day of next month')));
$lb = array_map(fn($r) => Laporan::namaBulan($r['bulan']), $bulanData['per_bulan']);
$js = 'new Chart(document.getElementById("grafik"),{type:"bar",data:{labels:' . json_encode($lb) . ',datasets:['
    . '{label:"Peminjaman",data:' . json_encode(array_column($bulanData['per_bulan'], 'pinjam')) . ',backgroundColor:"#3b6aa6"},'
    . '{label:"Pengembalian",data:' . json_encode(array_column($bulanData['per_bulan'], 'kembali')) . ',backgroundColor:"#e67e22"}]},'
    . 'options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});';

tampil_header('Dashboard Petugas', 'dashboard', ['ikon' => 'dashboard', 'crumb' => 'Dashboard', 'sub' => 'Jenis Perpustakaan : <strong>Perpustakaan Umum</strong>']);
?>
<div class="row">
    <?php foreach ([['#b72b2b', $judul, 'Judul', 'book', 'admin/bibliografi.php'], ['#e67e22', $eks, 'Eksemplar', 'book', 'admin/koleksi.php'],
                    ['#00b0e9', $ang, 'Anggota Aktif', 'users', 'admin/anggota.php'], ['#00965e', $pinjam, 'Sedang Dipinjam', 'refresh', 'admin/sirkulasi.php']] as [$c, $n, $l, $ic, $href]): ?>
        <div class="col-lg-3 col-xs-6">
            <div class="small-box" style="background:<?= $c ?>;color:#fff;border-radius:4px">
                <div class="inner" style="padding:15px 20px"><h3 style="font-size:38px;font-weight:bold;margin:0 0 5px"><?= angka($n) ?></h3><p style="font-size:15px;margin:0"><?= e($l) ?></p></div>
                <div class="icon" style="opacity:.35"><i class="fa fa-<?= $ic ?>"></i></div>
                <a href="<?= e(url($href)) ?>" class="small-box-footer" style="background:rgba(0,0,0,.15);color:#fff">Detail <i class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<div class="row">
    <div class="col-md-7">
        <div class="box box-default"><div class="box-header with-border" style="text-align:center"><h3 class="box-title" style="font-size:17px;font-weight:700">Sirkulasi 6 Bulan Terakhir</h3></div>
            <div class="box-body"><div style="position:relative;height:260px"><canvas id="grafik"></canvas></div></div></div>
    </div>
    <div class="col-md-5">
        <div class="box box-warning">
            <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-ticket"></i> Tiket Layanan</h3>
                <a href="<?= e(url('admin/tiket.php')) ?>" class="pull-right">Semua &raquo;</a></div>
            <div class="box-body">
                <p><?= badge_status('Baru') ?> <b><?= $ring['baru'] ?></b> &nbsp; <?= badge_status('Diproses') ?> <b><?= $ring['diproses'] ?></b> &nbsp; <?= badge_status('Selesai') ?> <b><?= $ring['selesai'] ?></b></p>
                <table class="table table-condensed" style="margin:0">
                    <?php foreach ($terbaru as $t): ?>
                        <tr><td><a href="<?= e(url('admin/tiket_detail.php?id=' . (int)$t['id_tiket'])) ?>"><b><?= e($t['nomor_tiket']) ?></b></a></td>
                            <td><?= e($t['kategori']) ?></td><td><?= badge_status($t['status']) ?></td></tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="box box-info">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-clock-o"></i> Peminjaman Aktif (terdekat jatuh tempo)</h3></div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>No. Anggota</th><th>Nama</th><th>Judul</th><th>Barcode</th><th>Tgl Pinjam</th><th>Jatuh Tempo</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($trx as $t): ?>
                <tr><td><?= e($t['nomor_anggota']) ?></td><td><?= e($t['nama_anggota']) ?></td><td><?= e($t['judul']) ?></td><td><code><?= e($t['barcode']) ?></code></td>
                    <td><?= e(tgl($t['tanggal_pinjam'])) ?></td><td><?= e(tgl($t['tanggal_jatuh_tempo'])) ?></td>
                    <td><?= (int)$t['hari_telat'] > 0 ? '<span class="label label-danger">Terlambat ' . (int)$t['hari_telat'] . ' hari</span>' : '<span class="label label-success">Dipinjam</span>' ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$trx): ?><tr><td colspan="7" class="text-center text-muted">Tidak ada peminjaman aktif.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php tampil_footer(['chart.min.js'], $js);
