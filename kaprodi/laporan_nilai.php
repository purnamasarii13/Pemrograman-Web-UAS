<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/export.php';
require_role('kaprodi');
$pageTitle = 'Laporan Nilai';
[$defSem, $defTahun] = semester_aktif_values();
$semester = $_GET['semester'] ?? $defSem;
$tahun = $_GET['tahun'] ?? $defTahun;
$sql = "SELECT m.nim,m.nama,mk.kode,mk.nama mata_kuliah,mk.sks,k.semester,k.tahun_akademik,n.nilai_akhir,n.nilai_huruf
    FROM nilai n
    JOIN mahasiswa m ON m.id=n.mahasiswa_id
    JOIN kelas k ON k.id=n.kelas_id
    JOIN mata_kuliah mk ON mk.id=k.mata_kuliah_id
    WHERE 1=1";
$params = [];
if ($semester !== '') { $sql .= " AND k.semester=?"; $params[] = $semester; }
if ($tahun !== '') { $sql .= " AND k.tahun_akademik=?"; $params[] = $tahun; }
$sql .= " ORDER BY m.nama,mk.kode";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();
$headers = ['NIM','Mahasiswa','Kode','Mata Kuliah','SKS','Semester','Nilai Akhir','Huruf'];
$rows = array_map(fn($r) => [$r['nim'],$r['nama'],$r['kode'],$r['mata_kuliah'],$r['sks'],$r['semester'].' '.$r['tahun_akademik'],$r['nilai_akhir'],$r['nilai_huruf']], $data);
$q = http_build_query(['semester' => $semester, 'tahun' => $tahun]);
if (($_GET['format'] ?? '') === 'pdf') pdf_download('laporan_nilai.pdf','Laporan Nilai',$headers,$rows,['Dicetak: '.date('d-m-Y H:i')]);
if (($_GET['format'] ?? '') === 'excel') excel_download('laporan_nilai.xls',$headers,$rows);
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="card shadow-sm mb-3 no-print"><div class="card-body"><form method="get" class="row g-2 align-items-end">
<div class="col-md-3"><label class="form-label">Semester</label><select name="semester" class="form-select"><option value="">Semua</option><option <?= $semester==='Ganjil'?'selected':'' ?>>Ganjil</option><option <?= $semester==='Genap'?'selected':'' ?>>Genap</option></select></div>
<div class="col-md-4"><label class="form-label">Tahun Akademik</label><input name="tahun" class="form-control" value="<?= e($tahun) ?>"></div>
<div class="col-md-2"><button class="btn btn-primary w-100">Filter</button></div>
<div class="col-md-3"><a class="btn btn-outline-danger" href="?<?= e($q) ?>&format=pdf">Export PDF</a> <a class="btn btn-outline-success" href="?<?= e($q) ?>&format=excel">Export Excel</a></div>
</form></div></div>
<div class="card shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><?php foreach($headers as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach($data as $r): ?><tr><td><?= e($r['nim']) ?></td><td><?= e($r['nama']) ?></td><td><?= e($r['kode']) ?></td><td><?= e($r['mata_kuliah']) ?></td><td><?= e($r['sks']) ?></td><td><?= e($r['semester'].' '.$r['tahun_akademik']) ?></td><td><?= e($r['nilai_akhir']) ?></td><td><span class="badge text-bg-primary"><?= e($r['nilai_huruf']) ?></span></td></tr><?php endforeach; ?><?php if(!$data): ?><tr><td colspan="8" class="text-center text-muted py-4">Tidak ada nilai.</td></tr><?php endif; ?></tbody></table></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
