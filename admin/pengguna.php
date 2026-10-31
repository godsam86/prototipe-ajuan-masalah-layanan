<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/views/layout.php';

$admin = Auth::wajib(['Admin']);
$aksi = get('aksi');
$id = (int)get('id');
$err = [];

if (is_post()) {
    csrf_cek();
    $a = post('aksi');
    if ($a === 'status') {
        try {
            $baru = post('status') === 'Aktif' ? 'Aktif' : 'Nonaktif';
            Pengguna::ubahStatus((int)post('id'), $baru);
            Log::catat('STATUS', 'pengguna', (int)post('id'), 'Status akun diubah menjadi ' . $baru);
            flash('success', 'Status akun diubah menjadi ' . $baru . '.');
        } catch (AturanBisnis $e) {
            flash('danger', $e->getMessage());
        }
        redirect('admin/pengguna.php');
    }
    if ($a === 'hapus') {
        try {
            $tgt = Pengguna::cari((int)post('id'));
            Pengguna::hapus((int)post('id'), (int)$admin['id_user']);
            Log::catat('HAPUS', 'pengguna', (int)post('id'), 'Hapus akun: ' . ($tgt['username'] ?? ''));
            flash('success', 'Akun ' . ($tgt['username'] ?? '') . ' telah dihapus.');
        } catch (AturanBisnis $e) {
            flash('danger', $e->getMessage());
        }
        redirect('admin/pengguna.php');
    }
    if ($a === 'simpan') {
        $idSimpan = (int)post('id') ?: null;
        $d = ['nama' => post('nama'), 'username' => post('username'), 'email' => post('email'), 'peran' => post('peran'),
              'status' => post('status'), 'password' => $_POST['password'] ?? '', 'password2' => $_POST['password2'] ?? '',
              'id_anggota' => post('id_anggota')];
        if ($idSimpan && !Pengguna::cari($idSimpan)) {
            flash('danger', 'Pengguna tidak ditemukan.');
            redirect('admin/pengguna.php');
        }
        $err = Pengguna::validasi($d, $idSimpan);
        if (!$err) {
            try {
                $idBaru = Pengguna::simpan($d, $idSimpan);
                Log::catat($idSimpan ? 'UBAH' : 'TAMBAH', 'pengguna', $idBaru, ($idSimpan ? 'Ubah' : 'Tambah') . ' akun: ' . $d['username']);
                flash('success', 'Akun pengguna disimpan.');
                redirect('admin/pengguna.php');
            } catch (AturanBisnis $e) {
                $err['id_anggota'] = $e->getMessage();
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $err['username'] = 'Username atau email sudah dipakai akun lain.';
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
    $row = $id ? Pengguna::cari($id) : null;
    if ($id && !$row) {
        flash('danger', 'Pengguna tidak ditemukan.');
        redirect('admin/pengguna.php');
    }
    $anggotaList = Pengguna::anggotaTanpaAkun($row ? (int)$row['id_user'] : null);
    $peranNow = is_post() ? post('peran') : ($row['peran'] ?? 'Pemustaka');
    $anggotaNow = is_post() ? post('id_anggota') : ($row['id_anggota'] ?? '');
    $diriSendiri = $row && (int)$row['id_user'] === (int)$admin['id_user'];
    tampil_header($row ? 'Ubah Pengguna' : 'Tambah Pengguna', 'pengguna', ['ikon' => 'user', 'crumb' => 'Pengguna']);
    ?>
    <div class="box box-primary">
        <form method="post" action="<?= e(url('admin/pengguna.php')) ?>" autocomplete="off">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="simpan"><input type="hidden" name="id" value="<?= $row ? (int)$row['id_user'] : 0 ?>">
            <div class="box-body" style="max-width:720px">
                <?php if ($err): ?><div class="alert alert-danger">Data belum dapat disimpan. Periksa isian yang bertanda merah.</div><?php endif; ?>
                <div class="form-group<?= kelas_galat($err, 'nama') ?>"><label>Nama Lengkap <span class="text-danger">*</span></label>
                    <input class="form-control" name="nama" maxlength="100" required value="<?= val('nama', $row['nama'] ?? '') ?>"><?= galat($err, 'nama') ?></div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group<?= kelas_galat($err, 'username') ?>"><label>Username <span class="text-danger">*</span></label>
                        <input class="form-control" name="username" maxlength="50" required value="<?= val('username', $row['username'] ?? '') ?>"><?= galat($err, 'username') ?></div></div>
                    <div class="col-md-6"><div class="form-group<?= kelas_galat($err, 'email') ?>"><label>Email</label>
                        <input class="form-control" type="email" name="email" maxlength="100" value="<?= val('email', $row['email'] ?? '') ?>"><?= galat($err, 'email') ?></div></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group<?= kelas_galat($err, 'peran') ?>"><label>Peran</label>
                        <select class="form-control" name="peran" id="peran"<?= $diriSendiri ? ' style="pointer-events:none;background:#eee"' : '' ?>>
                            <?php foreach (Pengguna::PERAN as $p): ?><option<?= selected($peranNow, $p) ?>><?= e($p) ?></option><?php endforeach; ?></select><?= galat($err, 'peran') ?></div></div>
                    <div class="col-md-6"><div class="form-group<?= kelas_galat($err, 'status') ?>"><label>Status</label>
                        <select class="form-control" name="status"<?= $diriSendiri ? ' style="pointer-events:none;background:#eee"' : '' ?>><?php $x = is_post() ? post('status') : ($row['status'] ?? 'Aktif'); foreach (Pengguna::STATUS as $s): ?><option<?= selected($x, $s) ?>><?= e($s) ?></option><?php endforeach; ?></select><?= galat($err, 'status') ?></div></div>
                </div>
                <div class="form-group<?= kelas_galat($err, 'id_anggota') ?>" id="grp-anggota"><label>Tautkan ke Data Anggota <span class="text-danger">*</span> <small class="text-muted">(khusus peran Pemustaka)</small></label>
                    <select class="form-control" name="id_anggota"><option value="">&mdash; pilih anggota &mdash;</option>
                        <?php foreach ($anggotaList as $a): ?><option value="<?= (int)$a['id_anggota'] ?>"<?= selected($anggotaNow, $a['id_anggota']) ?>><?= e($a['nomor_anggota'] . ' — ' . $a['nama']) ?></option><?php endforeach; ?>
                    </select><?= galat($err, 'id_anggota') ?></div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group<?= kelas_galat($err, 'password') ?>"><label>Password <?= $row ? '<small class="text-muted">(kosongkan bila tidak diganti)</small>' : '<span class="text-danger">*</span>' ?></label>
                        <input class="form-control" type="password" name="password" maxlength="200" autocomplete="new-password"<?= $row ? '' : ' required' ?>><?= galat($err, 'password') ?></div></div>
                    <div class="col-md-6"><div class="form-group<?= kelas_galat($err, 'password2') ?>"><label>Ulangi Password</label>
                        <input class="form-control" type="password" name="password2" maxlength="200" autocomplete="new-password"><?= galat($err, 'password2') ?></div></div>
                </div>
                <p class="form-hint">Password disimpan dalam bentuk hash (bcrypt), minimal 8 karakter.</p>
            </div>
            <div class="box-footer"><button class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button> <a class="btn btn-default" href="<?= e(url('admin/pengguna.php')) ?>">Batal</a></div>
        </form>
    </div>
    <?php
    tampil_footer([], <<<'JS'
function togglePeran() { $('#grp-anggota').toggle($('#peran').val() === 'Pemustaka'); }
$('#peran').on('change', togglePeran); togglePeran();
JS);
    exit;
}

$q = get('q');
$peran = get('peran');
$status = get('status');
$total = Pengguna::hitung($q, $peran, $status);
$pg = paginasi($total, 12);
$daftar = Pengguna::daftar($q, $peran, $status, $pg['per'], $pg['offset']);

tampil_header('Data Pengguna', 'pengguna', ['ikon' => 'user', 'crumb' => 'Pengguna']);
?>
<div class="box box-info">
    <div class="box-header with-border">
        <a href="<?= e(url('admin/pengguna.php?aksi=form')) ?>" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Tambah Pengguna</a>
        <form method="get" class="form-inline pull-right">
            <input type="text" name="q" value="<?= e($q) ?>" class="form-control input-sm" placeholder="Nama / username / email">
            <select name="peran" class="form-control input-sm"><option value="">Semua Peran</option><?php foreach (Pengguna::PERAN as $p): ?><option<?= selected($peran, $p) ?>><?= e($p) ?></option><?php endforeach; ?></select>
            <select name="status" class="form-control input-sm"><option value="">Semua Status</option><?php foreach (Pengguna::STATUS as $s): ?><option<?= selected($status, $s) ?>><?= e($s) ?></option><?php endforeach; ?></select>
            <button class="btn btn-default btn-sm"><i class="fa fa-search"></i></button>
        </form>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Nama</th><th>Username</th><th>Peran</th><th>Anggota</th><th>Status</th><th>Login Terakhir</th><th style="width:230px">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($daftar as $u): $diri = (int)$u['id_user'] === (int)$admin['id_user']; ?>
                <tr><td><?= e($u['nama']) ?></td><td><?= e($u['username']) ?></td><td><?= e($u['peran']) ?></td><td><?= e($u['nomor_anggota'] ?? '-') ?></td>
                    <td><span class="label label-<?= $u['status'] === 'Aktif' ? 'success' : 'default' ?>"><?= e($u['status']) ?></span></td>
                    <td><?= e(tgl_waktu($u['last_login'])) ?></td>
                    <td>
                        <a class="btn btn-warning btn-xs" href="<?= e(url('admin/pengguna.php?aksi=form&id=' . (int)$u['id_user'])) ?>"><i class="fa fa-pencil"></i> Ubah</a>
                        <?php if (!$diri): $baru = $u['status'] === 'Aktif' ? 'Nonaktif' : 'Aktif'; ?>
                        <form method="post" style="display:inline" data-konfirmasi="<?= $baru === 'Nonaktif' ? 'Nonaktifkan' : 'Aktifkan' ?> akun <?= e($u['username']) ?>?">
                            <?= csrf_field() ?><input type="hidden" name="aksi" value="status"><input type="hidden" name="id" value="<?= (int)$u['id_user'] ?>"><input type="hidden" name="status" value="<?= $baru ?>">
                            <button class="btn btn-<?= $baru === 'Nonaktif' ? 'danger' : 'success' ?> btn-xs"><i class="fa fa-<?= $baru === 'Nonaktif' ? 'ban' : 'check' ?>"></i> <?= $baru === 'Nonaktif' ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                        </form>
                        <form method="post" style="display:inline" data-konfirmasi="Hapus PERMANEN akun <?= e($u['username']) ?><?= $u['peran'] === 'Pemustaka' ? ' beserta data anggota, riwayat peminjaman, dan tiket keluhannya' : '' ?>? Tindakan ini tidak dapat dibatalkan.">
                            <?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$u['id_user'] ?>">
                            <button class="btn btn-danger btn-xs"><i class="fa fa-trash-o"></i> Hapus</button>
                        </form>
                        <?php endif; ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$daftar): ?><tr><td colspan="7" class="text-center text-muted">Data tidak ditemukan.</td></tr><?php endif; ?>
            </tbody>
        </table>
        <div class="text-muted"><?= angka($total) ?> pengguna</div>
        <?= paginasi_html($pg) ?>
    </div>
</div>
<?php tampil_footer();
