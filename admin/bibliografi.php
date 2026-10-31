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
            $b = Bibliografi::cari((int)post('id'));
            Bibliografi::hapus((int)post('id'));
            Log::catat('HAPUS', 'bibliografi', (int)post('id'), 'Hapus bibliografi: ' . ($b['judul'] ?? ''));
            flash('success', 'Data bibliografis dihapus.');
        } catch (AturanBisnis $e) {
            flash('danger', $e->getMessage());
        }
        redirect('admin/bibliografi.php');
    }
    if ($a === 'simpan') {
        $idSimpan = (int)post('id') ?: null;
        $d = [];
        foreach (['judul', 'pengarang', 'penerbit', 'kota_terbit', 'tahun_terbit', 'isbn', 'subjek', 'nomor_panggil', 'jenis_bahan', 'deskripsi_fisik'] as $k) {
            $d[$k] = post($k);
        }
        $err = Bibliografi::validasi($d, $idSimpan);
        if (!$err) {
            try {
                $cover = Bibliografi::simpanCover($_FILES['cover'] ?? null);
                $idBaru = Bibliografi::simpan($d, $idSimpan, $cover);
                Log::catat($idSimpan ? 'UBAH' : 'TAMBAH', 'bibliografi', $idBaru, ($idSimpan ? 'Ubah' : 'Tambah') . ' bibliografi: ' . $d['judul']);
                flash('success', 'Data bibliografis disimpan.');
                redirect('admin/bibliografi.php');
            } catch (AturanBisnis $e) {
                $err['cover'] = $e->getMessage();
            }
        }
        $aksi = 'form';
        $id = (int)post('id');
    }
}

if ($aksi === 'form') {
    $row = $id ? Bibliografi::cari($id) : null;
    if ($id && !$row) {
        flash('danger', 'Data tidak ditemukan.');
        redirect('admin/bibliografi.php');
    }
    tampil_header($row ? 'Ubah Data Bibliografis' : 'Tambah Data Bibliografis', 'bibliografi', ['ikon' => 'book', 'crumb' => 'Katalog > Data Bibliografis']);
    ?>
    <div class="box box-primary">
        <form method="post" enctype="multipart/form-data" action="<?= e(url('admin/bibliografi.php')) ?>">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="simpan"><input type="hidden" name="id" value="<?= $row ? (int)$row['id_bibliografi'] : 0 ?>">
            <div class="box-body">
                <?php if ($err): ?><div class="alert alert-danger">Data belum dapat disimpan. Periksa isian yang bertanda merah.</div><?php endif; ?>
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group<?= kelas_galat($err, 'judul') ?>"><label>Judul <span class="text-danger">*</span></label>
                            <input class="form-control" name="judul" maxlength="255" required value="<?= val('judul', $row['judul'] ?? '') ?>"><?= galat($err, 'judul') ?></div>
                        <div class="row">
                            <div class="col-md-6"><div class="form-group<?= kelas_galat($err, 'pengarang') ?>"><label>Pengarang <span class="text-danger">*</span></label>
                                <input class="form-control" name="pengarang" maxlength="150" required value="<?= val('pengarang', $row['pengarang'] ?? '') ?>"><?= galat($err, 'pengarang') ?></div></div>
                            <div class="col-md-6"><div class="form-group<?= kelas_galat($err, 'nomor_panggil') ?>"><label>Nomor Panggil</label>
                                <input class="form-control" name="nomor_panggil" maxlength="60" value="<?= val('nomor_panggil', $row['nomor_panggil'] ?? '') ?>"><?= galat($err, 'nomor_panggil') ?></div></div>
                        </div>
                        <div class="row">
                            <div class="col-md-4"><div class="form-group<?= kelas_galat($err, 'kota_terbit') ?>"><label>Kota Terbit</label>
                                <input class="form-control" name="kota_terbit" maxlength="80" value="<?= val('kota_terbit', $row['kota_terbit'] ?? '') ?>"><?= galat($err, 'kota_terbit') ?></div></div>
                            <div class="col-md-5"><div class="form-group<?= kelas_galat($err, 'penerbit') ?>"><label>Penerbit</label>
                                <input class="form-control" name="penerbit" maxlength="150" value="<?= val('penerbit', $row['penerbit'] ?? '') ?>"><?= galat($err, 'penerbit') ?></div></div>
                            <div class="col-md-3"><div class="form-group<?= kelas_galat($err, 'tahun_terbit') ?>"><label>Tahun Terbit</label>
                                <input class="form-control" name="tahun_terbit" maxlength="4" inputmode="numeric" value="<?= val('tahun_terbit', $row['tahun_terbit'] ?? '') ?>"><?= galat($err, 'tahun_terbit') ?></div></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6"><div class="form-group<?= kelas_galat($err, 'isbn') ?>"><label>ISBN / ISSN</label>
                                <input class="form-control" name="isbn" maxlength="30" value="<?= val('isbn', $row['isbn'] ?? '') ?>"><?= galat($err, 'isbn') ?></div></div>
                            <div class="col-md-6"><div class="form-group<?= kelas_galat($err, 'jenis_bahan') ?>"><label>Jenis Bahan</label>
                                <select class="form-control" name="jenis_bahan"><?php $jb = is_post() ? post('jenis_bahan') : ($row['jenis_bahan'] ?? 'Monograf'); foreach (Bibliografi::BAHAN as $b): ?><option<?= selected($jb, $b) ?>><?= e($b) ?></option><?php endforeach; ?></select><?= galat($err, 'jenis_bahan') ?></div></div>
                        </div>
                        <div class="form-group<?= kelas_galat($err, 'subjek') ?>"><label>Subjek</label>
                            <input class="form-control" name="subjek" maxlength="255" placeholder="Pisahkan dengan titik koma" value="<?= val('subjek', $row['subjek'] ?? '') ?>"><?= galat($err, 'subjek') ?></div>
                        <div class="form-group<?= kelas_galat($err, 'deskripsi_fisik') ?>"><label>Deskripsi Fisik</label>
                            <input class="form-control" name="deskripsi_fisik" maxlength="120" value="<?= val('deskripsi_fisik', $row['deskripsi_fisik'] ?? '') ?>"><?= galat($err, 'deskripsi_fisik') ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="well text-center<?= kelas_galat($err, 'cover') ?>">
                            <label>Cover</label><br>
                            <img class="book-cover" style="width:130px;height:190px" src="<?= e(Bibliografi::urlCover($row['cover'] ?? null)) ?>" alt=""><br><br>
                            <input type="file" name="cover" accept="image/jpeg,image/png,image/gif">
                            <p class="form-hint">JPG/PNG/GIF, maks. 2 MB. Kosongkan bila tidak diganti.</p><?= galat($err, 'cover') ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-footer"><button class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button> <a class="btn btn-default" href="<?= e(url('admin/bibliografi.php')) ?>">Batal</a></div>
        </form>
    </div>
    <?php
    tampil_footer();
    exit;
}

