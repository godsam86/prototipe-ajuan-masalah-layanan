<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/views/layout.php';

$admin = Auth::wajib(['Admin']);
$idAdmin = (int)$admin['id_user'];
$id = (int)get('id');
if (!PermintaanLayanan::cari($id)) {
    flash('danger', 'Tiket tidak ditemukan.');
    redirect('admin/tiket.php');
}
$buka = '';

if (is_post()) {
    csrf_cek();
    $aksi = post('aksi');
    try {
        switch ($aksi) {
            case 'proses':
                PermintaanLayanan::proses($id, $idAdmin);
                flash('success', 'Tiket diambil dan berstatus Diproses.');
                break;
            case 'standar':
                $buka = 'standar';
                PermintaanLayanan::selesaikanStandar($id, $idAdmin, post('catatan'));
                flash('success', 'Solusi standar diterapkan. Tiket diselesaikan.');
                break;
            case 'eskalasi':
                $buka = 'eskalasi';
                Masalah::eskalasi($id, $idAdmin, post('akar_masalah'), post('tindakan_perbaikan'), post('langkah_pencegahan'));
                flash('success', 'Tiket dieskalasi ke manajemen masalah.');
                break;
            case 'ubah_masalah':
                $buka = 'ubah';
                $m = Masalah::cariTiket($id);
                if (!$m) {
                    throw new AturanBisnis('Data masalah tidak ditemukan.');
                }
                Masalah::perbarui((int)$m['id_masalah'], $idAdmin, post('akar_masalah'), post('tindakan_perbaikan'), post('langkah_pencegahan'));
                flash('success', 'Analisis masalah diperbarui.');
                break;
            case 'selesai_masalah':
                $buka = 'selesai';
                $m = Masalah::cariTiket($id);
                if (!$m) {
                    throw new AturanBisnis('Data masalah tidak ditemukan.');
                }
                Masalah::selesaikan((int)$m['id_masalah'], $idAdmin, post('judul_solusi'), post('deskripsi_solusi'));
                flash('success', 'Solusi didokumentasikan ke basis pengetahuan dan tiket diselesaikan.');
                break;
            default:
                throw new AturanBisnis('Aksi tidak dikenal.');
        }
        redirect('admin/tiket_detail.php?id=' . $id);
    } catch (AturanBisnis $e) {
        flash('danger', $e->getMessage());
    }
}

$t = PermintaanLayanan::cari($id);
$masalah = Masalah::cariTiket($id);
$riwayat = PermintaanLayanan::riwayat($id);
$kb = Masalah::basisPengetahuan();
$selesai = $t['status'] === 'Selesai';

