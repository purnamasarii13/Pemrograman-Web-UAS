<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$pageTitle = 'Mata Kuliah & Jadwal';
$pdo = db();
try {
    if (is_post()) {
        $action = $_POST['action'] ?? '';
        if ($action === 'create_mk') {
            $pdo->prepare("INSERT INTO mata_kuliah (kode,nama,sks,semester,program_studi_id,status) VALUES (?,?,?,?,?,?)")
                ->execute([trim($_POST['kode']),trim($_POST['nama']),(int)$_POST['sks'],(int)$_POST['semester'],(int)$_POST['program_studi_id'],$_POST['status']]);
            set_flash('success','Mata kuliah ditambahkan.');
        } elseif ($action === 'update_mk') {
            $pdo->prepare("UPDATE mata_kuliah SET kode=?, nama=?, sks=?, semester=?, program_studi_id=?, status=? WHERE id=?")
                ->execute([trim($_POST['kode']),trim($_POST['nama']),(int)$_POST['sks'],(int)$_POST['semester'],(int)$_POST['program_studi_id'],$_POST['status'],(int)$_POST['id']]);
            set_flash('success','Mata kuliah diperbarui.');
        } elseif ($action === 'delete_mk') {
            $pdo->prepare("DELETE FROM mata_kuliah WHERE id=?")->execute([(int)$_POST['id']]); set_flash('success','Mata kuliah dihapus.');
        } elseif ($action === 'create_kelas') {
            $pdo->beginTransaction();
            $pdo->prepare("INSERT INTO kelas (mata_kuliah_id,dosen_id,nama_kelas,semester,tahun_akademik,kapasitas,status) VALUES (?,?,?,?,?,?, 'aktif')")
                ->execute([(int)$_POST['mata_kuliah_id'],(int)$_POST['dosen_id'],trim($_POST['nama_kelas']),$_POST['semester'],trim($_POST['tahun_akademik']),(int)$_POST['kapasitas']]);
            $kelasId = (int)$pdo->lastInsertId();
            $pdo->prepare("INSERT INTO jadwal (kelas_id,hari,jam_mulai,jam_selesai,ruangan) VALUES (?,?,?,?,?)")
                ->execute([$kelasId,$_POST['hari'],$_POST['jam_mulai'],$_POST['jam_selesai'],trim($_POST['ruangan'])]);
            $pdo->commit(); set_flash('success','Kelas dan jadwal ditambahkan.');
        } elseif ($action === 'delete_kelas') {
            $id = (int)$_POST['id'];
            if (kelas_dipakai_krs($id)) {
                $pdo->prepare("UPDATE kelas SET status = 'nonaktif' WHERE id = ?")->execute([$id]);
                set_flash('success', 'Kelas sudah dipakai KRS. Status diubah menjadi nonaktif (tidak dihapus).');
            } else {
                $pdo->prepare('DELETE FROM kelas WHERE id = ?')->execute([$id]);
                set_flash('success', 'Kelas dihapus.');
            }
        } elseif ($action === 'aktifkan_kelas') {
            $pdo->prepare("UPDATE kelas SET status = 'aktif' WHERE id = ?")->execute([(int)$_POST['id']]);
            set_flash('success', 'Kelas diaktifkan kembali.');
        }
        redirect('admin/mata_kuliah.php');
    }
} catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); set_flash('danger',$e->getMessage()); redirect('admin/mata_kuliah.php'); }
$prodi = $pdo->query("SELECT * FROM program_studi ORDER BY nama")->fetchAll();
$dosen = $pdo->query("SELECT * FROM dosen WHERE status='aktif' ORDER BY nama")->fetchAll();
$mk = $pdo->query("SELECT mk.*, ps.nama AS prodi FROM mata_kuliah mk LEFT JOIN program_studi ps ON ps.id=mk.program_studi_id ORDER BY mk.semester,mk.kode")->fetchAll();
[$defSem, $defTahun] = semester_aktif_values();
$kelas = $pdo->query("SELECT k.*, mk.kode, mk.nama AS mata_kuliah, mk.sks, d.nama AS dosen, j.hari, j.jam_mulai, j.jam_selesai, j.ruangan,
    (SELECT COUNT(*) FROM krs_detail kd WHERE kd.kelas_id = k.id) AS dipakai_krs
    FROM kelas k JOIN mata_kuliah mk ON mk.id=k.mata_kuliah_id LEFT JOIN dosen d ON d.id=k.dosen_id LEFT JOIN jadwal j ON j.kelas_id=k.id
    ORDER BY k.tahun_akademik DESC,k.semester,mk.kode")->fetchAll();
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php';
?>
<ul class="nav nav-pills mb-3" role="tablist"><li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tabMK">Mata Kuliah</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabKelas">Kelas & Jadwal</button></li></ul>
<div class="tab-content">
<div class="tab-pane fade show active" id="tabMK">
    <div class="card shadow-sm mb-3"><div class="card-body"><form method="post" class="row g-2 align-items-end"><input type="hidden" name="action" value="create_mk"><div class="col-md-2"><label class="form-label">Kode</label><input name="kode" class="form-control" required></div><div class="col-md-3"><label class="form-label">Nama</label><input name="nama" class="form-control" required></div><div class="col-md-1"><label class="form-label">SKS</label><input type="number" name="sks" class="form-control" value="3" min="1" max="6" required></div><div class="col-md-2"><label class="form-label">Semester</label><input type="number" name="semester" class="form-control" value="1" min="1" required></div><div class="col-md-2"><label class="form-label">Prodi</label><select name="program_studi_id" class="form-select" required><?php foreach ($prodi as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['nama']) ?></option><?php endforeach; ?></select></div><div class="col-md-1"><label class="form-label">Status</label><select name="status" class="form-select"><option value="aktif">Aktif</option><option value="nonaktif">Nonaktif</option></select></div><div class="col-md-1"><button class="btn btn-primary w-100">Tambah</button></div></form></div></div>
    <div class="card shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Kode</th><th>Mata Kuliah</th><th>SKS</th><th>Semester</th><th>Prodi</th><th>Status</th><th></th></tr></thead><tbody><?php foreach ($mk as $m): ?><tr><form method="post"><input type="hidden" name="action" value="update_mk"><input type="hidden" name="id" value="<?= $m['id'] ?>"><td><input name="kode" class="form-control form-control-sm" value="<?= e($m['kode']) ?>"></td><td><input name="nama" class="form-control form-control-sm" value="<?= e($m['nama']) ?>"></td><td><input type="number" name="sks" class="form-control form-control-sm" value="<?= e($m['sks']) ?>"></td><td><input type="number" name="semester" class="form-control form-control-sm" value="<?= e($m['semester']) ?>"></td><td><select name="program_studi_id" class="form-select form-select-sm"><?php foreach ($prodi as $p): ?><option value="<?= $p['id'] ?>" <?= $m['program_studi_id']==$p['id']?'selected':'' ?>><?= e($p['nama']) ?></option><?php endforeach; ?></select></td><td><select name="status" class="form-select form-select-sm"><option value="aktif" <?= $m['status']==='aktif'?'selected':'' ?>>Aktif</option><option value="nonaktif" <?= $m['status']==='nonaktif'?'selected':'' ?>>Nonaktif</option></select></td><td class="text-end"><button class="btn btn-sm btn-outline-primary">Simpan</button></form><form method="post" class="d-inline" onsubmit="return confirm('Hapus mata kuliah?')"><input type="hidden" name="action" value="delete_mk"><input type="hidden" name="id" value="<?= $m['id'] ?>"><button class="btn btn-sm btn-outline-danger">Hapus</button></form></td></tr><?php endforeach; ?></tbody></table></div></div>
</div>
<div class="tab-pane fade" id="tabKelas">
    <div class="card shadow-sm mb-3"><div class="card-body"><form method="post" class="row g-2 align-items-end"><input type="hidden" name="action" value="create_kelas"><div class="col-md-3"><label class="form-label">Mata Kuliah</label><select name="mata_kuliah_id" class="form-select" required><?php foreach ($mk as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['kode'].' - '.$m['nama']) ?></option><?php endforeach; ?></select></div><div class="col-md-2"><label class="form-label">Dosen</label><select name="dosen_id" class="form-select" required><?php foreach ($dosen as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['nama']) ?></option><?php endforeach; ?></select></div><div class="col-md-1"><label class="form-label">Kelas</label><input name="nama_kelas" class="form-control" value="A"></div><div class="col-md-2"><label class="form-label">Semester</label><select name="semester" class="form-select"><option <?= $defSem==='Ganjil'?'selected':'' ?>>Ganjil</option><option <?= $defSem==='Genap'?'selected':'' ?>>Genap</option></select></div><div class="col-md-2"><label class="form-label">Tahun</label><input name="tahun_akademik" class="form-control" value="<?= e($defTahun) ?>"></div><div class="col-md-2"><label class="form-label">Kapasitas</label><input type="number" name="kapasitas" class="form-control" value="40"></div><div class="col-md-2"><label class="form-label">Hari</label><select name="hari" class="form-select"><option>Senin</option><option>Selasa</option><option>Rabu</option><option>Kamis</option><option>Jumat</option><option>Sabtu</option></select></div><div class="col-md-2"><label class="form-label">Mulai</label><input type="time" name="jam_mulai" class="form-control" value="08:00"></div><div class="col-md-2"><label class="form-label">Selesai</label><input type="time" name="jam_selesai" class="form-control" value="09:40"></div><div class="col-md-2"><label class="form-label">Ruangan</label><input name="ruangan" class="form-control" value="R101"></div><div class="col-md-2"><button class="btn btn-primary w-100">Tambah Kelas</button></div></form></div></div>
    <div class="card shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Mata Kuliah</th><th>Dosen</th><th>Jadwal</th><th>Kapasitas</th><th>Status</th><th></th></tr></thead><tbody><?php foreach ($kelas as $k): $st = $k['status'] ?? 'aktif'; ?><tr class="<?= $st === 'nonaktif' ? 'table-secondary' : '' ?>"><td><div class="fw-semibold"><?= e($k['kode'].' - '.$k['mata_kuliah']) ?></div><small class="text-muted">Kelas <?= e($k['nama_kelas']) ?> | <?= e($k['sks']) ?> SKS | <?= e($k['semester'].' '.$k['tahun_akademik']) ?></small></td><td><?= e($k['dosen']) ?></td><td><?= e($k['hari'].' '.$k['jam_mulai'].'-'.$k['jam_selesai']) ?><br><small class="text-muted"><?= e($k['ruangan']) ?></small></td><td><?= e($k['kapasitas']) ?></td><td><?= status_badge($st) ?></td><td class="text-end"><?php if ($st === 'nonaktif'): ?><form method="post" class="d-inline"><input type="hidden" name="action" value="aktifkan_kelas"><input type="hidden" name="id" value="<?= $k['id'] ?>"><button type="submit" class="btn btn-sm btn-outline-success">Aktifkan</button></form><?php endif; ?><form method="post" class="d-inline" onsubmit="return confirm('<?= (int)$k['dipakai_krs'] > 0 ? 'Kelas dipakai KRS akan dinonaktifkan.' : 'Hapus kelas?' ?>')"><input type="hidden" name="action" value="delete_kelas"><input type="hidden" name="id" value="<?= $k['id'] ?>"><button type="submit" class="btn btn-sm btn-outline-danger"><?= (int)$k['dipakai_krs'] > 0 ? 'Nonaktifkan' : 'Hapus' ?></button></form></td></tr><?php endforeach; ?></tbody></table></div></div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
