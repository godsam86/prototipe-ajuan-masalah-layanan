<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/views/layout.php';

$admin = Auth::wajib(['Admin']);
$idPetugas = (int)$admin['id_user'];

if (is_post()) {
    csrf_cek();
    $a = post('aksi');
    try {
        if ($a === 'pinjam') {
            $r = Transaksi::pinjam(post('nomor_anggota'), post('barcode'), $idPetugas);
            flash('success', 'Peminjaman berhasil: "' . $r['judul'] . '" oleh ' . $r['anggota'] . '. Jatuh tempo ' . tgl($r['tempo']) . '.');
        } elseif ($a === 'kembali') {
            $r = Transaksi::kembali(post('barcode'), $idPetugas);
            $msg = 'Pengembalian berhasil: "' . $r['judul'] . '" dari ' . $r['anggota'] . '.';
            if ($r['denda'] > 0) {
                $msg .= ' Terlambat ' . $r['telat'] . ' hari, denda ' . rupiah($r['denda']) . '.';
            }
            flash($r['denda'] > 0 ? 'warning' : 'success', $msg);
        } elseif ($a === 'perpanjang') {
            $r = Transaksi::perpanjang(post('barcode'), $idPetugas);
            flash('success', 'Perpanjangan berhasil: "' . $r['judul'] . '". Jatuh tempo baru ' . tgl($r['tempo']) . '.');
        } else {
            throw new AturanBisnis('Aksi tidak dikenal.');
        }
    } catch (AturanBisnis $e) {
        flash('danger', $e->getMessage());
    }
    redirect('admin/sirkulasi.php');
}

$q = get('q');
$aktif = Transaksi::aktif($q);
$totalRiw = Transaksi::hitungRiwayat($q);
$pg = paginasi($totalRiw, 8);
$riwayat = Transaksi::riwayat($q, $pg['per'], $pg['offset']);

tampil_header('Sirkulasi Pelayanan', 'sirkulasi', ['ikon' => 'refresh', 'crumb' => 'Sirkulasi',
    'sub' => 'Lama pinjam ' . Pengaturan::int('lama_pinjam_hari', 7) . ' hari &bull; maksimal ' . Pengaturan::int('maks_pinjam', 3) . ' koleksi &bull; denda ' . e(rupiah(Transaksi::dendaPerHari())) . '/hari']);
?>
<div class="row">
    <div class="col-md-6">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-arrow-circle-up"></i> Peminjaman</h3></div>
            <form method="post" action="<?= e(url('admin/sirkulasi.php')) ?>">
                <?= csrf_field() ?><input type="hidden" name="aksi" value="pinjam">
                <div class="box-body">
                    <div class="form-group"><label>Nomor Anggota</label><input class="form-control" name="nomor_anggota" maxlength="30" required placeholder="mis. SU-2024-00205"></div>
                    <div class="form-group"><label>Barcode Koleksi</label><input class="form-control" name="barcode" maxlength="40" required placeholder="mis. B00001-1"></div>
                </div>
                <div class="box-footer"><button class="btn btn-primary"><i class="fa fa-check"></i> Proses Peminjaman</button></div>
            </form>
        </div>
    </div>
    <div class="col-md-6">
        <div class="box box-success">
            <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-arrow-circle-down"></i> Pengembalian &amp; Perpanjangan</h3></div>
            <form method="post" action="<?= e(url('admin/sirkulasi.php')) ?>">
                <?= csrf_field() ?>
                <div class="box-body">
                    <div class="form-group"><label>Barcode Koleksi</label><input class="form-control" name="barcode" maxlength="40" required placeholder="mis. B00001-1"></div>
                    <p class="form-hint">Pilih tindakan: kembalikan koleksi (denda dihitung otomatis) atau perpanjang masa pinjam.</p>
                </div>
                <div class="box-footer">
                    <button class="btn btn-success" name="aksi" value="kembali"><i class="fa fa-check"></i> Proses Pengembalian</button>
                    <button class="btn btn-default" name="aksi" value="perpanjang"><i class="fa fa-calendar"></i> Perpanjang</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="get" class="form-inline" style="margin-bottom:12px">
    <input type="text" name="q" value="<?= e($q) ?>" class="form-control input-sm" placeholder="Cari anggota / barcode / judul" style="min-width:260px">
    <button class="btn btn-default btn-sm"><i class="fa fa-search"></i> Cari</button>
    <?php if ($q !== ''): ?><a class="btn btn-link btn-sm" href="<?= e(url('admin/sirkulasi.php')) ?>">Reset</a><?php endif; ?>
</form>

<div class="box box-warning">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-book"></i> Koleksi Sedang Dipinjam (<?= count($aktif) ?>)</h3></div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Anggota</th><th>Judul</th><th>Barcode</th><th>Pinjam</th><th>Jatuh Tempo</th><th>Perpanjang</th><th>Status</th><th style="width:170px">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($aktif as $t): ?>
                <tr><td><?= e($t['nama_anggota']) ?><br><small class="text-muted"><?= e($t['nomor_anggota']) ?></small></td><td><?= e($t['judul']) ?></td><td><code><?= e($t['barcode']) ?></code></td>
                    <td><?= e(tgl($t['tanggal_pinjam'])) ?></td><td><?= e(tgl($t['tanggal_jatuh_tempo'])) ?></td><td><?= (int)$t['jumlah_perpanjang'] ?>x</td>
                    <td><?php if ((int)$t['hari_telat'] > 0): ?><span class="label label-danger">Terlambat <?= (int)$t['hari_telat'] ?> hari</span><br><small>Denda <?= e(rupiah((int)$t['hari_telat'] * Transaksi::dendaPerHari())) ?></small>
                        <?php else: ?><span class="label label-success">Dipinjam</span><?php endif; ?></td>
                    <td>
                        <?php foreach ([['kembali', 'success', 'check', 'Kembali'], ['perpanjang', 'default', 'calendar-plus-o', 'Perpanjang']] as [$ak, $cl, $ic, $tx]): ?>
                            <form method="post" style="display:inline" action="<?= e(url('admin/sirkulasi.php')) ?>">
                                <?= csrf_field() ?><input type="hidden" name="aksi" value="<?= $ak ?>"><input type="hidden" name="barcode" value="<?= e($t['barcode']) ?>">
                                <button class="btn btn-<?= $cl ?> btn-xs"><i class="fa fa-<?= $ic ?>"></i> <?= $tx ?></button>
                            </form>
                        <?php endforeach; ?>
                    </td></tr>
            <?php endforeach; ?>
            <?php if (!$aktif): ?><tr><td colspan="8" class="text-center text-muted">Tidak ada peminjaman aktif.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="box box-default">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-clock-o"></i> Riwayat Pengembalian</h3></div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Anggota</th><th>Judul</th><th>Barcode</th><th>Pinjam</th><th>Kembali</th><th>Denda</th></tr></thead>
            <tbody>
            <?php foreach ($riwayat as $t): ?>
                <tr><td><?= e($t['nama_anggota']) ?></td><td><?= e($t['judul']) ?></td><td><code><?= e($t['barcode']) ?></code></td>
                    <td><?= e(tgl($t['tanggal_pinjam'])) ?></td><td><?= e(tgl($t['tanggal_kembali'])) ?></td><td><?= (int)$t['denda'] > 0 ? e(rupiah($t['denda'])) : '-' ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$riwayat): ?><tr><td colspan="6" class="text-center text-muted">Belum ada riwayat.</td></tr><?php endif; ?>
            </tbody>
        </table>
        <?= paginasi_html($pg) ?>
    </div>
</div>
<?php tampil_footer();
