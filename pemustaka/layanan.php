<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/views/layout.php';

$user = Auth::wajib(['Pemustaka']);
$anggota = Anggota::cariUser((int)$user['id_user']);
$err = [];

if (is_post()) {
    csrf_cek();
    $d = [
        'jenis'     => post('jenis'),
        'kategori'  => post('kategori'),
        'urgensi'   => post('urgensi'),
        'deskripsi' => post('deskripsi'),
        'kontak'    => post('kontak'),
    ];
    $err = PermintaanLayanan::validasi($d);
    if (!$err) {
        $jam = (int)Database::value(
            "SELECT COUNT(*) FROM tiket_insiden WHERE id_pelapor = ? AND tanggal_dibuat > (NOW() - INTERVAL 1 HOUR)",
            [(int)$user['id_user']]
        );
        if ($jam >= 5) {
            $err['deskripsi'] = 'Anda telah mengirim 5 pengajuan dalam 1 jam terakhir. Silakan coba lagi nanti.';
        }
    }
    if (!$err) {
        $t = PermintaanLayanan::buat((int)$user['id_user'], $d);
        flash('success', 'Pengajuan diterima. Nomor tiket Anda: ' . $t['nomor'] . ' (status: Baru). Simpan nomor ini untuk memantau penanganan.');
        redirect('pemustaka/tiket.php?id=' . $t['id']);
    }
}

tampil_header('Layanan Pemustaka', 'layanan_baru', ['ikon' => 'comment', 'crumb' => 'Layanan Pemustaka > Ajukan Keluhan']);
?>
<div class="box box-default" style="border-radius:8px;">
    <div class="box-body" style="padding:28px 34px;">
        <h2 style="font-size:20px;font-weight:700;color:#1e293b;margin:0 0 22px;padding-bottom:14px;border-bottom:1px solid #e2e8f0;">
            Formulir Pengajuan Keluhan / Permintaan Layanan
        </h2>
        <?php if ($err): ?><div class="alert alert-danger">Pengajuan belum dapat dikirim. Periksa isian yang bertanda merah.</div><?php endif; ?>
        <form method="post" action="<?= e(url('pemustaka/layanan.php')) ?>">
            <?= csrf_field() ?>
            <div class="inner-card">
                <div class="row">
                    <div class="col-sm-6" style="margin-bottom:18px;">
                        <label class="form-label">Nama Pemustaka</label>
                        <input type="text" class="form-control-custom" value="<?= e($user['nama']) ?>" readonly>
                    </div>
                    <div class="col-sm-6" style="margin-bottom:18px;">
                        <label class="form-label">Nomor Anggota</label>
                        <input type="text" class="form-control-custom" value="<?= e($anggota['nomor_anggota'] ?? '-') ?>" readonly>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4<?= kelas_galat($err, 'jenis') ?>" style="margin-bottom:18px;">
                        <label class="form-label" for="jenis">Jenis Pengajuan</label>
                        <select class="form-control-custom" id="jenis" name="jenis" required>
                            <?php foreach (Referensi::nama('jenis') as $j): ?>
                                <option value="<?= e($j) ?>"<?= selected(is_post() ? post('jenis') : (Referensi::nama('jenis')[0] ?? ''), $j) ?>><?= e($j) ?></option>
                            <?php endforeach; ?>
                        </select><?= galat($err, 'jenis') ?>
                    </div>
                    <div class="col-sm-4<?= kelas_galat($err, 'kategori') ?>" style="margin-bottom:18px;">
                        <label class="form-label" for="kategori">Kategori Keluhan</label>
                        <select class="form-control-custom" id="kategori" name="kategori" required>
                            <option value="" disabled<?= is_post() && post('kategori') !== '' ? '' : ' selected' ?>>Pilih kategori</option>
                            <?php foreach (Referensi::nama('kategori') as $k): ?>
                                <option value="<?= e($k) ?>"<?= selected(post('kategori'), $k) ?>><?= e($k) ?></option>
                            <?php endforeach; ?>
                        </select><?= galat($err, 'kategori') ?>
                    </div>
                    <div class="col-sm-4<?= kelas_galat($err, 'urgensi') ?>" style="margin-bottom:18px;">
                        <label class="form-label" for="urgensi">Tingkat Urgensi</label>
                        <select class="form-control-custom" id="urgensi" name="urgensi" required>
                            <option value="" disabled<?= is_post() && post('urgensi') !== '' ? '' : ' selected' ?>>Pilih urgensi</option>
                            <option value="Rendah"<?= selected(post('urgensi'), 'Rendah') ?>>Rendah (Pertanyaan / Usulan)</option>
                            <option value="Sedang"<?= selected(post('urgensi'), 'Sedang') ?>>Sedang (Kendala Fitur Tertentu)</option>
                            <option value="Tinggi"<?= selected(post('urgensi'), 'Tinggi') ?>>Tinggi (Sistem Tidak Dapat Diakses)</option>
                        </select><?= galat($err, 'urgensi') ?>
                    </div>
                </div>
                <div class="<?= kelas_galat($err, 'kontak') ?>" style="margin-bottom:18px;">
                    <label class="form-label" for="kontak">Kontak (email / telepon) <small class="text-muted">- opsional</small></label>
                    <input type="text" class="form-control-custom" id="kontak" name="kontak" maxlength="100"
                           value="<?= val('kontak', $anggota['telepon'] ?? ($user['email'] ?? '')) ?>" placeholder="Email atau nomor telepon yang dapat dihubungi">
                    <?= galat($err, 'kontak') ?>
                </div>
                <div class="<?= kelas_galat($err, 'deskripsi') ?>" style="margin-bottom:22px;">
                    <label class="form-label" for="deskripsi">Deskripsi Keluhan</label>
                    <textarea class="form-control-custom" id="deskripsi" name="deskripsi" maxlength="3000" required
                              placeholder="Jelaskan kendala yang Anda alami secara rinci..."><?= val('deskripsi') ?></textarea>
                    <?= galat($err, 'deskripsi') ?>
                </div>
                <div>
                    <button type="submit" class="btn-blue-pill" style="margin-right:12px;">Kirim Pengajuan</button>
                    <a href="<?= e(url('pemustaka/index.php')) ?>" class="btn-cancel-custom">Batal</a>
                </div>
            </div>
            <div class="notice-box">
                Setelah dikirim, sistem akan menerbitkan nomor tiket sebagai referensi pemantauan status penanganan.
            </div>
        </form>
    </div>
</div>
<?php tampil_footer(); ?>
