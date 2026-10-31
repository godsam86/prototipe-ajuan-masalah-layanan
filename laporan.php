<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require APP_ROOT . '/app/views/layout.php';

$user = Auth::wajib(['Admin', 'Pimpinan']);
$jenisList = ['layanan' => 'Layanan Pemustaka (Tiket)', 'sirkulasi' => 'Sirkulasi', 'koleksi' => 'Koleksi & Keanggotaan'];
$jenis = get('jenis', 'layanan');
if (!isset($jenisList[$jenis])) {
    $jenis = 'layanan';
}
$tahunList = DashboardManfaat::tahunTersedia();
$tahun = (int)get('tahun', (string)$tahunList[0]);
if (!in_array($tahun, $tahunList, true)) {
    $tahun = $tahunList[0];
}
$periode = get('periode', 'tahun');
if (!preg_match('/^(tahun|q[1-4]|[1-9]|1[0-2])$/', $periode)) {
    $periode = 'tahun';
}
[$awal, $akhir, $label] = rentang_periode($tahun, $periode);

if (is_post() && $user['peran'] === 'Admin') {
    csrf_cek();
    if (post('aksi') === 'simpan') {
        $isi = Laporan::teksKinerja($label, $awal, $akhir);
        Laporan::simpanKinerja((int)$user['id_user'], $awal, $isi);
        flash('success', 'Laporan kinerja periode ' . $label . ' disimpan.');
    }
    redirect('laporan.php?' . http_build_query(['jenis' => $jenis, 'tahun' => $tahun, 'periode' => $periode]));
}

$data = match ($jenis) {
    'layanan' => Laporan::layanan($awal, $akhir),
    'sirkulasi' => Laporan::sirkulasi($awal, $akhir),
    default => Laporan::koleksi(),
};
$arsip = $jenis === 'layanan' ? Laporan::daftarKinerja(10) : [];
$js = '';

tampil_header('Laporan & Statistik', 'laporan', ['ikon' => 'bar-chart-o', 'crumb' => 'Laporan & Statistik']);
?>
<div class="box box-default no-print">
    <div class="box-body">
        <form method="get" class="form-inline">
            <label>Jenis laporan&nbsp;</label>
            <select name="jenis" class="form-control input-sm"><?php foreach ($jenisList as $k => $v): ?><option value="<?= e($k) ?>"<?= selected($jenis, $k) ?>><?= e($v) ?></option><?php endforeach; ?></select>
            <label>&nbsp;Periode&nbsp;</label>
            <select name="tahun" class="form-control input-sm"><?php foreach ($tahunList as $y): ?><option<?= selected($tahun, $y) ?>><?= $y ?></option><?php endforeach; ?></select>
            <select name="periode" class="form-control input-sm"><?= opsi_periode($periode) ?></select>
            <button class="btn btn-primary btn-sm"><i class="fa fa-filter"></i> Tampilkan</button>
            <button type="button" class="btn btn-default btn-sm" onclick="window.print()"><i class="fa fa-print"></i> Cetak</button>
        </form>
    </div>
</div>

