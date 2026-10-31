<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/views/layout.php';

Auth::wajib(['Admin']);
$aksi = get('aksi');
$id = (int)get('id');
$err = [];

if (is_post()) {
    csrf_cek();
    $a = post('aksi');
    if ($a === 'hapus') {
        try {
            $k = Koleksi::cari((int)post('id'));
            Koleksi::hapus((int)post('id'));
            Log::catat('HAPUS', 'koleksi', (int)post('id'), 'Hapus eksemplar: ' . ($k['barcode'] ?? ''));
            flash('success', 'Eksemplar dihapus.');
        } catch (AturanBisnis $e) {
            flash('danger', $e->getMessage());
        }
        redirect('admin/koleksi.php');
    }
    if ($a === 'simpan') {
        $idSimpan = (int)post('id') ?: null;
        $lama = $idSimpan ? Koleksi::cari($idSimpan) : null;
        $d = [];
        foreach (['id_bibliografi', 'barcode', 'lokasi', 'status', 'tanggal_pengadaan'] as $k) {
            $d[$k] = post($k);
        }
        if ($idSimpan && !$lama) {
            flash('danger', 'Data tidak ditemukan.');
            redirect('admin/koleksi.php');
        }
        $err = Koleksi::validasi($d, $idSimpan, $lama);
        if (!$err) {
            try {
                $idBaru = Koleksi::simpan($d, $idSimpan, $lama);
                Log::catat($idSimpan ? 'UBAH' : 'TAMBAH', 'koleksi', $idBaru, ($idSimpan ? 'Ubah' : 'Tambah') . ' eksemplar: ' . $d['barcode']);
                flash('success', 'Data koleksi disimpan.');
                redirect('admin/koleksi.php');
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $err['barcode'] = 'Nomor barcode sudah dipakai koleksi lain.';
                } else {
                    throw $e;
                }
            }
        }
        $aksi = 'form';
        $id = (int)post('id');
    }
}

if ($aksi === 'form') {
    $row = $id ? Koleksi::cari($id) : null;
    if ($id && !$row) {
        flash('danger', 'Data tidak ditemukan.');
        redirect('admin/koleksi.php');
    }
    $bibList = Database::all('SELECT id_bibliografi, judul, pengarang FROM bibliografi ORDER BY judul');
    $idBibPilih = is_post() ? (int)post('id_bibliografi') : (int)($row['id_bibliografi'] ?? get('bib'));
    $barcodeDefault = $row['barcode'] ?? ($idBibPilih ? Koleksi::barcodeBerikut($idBibPilih) : '');
    $dipinjam = $row && $row['status'] === 'Dipinjam';
    tampil_header($row ? 'Ubah Data Koleksi' : 'Tambah Data Koleksi', 'koleksi', ['ikon' => 'book', 'crumb' => 'Katalog > Data Koleksi']);
    ?>
    <div class="box box-primary">
        <form method="post" action="<?= e(url('admin/koleksi.php')) ?>">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="simpan"><input type="hidden" name="id" value="<?= $row ? (int)$row['id_koleksi'] : 0 ?>">
            <div class="box-body" style="max-width:720px">
                <?php if ($err): ?><div class="alert alert-danger">Data belum dapat disimpan. Periksa isian yang bertanda merah.</div><?php endif; ?>
                <div class="form-group<?= kelas_galat($err, 'id_bibliografi') ?>"><label>Bibliografi (Judul) <span class="text-danger">*</span></label>
                    <select class="form-control" name="id_bibliografi" required>
                        <option value="">&mdash; pilih judul &mdash;</option>
                        <?php foreach ($bibList as $b): ?><option value="<?= (int)$b['id_bibliografi'] ?>"<?= selected($idBibPilih, $b['id_bibliografi']) ?>><?= e($b['judul'] . ' — ' . $b['pengarang']) ?></option><?php endforeach; ?>
                    </select><?= galat($err, 'id_bibliografi') ?></div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group<?= kelas_galat($err, 'barcode') ?>"><label>Nomor Barcode <span class="text-danger">*</span></label>
                        <input class="form-control" name="barcode" maxlength="40" required value="<?= val('barcode', $barcodeDefault) ?>"><?= galat($err, 'barcode') ?></div></div>
                    <div class="col-md-6"><div class="form-group<?= kelas_galat($err, 'tanggal_pengadaan') ?>"><label>Tanggal Pengadaan</label>
                        <input class="form-control" type="date" name="tanggal_pengadaan" value="<?= val('tanggal_pengadaan', $row['tanggal_pengadaan'] ?? date('Y-m-d')) ?>"><?= galat($err, 'tanggal_pengadaan') ?></div></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group<?= kelas_galat($err, 'lokasi') ?>"><label>Lokasi Rak / Ruang <span class="text-danger">*</span></label>
                        <input class="form-control" name="lokasi" maxlength="100" required list="lokasi-list" value="<?= val('lokasi', $row['lokasi'] ?? 'Layanan Umum Lt. 2') ?>">
                        <datalist id="lokasi-list"><option>Layanan Umum Lt. 2</option><option>Layanan Anak</option><option>Layanan Referensi</option><option>Koleksi Deposit Sumut</option></datalist><?= galat($err, 'lokasi') ?></div></div>
                    <div class="col-md-6"><div class="form-group<?= kelas_galat($err, 'status') ?>"><label>Status</label>
                        <?php if ($dipinjam): ?>
                            <input class="form-control" value="Dipinjam (diatur otomatis oleh sirkulasi)" readonly><input type="hidden" name="status" value="Dipinjam">
                        <?php else: ?>
                            <select class="form-control" name="status"><?php $st = is_post() ? post('status') : ($row['status'] ?? 'Tersedia'); foreach (Koleksi::STATUS_MANUAL as $s): ?><option<?= selected($st, $s) ?>><?= e($s) ?></option><?php endforeach; ?></select>
                        <?php endif; ?><?= galat($err, 'status') ?></div></div>
                </div>
            </div>
            <div class="box-footer"><button class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button> <a class="btn btn-default" href="<?= e(url('admin/koleksi.php')) ?>">Batal</a></div>
        </form>
    </div>
    <?php
    tampil_footer();
    exit;
}

