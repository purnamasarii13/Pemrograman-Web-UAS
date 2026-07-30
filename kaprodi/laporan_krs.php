<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/export.php';
require_role('kaprodi');
$pageTitle = 'Laporan KRS';
[$defSem, $defTahun] = semester_aktif_values();
$status = $_GET['status'] ?? '';
$semester = $_GET['semester'] ?? $defSem;
$tahun = $_GET['tahun'] ?? $defTahun;
$sql = "SELECT kr.*, m.nim,m.nama, ps.nama prodi FROM krs kr JOIN mahasiswa m ON m.id=kr.mahasiswa_id LEFT JOIN program_studi ps ON ps.id=m.program_studi_id WHERE 1=1";
$params = [];
if ($status !== '') { $sql .= " AND kr.status=?"; $params[] = $status; }
if ($semester !== '') { $sql .= " AND kr.semester=?"; $params[] = $semester; }
if ($tahun !== '') { $sql .= " AND kr.tahun_akademik=?"; $params[] = $tahun; }
$sql .= " ORDER BY kr.created_at DESC";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();
$headers = ['NIM','Mahasiswa','Prodi','Semester','Total SKS','Status','Catatan'];
$rows = array_map(fn($r) => [$r['nim'],$r['nama'],$r['prodi'],$r['semester'].' '.$r['tahun_akademik'],$r['total_sks'],$r['status'],$r['catatan']], $data);
$q = http_build_query(['status' => $status, 'semester' => $semester, 'tahun' => $tahun]);
if (($_GET['format'] ?? '') === 'pdf') pdf_download('laporan_krs.pdf','Laporan KRS',$headers,$rows,['Dicetak: '.date('d-m-Y H:i')]);
if (($_GET['format'] ?? '') === 'excel') excel_download('laporan_krs.xls',$headers,$rows);
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="card shadow-sm mb-3 no-print"><div class="card-body"><form method="get" class="row g-2 align-items-end">
<div class="col-md-2"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">Semua</option><option value="menunggu" <?= $status==='menunggu'?'selected':'' ?>>Menunggu</option><option value="disetujui" <?= $status==='disetujui'?'selected':'' ?>>Disetujui</option><option value="ditolak" <?= $status==='ditolak'?'selected':'' ?>>Ditolak</option></select></div>
<div class="col-md-2"><label class="form-label">Semester</label><select name="semester" class="form-select"><option value="">Semua</option><option <?= $semester==='Ganjil'?'selected':'' ?>>Ganjil</option><option <?= $semester==='Genap'?'selected':'' ?>>Genap</option></select></div>
<div class="col-md-3"><label class="form-label">Tahun Akademik</label><input name="tahun" class="form-control" value="<?= e($tahun) ?>"></div>
<div class="col-md-2"><button class="btn btn-primary w-100">Filter</button></div>
<div class="col-md-3"><a class="btn btn-outline-danger w-100" href="?<?= e($q) ?>&format=pdf">Export PDF</a></div>
</form></div></div>
<div class="card shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><?php foreach($headers as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach($data as $r): ?><tr><td><?= e($r['nim']) ?></td><td><?= e($r['nama']) ?></td><td><?= e($r['prodi']) ?></td><td><?= e($r['semester'].' '.$r['tahun_akademik']) ?></td><td><?= e($r['total_sks']) ?></td><td><?= status_badge($r['status']) ?></td><td><?= e($r['catatan']) ?></td></tr><?php endforeach; ?><?php if(!$data): ?><tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data.</td></tr><?php endif; ?></tbody></table></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
