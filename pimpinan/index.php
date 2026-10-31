<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/views/layout.php';

Auth::wajib(['Pimpinan']);

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

$k = DashboardManfaat::ringkasan($awal, $akhir);
$tren = DashboardManfaat::tren($awal, $akhir);
$kategori = DashboardManfaat::kategoriTerbanyak($awal, $akhir, 3);
$labels = [];
$nilai = [];
foreach ($tren as $r) {
    $labels[] = Laporan::namaBulan($r['periode']);
    $nilai[] = (float)$r['nilai'];
}

tampil_header('Dashboard Manfaat Layanan', 'dashboard', ['ikon' => 'bar-chart-o', 'crumb' => 'Pimpinan > Manfaat Layanan']);
?>
<div class="box box-default" style="border-radius:8px">
    <div class="box-body" style="padding:24px 30px">
        <form method="get" class="form-inline pull-right no-print" style="margin-top:-4px">
            <select name="tahun" class="form-control input-sm"><?php foreach ($tahunList as $y): ?><option<?= selected($tahun, $y) ?>><?= $y ?></option><?php endforeach; ?></select>
            <select name="periode" class="form-control input-sm"><?= opsi_periode($periode) ?></select>
            <button class="btn btn-primary btn-sm"><i class="fa fa-filter"></i> Tampilkan</button>
        </form>
        <h2 style="font-size:20px;font-weight:700;color:#1e293b;margin:0 0 22px;padding-bottom:12px;border-bottom:1px solid #e2e8f0">
            Ringkasan Capaian Manfaat &mdash; <?= e($label) ?>
        </h2>

        <?php if ($k['total'] === 0): ?>
            <div class="alert alert-warning"><i class="fa fa-info-circle"></i> Data layanan pada periode ini tidak tersedia. Silakan pilih periode lain.</div>
        <?php else: ?>
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">Tingkat Penyelesaian Insiden</div>
                <div class="kpi-value"><?= e(angka($k['penyelesaian'], 0)) ?>%</div>
                <div class="kpi-sub"><?= $k['selesai'] ?> dari <?= $k['total'] ?> tiket selesai</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Rata-rata Waktu Penanganan</div>
                <div class="kpi-value"><?= e(durasi_jam($k['rata_jam'])) ?></div>
                <div class="kpi-sub">dari tiket dikirim hingga selesai</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Kepuasan Pemustaka</div>
                <div class="kpi-value"><?= $k['kepuasan'] === null ? '-' : e(angka($k['kepuasan'], 1)) . ' / 5' ?></div>
                <div class="kpi-sub"><?= $k['jml_rating'] ?> penilaian</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Capaian Target Layanan</div>
                <div class="kpi-value"><?= $k['target'] === null ? '-' : e(angka($k['target'], 0)) . '%' ?></div>
                <div class="kpi-sub">selesai dalam target waktu</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-7">
                <div class="content-box">
                    <h3 class="box-title-x">Tren Penyelesaian Insiden per Bulan</h3>
                    <?php if ($nilai): ?>
                        <div style="position:relative;height:230px"><canvas id="trenChart"></canvas></div>
                    <?php else: ?><p class="text-muted">Data tren belum tersedia.</p><?php endif; ?>
                </div>
            </div>
            <div class="col-md-5">
                <div class="content-box">
                    <h3 class="box-title-x">Kategori Insiden Terbanyak</h3>
                    <ul class="incidents-list">
                        <?php foreach ($kategori as $i => $c): ?>
                            <li><?= $i + 1 ?>. <?= e(label_kategori($c['kategori'])) ?> <span class="text-muted">(<?= (int)$c['jumlah'] ?> tiket)</span></li>
                        <?php endforeach; ?>
                    </ul>
                    <a class="btn-teal-pill" style="width:100%;text-align:center" href="<?= e(url('unduh.php?tahun=' . $tahun . '&periode=' . urlencode($periode))) ?>">
                        <i class="fa fa-download"></i> Unduh Laporan Kinerja
                    </a>
                    <a class="btn btn-default btn-block" style="margin-top:10px;border-radius:25px" href="<?= e(url('laporan.php?jenis=layanan&tahun=' . $tahun . '&periode=' . urlencode($periode))) ?>">
                        <i class="fa fa-bar-chart-o"></i> Lihat Laporan Lengkap
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div style="margin-top:6px;padding-top:12px;border-top:1px dashed #cbd5e1;font-size:12.5px;color:#64748b">
            <i class="fa fa-info-circle"></i> Indikator dihitung otomatis dari tiket layanan pemustaka yang dikirim pada periode terpilih dan ditangani oleh petugas.
        </div>
    </div>
</div>
<?php
$js = '';
if ($k['total'] > 0 && $nilai) {
    $js = 'new Chart(document.getElementById("trenChart"), {type:"line", data:{labels:' . json_encode($labels) . ',
        datasets:[{label:"Penyelesaian (%)", data:' . json_encode($nilai) . ', borderColor:"#2b827a", backgroundColor:"rgba(43,130,122,.08)",
        borderWidth:3, pointBackgroundColor:"#2b827a", pointBorderColor:"#fff", pointBorderWidth:2, pointRadius:5, tension:.25, fill:true}]},
        options:{responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}},
        scales:{x:{grid:{display:false}}, y:{min:0, max:100, ticks:{callback:function(v){return v+"%";}}}}}});';
}
tampil_footer(['chart.min.js'], $js);
