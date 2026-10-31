<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/views/layout.php';

Auth::wajib(['Admin']);

if (is_post()) {
    csrf_cek();
    $t = post('tabel') === 'jenis' ? 'jenis' : 'kategori';
    $a = post('aksi');
    try {
        if ($a === 'tambah') {
            Referensi::tambah($t, post('nama'));
            Log::catat('TAMBAH', $t . '_layanan', null, 'Tambah ' . $t . ': ' . post('nama'));
            flash('success', 'Pilihan ditambahkan.');
        } elseif ($a === 'ubah') {
            Referensi::ubah($t, (int)post('id'), post('nama'));
            Log::catat('UBAH', $t . '_layanan', (int)post('id'), 'Ubah ' . $t . ' menjadi: ' . post('nama'));
            flash('success', 'Nama pilihan diperbarui (tiket terkait ikut diperbarui).');
        } elseif ($a === 'aktif') {
            Referensi::setAktif($t, (int)post('id'), post('aktif') === '1');
            Log::catat('STATUS', $t . '_layanan', (int)post('id'), 'Ubah status tampil ' . $t);
            flash('success', 'Status tampil diperbarui.');
        } elseif ($a === 'hapus') {
            Referensi::hapus($t, (int)post('id'));
            Log::catat('HAPUS', $t . '_layanan', (int)post('id'), 'Hapus ' . $t);
            flash('success', 'Pilihan dihapus.');
        }
    } catch (AturanBisnis $e) {
        flash('danger', $e->getMessage());
    }
    redirect('admin/kategori.php');
}

tampil_header('Kategori & Jenis Layanan', 'kategori', ['ikon' => 'tags', 'crumb' => 'Referensi Formulir Keluhan',
    'sub' => 'Pilihan di bawah muncul pada formulir keluhan pemustaka dan filter tiket.']);

$blok = [['jenis', 'Jenis Pengajuan', 'Contoh: Gangguan, Permintaan Layanan'], ['kategori', 'Kategori Keluhan', 'Contoh: Login, Pencarian, Sirkulasi']];
echo '<div class="row">';
foreach ($blok as [$t, $judul, $contoh]) {
    $daftar = Referensi::daftar($t);
    $pk = $t === 'jenis' ? 'id_jenis' : 'id_kategori';
    ?>
    <div class="col-md-6">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><?= e($judul) ?></h3>
                <span class="pull-right text-muted small hidden-xs"><?= e($contoh) ?></span>
            </div>
            <div class="box-body">
                <ul class="ref-list">
                <?php foreach ($daftar as $r): $id = (int)$r[$pk]; ?>
                    <li class="ref-item">
                        <div class="ref-nama">
                            <form method="post">
                                <?= csrf_field() ?><input type="hidden" name="tabel" value="<?= $t ?>"><input type="hidden" name="aksi" value="ubah"><input type="hidden" name="id" value="<?= $id ?>">
                                <input name="nama" maxlength="60" value="<?= e($r['nama']) ?>" required aria-label="Nama" title="Ubah nama lalu tekan Enter">
                            </form>
                        </div>
                        <span class="ref-pakai"><?= (int)$r['dipakai'] ?> tiket</span>
                        <div class="ref-aksi">
                            <form method="post">
                                <?= csrf_field() ?><input type="hidden" name="tabel" value="<?= $t ?>"><input type="hidden" name="aksi" value="aktif"><input type="hidden" name="id" value="<?= $id ?>">
                                <input type="hidden" name="aktif" value="<?= $r['aktif'] ? '0' : '1' ?>">
                                <button class="btn btn-xs btn-<?= $r['aktif'] ? 'success' : 'default' ?>" title="Klik untuk <?= $r['aktif'] ? 'menyembunyikan dari formulir' : 'menampilkan di formulir' ?>"><?= $r['aktif'] ? 'Tampil' : 'Disembunyikan' ?></button>
                            </form>
                            <?php if ((int)$r['dipakai'] === 0): ?>
                            <form method="post" data-konfirmasi="Hapus pilihan &quot;<?= e($r['nama']) ?>&quot;?">
                                <?= csrf_field() ?><input type="hidden" name="tabel" value="<?= $t ?>"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= $id ?>">
                                <button class="btn btn-xs btn-danger" title="Hapus"><i class="fa fa-trash-o"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
                <?php if (!$daftar): ?><li class="ref-kosong">Belum ada pilihan.</li><?php endif; ?>
                </ul>
                <form method="post" class="ref-tambah">
                    <?= csrf_field() ?><input type="hidden" name="tabel" value="<?= $t ?>"><input type="hidden" name="aksi" value="tambah">
                    <input class="form-control" name="nama" maxlength="60" placeholder="Tambah pilihan baru..." required aria-label="Pilihan baru">
                    <button class="btn btn-primary"><i class="fa fa-plus"></i> Tambah</button>
                </form>
            </div>
        </div>
    </div>
    <?php
}
echo '</div>';
echo '<p class="text-muted small"><i class="fa fa-info-circle"></i> Ubah nama langsung pada kolomnya lalu tekan Enter. Pilihan yang sudah dipakai tiket tidak dapat dihapus, cukup disembunyikan dari formulir.</p>';
tampil_footer();