$q = get('q');
$status = get('status');
$total = Koleksi::hitung($q, $status);
$pg = paginasi($total, 12);
$daftar = Koleksi::daftar($q, $status, 0, $pg['per'], $pg['offset']);

tampil_header('Data Koleksi (Eksemplar)', 'koleksi', ['ikon' => 'book', 'crumb' => 'Katalog > Data Koleksi']);
?>
<div class="box box-info">
    <div class="box-header with-border">
        <a href="<?= e(url('admin/koleksi.php?aksi=form')) ?>" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Tambah Eksemplar</a>
        <form method="get" class="form-inline pull-right">
            <input type="text" name="q" value="<?= e($q) ?>" class="form-control input-sm" placeholder="Barcode / judul / lokasi">
            <select name="status" class="form-control input-sm"><option value="">Semua Status</option><?php foreach (Koleksi::STATUS as $s): ?><option<?= selected($status, $s) ?>><?= e($s) ?></option><?php endforeach; ?></select>
            <button class="btn btn-default btn-sm"><i class="fa fa-search"></i></button>
        </form>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Barcode</th><th>Judul</th><th>Lokasi</th><th>Status</th><th>Pengadaan</th><th style="width:150px">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($daftar as $k): ?>
                <tr><td><code><?= e($k['barcode']) ?></code></td><td><?= e($k['judul']) ?><br><small class="text-muted"><?= e($k['pengarang']) ?></small></td><td><?= e($k['lokasi']) ?></td>
                    <td><span class="label label-<?= $k['status'] === 'Tersedia' ? 'success' : ($k['status'] === 'Dipinjam' ? 'warning' : 'danger') ?>"><?= e($k['status']) ?></span></td>
                    <td><?= e(tgl($k['tanggal_pengadaan'])) ?></td>
                    <td>
                        <a class="btn btn-warning btn-xs" href="<?= e(url('admin/koleksi.php?aksi=form&id=' . (int)$k['id_koleksi'])) ?>"><i class="fa fa-pencil"></i> Ubah</a>
                        <form method="post" style="display:inline" data-konfirmasi="Hapus eksemplar <?= e($k['barcode']) ?>?">
                            <?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$k['id_koleksi'] ?>">
                            <button class="btn btn-danger btn-xs"><i class="fa fa-trash-o"></i> Hapus</button>
                        </form></td></tr>
            <?php endforeach; ?>
            <?php if (!$daftar): ?><tr><td colspan="6" class="text-center text-muted">Data tidak ditemukan.</td></tr><?php endif; ?>
            </tbody>
        </table>
        <div class="text-muted"><?= angka($total) ?> eksemplar</div>
        <?= paginasi_html($pg) ?>
    </div>
</div>
<?php tampil_footer();
