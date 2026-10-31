<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/views/layout.php';

Auth::wajib(['Admin']);
$def = [
    'nama_perpustakaan' => ['Nama Perpustakaan', 'teks', 150],
    'alamat_perpustakaan' => ['Alamat', 'teks', 200],
    'lama_pinjam_hari' => ['Lama peminjaman (hari)', 'angka', [1, 60]],
    'maks_pinjam' => ['Maksimal koleksi dipinjam per anggota', 'angka', [1, 20]],
    'maks_perpanjang' => ['Maksimal perpanjangan per transaksi', 'angka', [0, 10]],
    'denda_per_hari' => ['Denda keterlambatan per hari (Rp)', 'angka', [0, 1000000]],
    'target_tinggi_jam' => ['Target penyelesaian tiket urgensi Tinggi (jam)', 'angka', [1, 2000]],
    'target_sedang_jam' => ['Target penyelesaian tiket urgensi Sedang (jam)', 'angka', [1, 2000]],
    'target_rendah_jam' => ['Target penyelesaian tiket urgensi Rendah (jam)', 'angka', [1, 2000]],
];
$err = [];
if (is_post()) {
    csrf_cek();
    $nilai = [];
    foreach ($def as $k => [$lbl, $tipe, $batas]) {
        $v = post($k);
        if ($tipe === 'teks') {
            if ($v === '' || mb_strlen($v) > $batas) {
                $err[$k] = "$lbl wajib diisi (maksimal $batas karakter).";
            }
        } elseif (!ctype_digit($v) || (int)$v < $batas[0] || (int)$v > $batas[1]) {
            $err[$k] = "$lbl harus berupa angka {$batas[0]} - {$batas[1]}.";
        }
        $nilai[$k] = $v;
    }
    if (!$err) {
        Database::transaksi(function () use ($nilai) {
            foreach ($nilai as $k => $v) {
                Pengaturan::simpan($k, $v);
            }
        });
        Log::catat('UBAH', 'pengaturan', null, 'Pengaturan diperbarui');
        DashboardManfaat::hitungSemua();   // target layanan berubah -> hitung ulang ringkasan
        flash('success', 'Pengaturan disimpan.');
        redirect('admin/pengaturan.php');
    }
}
tampil_header('Pengaturan', 'pengaturan', ['ikon' => 'gears', 'crumb' => 'Pengaturan']);
?>
<div class="box box-primary">
    <form method="post" action="<?= e(url('admin/pengaturan.php')) ?>">
        <?= csrf_field() ?>
        <div class="box-body" style="max-width:720px">
            <?php if ($err): ?><div class="alert alert-danger">Pengaturan belum dapat disimpan.</div><?php endif; ?>
            <?php foreach ($def as $k => [$lbl, $tipe, $batas]): ?>
                <div class="form-group<?= kelas_galat($err, $k) ?>"><label><?= e($lbl) ?></label>
                    <input class="form-control" name="<?= e($k) ?>" required <?= $tipe === 'angka' ? 'inputmode="numeric"' : '' ?> value="<?= val($k, Pengaturan::ambil($k)) ?>"><?= galat($err, $k) ?></div>
            <?php endforeach; ?>
        </div>
        <div class="box-footer"><button class="btn btn-primary"><i class="fa fa-save"></i> Simpan Pengaturan</button></div>
    </form>
</div>
<?php tampil_footer();
