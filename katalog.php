<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require APP_ROOT . '/app/views/publik.php';

$q = get('q');
$ruas = get('ruas', 'semua');
if (!isset(Bibliografi::RUAS[$ruas])) {
    $ruas = 'semua';
}
$bahan = get('bahan');
if ($bahan !== '' && !in_array($bahan, Bibliografi::BAHAN, true)) {
    $bahan = '';
}
$dicari = isset($_GET['cari']) || isset($_GET['q']);
$total = $dicari ? Bibliografi::hitung($q, $ruas, $bahan) : 0;
$pg = paginasi($total, 10);
$hasil = $dicari ? Bibliografi::daftar($q, $ruas, $bahan, $pg['per'], $pg['offset']) : [];

publik_header('Katalog', 'katalog');
?>
<div class="pub-wrap">
    <section class="sec">
        <h2>Katalog Koleksi (OPAC)</h2>
        <form class="form-cari" method="get" action="<?= e(url('katalog.php')) ?>">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Kata kunci" maxlength="100" aria-label="Kata kunci">
            <select name="ruas" aria-label="Cari pada"><?php foreach (Bibliografi::RUAS as $k => $v): ?><option value="<?= e($k) ?>"<?= selected($ruas, $k) ?>><?= e($v) ?></option><?php endforeach; ?></select>
            <select name="bahan" aria-label="Jenis bahan"><option value="">Semua Bahan</option><?php foreach (Bibliografi::BAHAN as $b): ?><option value="<?= e($b) ?>"<?= selected($bahan, $b) ?>><?= e($b) ?></option><?php endforeach; ?></select>
            <input type="hidden" name="cari" value="1">
            <button type="submit"><i class="fa fa-search"></i> Cari</button>
        </form>
        <?php if ($dicari): ?>
            <p><b><?= angka($total) ?></b> hasil<?= $q !== '' ? ' untuk &ldquo;<b>' . e($q) . '</b>&rdquo;' : '' ?></p>
            <?php foreach ($hasil as $b): ?>
                <div class="hasil">
                    <img src="<?= e(Bibliografi::urlCover($b['cover'])) ?>" alt="" loading="lazy">
                    <div>
                        <h4><?= e($b['judul']) ?></h4>
                        <p><?= e($b['pengarang']) ?></p>
                        <p><?= e(trim(($b['kota_terbit'] ? $b['kota_terbit'] . ' : ' : '') . $b['penerbit'] . ', ' . $b['tahun_terbit'], ' ,:')) ?> &bull; <?= e($b['jenis_bahan']) ?></p>
                        <p>Nomor panggil: <b><?= e($b['nomor_panggil'] ?: '-') ?></b></p>
                        <span class="tag <?= (int)$b['tersedia'] > 0 ? 'ok' : 'no' ?>"><?= (int)$b['tersedia'] > 0 ? 'Tersedia' : 'Sedang dipinjam' ?> (<?= (int)$b['tersedia'] ?>/<?= (int)$b['total_eks'] ?>)</span>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$hasil): ?><div class="note">Koleksi tidak ditemukan. Coba kata kunci atau filter lain.</div><?php endif; ?>
            <div class="pager"><?= paginasi_html($pg) ?></div>
        <?php else: ?>
            <div class="note">Masukkan kata kunci untuk mencari koleksi. Untuk meminjam atau menyampaikan keluhan, silakan <a href="<?= e(url('login.php')) ?>">masuk</a> terlebih dahulu.</div>
        <?php endif; ?>
    </section>
</div>
<?php publik_footer();
