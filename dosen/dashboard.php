<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('dosen');
$pageTitle = 'Dashboard Dosen';
$pageDescription = 'Kelas yang Anda ampu dan aktivitas akademik semester ini.';
$breadcrumbs = [['Dashboard', null]];
$dosen = current_dosen();
if (!$dosen) { set_flash('danger','Profil dosen belum dibuat oleh admin.'); redirect('auth/logout.php'); }
$stmt = db()->prepare("SELECT k.*, mk.kode, mk.nama mata_kuliah, mk.sks, SUM(CASE WHEN kr.id IS NOT NULL THEN 1 ELSE 0 END) jumlah_mahasiswa FROM kelas k JOIN mata_kuliah mk ON mk.id=k.mata_kuliah_id LEFT JOIN krs_detail kd ON kd.kelas_id=k.id LEFT JOIN krs kr ON kr.id=kd.krs_id AND kr.status='disetujui' WHERE k.dosen_id=? GROUP BY k.id ORDER BY k.tahun_akademik DESC,k.semester,mk.kode");
$stmt->execute([$dosen['id']]); $kelas = $stmt->fetchAll();
$stmt = db()->prepare("SELECT COUNT(*) total FROM krs kr JOIN mahasiswa m ON m.id=kr.mahasiswa_id WHERE m.dosen_wali_id=? AND kr.status='menunggu'");
$stmt->execute([$dosen['id']]); $pendingKrs = (int)$stmt->fetch()['total'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
echo ui_page_header();
?>
<div class="row g-3 mb-4">
    <?= ui_stat_card('Kelas Diampu', (string)count($kelas), 'bi-collection', 'primary') ?>
    <?= ui_stat_card('KRS Menunggu', (string)$pendingKrs, 'bi-hourglass-split', 'warning') ?>
    <?= ui_stat_card('Jabatan', e($dosen['jabatan'] ?: '-'), 'bi-award', 'success') ?>
</div>
<?php if ($kelas): ?>
<div class="row g-3">
    <?php foreach ($kelas as $k): ?>
    <div class="col-md-6 col-xl-4">
        <div class="class-card">
            <div class="class-title"><?= e($k['kode'].' — '.$k['mata_kuliah']) ?></div>
            <div class="class-meta mb-2">
                <span class="schedule-chip"><i class="bi bi-people"></i> Kelas <?= e($k['nama_kelas']) ?></span>
                <span class="schedule-chip"><i class="bi bi-book"></i> <?= e($k['sks']) ?> SKS</span>
            </div>
            <div class="class-meta"><?= e($k['semester'].' · '.$k['tahun_akademik']) ?> · <?= e($k['jumlah_mahasiswa']) ?> mahasiswa</div>
            <div class="class-actions">
                <a href="<?= base_url('dosen/nilai.php?kelas_id='.$k['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Nilai</a>
                <a href="<?= base_url('dosen/absensi.php?kelas_id='.$k['id']) ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-check2-square me-1"></i>Absensi</a>
                <a href="<?= base_url('dosen/materi.php?kelas_id='.$k['id']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-upload me-1"></i>Materi</a>
                <a href="<?= base_url('dosen/tugas.php?kelas_id='.$k['id']) ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-clipboard-check me-1"></i>Tugas</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="card shadow-sm"><div class="card-body empty-state"><i class="bi bi-collection"></i><p>Belum ada kelas yang diampu pada semester ini.</p></div></div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
