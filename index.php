<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require APP_ROOT . '/app/views/publik.php';

$nm = Pengaturan::ambil('nama_perpustakaan', 'Perpustakaan');
$jBib = (int)Database::value('SELECT COUNT(*) FROM bibliografi');
$jEks = (int)Database::value('SELECT COUNT(*) FROM koleksi');
$jAng = (int)Database::value("SELECT COUNT(*) FROM anggota WHERE status = 'Aktif'");
$jTik = (int)Database::value("SELECT COUNT(*) FROM tiket_insiden WHERE status = 'Selesai'");
$terbaru = Bibliografi::terbaru(4);
$flash = flash_ambil();

publik_header('Beranda');
?>
<section class="hero">
    <img src="<?= e(asset('images/logo-inlislite_bo.png')) ?>" alt="INLISLite">
    <h1><?= e(mb_strtoupper($nm)) ?></h1>
    <p>Cari koleksi perpustakaan, sampaikan keluhan atau permintaan layanan, dan pantau penanganannya secara transparan.</p>
    <?php foreach ($flash as $f): ?><div class="note" style="max-width:560px;margin:0 auto 14px;color:#243b4a"><?= e($f['msg']) ?></div><?php endforeach; ?>
    <form class="cari" method="get" action="<?= e(url('katalog.php')) ?>">
        <input type="search" name="q" placeholder="Cari judul, pengarang, atau subjek..." maxlength="100" aria-label="Cari koleksi">
        <input type="hidden" name="cari" value="1">
        <button type="submit"><i class="fa fa-search"></i> Cari</button>
    </form>
    <div class="cta">
        <?php if (Auth::user()): ?>
            <a class="a1" href="<?= e(url(Auth::beranda())) ?>"><i class="fa fa-dashboard"></i> Buka Dashboard</a>
        <?php else: ?>
            <a class="a1" href="<?= e(url('login.php')) ?>"><i class="fa fa-sign-in"></i> Masuk</a>
        <?php endif; ?>
        <a class="a2" href="<?= e(url('katalog.php')) ?>"><i class="fa fa-book"></i> Jelajahi Katalog</a>
    </div>
</section>

<div class="stat">
    <div><b><?= angka($jBib) ?></b><span>Judul Koleksi</span></div>
    <div><b><?= angka($jEks) ?></b><span>Eksemplar</span></div>
    <div><b><?= angka($jAng) ?></b><span>Anggota Aktif</span></div>
    <div><b><?= angka($jTik) ?></b><span>Layanan Terselesaikan</span></div>
</div>

<div class="pub-wrap">
    <section class="sec">
        <h2>Layanan Kami</h2>
        <p class="lead">Tiga layanan utama untuk pemustaka, petugas, dan pimpinan.</p>
        <div class="cards">
            <div class="card" style="--c:#3b6aa6"><i class="fa fa-comment"></i><h3>Layanan Pemustaka</h3><p>Ajukan keluhan atau permintaan layanan, terima nomor tiket, lalu pantau status dan beri penilaian setelah selesai.</p></div>
            <div class="card" style="--c:#6f42c1"><i class="fa fa-ticket"></i><h3>Penanganan Layanan</h3><p>Petugas menangani tiket dengan solusi standar atau eskalasi masalah, tercatat lengkap dalam riwayat dan basis pengetahuan.</p></div>
            <div class="card" style="--c:#267e76"><i class="fa fa-bar-chart-o"></i><h3>Manfaat Layanan</h3><p>Pimpinan memantau tingkat penyelesaian, waktu penanganan, kepuasan, dan capaian target layanan.</p></div>
        </div>
    </section>

    <section class="sec">
        <h2>Cara Menyampaikan Keluhan</h2>
        <p class="lead">Masuk dengan akun pemustaka Anda, lalu ikuti langkah berikut.</p>
        <div class="alur">
            <div><b>Masuk</b>Login memakai akun pemustaka.</div>
            <div><b>Ajukan</b>Isi formulir keluhan di menu Layanan Pemustaka.</div>
            <div><b>Pantau</b>Lihat status dan perkembangan di Riwayat Tiket.</div>
            <div><b>Nilai</b>Beri penilaian setelah tiket selesai.</div>
        </div>
    </section>

    <?php if ($terbaru): ?>
    <section class="sec">
        <h2>Koleksi Terbaru</h2>
        <p class="lead">Tambahan terbaru di katalog kami.</p>
        <div class="buku">
            <?php foreach ($terbaru as $b): ?>
                <a href="<?= e(url('katalog.php?cari=1&q=' . rawurlencode($b['judul']))) ?>">
                    <img src="<?= e(Bibliografi::urlCover($b['cover'])) ?>" alt="" loading="lazy">
                    <b><?= e($b['judul']) ?></b><small><?= e($b['pengarang']) ?></small>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>
<?php publik_footer();
