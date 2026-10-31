<?php
declare(strict_types=1);

/** Menu sidebar per peran. Setiap item: [label, ikon, path, kunci] atau treeview dengan 'anak'. */
function menu_peran(string $peran): array
{
    switch ($peran) {
        case 'Admin':
            return [
                ['Dashboard', 'dashboard', 'admin/index.php', 'dashboard'],
                ['Penanganan Layanan', 'ticket', null, 'layanan', [
                    ['Tiket Layanan', 'admin/tiket.php', 'tiket'],
                    ['Manajemen Masalah', 'admin/masalah.php', 'masalah'],
                ]],
                ['Katalog', 'book', null, 'katalog', [
                    ['Data Bibliografis', 'admin/bibliografi.php', 'bibliografi'],
                    ['Data Koleksi', 'admin/koleksi.php', 'koleksi'],
                ]],
                ['Keanggotaan', 'users', 'admin/anggota.php', 'anggota'],
                ['Sirkulasi', 'refresh', 'admin/sirkulasi.php', 'sirkulasi'],
                ['Laporan & Statistik', 'bar-chart-o', 'laporan.php', 'laporan'],
                ['Pengguna', 'user', 'admin/pengguna.php', 'pengguna'],
                ['Kategori & Jenis', 'tags', 'admin/kategori.php', 'kategori'],
                ['Pengaturan', 'gears', 'admin/pengaturan.php', 'pengaturan'],
                ['Log Aktivitas', 'clock-o', 'admin/log.php', 'log'],
            ];
        case 'Pimpinan':
            return [
                ['Manfaat Layanan', 'bar-chart-o', 'pimpinan/index.php', 'dashboard'],
                ['Laporan & Statistik', 'bar-chart-o', 'laporan.php', 'laporan'],
                ['Data Pengguna', 'user', 'pimpinan/pengguna.php', 'pengguna'],
            ];
        default:
            return [
                ['Dashboard', 'dashboard', 'pemustaka/index.php', 'dashboard'],
                ['Katalog Koleksi', 'book', 'pemustaka/katalog.php', 'katalog'],
                ['Pencarian (OPAC)', 'search', 'pemustaka/opac.php', 'opac'],
                ['Layanan Pemustaka', 'comment', null, 'layanan', [
                    ['Ajukan Keluhan / Permintaan', 'pemustaka/layanan.php', 'layanan_baru'],
                    ['Riwayat & Status Tiket', 'pemustaka/riwayat.php', 'layanan_riwayat'],
                ]],
            ];
    }
}

function tampil_header(string $judul, string $aktif = '', array $opsi = []): void
{
    $u = Auth::user();
    $peran = $u['peran'] ?? 'Pemustaka';
    $nmPerpus = Pengaturan::ambil('nama_perpustakaan', 'Perpustakaan');
    $alamat = Pengaturan::ambil('alamat_perpustakaan', '');
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul) ?> - Perpustakaan</title>
    <link rel="shortcut icon" type="image/x-icon" href="<?= e(asset('images/favicon.png')) ?>">
    <link href="<?= e(asset('css/bootstrap.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('css/font-awesome.min.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('css/AdminLTE.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('css/_all-skins.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('css/sweetalert.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
