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
            $ang = Anggota::cari((int)post('id'));
            Anggota::hapus((int)post('id'));
            Log::catat('HAPUS', 'anggota', (int)post('id'), 'Hapus anggota: ' . ($ang['nomor_anggota'] ?? ''));
            flash('success', 'Anggota beserta akun dan riwayatnya telah dihapus.');
        } catch (AturanBisnis $e) {
            flash('danger', $e->getMessage());
        }
        redirect('admin/anggota.php');
    }
    if ($a === 'simpan') {
        $idSimpan = (int)post('id') ?: null;
        $d = [];
        foreach (['nomor_anggota', 'nama', 'jenis_anggota', 'jenis_kelamin', 'alamat', 'telepon', 'email', 'tanggal_daftar', 'masa_berlaku', 'status'] as $k) {
            $d[$k] = post($k);
        }
        if ($idSimpan && !Anggota::cari($idSimpan)) {
            flash('danger', 'Data tidak ditemukan.');
            redirect('admin/anggota.php');
        }
        $err = Anggota::validasi($d, $idSimpan);
        if (!$err) {
            try {
                $idBaru = Anggota::simpan($d, $idSimpan);
                Log::catat($idSimpan ? 'UBAH' : 'TAMBAH', 'anggota', $idBaru, ($idSimpan ? 'Ubah' : 'Tambah') . ' anggota: ' . $d['nomor_anggota']);
                flash('success', 'Data anggota disimpan.');
                redirect('admin/anggota.php');
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $err['nomor_anggota'] = 'Nomor anggota sudah terdaftar.';
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
    $row = $id ? Anggota::cari($id) : null;
    if ($id && !$row) {
        flash('danger', 'Data tidak ditemukan.');
        redirect('admin/anggota.php');
    }
    tampil_header($row ? 'Ubah Data Anggota' : 'Pendaftaran Anggota Baru', 'anggota', ['ikon' => 'users', 'crumb' => 'Keanggotaan']);
    $v = fn(string $k, $def = '') => val($k, $row[$k] ?? $def);
    ?>
    <div class="box box-primary">
        <form method="post" action="<?= e(url('admin/anggota.php')) ?>">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="simpan"><input type="hidden" name="id" value="<?= $row ? (int)$row['id_anggota'] : 0 ?>">
            <div class="box-body" style="max-width:820px">
                <?php if ($err): ?><div class="alert alert-danger">Data belum dapat disimpan. Periksa isian yang bertanda merah.</div><?php endif; ?>
                <div class="row">
                    <div class="col-md-5"><div class="form-group<?= kelas_galat($err, 'nomor_anggota') ?>"><label>Nomor Anggota <span class="text-danger">*</span></label>
                        <input class="form-control" name="nomor_anggota" maxlength="30" required value="<?= $v('nomor_anggota', Anggota::nomorBerikut()) ?>"><?= galat($err, 'nomor_anggota') ?></div></div>
                    <div class="col-md-7"><div class="form-group<?= kelas_galat($err, 'nama') ?>"><label>Nama Lengkap <span class="text-danger">*</span></label>
                        <input class="form-control" name="nama" maxlength="100" required value="<?= $v('nama') ?>"><?= galat($err, 'nama') ?></div></div>
                </div>
                <div class="row">
                    <div class="col-md-4"><div class="form-group<?= kelas_galat($err, 'jenis_anggota') ?>"><label>Jenis Anggota</label>
                        <select class="form-control" name="jenis_anggota"><?php $x = is_post() ? post('jenis_anggota') : ($row['jenis_anggota'] ?? 'Umum'); foreach (Anggota::JENIS as $j): ?><option<?= selected($x, $j) ?>><?= e($j) ?></option><?php endforeach; ?></select><?= galat($err, 'jenis_anggota') ?></div></div>
                    <div class="col-md-4"><div class="form-group<?= kelas_galat($err, 'jenis_kelamin') ?>"><label>Jenis Kelamin</label>
                        <select class="form-control" name="jenis_kelamin"><option value="">-</option><?php $x = is_post() ? post('jenis_kelamin') : ($row['jenis_kelamin'] ?? ''); foreach (['Laki-laki', 'Perempuan'] as $j): ?><option<?= selected($x, $j) ?>><?= e($j) ?></option><?php endforeach; ?></select><?= galat($err, 'jenis_kelamin') ?></div></div>
                    <div class="col-md-4"><div class="form-group<?= kelas_galat($err, 'status') ?>"><label>Status</label>
                        <select class="form-control" name="status"><?php $x = is_post() ? post('status') : ($row['status'] ?? 'Aktif'); foreach (Anggota::STATUS as $j): ?><option<?= selected($x, $j) ?>><?= e($j) ?></option><?php endforeach; ?></select><?= galat($err, 'status') ?></div></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group<?= kelas_galat($err, 'tanggal_daftar') ?>"><label>Tanggal Daftar</label>
                        <input class="form-control" type="date" name="tanggal_daftar" required value="<?= $v('tanggal_daftar', date('Y-m-d')) ?>"><?= galat($err, 'tanggal_daftar') ?></div></div>
                    <div class="col-md-6"><div class="form-group<?= kelas_galat($err, 'masa_berlaku') ?>"><label>Masa Berlaku s.d.</label>
                        <input class="form-control" type="date" name="masa_berlaku" required value="<?= $v('masa_berlaku', date('Y-m-d', strtotime('+5 years'))) ?>"><?= galat($err, 'masa_berlaku') ?></div></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group<?= kelas_galat($err, 'telepon') ?>"><label>Telepon</label>
                        <input class="form-control" name="telepon" maxlength="20" value="<?= $v('telepon') ?>"><?= galat($err, 'telepon') ?></div></div>
                    <div class="col-md-6"><div class="form-group<?= kelas_galat($err, 'email') ?>"><label>Email</label>
                        <input class="form-control" type="email" name="email" maxlength="100" value="<?= $v('email') ?>"><?= galat($err, 'email') ?></div></div>
                </div>
                <div class="form-group<?= kelas_galat($err, 'alamat') ?>"><label>Alamat</label>
                    <input class="form-control" name="alamat" maxlength="255" value="<?= $v('alamat') ?>"><?= galat($err, 'alamat') ?></div>
                <?php if ($row): ?><p class="form-hint">Akun login: <?= $row['username'] ? '<b>' . e($row['username']) . '</b>' : 'belum ada (buat melalui menu Pengguna)' ?></p><?php endif; ?>
            </div>
            <div class="box-footer"><button class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button> <a class="btn btn-default" href="<?= e(url('admin/anggota.php')) ?>">Batal</a></div>
        </form>
    </div>
    <?php
    tampil_footer();
    exit;
}

$q = get('q');
$status = get('status');
$total = Anggota::hitung($q, $status);
$pg = paginasi($total, 10);
$daftar = Anggota::daftar($q, $status, $pg['per'], $pg['offset']);

tampil_header('Data Anggota', 'anggota', ['ikon' => 'users', 'crumb' => 'Keanggotaan']);
?>
<div class="box box-info">
    <div class="box-header with-border">
        <a href="<?= e(url('admin/anggota.php?aksi=form')) ?>" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Pendaftaran Anggota Baru</a>
        <form method="get" class="form-inline pull-right">
            <input type="text" name="q" value="<?= e($q) ?>" class="form-control input-sm" placeholder="Nama / nomor anggota">
            <select name="status" class="form-control input-sm"><option value="">Semua</option><?php foreach (Anggota::STATUS as $s): ?><option<?= selected($status, $s) ?>><?= e($s) ?></option><?php endforeach; ?></select>
            <button class="btn btn-default btn-sm"><i class="fa fa-search"></i></button>
        </form>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>No. Anggota</th><th>Nama</th><th>Jenis</th><th>Berlaku s.d.</th><th>Status</th><th>Akun</th><th>Pinjam</th><th style="width:150px">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($daftar as $a): $kedaluwarsa = $a['masa_berlaku'] < date('Y-m-d'); ?>
                <tr><td><?= e($a['nomor_anggota']) ?></td><td><?= e($a['nama']) ?></td><td><?= e($a['jenis_anggota']) ?></td>
                    <td><?= e(tgl($a['masa_berlaku'])) ?><?= $kedaluwarsa ? ' <span class="label label-danger">Habis</span>' : '' ?></td>
                    <td><span class="label label-<?= $a['status'] === 'Aktif' ? 'success' : 'default' ?>"><?= e($a['status']) ?></span></td>
                    <td><?= e($a['username'] ?? '-') ?></td><td><?= (int)$a['pinjaman_aktif'] ?></td>
                    <td>
                        <a class="btn btn-warning btn-xs" href="<?= e(url('admin/anggota.php?aksi=form&id=' . (int)$a['id_anggota'])) ?>"><i class="fa fa-pencil"></i> Ubah</a>
                        <form method="post" style="display:inline" data-konfirmasi="Hapus PERMANEN anggota <?= e($a['nama']) ?> beserta akun, riwayat peminjaman, dan tiket keluhannya? Tindakan ini tidak dapat dibatalkan.">
                            <?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$a['id_anggota'] ?>">
                            <button class="btn btn-danger btn-xs"><i class="fa fa-trash-o"></i> Hapus</button>
                        </form></td></tr>
            <?php endforeach; ?>
            <?php if (!$daftar): ?><tr><td colspan="8" class="text-center text-muted">Data tidak ditemukan.</td></tr><?php endif; ?>
            </tbody>
        </table>
        <div class="text-muted"><?= angka($total) ?> anggota</div>
        <?= paginasi_html($pg) ?>
    </div>
</div>
<?php tampil_footer();
