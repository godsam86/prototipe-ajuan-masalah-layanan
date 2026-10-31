<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/views/layout.php';

$user = Auth::wajib(['Pemustaka']);
$id = (int)get('id');
$t = PermintaanLayanan::cari($id);
if (!$t || (int)$t['id_pelapor'] !== (int)$user['id_user']) {
    flash('danger', 'Tiket tidak ditemukan.');
    redirect('pemustaka/riwayat.php');
}

if (is_post()) {
    csrf_cek();
    try {
        PermintaanLayanan::beriRating($id, (int)$user['id_user'], (int)post('rating'), post('ulasan'));
        flash('success', 'Terima kasih, penilaian Anda telah disimpan.');
    } catch (AturanBisnis $e) {
        flash('danger', $e->getMessage());
    }
    redirect('pemustaka/tiket.php?id=' . $id);
}
$riwayat = PermintaanLayanan::riwayat($id);

tampil_header('Detail Tiket ' . $t['nomor_tiket'], 'layanan_riwayat', ['ikon' => 'comment', 'crumb' => 'Layanan Pemustaka > Detail Tiket']);
?>
<div class="row">
    <div class="col-md-7">
        <div class="detail-card" style="margin-bottom:20px;">
            <h3 class="detail-heading">Tiket <?= e($t['nomor_tiket']) ?> &nbsp; <?= badge_status($t['status']) ?></h3>
            <table class="table table-condensed" style="margin:0;background:transparent">
                <tr><td width="32%"><b>Jenis</b></td><td><?= e($t['jenis']) ?></td></tr>
                <tr><td><b>Kategori</b></td><td><?= e($t['kategori']) ?></td></tr>
                <tr><td><b>Urgensi</b></td><td><?= badge_urgensi($t['urgensi']) ?></td></tr>
                <tr><td><b>Dikirim</b></td><td><?= e(tgl_waktu($t['tanggal_dibuat'])) ?></td></tr>
                <tr><td><b>Petugas</b></td><td><?= e($t['nama_admin'] ?? 'Belum ditugaskan') ?></td></tr>
                <?php if ($t['tanggal_selesai']): ?>
                    <tr><td><b>Selesai</b></td><td><?= e(tgl_waktu($t['tanggal_selesai'])) ?> (<?= e(durasi_jam($t['durasi_jam'])) ?>)</td></tr>
                <?php endif; ?>
                <tr><td valign="top"><b>Deskripsi</b></td><td class="pre-wrap"><?= e($t['deskripsi']) ?></td></tr>
                <?php if ($t['status'] === 'Selesai' && $t['penanganan']): ?>
                    <tr><td valign="top"><b>Hasil Penanganan</b></td><td class="pre-wrap"><?= e($t['penanganan']) ?></td></tr>
                <?php endif; ?>
            </table>
        </div>

        <?php if ($t['status'] === 'Selesai'): ?>
            <div class="content-box">
                <h3 class="box-title-x"><i class="fa fa-star text-yellow"></i> Penilaian Layanan</h3>
                <?php if ($t['rating'] !== null): ?>
                    <p style="margin:0">Penilaian Anda: <strong><?= (int)$t['rating'] ?> / 5</strong>
                        <?= $t['ulasan'] ? '&mdash; &ldquo;' . e($t['ulasan']) . '&rdquo;' : '' ?></p>
                <?php else: ?>
                    <form method="post" action="<?= e(url('pemustaka/tiket.php?id=' . $id)) ?>">
                        <?= csrf_field() ?>
                        <p>Seberapa puas Anda dengan penanganan tiket ini?</p>
                        <div class="rating-input" style="margin-bottom:12px">
                            <?php foreach ([5 => 'Sangat puas', 4 => 'Puas', 3 => 'Cukup', 2 => 'Kurang', 1 => 'Tidak puas'] as $n => $lbl): ?>
                                <label><input type="radio" name="rating" value="<?= $n ?>" required> <?= $n ?> - <?= e($lbl) ?></label>
                            <?php endforeach; ?>
                        </div>
                        <input type="text" name="ulasan" class="form-control" maxlength="255" placeholder="Komentar (opsional)" style="margin-bottom:12px">
                        <button class="btn-teal-pill">Kirim Penilaian</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-md-5">
        <div class="content-box">
            <h3 class="box-title-x"><i class="fa fa-clock-o"></i> Perkembangan Penanganan</h3>
            <ul class="timeline-lite">
                <?php foreach ($riwayat as $r): ?>
                    <li>
                        <div><?= $r['status_lama'] && $r['status_lama'] !== $r['status_baru'] ? e($r['status_lama']) . ' &rarr; ' : '' ?><?= badge_status($r['status_baru']) ?></div>
                        <div class="when"><?= e(tgl_waktu($r['created_at'])) ?><?= $r['nama_user'] ? ' &bull; ' . e($r['peran'] === 'Admin' ? 'Petugas ' . $r['nama_user'] : $r['nama_user']) : '' ?></div>
                        <?php if ($r['catatan']): ?><div class="note"><?= e($r['catatan']) ?></div><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <a href="<?= e(url('pemustaka/riwayat.php')) ?>" class="btn-cancel-custom"><i class="fa fa-arrow-left"></i> Kembali</a>
    </div>
</div>
<?php tampil_footer(); ?>
