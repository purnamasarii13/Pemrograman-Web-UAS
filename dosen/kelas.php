<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('dosen');
$pageTitle = 'Kelas & Approval KRS';
$pdo = db(); $dosen = current_dosen();
try {
    if (is_post()) {
        $krsId = (int)($_POST['krs_id'] ?? 0); $status = $_POST['status'] ?? 'menunggu';
        if (!in_array($status, ['disetujui','ditolak'], true)) throw new RuntimeException('Status tidak valid.');
        $stmt = $pdo->prepare("SELECT kr.* FROM krs kr JOIN mahasiswa m ON m.id=kr.mahasiswa_id WHERE kr.id=? AND m.dosen_wali_id=?");
        $stmt->execute([$krsId,$dosen['id']]); if (!$stmt->fetch()) throw new RuntimeException('KRS tidak ditemukan atau bukan mahasiswa bimbingan Anda.');
        $pdo->prepare("UPDATE krs SET status=?, catatan=?, approved_by=?, approved_at=NOW(), updated_at=NOW() WHERE id=?")
            ->execute([$status,trim($_POST['catatan'] ?? ''),current_user()['id'],$krsId]);
        set_flash('success','Status KRS berhasil diperbarui.'); redirect('dosen/kelas.php');
    }
} catch (Throwable $e) { set_flash('danger',$e->getMessage()); redirect('dosen/kelas.php'); }
$stmt = $pdo->prepare("SELECT k.*, mk.kode, mk.nama mata_kuliah, mk.sks, j.hari,j.jam_mulai,j.jam_selesai,j.ruangan FROM kelas k JOIN mata_kuliah mk ON mk.id=k.mata_kuliah_id LEFT JOIN jadwal j ON j.kelas_id=k.id WHERE k.dosen_id=? ORDER BY k.tahun_akademik DESC,k.semester,mk.kode");
$stmt->execute([$dosen['id']]); $kelas = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT kr.*, m.nim,m.nama FROM krs kr JOIN mahasiswa m ON m.id=kr.mahasiswa_id WHERE m.dosen_wali_id=? ORDER BY FIELD(kr.status,'menunggu','ditolak','disetujui'), kr.created_at DESC");
$stmt->execute([$dosen['id']]); $krsList = $stmt->fetchAll();
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php';
?>
<div class="row g-3">
    <div class="col-lg-6"><div class="card shadow-sm h-100"><div class="card-header bg-transparent fw-semibold">Kelas Diampu</div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Mata Kuliah</th><th>Jadwal</th></tr></thead><tbody><?php foreach ($kelas as $k): ?><tr><td><div class="fw-semibold"><?= e($k['kode'].' - '.$k['mata_kuliah']) ?></div><small class="text-muted">Kelas <?= e($k['nama_kelas']) ?> | <?= e($k['sks']) ?> SKS</small></td><td><?= e($k['hari'].' '.substr($k['jam_mulai'],0,5).'-'.substr($k['jam_selesai'],0,5)) ?><br><small class="text-muted"><?= e($k['ruangan']) ?></small></td></tr><?php endforeach; ?><?php if (!$kelas): ?><tr><td colspan="2" class="text-center text-muted py-4">Belum ada kelas.</td></tr><?php endif; ?></tbody></table></div></div></div>
    <div class="col-lg-6"><div class="card shadow-sm h-100"><div class="card-header bg-transparent fw-semibold">Approval KRS Mahasiswa Bimbingan</div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Mahasiswa</th><th>Total SKS</th><th>Status</th><th>Aksi</th></tr></thead><tbody><?php foreach ($krsList as $kr): ?><tr><td><div class="fw-semibold"><?= e($kr['nama']) ?></div><small class="text-muted"><?= e($kr['nim'].' | '.$kr['semester'].' '.$kr['tahun_akademik']) ?></small></td><td><?= e($kr['total_sks']) ?></td><td><?= status_badge($kr['status']) ?></td><td><?php if ($kr['status']==='menunggu'): ?><button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#krs<?= $kr['id'] ?>">Proses</button><?php else: ?><small class="text-muted"><?= e($kr['catatan'] ?: '-') ?></small><?php endif; ?></td></tr><tr class="collapse" id="krs<?= $kr['id'] ?>"><td colspan="4"><form method="post" class="row g-2"><input type="hidden" name="krs_id" value="<?= $kr['id'] ?>"><div class="col-md-4"><select name="status" class="form-select"><option value="disetujui">Setujui</option><option value="ditolak">Tolak</option></select></div><div class="col-md-6"><input name="catatan" class="form-control" placeholder="Catatan opsional"></div><div class="col-md-2"><button class="btn btn-primary w-100">Simpan</button></div></form></td></tr><?php endforeach; ?><?php if (!$krsList): ?><tr><td colspan="4" class="text-center text-muted py-4">Belum ada KRS bimbingan.</td></tr><?php endif; ?></tbody></table></div></div></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
