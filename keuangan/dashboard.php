<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('keuangan');
$pageTitle = 'Dashboard Keuangan';
$pageDescription = 'Ringkasan tagihan, pembayaran, dan status keuangan mahasiswa.';
$breadcrumbs = [['Dashboard', null]];
$totalTagihan = db()->query("SELECT COALESCE(SUM(jumlah),0) total FROM tagihan")->fetch()['total'];
$totalDibayar = db()->query("SELECT COALESCE(SUM(jumlah_bayar),0) total FROM pembayaran")->fetch()['total'];
$lunas = count_rows('tagihan', "status='lunas'");
$belum = count_rows('tagihan', "status='belum_lunas'");
$recent = db()->query("SELECT p.*,m.nama,m.nim,t.jenis FROM pembayaran p JOIN mahasiswa m ON m.id=p.mahasiswa_id JOIN tagihan t ON t.id=p.tagihan_id ORDER BY p.id DESC LIMIT 8")->fetchAll();
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
echo ui_page_header();
?>
<div class="row g-3 mb-4">
    <?= ui_stat_card('Total Tagihan', rupiah($totalTagihan), 'bi-receipt', 'primary') ?>
    <?= ui_stat_card('Total Dibayar', rupiah($totalDibayar), 'bi-cash-stack', 'success') ?>
    <?= ui_stat_card('Lunas', (string)$lunas, 'bi-check-circle', 'info') ?>
    <?= ui_stat_card('Belum Lunas', (string)$belum, 'bi-exclamation-circle', 'orange') ?>
</div>
<div class="card shadow-sm">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-clock-history me-1"></i> Pembayaran Terbaru</span>
        <?= ui_table_search('tblPembayaranRecent', 'Cari pembayaran...') ?>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tblPembayaranRecent">
            <thead><tr><th>Mahasiswa</th><th>Tagihan</th><th>Tanggal</th><th>Jumlah</th><th>Metode</th></tr></thead>
            <tbody>
            <?php foreach ($recent as $r): ?>
                <tr>
                    <td><div class="fw-semibold"><?= e($r['nama']) ?></div><small class="text-muted"><?= e($r['nim']) ?></small></td>
                    <td><?= e($r['jenis']) ?></td>
                    <td><?= e($r['tanggal_bayar']) ?></td>
                    <td class="text-money"><?= rupiah($r['jumlah_bayar']) ?></td>
                    <td><span class="badge text-bg-light text-dark border"><?= e($r['metode']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?= !$recent ? ui_empty_state('Belum ada pembayaran tercatat.', 'bi-cash', 5) : '' ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
