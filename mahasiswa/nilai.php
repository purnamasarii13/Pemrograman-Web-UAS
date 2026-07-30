<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('mahasiswa');
$pageTitle = 'Nilai dan IPS';
$mhs = current_mahasiswa();
[$defSem, $defTahun] = semester_aktif_values();
$semester = $_GET['semester'] ?? '';
$tahun = $_GET['tahun'] ?? '';
$sql = "SELECT n.*, mk.kode, mk.nama AS mata_kuliah, mk.sks, k.semester, k.tahun_akademik FROM nilai n JOIN kelas k ON k.id=n.kelas_id JOIN mata_kuliah mk ON mk.id=k.mata_kuliah_id WHERE n.mahasiswa_id=?";
$params = [$mhs['id']];
if ($semester !== '') { $sql .= " AND k.semester=?"; $params[]=$semester; }
if ($tahun !== '') { $sql .= " AND k.tahun_akademik=?"; $params[]=$tahun; }
$sql .= " ORDER BY k.tahun_akademik DESC,k.semester,mk.kode";
$stmt = db()->prepare($sql); $stmt->execute($params); $nilai = $stmt->fetchAll();
$ipsSem = $semester !== '' ? $semester : $defSem;
$ipsTahun = $tahun !== '' ? $tahun : $defTahun;
$ips = hitung_ip((int)$mhs['id'], $ipsSem, $ipsTahun);
$ipk = hitung_ip((int)$mhs['id']);
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php';
?>
<div class="row g-3 mb-3"><div class="col-md-6"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">IPS (<?= e($ipsSem) ?> <?= e($ipsTahun) ?>)</div><div class="display-6 fw-bold"><?= e($ips) ?></div></div></div></div><div class="col-md-6"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">IPK</div><div class="display-6 fw-bold"><?= e($ipk) ?></div></div></div></div></div>
<div class="card shadow-sm mb-3 no-print"><div class="card-body"><form method="get" class="row g-2 align-items-end"><div class="col-md-4"><label class="form-label">Semester</label><select name="semester" class="form-select"><option value="">Semua</option><option <?= $semester==='Ganjil'?'selected':'' ?>>Ganjil</option><option <?= $semester==='Genap'?'selected':'' ?>>Genap</option></select></div><div class="col-md-4"><label class="form-label">Tahun Akademik</label><input name="tahun" class="form-control" value="<?= e($tahun) ?>" placeholder="2025/2026"></div><div class="col-md-4"><button class="btn btn-primary w-100">Filter</button></div></form></div></div>
<div class="card shadow-sm"><div class="card-header bg-transparent fw-semibold">Riwayat Nilai</div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Mata Kuliah</th><th>Semester</th><th>Tugas</th><th>UTS</th><th>UAS</th><th>Akhir</th><th>Huruf</th></tr></thead><tbody>
<?php foreach ($nilai as $n): ?><tr><td><div class="fw-semibold"><?= e($n['kode'].' - '.$n['mata_kuliah']) ?></div><small class="text-muted"><?= e($n['sks']) ?> SKS</small></td><td><?= e($n['semester'].' '.$n['tahun_akademik']) ?></td><td><?= e($n['tugas']) ?></td><td><?= e($n['uts']) ?></td><td><?= e($n['uas']) ?></td><td><?= e($n['nilai_akhir']) ?></td><td><span class="badge text-bg-primary"><?= e($n['nilai_huruf']) ?></span></td></tr><?php endforeach; ?>
<?php if (!$nilai): ?><tr><td colspan="7" class="text-center text-muted py-4">Belum ada nilai.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
