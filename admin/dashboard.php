<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$pageTitle = 'Dashboard Admin';
$pageDescription = 'Ringkasan data akademik dan keuangan kampus.';
$breadcrumbs = [['Dashboard', null]];
$stats = [
    ['Mahasiswa', (string)count_rows('mahasiswa'), 'bi-people', 'primary'],
    ['Dosen', (string)count_rows('dosen'), 'bi-person-video3', 'success'],
    ['Mata Kuliah', (string)count_rows('mata_kuliah'), 'bi-journal-bookmark', 'purple'],
    ['Kelas', (string)count_rows('kelas'), 'bi-collection', 'info'],
    ['Tagihan Lunas', (string)count_rows('tagihan', "status='lunas'"), 'bi-check-circle', 'success'],
    ['Belum Lunas', (string)count_rows('tagihan', "status='belum_lunas'"), 'bi-exclamation-circle', 'orange'],
];
$tagihanStat = db()->query("SELECT status, COUNT(*) total, COALESCE(SUM(jumlah),0) nominal FROM tagihan GROUP BY status")->fetchAll();
$recentTagihan = db()->query("SELECT t.*, m.nim, m.nama FROM tagihan t JOIN mahasiswa m ON m.id=t.mahasiswa_id ORDER BY t.id DESC LIMIT 6")->fetchAll();
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
echo ui_page_header();
?>
<div class="row g-3 mb-4">
    <?php foreach ($stats as $s): echo ui_stat_card($s[0], e($s[1]), $s[2], $s[3]); endforeach; ?>
</div>
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-pie-chart text-primary"></i> Statistik Pembayaran
            </div>
            <div class="card-body">
                <?php foreach ($tagihanStat as $row): ?>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div><?= status_badge($row['status']) ?></div>
                        <div class="text-end">
                            <div class="fw-semibold text-money"><?= rupiah($row['nominal']) ?></div>
                            <small class="text-muted"><?= e($row['total']) ?> tagihan</small>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (!$tagihanStat): ?>
                    <div class="empty-state py-4"><i class="bi bi-receipt"></i><p>Belum ada data tagihan.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span><i class="bi bi-clock-history text-primary me-1"></i> Tagihan Terbaru</span>
                <?= ui_table_search('tblTagihanAdmin', 'Cari mahasiswa...') ?>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tblTagihanAdmin">
                    <thead><tr><th>Mahasiswa</th><th>Tagihan</th><th>Nominal</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentTagihan as $row): ?>
                        <tr>
                            <td><div class="fw-semibold"><?= e($row['nama']) ?></div><small class="text-muted"><?= e($row['nim']) ?></small></td>
                            <td><?= e($row['jenis']) ?><br><small class="text-muted"><?= e($row['semester'].' '.$row['tahun_akademik']) ?></small></td>
                            <td class="text-money"><?= rupiah($row['jumlah']) ?></td>
                            <td><?= status_badge($row['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?= !$recentTagihan ? ui_empty_state('Belum ada tagihan terbaru.', 'bi-inbox', 4) : '' ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
