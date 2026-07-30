<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('kaprodi');
$pageTitle = 'Dashboard Kaprodi';
$pageDescription = 'Monitoring akademik program studi dan persetujuan KRS.';
$breadcrumbs = [['Dashboard', null]];
$angkatan = db()->query("SELECT angkatan, COUNT(*) total FROM mahasiswa GROUP BY angkatan ORDER BY angkatan DESC")->fetchAll();
$krsStat = db()->query("SELECT status, COUNT(*) total FROM krs GROUP BY status")->fetchAll();
$mkStat = db()->query("SELECT ps.nama prodi, COUNT(mk.id) total FROM program_studi ps LEFT JOIN mata_kuliah mk ON mk.program_studi_id=ps.id GROUP BY ps.id")->fetchAll();
$menunggu = count_rows('krs', "status='menunggu'");
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
echo ui_page_header();
?>
<div class="row g-3 mb-4">
    <?= ui_stat_card('Total Mahasiswa', (string)count_rows('mahasiswa'), 'bi-people', 'primary') ?>
    <?= ui_stat_card('Mata Kuliah', (string)count_rows('mata_kuliah'), 'bi-journal-bookmark', 'purple') ?>
    <?= ui_stat_card('KRS Masuk', (string)count_rows('krs'), 'bi-card-checklist', 'info') ?>
    <?= ui_stat_card('KRS Menunggu', (string)$menunggu, 'bi-hourglass-split', 'warning') ?>
</div>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header"><i class="bi bi-bar-chart me-1"></i> Mahasiswa per Angkatan</div>
            <div class="card-body">
                <?php foreach ($angkatan as $a): ?>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <span class="fw-semibold"><?= e($a['angkatan']) ?></span>
                        <span class="badge text-bg-primary"><?= e($a['total']) ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if (!$angkatan): ?><div class="empty-state py-3"><i class="bi bi-people"></i><p>Belum ada data mahasiswa.</p></div><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header"><i class="bi bi-card-checklist me-1"></i> Status KRS</div>
            <div class="card-body">
                <?php foreach ($krsStat as $s): ?>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <?= status_badge($s['status']) ?>
                        <span class="fw-bold"><?= e($s['total']) ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if (!$krsStat): ?><div class="empty-state py-3"><i class="bi bi-inbox"></i><p>Belum ada KRS.</p></div><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header"><i class="bi bi-journal me-1"></i> Mata Kuliah per Prodi</div>
            <div class="card-body">
                <?php foreach ($mkStat as $m): ?>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <span><?= e($m['prodi']) ?></span>
                        <span class="badge text-bg-secondary"><?= e($m['total']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<div class="row g-3 mt-1">
    <div class="col-md-6">
        <a href="<?= base_url('kaprodi/laporan_krs.php') ?>" class="btn btn-outline-primary w-100 py-3"><i class="bi bi-card-checklist me-2"></i>Laporan KRS</a>
    </div>
    <div class="col-md-6">
        <a href="<?= base_url('kaprodi/laporan_nilai.php') ?>" class="btn btn-outline-success w-100 py-3"><i class="bi bi-award me-2"></i>Laporan Nilai</a>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