tampil_header('Detail Penanganan ' . $t['nomor_tiket'], 'tiket', ['ikon' => 'ticket', 'crumb' => 'Penanganan Layanan > Detail Tiket']);
?>
<div class="row">
<div class="col-md-8">
    <div class="detail-card" style="margin-bottom:20px">
        <h3 class="detail-heading">Detail Penanganan &mdash; <?= e($t['nomor_tiket']) ?> &nbsp; <?= badge_status($t['status']) ?></h3>
        <table class="table table-condensed" style="background:transparent;margin-bottom:6px">
            <tr><td width="28%"><b>Pelapor</b></td><td><?= e($t['nama_pelapor']) ?> <?= $t['nomor_anggota'] ? '(' . e($t['nomor_anggota']) . ')' : '' ?></td></tr>
            <tr><td><b>Kontak</b></td><td><?= e($t['kontak'] ?: '-') ?></td></tr>
            <tr><td><b>Klasifikasi Insiden</b></td><td><?= e(label_kategori($t['kategori'])) ?> &bull; <?= e($t['jenis']) ?> &bull; Urgensi <?= badge_urgensi($t['urgensi']) ?></td></tr>
            <tr><td><b>Dikirim</b></td><td><?= e(tgl_waktu($t['tanggal_dibuat'])) ?></td></tr>
            <tr><td><b>Petugas</b></td><td><?= e($t['nama_admin'] ?? '-') ?></td></tr>
            <?php if ($selesai): ?>
                <tr><td><b>Selesai</b></td><td><?= e(tgl_waktu($t['tanggal_selesai'])) ?> &mdash; durasi <?= e(durasi_jam($t['durasi_jam'])) ?></td></tr>
                <tr><td><b>Kepuasan Pemustaka</b></td><td><?= $t['rating'] !== null ? '<b>' . (int)$t['rating'] . ' / 5</b>' . ($t['ulasan'] ? ' &mdash; &ldquo;' . e($t['ulasan']) . '&rdquo;' : '') : '<span class="text-muted">Belum dinilai</span>' ?></td></tr>
            <?php endif; ?>
            <tr><td valign="top"><b>Deskripsi Laporan</b></td><td class="pre-wrap"><?= e($t['deskripsi']) ?></td></tr>
            <?php if ($selesai && $t['penanganan']): ?>
                <tr><td valign="top"><b>Hasil Penanganan</b></td><td class="pre-wrap"><?= e($t['penanganan']) ?></td></tr>
            <?php endif; ?>
        </table>

        <?php if (!$selesai): ?>
            <hr>
            <?php if ($t['status'] === 'Baru'): ?>
                <form method="post" style="display:inline" action="<?= e(url('admin/tiket_detail.php?id=' . $id)) ?>">
                    <?= csrf_field() ?><input type="hidden" name="aksi" value="proses">
                    <button class="btn-blue-pill" style="margin-right:12px"><i class="fa fa-play"></i> Ambil &amp; Proses Tiket</button>
                </form>
                <span class="form-hint">atau langsung pilih penanganan di bawah.</span>
                <hr>
            <?php endif; ?>

            <?php if (!$masalah): ?>
                <div class="detail-prompt">Solusi standar tersedia?</div>
                <div>
                    <button type="button" class="btn-blue-pill" style="margin-right:12px" data-toggle="collapse" data-target="#f-standar">Ya, terapkan</button>
                    <button type="button" class="btn-terracotta-pill" data-toggle="collapse" data-target="#f-eskalasi">Tidak, eskalasi masalah</button>
                </div>

                <div id="f-standar" class="collapse<?= $buka === 'standar' ? ' in' : '' ?>" style="margin-top:18px">
                    <form method="post" action="<?= e(url('admin/tiket_detail.php?id=' . $id)) ?>">
                        <?= csrf_field() ?><input type="hidden" name="aksi" value="standar">
                        <?php if ($kb): ?>
                        <div class="form-group">
                            <label>Gunakan solusi dari basis pengetahuan (opsional)</label>
                            <select class="form-control" id="pilih-solusi">
                                <option value="">&mdash; pilih solusi &mdash;</option>
                                <?php foreach ($kb as $s): ?>
                                    <option value="<?= e($s['judul_solusi'] . "\n" . $s['deskripsi_solusi']) ?>"><?= e($s['judul_solusi']) ?> (<?= e($s['nomor_tiket']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label>Catatan penanganan / solusi yang diterapkan <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="catatan" name="catatan" rows="4" maxlength="3000" required><?= $buka === 'standar' ? val('catatan') : '' ?></textarea>
                        </div>
                        <button class="btn btn-success"><i class="fa fa-check"></i> Terapkan &amp; Selesaikan Tiket</button>
                    </form>
                </div>

                <div id="f-eskalasi" class="collapse<?= $buka === 'eskalasi' ? ' in' : '' ?>" style="margin-top:18px">
                    <form method="post" action="<?= e(url('admin/tiket_detail.php?id=' . $id)) ?>">
                        <?= csrf_field() ?><input type="hidden" name="aksi" value="eskalasi">
                        <div class="form-group"><label>Analisis akar masalah <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="akar_masalah" rows="3" maxlength="3000" required><?= $buka === 'eskalasi' ? val('akar_masalah') : '' ?></textarea></div>
                        <div class="form-group"><label>Tindakan perbaikan</label>
                            <textarea class="form-control" name="tindakan_perbaikan" rows="2" maxlength="3000"><?= $buka === 'eskalasi' ? val('tindakan_perbaikan') : '' ?></textarea></div>
                        <div class="form-group"><label>Langkah pencegahan</label>
                            <textarea class="form-control" name="langkah_pencegahan" rows="2" maxlength="3000"><?= $buka === 'eskalasi' ? val('langkah_pencegahan') : '' ?></textarea></div>
                        <button class="btn btn-warning"><i class="fa fa-level-up"></i> Eskalasi ke Manajemen Masalah</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="detail-prompt"><i class="fa fa-search text-yellow"></i> Tiket dieskalasi &mdash; masalah sedang dianalisis</div>
                <form method="post" action="<?= e(url('admin/tiket_detail.php?id=' . $id)) ?>" style="margin-bottom:20px">
                    <?= csrf_field() ?><input type="hidden" name="aksi" value="ubah_masalah">
                    <div class="form-group"><label>Analisis akar masalah</label>
                        <textarea class="form-control" name="akar_masalah" rows="3" maxlength="3000" required><?= $buka === 'ubah' ? val('akar_masalah') : e($masalah['akar_masalah']) ?></textarea></div>
                    <div class="form-group"><label>Tindakan perbaikan</label>
                        <textarea class="form-control" name="tindakan_perbaikan" rows="2" maxlength="3000"><?= $buka === 'ubah' ? val('tindakan_perbaikan') : e($masalah['tindakan_perbaikan']) ?></textarea></div>
                    <div class="form-group"><label>Langkah pencegahan</label>
                        <textarea class="form-control" name="langkah_pencegahan" rows="2" maxlength="3000"><?= $buka === 'ubah' ? val('langkah_pencegahan') : e($masalah['langkah_pencegahan']) ?></textarea></div>
                    <button class="btn btn-default"><i class="fa fa-save"></i> Simpan Analisis</button>
                </form>
                <div class="detail-prompt">Masalah sudah teratasi? Dokumentasikan solusi permanen</div>
                <form method="post" action="<?= e(url('admin/tiket_detail.php?id=' . $id)) ?>">
                    <?= csrf_field() ?><input type="hidden" name="aksi" value="selesai_masalah">
                    <div class="form-group"><label>Judul solusi <span class="text-danger">*</span></label>
                        <input class="form-control" name="judul_solusi" maxlength="150" required value="<?= $buka === 'selesai' ? val('judul_solusi') : '' ?>"></div>
                    <div class="form-group"><label>Uraian solusi permanen <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="deskripsi_solusi" rows="3" maxlength="3000" required><?= $buka === 'selesai' ? val('deskripsi_solusi') : '' ?></textarea></div>
                    <button class="btn btn-success"><i class="fa fa-check"></i> Simpan ke Basis Pengetahuan &amp; Selesaikan Tiket</button>
                </form>
            <?php endif; ?>
            <div class="form-hint" style="margin-top:14px">Riwayat status tersimpan otomatis dan dapat dilihat pemustaka sebagai perkembangan penanganan.</div>
        <?php endif; ?>

        <?php if ($masalah && $selesai): ?>
            <hr>
            <h4 class="detail-heading"><i class="fa fa-wrench text-yellow"></i> Manajemen Masalah</h4>
            <table class="table table-condensed" style="background:transparent">
                <tr><td width="28%"><b>Akar masalah</b></td><td class="pre-wrap"><?= e($masalah['akar_masalah']) ?></td></tr>
                <tr><td><b>Tindakan perbaikan</b></td><td class="pre-wrap"><?= e($masalah['tindakan_perbaikan'] ?: '-') ?></td></tr>
                <tr><td><b>Langkah pencegahan</b></td><td class="pre-wrap"><?= e($masalah['langkah_pencegahan'] ?: '-') ?></td></tr>
                <?php if ($masalah['judul_solusi']): ?>
                    <tr><td><b>Solusi (basis pengetahuan)</b></td><td><b><?= e($masalah['judul_solusi']) ?></b><div class="pre-wrap"><?= e($masalah['deskripsi_solusi']) ?></div></td></tr>
                <?php endif; ?>
            </table>
        <?php endif; ?>
    </div>
    <a href="<?= e(url('admin/tiket.php')) ?>" class="btn-cancel-custom"><i class="fa fa-arrow-left"></i> Kembali ke Daftar</a>
</div>
<div class="col-md-4">
    <div class="content-box">
        <h3 class="box-title-x"><i class="fa fa-clock-o"></i> Riwayat Status</h3>
        <ul class="timeline-lite">
            <?php foreach ($riwayat as $r): ?>
                <li>
                    <div><?= $r['status_lama'] && $r['status_lama'] !== $r['status_baru'] ? e($r['status_lama']) . ' &rarr; ' : '' ?><?= badge_status($r['status_baru']) ?></div>
                    <div class="when"><?= e(tgl_waktu($r['created_at'])) ?><?= $r['nama_user'] ? ' &bull; ' . e($r['nama_user']) : '' ?></div>
                    <?php if ($r['catatan']): ?><div class="note"><?= e($r['catatan']) ?></div><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
</div>
<?php
tampil_footer([], <<<'JS'
$('#pilih-solusi').on('change', function () {
    if (this.value) { $('#catatan').val(this.value); }
});
JS);
