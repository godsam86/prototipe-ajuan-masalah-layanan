<?php
declare(strict_types=1);

/** Kerangka halaman publik (beranda & katalog) — tanpa login. */
function publik_header(string $judul, string $aktif = 'beranda'): void
{
    $nm = Pengaturan::ambil('nama_perpustakaan', 'Perpustakaan');
    $u = Auth::user();
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="<?= e(asset('images/favicon.png')) ?>">
    <title><?= e($judul) ?> - <?= e($nm) ?></title>
    <link href="<?= e(asset('css/bootstrap.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('css/font-awesome.min.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('css/publik.css')) ?>" rel="stylesheet">
</head>
<body>
<nav class="pub-nav">
    <div class="pub-wrap">
        <a class="pub-brand" href="<?= e(url('index.php')) ?>">
            <img src="<?= e(asset('images/logo_perpusnas_2015.png')) ?>" alt="">
            <span><b>INLISLite</b><small><?= e($nm) ?></small></span>
        </a>
        <div class="pub-menu">
            <a href="<?= e(url('index.php')) ?>" class="<?= $aktif === 'beranda' ? 'on' : '' ?>">Beranda</a>
            <a href="<?= e(url('katalog.php')) ?>" class="<?= $aktif === 'katalog' ? 'on' : '' ?>">Katalog</a>
            <?php if ($u): ?>
                <a class="pub-btn" href="<?= e(url(Auth::beranda())) ?>"><i class="fa fa-dashboard"></i> Dashboard</a>
                <form method="post" action="<?= e(url('logout.php')) ?>" class="pub-logout">
                    <?= csrf_field() ?><button type="submit" class="pub-btn out"><i class="fa fa-sign-out"></i> Keluar</button>
                </form>
            <?php else: ?>
                <a class="pub-btn" href="<?= e(url('login.php')) ?>"><i class="fa fa-sign-in"></i> Masuk</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<?php
}

function publik_footer(): void
{
    $nm = Pengaturan::ambil('nama_perpustakaan', '');
    $al = Pengaturan::ambil('alamat_perpustakaan', '');
    ?>
<footer class="pub-foot">
    <div class="pub-wrap">
        <b><?= e($nm) ?></b><br><?= e($al) ?>
    </div>
</footer>
</body>
</html>
<?php
}
