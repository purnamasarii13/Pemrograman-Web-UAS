<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/export.php';
require_role('mahasiswa');
$pageTitle = 'Transkrip Nilai';
$mhs = current_mahasiswa();
$stmt = db()->prepare("SELECT n.*, mk.kode, mk.nama AS mata_kuliah, mk.sks, k.semester, k.tahun_akademik FROM nilai n JOIN kelas k ON k.id=n.kelas_id JOIN mata_kuliah mk ON mk.id=k.mata_kuliah_id WHERE n.mahasiswa_id=? ORDER BY k.tahun_akademik,mk.kode");
$stmt->execute([$mhs['id']]);
$nilai = $stmt->fetchAll();
$rows = array_map(fn($n) => [$n['kode'],$n['mata_kuliah'],$n['sks'],$n['semester'].' '.$n['tahun_akademik'],$n['nilai_akhir'],$n['nilai_huruf']], $nilai);
$ipk = hitung_ip((int)$mhs['id']);
if (($_GET['export'] ?? '') === 'pdf') {
    pdf_download('transkrip_'.$mhs['nim'].'.pdf', 'Transkrip Nilai Sederhana', ['Kode','Mata Kuliah','SKS','Semester','Nilai','Huruf'], $rows, ['Nama: '.$mhs['nama'], 'NIM: '.$mhs['nim'], 'Program Studi: '.$mhs['program_studi'], 'IPK: '.$ipk]);
}
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3 no-print"><p class="text-muted mb-0">Transkrip sederhana berisi seluruh nilai yang sudah diinput dosen.</p><a href="?export=pdf" class="btn btn-outline-danger"><i class="bi bi-filetype-pdf me-1"></i>Cetak PDF</a></div>
<div class="card shadow-sm"><div class="card-body">
    <div class="text-center mb-4"><h4 class="fw-bold mb-1">TRANSKRIP NILAI SEMENTARA</h4><div class="text-muted"><?= APP_NAME ?></div></div>
    <div class="row mb-3"><div class="col-md-6"><table class="table table-borderless table-sm"><tr><td width="130">Nama</td><td>: <?= e($mhs['nama']) ?></td></tr><tr><td>NIM</td><td>: <?= e($mhs['nim']) ?></td></tr><tr><td>Program Studi</td><td>: <?= e($mhs['program_studi']) ?></td></tr></table></div><div class="col-md-6 text-md-end"><div class="text-muted small">IPK Kumulatif</div><div class="display-6 fw-bold"><?= e($ipk) ?></div></div></div>
    <div class="table-responsive"><table class="table table-bordered align-middle"><thead><tr><th>No</th><th>Kode</th><th>Mata Kuliah</th><th>SKS</th><th>Semester</th><th>Nilai</th><th>Huruf</th></tr></thead><tbody><?php foreach ($nilai as $i=>$n): ?><tr><td><?= $i+1 ?></td><td><?= e($n['kode']) ?></td><td><?= e($n['mata_kuliah']) ?></td><td><?= e($n['sks']) ?></td><td><?= e($n['semester'].' '.$n['tahun_akademik']) ?></td><td><?= e($n['nilai_akhir']) ?></td><td><?= e($n['nilai_huruf']) ?></td></tr><?php endforeach; ?><?php if (!$nilai): ?><tr><td colspan="7" class="text-center text-muted py-4">Belum ada nilai.</td></tr><?php endif; ?></tbody></table></div>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
