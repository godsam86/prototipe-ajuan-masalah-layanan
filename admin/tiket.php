<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/views/layout.php';

Auth::wajib(['Admin']);
$f = [
    'status'   => get('status'),
    'urgensi'  => get('urgensi'),
    'kategori' => get('kategori'),
    'q'        => get('q'),
];
$ring = PermintaanLayanan::ringkasStatus();
$total = PermintaanLayanan::hitung($f);
$pg = paginasi($total, 10);
$daftar = PermintaanLayanan::daftar($f, $pg['per'], $pg['offset']);
$bulanIni = DashboardManfaat::ringkasan(date('Y-m-01'), date('Y-m-d', strtotime('first day of next month')));

tampil_header('Daftar Tiket Layanan', 'tiket', ['ikon' => 'ticket', 'crumb' => 'Penanganan Layanan > Tiket Layanan']);
?>
<div class="row">
    <div class="col-md-3 col-xs-6"><div class="small-box" style="background:#c94a29;color:#fff;border-radius:4px"><div class="inner"><h3><?= $ring['baru'] ?></h3><p>Tiket Baru</p></div><div class="icon" style="opacity:.3"><i class="fa fa-inbox"></i></div></div></div>
    <div class="col-md-3 col-xs-6"><div class="small-box" style="background:#e08e0b;color:#fff;border-radius:4px"><div class="inner"><h3><?= $ring['diproses'] ?></h3><p>Sedang Diproses</p></div><div class="icon" style="opacity:.3"><i class="fa fa-wrench"></i></div></div></div>
    <div class="col-md-3 col-xs-6"><div class="small-box" style="background:#00965e;color:#fff;border-radius:4px"><div class="inner"><h3><?= $ring['selesai'] ?></h3><p>Selesai</p></div><div class="icon" style="opacity:.3"><i class="fa fa-check-circle"></i></div></div></div>
    <div class="col-md-3 col-xs-6"><div class="small-box" style="background:#6f42c1;color:#fff;border-radius:4px"><div class="inner"><h3 style="font-size:28px;padding-top:6px"><?= e(durasi_jam($bulanIni['rata_jam'])) ?></h3><p>Rata-rata Penanganan (bulan ini)</p></div><div class="icon" style="opacity:.3"><i class="fa fa-clock-o"></i></div></div></div>
</div>

<div class="box box-default">
    <div class="box-body">
        <form method="get" class="form-inline" style="margin-bottom:14px">
            <input type="text" name="q" value="<?= e($f['q']) ?>" class="form-control input-sm" placeholder="Cari no. tiket / pelapor / uraian" style="min-width:230px">
            <select name="status" class="form-control input-sm"><option value="">Semua Status</option>
                <?php foreach (PermintaanLayanan::STATUS as $s): ?><option<?= selected($f['status'], $s) ?>><?= e($s) ?></option><?php endforeach; ?></select>
            <select name="urgensi" class="form-control input-sm"><option value="">Semua Urgensi</option>
                <?php foreach (PermintaanLayanan::URGENSI as $s): ?><option<?= selected($f['urgensi'], $s) ?>><?= e($s) ?></option><?php endforeach; ?></select>
            <select name="kategori" class="form-control input-sm"><option value="">Semua Kategori</option>
                <?php foreach (Referensi::nama('kategori', false) as $s): ?><option<?= selected($f['kategori'], $s) ?>><?= e($s) ?></option><?php endforeach; ?></select>
            <button class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Filter</button>
            <a href="<?= e(url('admin/tiket.php')) ?>" class="btn btn-default btn-sm">Reset</a>
        </form>
        <div class="table-modern table-responsive">
            <table class="table" style="width:100%">
                <thead><tr><th style="width:110px">ID Tiket</th><th>Pelapor</th><th>Kategori</th><th>Urgensi</th><th>Dikirim</th><th style="width:130px">Status</th><th style="width:110px">Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($daftar as $t): ?>
                    <tr>
                        <td><strong><?= e($t['nomor_tiket']) ?></strong></td>
                        <td><?= e($t['nama_pelapor']) ?><br><small class="text-muted"><?= e($t['nomor_anggota'] ?? '') ?></small></td>
                        <td><?= e($t['kategori']) ?><br><small class="text-muted"><?= e($t['jenis']) ?></small></td>
                        <td><?= badge_urgensi($t['urgensi']) ?></td>
                        <td><?= e(tgl_waktu($t['tanggal_dibuat'])) ?></td>
                        <td><?= badge_status($t['status']) ?></td>
                        <td><a class="btn-action-outline" href="<?= e(url('admin/tiket_detail.php?id=' . (int)$t['id_tiket'])) ?>"><?= $t['status'] === 'Baru' ? 'Tangani' : 'Lihat' ?></a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$daftar): ?><tr><td colspan="7" class="text-center text-muted">Tidak ada tiket yang sesuai.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="text-muted" style="margin-top:8px"><?= angka($total) ?> tiket</div>
        <?= paginasi_html($pg) ?>
    </div>
</div>
<?php tampil_footer(); ?>
