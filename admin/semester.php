<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$pageTitle = 'Semester Aktif';
$pageDescription = 'Kelola pembukaan semester akademik. Hanya satu semester yang boleh aktif.';
$pdo = db();

try {
    if (is_post()) {
        $action = $_POST['action'] ?? '';
        if ($action === 'buka_semester') {
            $semester = $_POST['semester'] ?? '';
            $tahun = trim($_POST['tahun_akademik'] ?? '');
            if (!in_array($semester, ['Ganjil', 'Genap'], true) || $tahun === '') {
                throw new RuntimeException('Semester dan tahun akademik wajib diisi.');
            }
            $pdo->beginTransaction();
            $pdo->exec("UPDATE semester_aktif SET status = 'nonaktif' WHERE status = 'aktif'");
            $pdo->prepare("INSERT INTO semester_aktif (semester, tahun_akademik, status) VALUES (?, ?, 'aktif')")
                ->execute([$semester, $tahun]);
            $pdo->commit();
            set_flash('success', "Semester $semester $tahun berhasil dibuka. Semester sebelumnya otomatis nonaktif.");
        }
        redirect('admin/semester.php');
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('danger', $e->getMessage());
    redirect('admin/semester.php');
}

$aktif = semester_aktif();
$riwayat = $pdo->query("SELECT * FROM semester_aktif ORDER BY id DESC")->fetchAll();

$nextSem = 'Genap';
$nextTahun = CURRENT_TAHUN_AKADEMIK;
if ($aktif) {
    if ($aktif['semester'] === 'Ganjil') {
        $nextSem = 'Genap';
        $nextTahun = $aktif['tahun_akademik'];
    } else {
        $nextSem = 'Ganjil';
        if (preg_match('/^(\d{4})\/(\d{4})$/', $aktif['tahun_akademik'], $m)) {
            $nextTahun = ((int)$m[1] + 1) . '/' . ((int)$m[2] + 1);
        } else {
            $nextTahun = $aktif['tahun_akademik'];
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
echo ui_page_header();
?>
<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card shadow-sm h-100 border-primary border-2">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-calendar-check me-1"></i> Semester Aktif Saat Ini
            </div>
            <div class="card-body">
                <?php if ($aktif): ?>
                    <div class="display-6 fw-bold text-primary mb-2"><?= e($aktif['semester']) ?></div>
                    <p class="h5 text-muted mb-3"><?= e($aktif['tahun_akademik']) ?></p>
                    <?= status_badge('aktif') ?>
                    <p class="small text-muted mt-3 mb-0">
                        Mahasiswa mengajukan KRS, keuangan membuat tagihan, dan admin membuat kelas untuk semester ini.
                    </p>
                <?php else: ?>
                    <div class="empty-state py-4">
                        <i class="bi bi-exclamation-circle"></i>
                        <p>Belum ada semester aktif. Buka semester baru di form sebelah.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header fw-semibold">Buka Semester Baru</div>
            <div class="card-body">
                <div class="alert alert-warning small">
                    <i class="bi bi-info-circle me-1"></i>
                    Saat semester baru dibuka, semester aktif sebelumnya otomatis menjadi <strong>nonaktif</strong>.
                    Data KRS dan nilai semester lama <strong>tidak dihapus</strong>.
                </div>
                <form method="post" class="row g-3">
                    <input type="hidden" name="action" value="buka_semester">
                    <div class="col-md-6">
                        <label class="form-label">Semester</label>
                        <select name="semester" class="form-select" required>
                            <option value="Ganjil" <?= $nextSem === 'Ganjil' ? 'selected' : '' ?>>Ganjil</option>
                            <option value="Genap" <?= $nextSem === 'Genap' ? 'selected' : '' ?>>Genap</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tahun Akademik</label>
                        <input name="tahun_akademik" class="form-control" value="<?= e($nextTahun) ?>" placeholder="2025/2026" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary" onclick="return confirm('Buka semester baru? Semester aktif saat ini akan dinonaktifkan.')">
                            <i class="bi bi-play-circle me-1"></i>Buka Semester
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header fw-semibold"><i class="bi bi-clock-history me-1"></i> Riwayat Semester</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>Semester</th><th>Tahun Akademik</th><th>Status</th><th>Dibuka</th></tr>
            </thead>
            <tbody>
            <?php foreach ($riwayat as $r): ?>
                <tr>
                    <td class="fw-semibold"><?= e($r['semester']) ?></td>
                    <td><?= e($r['tahun_akademik']) ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td><?= e($r['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?= !$riwayat ? ui_empty_state('Belum ada riwayat semester.', 'bi-calendar', 4) : '' ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
