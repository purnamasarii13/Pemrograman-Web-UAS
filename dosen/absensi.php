<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('dosen');
$pageTitle = 'Input Absensi';
$pdo = db(); $dosen = current_dosen();
$stmt = $pdo->prepare("SELECT k.id, mk.kode, mk.nama mata_kuliah, k.nama_kelas FROM kelas k JOIN mata_kuliah mk ON mk.id=k.mata_kuliah_id WHERE k.dosen_id=? ORDER BY mk.kode"); $stmt->execute([$dosen['id']]); $kelasList = $stmt->fetchAll();
$kelasId = (int)($_GET['kelas_id'] ?? ($_POST['kelas_id'] ?? ($kelasList[0]['id'] ?? 0)));
$tanggal = $_GET['tanggal'] ?? ($_POST['tanggal'] ?? date('Y-m-d')); $pertemuan = (int)($_GET['pertemuan'] ?? ($_POST['pertemuan'] ?? 1));
try {
    if (is_post()) {
        $cek = $pdo->prepare("SELECT id FROM kelas WHERE id=? AND dosen_id=?"); $cek->execute([$kelasId,$dosen['id']]); if (!$cek->fetch()) throw new RuntimeException('Kelas tidak valid.');
        foreach (($_POST['mahasiswa_id'] ?? []) as $idx=>$mahasiswaId) {
            $status = $_POST['status'][$idx] ?? 'Hadir';
            if (!in_array($status, ['Hadir','Izin','Sakit','Alfa'], true)) $status='Hadir';
            $pdo->prepare("INSERT INTO absensi (mahasiswa_id,kelas_id,tanggal,pertemuan,status,keterangan) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status), keterangan=VALUES(keterangan), updated_at=NOW()")
                ->execute([(int)$mahasiswaId,$kelasId,$tanggal,$pertemuan,$status,trim($_POST['keterangan'][$idx] ?? '')]);
        }
        set_flash('success','Absensi berhasil disimpan.'); redirect('dosen/absensi.php?kelas_id='.$kelasId.'&tanggal='.$tanggal.'&pertemuan='.$pertemuan);
    }
} catch (Throwable $e) { set_flash('danger',$e->getMessage()); redirect('dosen/absensi.php?kelas_id='.$kelasId); }
$students=[];
if ($kelasId) { $stmt=$pdo->prepare("SELECT m.id,m.nim,m.nama,a.status,a.keterangan FROM krs_detail kd JOIN krs kr ON kr.id=kd.krs_id AND kr.status='disetujui' JOIN mahasiswa m ON m.id=kr.mahasiswa_id LEFT JOIN absensi a ON a.mahasiswa_id=m.id AND a.kelas_id=kd.kelas_id AND a.tanggal=? AND a.pertemuan=? WHERE kd.kelas_id=? ORDER BY m.nama"); $stmt->execute([$tanggal,$pertemuan,$kelasId]); $students=$stmt->fetchAll(); }
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php';
?>
<div class="card shadow-sm mb-3"><div class="card-body"><form method="get" class="row g-2 align-items-end"><div class="col-md-5"><label class="form-label">Kelas</label><select name="kelas_id" class="form-select"><?php foreach ($kelasList as $k): ?><option value="<?= $k['id'] ?>" <?= $kelasId==$k['id']?'selected':'' ?>><?= e($k['kode'].' - '.$k['mata_kuliah'].' ('.$k['nama_kelas'].')') ?></option><?php endforeach; ?></select></div><div class="col-md-3"><label class="form-label">Tanggal</label><input type="date" name="tanggal" class="form-control" value="<?= e($tanggal) ?>"></div><div class="col-md-2"><label class="form-label">Pertemuan</label><input type="number" name="pertemuan" class="form-control" value="<?= e($pertemuan) ?>" min="1"></div><div class="col-md-2"><button class="btn btn-primary w-100">Tampilkan</button></div></form></div></div>
<form method="post" class="card shadow-sm"><input type="hidden" name="kelas_id" value="<?= e($kelasId) ?>"><input type="hidden" name="tanggal" value="<?= e($tanggal) ?>"><input type="hidden" name="pertemuan" value="<?= e($pertemuan) ?>"><div class="card-header bg-transparent d-flex justify-content-between"><span class="fw-semibold">Daftar Mahasiswa</span><button class="btn btn-primary" <?= !$students?'disabled':'' ?>>Simpan Absensi</button></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Mahasiswa</th><th>Status</th><th>Keterangan</th></tr></thead><tbody><?php foreach ($students as $i=>$s): ?><tr><td><input type="hidden" name="mahasiswa_id[]" value="<?= $s['id'] ?>"><div class="fw-semibold"><?= e($s['nama']) ?></div><small class="text-muted"><?= e($s['nim']) ?></small></td><td><select name="status[]" class="form-select"><option <?= ($s['status']??'Hadir')==='Hadir'?'selected':'' ?>>Hadir</option><option <?= ($s['status']??'')==='Izin'?'selected':'' ?>>Izin</option><option <?= ($s['status']??'')==='Sakit'?'selected':'' ?>>Sakit</option><option <?= ($s['status']??'')==='Alfa'?'selected':'' ?>>Alfa</option></select></td><td><input name="keterangan[]" class="form-control" value="<?= e($s['keterangan'] ?? '') ?>"></td></tr><?php endforeach; ?><?php if (!$students): ?><tr><td colspan="3" class="text-center text-muted py-4">Belum ada mahasiswa.</td></tr><?php endif; ?></tbody></table></div></form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