</head>
<body class="skin-blue sidebar-mini">
<div class="wrapper">
    <header class="main-header no-print">
        <a href="<?= e(url(Auth::beranda())) ?>" class="logo" style="background-color:#2b3a42;border-bottom:none;height:60px;line-height:60px;">
            <span class="logo-mini"><b>INLIS</b></span>
            <span class="logo-lg" style="font-size:16px;"><b>INLISLite</b></span>
        </a>
        <nav class="navbar navbar-static-top" style="background-color:#2a5a8a;min-height:60px;">
            <a href="javascript:void(0)" class="sidebar-toggle" role="button" title="Buka / Tutup Sidebar">
                <i class="fa fa-bars"></i><span class="sr-only">Toggle navigation</span>
            </a>
            <div class="hidden-xs header-brand">
                <img src="<?= e(asset('images/logo_perpusnas_2015.png')) ?>" alt="Logo">
                <div>
                    <div class="b1">INTEGRATED LIBRARY SYSTEM <i class="fa fa-book"></i></div>
                    <div class="b2"><?= e(mb_strtoupper($nmPerpus)) ?></div>
                    <div class="b3"><?= e($alamat) ?></div>
                </div>
            </div>
            <div class="navbar-custom-menu">
                <ul class="nav navbar-nav" style="padding-top:5px;">
                    <li class="hidden-sm hidden-xs header-clock"><i class="fa fa-clock-o"></i> <span id="jam-live"></span></li>
                    <li class="dropdown user user-menu">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" style="padding-top:15px;padding-bottom:15px;" aria-label="Menu akun">
                            <i class="fa fa-user fa-lg"></i>
                            <span class="hidden-xs nama-user"><?= e($u['nama'] ?? '') ?></span>
                            <i class="fa fa-caret-down" style="margin-left:4px;"></i>
                        </a>
                        <ul class="dropdown-menu menu-akun">
                            <li><a href="<?= e(url('profil.php')) ?>"><i class="fa fa-user"></i> Lihat Profil</a></li>
                            <li>
                                <form method="post" action="<?= e(url('logout.php')) ?>" style="margin:0">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="menu-akun-keluar"><i class="fa fa-sign-out"></i> Log Out</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    <aside class="main-sidebar no-print">
        <section class="sidebar">
            <div class="user-panel">
                <div class="pull-left image"><img src="<?= e(asset('images/logo_perpusnas_kecil.png')) ?>" class="img-circle" alt="Logo"></div>
                <div class="pull-left info">
                    <p><?= e($peran === 'Admin' ? 'Petugas Perpustakaan' : ($peran === 'Pimpinan' ? 'Pimpinan / Kepala Dinas' : 'Pemustaka')) ?></p>
                    <a href="#"><i class="fa fa-circle text-success"></i> <?= e($u['username'] ?? '') ?> &bull; Online</a>
                </div>
            </div>
            <ul class="sidebar-menu">
                <li class="header">MENU <?= e(mb_strtoupper($peran)) ?></li>
                <?php foreach (menu_peran($peran) as $m): ?>
                    <?php if (isset($m[4])): ?>
                        <?php $buka = false; foreach ($m[4] as $a) { if ($a[2] === $aktif) { $buka = true; } } ?>
                        <li class="treeview<?= $buka ? ' active' : '' ?>">
                            <a href="javascript:void(0)"><i class="fa fa-<?= e($m[1]) ?>"></i> <span><?= e($m[0]) ?></span><i class="fa fa-angle-left pull-right"></i></a>
                            <ul class="treeview-menu">
                                <?php foreach ($m[4] as $a): ?>
                                    <li<?= $a[2] === $aktif ? ' class="active"' : '' ?>><a href="<?= e(url($a[1])) ?>"><i class="fa fa-circle-o"></i> <?= e($a[0]) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li<?= $m[3] === $aktif ? ' class="active"' : '' ?>>
                            <a href="<?= e(url($m[2])) ?>"><i class="fa fa-<?= e($m[1]) ?>"></i> <span><?= e($m[0]) ?></span></a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </section>
    </aside>

    <div class="content-wrapper" style="background-color:#ecf0f5;">
        <section class="content" style="padding-top:15px;">
            <div class="page-title-box">
                <?php if (!empty($opsi['crumb'])): ?>
                    <div class="pull-right crumb"><i class="fa fa-<?= e($opsi['ikon'] ?? 'dashboard') ?>"></i> <?= e($opsi['crumb']) ?></div>
                <?php endif; ?>
                <h3><?= e($judul) ?></h3>
                <?php if (!empty($opsi['sub'])): ?><div class="sub"><?= $opsi['sub'] /* HTML tepercaya dari pemanggil */ ?></div><?php endif; ?>
            </div>
            <?php foreach (flash_ambil() as $f): ?>
                <div class="alert alert-<?= e($f['type']) ?> alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                    <?= e($f['msg']) ?>
                </div>
            <?php endforeach; ?>
    <?php
}

function tampil_footer(array $skrip = [], string $inline = ''): void
{
    $nm = Pengaturan::ambil('nama_perpustakaan', '');
    ?>
        </section>
    </div>

    <footer class="main-footer no-print">
        <?= e($nm) ?>
    </footer>
</div>

<script src="<?= e(asset('js/jquery.js')) ?>"></script>
<script src="<?= e(asset('js/bootstrap.min.js')) ?>"></script>
<script src="<?= e(asset('js/sweetalert.min.js')) ?>"></script>
<?php foreach ($skrip as $s): ?><script src="<?= e(asset('js/' . $s)) ?>"></script>
<?php endforeach; ?>
<script src="<?= e(asset('js/app.js')) ?>"></script>
<?php if ($inline !== ''): ?><script>
<?= $inline /* JavaScript tepercaya dari pemanggil */ ?>
</script><?php endif; ?>
</body>
</html>
    <?php
}
