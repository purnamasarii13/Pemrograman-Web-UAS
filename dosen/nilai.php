<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('dosen');
$pageTitle = 'Input Nilai';
$pdo = db(); $dosen = current_dosen();
[$defSem, $defTahun] = semester_aktif_values();
$filterSem = $_GET['semester'] ?? '';
$filterTahun = $_GET['tahun'] ?? '';
$sqlKelas = "SELECT k.id, mk.kode, mk.nama mata_kuliah, k.nama_kelas, k.semester, k.tahun_akademik
    FROM kelas k JOIN mata_kuliah mk ON mk.id=k.mata_kuliah_id WHERE k.dosen_id=?";
$paramsKelas = [$dosen['id']];
if ($filterSem !== '') { $sqlKelas .= " AND k.semester=?"; $paramsKelas[] = $filterSem; }
if ($filterTahun !== '') { $sqlKelas .= " AND k.tahun_akademik=?"; $paramsKelas[] = $filterTahun; }
$sqlKelas .= " ORDER BY k.tahun_akademik DESC, FIELD(k.semester,'Ganjil','Genap'), mk.kode";
$kelasListStmt = $pdo->prepare($sqlKelas);
$kelasListStmt->execute($paramsKelas);
$kelasList = $kelasListStmt->fetchAll();
$kelasId = (int)($_GET['kelas_id'] ?? ($kelasList[0]['id'] ?? 0));
try {
    if (is_post()) {
        $kelasId = (int)$_POST['kelas_id'];
        $cek = $pdo->prepare("SELECT id FROM kelas WHERE id=? AND dosen_id=?"); $cek->execute([$kelasId,$dosen['id']]); if (!$cek->fetch()) throw new RuntimeException('Kelas tidak valid.');
        foreach (($_POST['mahasiswa_id'] ?? []) as $idx=>$mahasiswaId) {
            $tugas = max(0, min(100, (float)($_POST['tugas'][$idx] ?? 0)));
            $uts = max(0, min(100, (float)($_POST['uts'][$idx] ?? 0)));
            $uas = max(0, min(100, (float)($_POST['uas'][$idx] ?? 0)));
            $akhir = round(($tugas*0.3)+($uts*0.3)+($uas*0.4),2);
            $huruf = nilai_huruf($akhir);
            $pdo->prepare("INSERT INTO nilai (mahasiswa_id,kelas_id,tugas,uts,uas,nilai_akhir,nilai_huruf) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE tugas=VALUES(tugas), uts=VALUES(uts), uas=VALUES(uas), nilai_akhir=VALUES(nilai_akhir), nilai_huruf=VALUES(nilai_huruf), updated_at=NOW()")
                ->execute([(int)$mahasiswaId,$kelasId,$tugas,$uts,$uas,$akhir,$huruf]);
        }
        set_flash('success','Nilai berhasil disimpan.'); redirect('dosen/nilai.php?kelas_id='.$kelasId);
    }
} catch (Throwable $e) { set_flash('danger',$e->getMessage()); redirect('dosen/nilai.php?kelas_id='.$kelasId); }
$students = [];
if ($kelasId) {
    $stmt = $pdo->prepare("SELECT m.id,m.nim,m.nama,n.tugas,n.uts,n.uas,n.nilai_akhir,n.nilai_huruf FROM krs_detail kd JOIN krs kr ON kr.id=kd.krs_id AND kr.status='disetujui' JOIN mahasiswa m ON m.id=kr.mahasiswa_id LEFT JOIN nilai n ON n.mahasiswa_id=m.id AND n.kelas_id=kd.kelas_id WHERE kd.kelas_id=? ORDER BY m.nama");
    $stmt->execute([$kelasId]); $students = $stmt->fetchAll();
}
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php';
?>
<div class="card shadow-sm mb-3 no-print"><div class="card-body"><form method="get" class="row g-2 align-items-end"><div class="col-md-2"><label class="form-label">Semester</label><select name="semester" class="form-select"><option value="">Semua</option><option <?= $filterSem==='Ganjil'?'selected':'' ?>>Ganjil</option><option <?= $filterSem==='Genap'?'selected':'' ?>>Genap</option></select></div><div class="col-md-3"><label class="form-label">Tahun</label><input name="tahun" class="form-control" value="<?= e($filterTahun) ?>" placeholder="<?= e($defTahun) ?>"></div><div class="col-md-5"><label class="form-label">Pilih Kelas</label><select name="kelas_id" class="form-select"><?php foreach ($kelasList as $k): ?><option value="<?= $k['id'] ?>" <?= $kelasId==$k['id']?'selected':'' ?>><?= e($k['kode'].' - '.$k['mata_kuliah'].' ('.$k['nama_kelas'].') '.$k['semester'].' '.$k['tahun_akademik']) ?></option><?php endforeach; ?></select></div><div class="col-md-2"><button class="btn btn-primary w-100">Tampilkan</button></div></form></div></div>
<form method="post" class="card shadow-sm"><input type="hidden" name="kelas_id" value="<?= e($kelasId) ?>"><div class="card-header bg-transparent d-flex justify-content-between"><span class="fw-semibold">Daftar Mahasiswa</span><button class="btn btn-primary" <?= !$students?'disabled':'' ?>>Simpan Nilai</button></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Mahasiswa</th><th>Tugas</th><th>UTS</th><th>UAS</th><th>Akhir</th><th>Huruf</th></tr></thead><tbody><?php foreach ($students as $i=>$s): ?><tr><td><input type="hidden" name="mahasiswa_id[]" value="<?= $s['id'] ?>"><div class="fw-semibold"><?= e($s['nama']) ?></div><small class="text-muted"><?= e($s['nim']) ?></small></td><td><input type="number" min="0" max="100" step="0.01" name="tugas[]" class="form-control" value="<?= e($s['tugas'] ?? 0) ?>"></td><td><input type="number" min="0" max="100" step="0.01" name="uts[]" class="form-control" value="<?= e($s['uts'] ?? 0) ?>"></td><td><input type="number" min="0" max="100" step="0.01" name="uas[]" class="form-control" value="<?= e($s['uas'] ?? 0) ?>"></td><td><?= e($s['nilai_akhir'] ?? '-') ?></td><td><?= $s['nilai_huruf'] ? '<span class="badge text-bg-primary">'.e($s['nilai_huruf']).'</span>' : '-' ?></td></tr><?php endforeach; ?><?php if (!$students): ?><tr><td colspan="6" class="text-center text-muted py-4">Belum ada mahasiswa dengan KRS disetujui pada kelas ini.</td></tr><?php endif; ?></tbody></table></div></form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
