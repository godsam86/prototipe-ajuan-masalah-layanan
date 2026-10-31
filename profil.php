<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require APP_ROOT . '/app/views/layout.php';

$user = Auth::wajib();
$errProfil = [];
$errPass = [];

if (is_post()) {
    csrf_cek();
    if (post('aksi') === 'profil') {
        $errProfil = Pengguna::ubahProfil((int)$user['id_user'], post('nama'), post('email'));
        if (!$errProfil) {
            Log::catat('UBAH', 'profil', (int)$user['id_user'], 'Profil diperbarui');
            flash('success', 'Profil berhasil diperbarui.');
            redirect('profil.php');
        }
    } elseif (post('aksi') === 'password') {
        $errPass = Pengguna::ubahPassword((int)$user['id_user'], $_POST['lama'] ?? '', $_POST['baru'] ?? '', $_POST['baru2'] ?? '');
        if (!$errPass) {
            Log::catat('UBAH_PASSWORD', 'profil', (int)$user['id_user'], 'Password diganti');
            flash('success', 'Password berhasil diganti.');
            redirect('profil.php');
        }
    }
}
$p = Pengguna::cari((int)$user['id_user']);

tampil_header('Profil Saya', '', ['ikon' => 'user', 'crumb' => 'Profil']);
?>
<div class="row">
    <div class="col-md-6">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-user"></i> Data Akun</h3></div>
            <form method="post" action="<?= e(url('profil.php')) ?>">
                <?= csrf_field() ?><input type="hidden" name="aksi" value="profil">
                <div class="box-body">
                    <div class="form-group"><label>Username</label><input class="form-control" value="<?= e($p['username']) ?>" readonly></div>
                    <div class="form-group"><label>Peran</label><input class="form-control" value="<?= e($p['peran']) ?>" readonly></div>
                    <div class="form-group <?= isset($errProfil['nama']) ? 'has-error' : '' ?>">
                        <label>Nama Lengkap</label>
                        <input class="form-control" name="nama" maxlength="100" value="<?= val('nama', $p['nama']) ?>" required>
                        <?php if (isset($errProfil['nama'])): ?><span class="help-block"><?= e($errProfil['nama']) ?></span><?php endif; ?>
                    </div>
                    <div class="form-group <?= isset($errProfil['email']) ? 'has-error' : '' ?>">
                        <label>Email</label>
                        <input class="form-control" type="email" name="email" maxlength="100" value="<?= val('email', $p['email'] ?? '') ?>">
                        <?php if (isset($errProfil['email'])): ?><span class="help-block"><?= e($errProfil['email']) ?></span><?php endif; ?>
                    </div>
                </div>
                <div class="box-footer"><button class="btn btn-primary"><i class="fa fa-save"></i> Simpan Profil</button></div>
            </form>
        </div>
    </div>
    <div class="col-md-6">
        <div class="box box-warning">
            <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-lock"></i> Ganti Password</h3></div>
            <form method="post" action="<?= e(url('profil.php')) ?>" autocomplete="off">
                <?= csrf_field() ?><input type="hidden" name="aksi" value="password">
                <div class="box-body">
                    <?php foreach ([['lama', 'Password Lama'], ['baru', 'Password Baru (min. 8 karakter)'], ['baru2', 'Ulangi Password Baru']] as [$k, $lbl]): ?>
                        <div class="form-group <?= isset($errPass[$k]) ? 'has-error' : '' ?>">
                            <label><?= e($lbl) ?></label>
                            <input class="form-control" type="password" name="<?= e($k) ?>" maxlength="200" required>
                            <?php if (isset($errPass[$k])): ?><span class="help-block"><?= e($errPass[$k]) ?></span><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="box-footer"><button class="btn btn-warning"><i class="fa fa-key"></i> Ganti Password</button></div>
            </form>
        </div>
    </div>
</div>
<?php tampil_footer(); ?>