$q = get('q');
$ruas = get('ruas', 'semua');
if (!isset(Bibliografi::RUAS[$ruas])) {
    $ruas = 'semua';
}
$total = Bibliografi::hitung($q, $ruas, '');
$pg = paginasi($total, 10);
$daftar = Bibliografi::daftar($q, $ruas, '', $pg['per'], $pg['offset']);

tampil_header('Data Bibliografis', 'bibliografi', ['ikon' => 'book', 'crumb' => 'Katalog > Data Bibliografis']);
?>
<div class="box box-info">
    <div class="box-header with-border">
        <a href="<?= e(url('admin/bibliografi.php?aksi=form')) ?>" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Entri Baru</a>
        <form method="get" class="form-inline pull-right">
            <input type="text" name="q" value="<?= e($q) ?>" class="form-control input-sm" placeholder="Cari katalog">
            <select name="ruas" class="form-control input-sm"><?php foreach (Bibliografi::RUAS as $k => $v): ?><option value="<?= e($k) ?>"<?= selected($ruas, $k) ?>><?= e($v) ?></option><?php endforeach; ?></select>
            <button class="btn btn-default btn-sm"><i class="fa fa-search"></i></button>
        </form>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th style="width:60px">Cover</th><th>Judul / Pengarang</th><th>Penerbit</th><th>ISBN</th><th>Eksemplar</th><th style="width:150px">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($daftar as $b): ?>
                <tr>
                    <td><img src="<?= e(Bibliografi::urlCover($b['cover'])) ?>" style="width:40px;height:58px;object-fit:cover" alt=""></td>
                    <td><a href="<?= e(url('detail.php?id=' . (int)$b['id_bibliografi'])) ?>"><b><?= e($b['judul']) ?></b></a><br><small><?= e($b['pengarang']) ?></small></td>
                    <td><?= e($b['penerbit'] ?: '-') ?><br><small class="text-muted"><?= e($b['tahun_terbit'] ?: '') ?></small></td>
                    <td><?= e($b['isbn'] ?: '-') ?></td>
                    <td><?= (int)$b['tersedia'] ?> / <?= (int)$b['total_eks'] ?></td>
                    <td>
                        <a class="btn btn-warning btn-xs" href="<?= e(url('admin/bibliografi.php?aksi=form&id=' . (int)$b['id_bibliografi'])) ?>"><i class="fa fa-pencil"></i> Ubah</a>
                        <form method="post" style="display:inline" data-konfirmasi="Hapus bibliografi &quot;<?= e($b['judul']) ?>&quot;?">
                            <?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$b['id_bibliografi'] ?>">
                            <button class="btn btn-danger btn-xs"><i class="fa fa-trash-o"></i> Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$daftar): ?><tr><td colspan="6" class="text-center text-muted">Data tidak ditemukan.</td></tr><?php endif; ?>
            </tbody>
        </table>
        <div class="text-muted"><?= angka($total) ?> judul</div>
        <?= paginasi_html($pg) ?>
    </div>
</div>
<?php tampil_footer();