<div class="box box-info">
    <div class="box-header with-border"><h3 class="box-title"><?= e($jenisList[$jenis]) ?><?= $jenis !== 'koleksi' ? ' &mdash; ' . e($label) : ' &mdash; kondisi saat ini' ?></h3></div>
    <div class="box-body">
    <?php if ($jenis === 'layanan'): $k = $data['ringkasan']; ?>
        <?php if ($k['total'] === 0): ?>
            <div class="alert alert-warning">Data tidak tersedia untuk periode ini. Silakan pilih periode lain.</div>
        <?php else: ?>
            <div class="kpi-grid">
                <div class="kpi-card"><div class="kpi-label">Tiket Masuk</div><div class="kpi-value"><?= $k['total'] ?></div><div class="kpi-sub">Selesai <?= $k['selesai'] ?> &bull; Diproses <?= $k['diproses'] ?> &bull; Baru <?= $k['baru'] ?></div></div>
                <div class="kpi-card"><div class="kpi-label">Tingkat Penyelesaian</div><div class="kpi-value"><?= e(angka($k['penyelesaian'], 1)) ?>%</div></div>
                <div class="kpi-card"><div class="kpi-label">Rata-rata Penanganan</div><div class="kpi-value"><?= e(durasi_jam($k['rata_jam'])) ?></div></div>
                <div class="kpi-card"><div class="kpi-label">Kepuasan Pemustaka</div><div class="kpi-value"><?= $k['kepuasan'] === null ? '-' : e(angka($k['kepuasan'], 1)) . ' / 5' ?></div><div class="kpi-sub">Capaian target: <?= $k['target'] === null ? '-' : e(angka($k['target'], 1)) . '%' ?></div></div>
            </div>
            <div class="row">
                <div class="col-md-7"><div style="position:relative;height:260px"><canvas id="grafik"></canvas></div></div>
                <div class="col-md-5">
                    <table class="table table-bordered table-striped">
                        <thead><tr><th>Kategori</th><th>Jumlah</th><th>Selesai</th><th>Rata-rata</th></tr></thead>
                        <tbody>
                        <?php foreach ($data['kategori'] as $r): ?>
                            <tr><td><?= e(label_kategori($r['kategori'])) ?></td><td><?= (int)$r['jumlah'] ?></td><td><?= (int)$r['selesai'] ?></td><td><?= e(durasi_jam($r['rata_jam'])) ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <table class="table table-bordered table-striped">
                        <thead><tr><th>Urgensi</th><th>Jumlah</th><th>Selesai</th></tr></thead>
                        <tbody><?php foreach ($data['urgensi'] as $r): ?><tr><td><?= e($r['urgensi']) ?></td><td><?= (int)$r['jumlah'] ?></td><td><?= (int)$r['selesai'] ?></td></tr><?php endforeach; ?></tbody>
                    </table>
                </div>
            </div>
            <?php
            $lb = array_map(fn($r) => Laporan::namaBulan($r['bulan']), $data['per_bulan']);
            $js = 'new Chart(document.getElementById("grafik"),{type:"bar",data:{labels:' . json_encode($lb) . ',datasets:['
                . '{label:"Tiket masuk",data:' . json_encode(array_column($data['per_bulan'], 'jumlah')) . ',backgroundColor:"#3b6aa6"},'
                . '{label:"Selesai",data:' . json_encode(array_column($data['per_bulan'], 'selesai')) . ',backgroundColor:"#2b827a"}]},'
                . 'options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});';
            ?>
            <?php if ($user['peran'] === 'Admin'): ?>
                <form method="post" class="no-print" style="margin-top:14px">
                    <?= csrf_field() ?><input type="hidden" name="aksi" value="simpan">
                    <button class="btn btn-success"><i class="fa fa-save"></i> Simpan sebagai Laporan Kinerja</button>
                    <a class="btn btn-default" href="<?= e(url('unduh.php?tahun=' . $tahun . '&periode=' . urlencode($periode))) ?>"><i class="fa fa-download"></i> Unduh CSV</a>
                </form>
            <?php else: ?>
                <a class="btn btn-default no-print" href="<?= e(url('unduh.php?tahun=' . $tahun . '&periode=' . urlencode($periode))) ?>"><i class="fa fa-download"></i> Unduh CSV</a>
            <?php endif; ?>
        <?php endif; ?>

    <?php elseif ($jenis === 'sirkulasi'): ?>
        <?php if ($data['total_pinjam'] === 0 && $data['total_kembali'] === 0): ?>
            <div class="alert alert-warning">Data tidak tersedia untuk periode ini. Silakan pilih periode lain.</div>
        <?php else: ?>
            <div class="kpi-grid">
                <div class="kpi-card"><div class="kpi-label">Peminjaman</div><div class="kpi-value"><?= $data['total_pinjam'] ?></div></div>
                <div class="kpi-card"><div class="kpi-label">Pengembalian</div><div class="kpi-value"><?= $data['total_kembali'] ?></div></div>
                <div class="kpi-card"><div class="kpi-label">Denda Terkumpul</div><div class="kpi-value" style="font-size:24px"><?= e(rupiah($data['total_denda'])) ?></div></div>
                <div class="kpi-card"><div class="kpi-label">Sedang Dipinjam</div><div class="kpi-value"><?= $data['aktif'] ?></div><div class="kpi-sub"><?= $data['terlambat'] ?> terlambat (saat ini)</div></div>
            </div>
            <div class="row">
                <div class="col-md-7"><div style="position:relative;height:260px"><canvas id="grafik"></canvas></div></div>
                <div class="col-md-5">
                    <table class="table table-bordered table-striped">
                        <thead><tr><th>Judul Terpopuler</th><th>Dipinjam</th></tr></thead>
                        <tbody><?php foreach ($data['top'] as $r): ?><tr><td><?= e($r['judul']) ?></td><td><?= (int)$r['jumlah'] ?>x</td></tr><?php endforeach; ?></tbody>
                    </table>
                </div>
            </div>
            <?php
            $lb = array_map(fn($r) => Laporan::namaBulan($r['bulan']), $data['per_bulan']);
            $js = 'new Chart(document.getElementById("grafik"),{type:"bar",data:{labels:' . json_encode($lb) . ',datasets:['
                . '{label:"Peminjaman",data:' . json_encode(array_column($data['per_bulan'], 'pinjam')) . ',backgroundColor:"#3b6aa6"},'
                . '{label:"Pengembalian",data:' . json_encode(array_column($data['per_bulan'], 'kembali')) . ',backgroundColor:"#e67e22"}]},'
                . 'options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});';
            ?>
        <?php endif; ?>

    <?php else: ?>
        <div class="row">
            <div class="col-md-6">
                <p><b>Total judul bibliografi:</b> <?= angka($data['judul']) ?></p>
                <table class="table table-bordered table-striped"><thead><tr><th>Status Eksemplar</th><th>Jumlah</th></tr></thead>
                    <tbody><?php foreach ($data['eksemplar'] as $r): ?><tr><td><?= e($r['status']) ?></td><td><?= (int)$r['jumlah'] ?></td></tr><?php endforeach; ?></tbody></table>
                <table class="table table-bordered table-striped"><thead><tr><th>Jenis Bahan</th><th>Judul</th></tr></thead>
                    <tbody><?php foreach ($data['bahan'] as $r): ?><tr><td><?= e($r['jenis_bahan']) ?></td><td><?= (int)$r['jumlah'] ?></td></tr><?php endforeach; ?></tbody></table>
            </div>
            <div class="col-md-6">
                <table class="table table-bordered table-striped"><thead><tr><th>Status Anggota</th><th>Jumlah</th></tr></thead>
                    <tbody><?php foreach ($data['anggota_status'] as $r): ?><tr><td><?= e($r['status']) ?></td><td><?= (int)$r['jumlah'] ?></td></tr><?php endforeach; ?></tbody></table>
                <table class="table table-bordered table-striped"><thead><tr><th>Jenis Anggota</th><th>Jumlah</th></tr></thead>
                    <tbody><?php foreach ($data['anggota_jenis'] as $r): ?><tr><td><?= e($r['jenis_anggota']) ?></td><td><?= (int)$r['jumlah'] ?></td></tr><?php endforeach; ?></tbody></table>
            </div>
        </div>
    <?php endif; ?>
    </div>
</div>

<?php if ($arsip): ?>
<div class="box box-default no-print">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-archive"></i> Arsip Laporan Kinerja</h3></div>
    <div class="box-body">
        <?php foreach ($arsip as $a): ?>
            <details style="margin-bottom:8px">
                <summary><b>Periode <?= e(BULAN_ID[(int)date('n', strtotime($a['periode']))] . ' ' . date('Y', strtotime($a['periode']))) ?></b>
                    <span class="text-muted"> &mdash; dibuat <?= e(tgl_waktu($a['created_at'])) ?> oleh <?= e($a['nama_admin'] ?? '-') ?></span></summary>
                <pre style="margin-top:8px"><?= e($a['isi_laporan']) ?></pre>
            </details>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
<?php tampil_footer($js !== '' ? ['chart.min.js'] : [], $js);
