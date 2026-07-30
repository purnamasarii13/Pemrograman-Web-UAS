<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('mahasiswa');
$pageTitle = 'Kartu Rencana Studi';
$pdo = db();
$mhs = current_mahasiswa();
if (!$mhs) {
    redirect('auth/logout.php');
}

$sa = semester_aktif();
if (!$sa) {
    set_flash('danger', 'Semester aktif belum diatur. Hubungi admin.');
    redirect('mahasiswa/dashboard.php');
}
$semesterAktif = $sa['semester'];
$tahunAktif = $sa['tahun_akademik'];

$keuanganLunas = mahasiswa_keuangan_lunas((int)$mhs['id'], $semesterAktif, $tahunAktif);

$stmt = $pdo->prepare(
    "SELECT * FROM krs
     WHERE mahasiswa_id = ?
       AND semester = ?
       AND tahun_akademik = ?
     ORDER BY id DESC
     LIMIT 1"
);
$stmt->execute([$mhs['id'], $semesterAktif, $tahunAktif]);
$currentKrs = $stmt->fetch() ?: null;

$krsTerkunci = $currentKrs && $currentKrs['status'] === 'disetujui';
$formDisabled = !$keuanganLunas || $krsTerkunci;

try {
    if (is_post()) {
        if (!$keuanganLunas) {
            throw new RuntimeException('Lunasi tagihan ' . $semesterAktif . ' ' . $tahunAktif . ' terlebih dahulu.');
        }
        if ($krsTerkunci) {
            throw new RuntimeException('KRS sudah disetujui dan tidak dapat diubah.');
        }

        $kelasIds = array_values(array_unique(array_filter(array_map('intval', $_POST['kelas_id'] ?? []))));
        if (!$kelasIds) {
            throw new RuntimeException('Pilih minimal satu mata kuliah.');
        }

        $placeholders = implode(',', array_fill(0, count($kelasIds), '?'));
        $params = array_merge($kelasIds, [$semesterAktif, $tahunAktif]);
        $stmt = $pdo->prepare(
            "SELECT k.id, mk.sks FROM kelas k
             INNER JOIN mata_kuliah mk ON mk.id = k.mata_kuliah_id
             WHERE k.id IN ($placeholders)
               AND k.semester = ?
               AND k.tahun_akademik = ?
               AND k.status = 'aktif'
               AND mk.status = 'aktif'"
        );
        $stmt->execute($params);
        $selected = $stmt->fetchAll();
        if (count($selected) !== count($kelasIds)) {
            throw new RuntimeException('Kelas tidak valid untuk semester aktif.');
        }

        $totalSks = array_sum(array_map(fn($r) => (int)$r['sks'], $selected));
        if ($totalSks > 24) {
            throw new RuntimeException('Total SKS melebihi 24. Saat ini: ' . $totalSks . ' SKS.');
        }

        $conflicts = cek_konflik_jadwal($kelasIds);
        if ($conflicts) {
            throw new RuntimeException('Konflik jadwal: ' . implode('; ', $conflicts));
        }

        $pdo->beginTransaction();
        if ($currentKrs) {
            $krsId = (int)$currentKrs['id'];
            $pdo->prepare('DELETE FROM krs_detail WHERE krs_id = ?')->execute([$krsId]);
            $pdo->prepare("UPDATE krs SET total_sks=?, status='menunggu', catatan=NULL, updated_at=NOW() WHERE id=?")
                ->execute([$totalSks, $krsId]);
        } else {
            $pdo->prepare("INSERT INTO krs (mahasiswa_id, semester, tahun_akademik, total_sks, status) VALUES (?,?,?,?,'menunggu')")
                ->execute([$mhs['id'], $semesterAktif, $tahunAktif, $totalSks]);
            $krsId = (int)$pdo->lastInsertId();
        }
        $ins = $pdo->prepare('INSERT INTO krs_detail (krs_id, kelas_id) VALUES (?,?)');
        foreach ($kelasIds as $kid) {
            $ins->execute([$krsId, $kid]);
        }
        $pdo->commit();
        set_flash('success', 'KRS ' . $semesterAktif . ' ' . $tahunAktif . ' berhasil diajukan.');
        redirect('mahasiswa/krs.php');
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('danger', $e->getMessage());
    redirect('mahasiswa/krs.php');
}

$selectedIds = [];
if ($currentKrs) {
    $stmt = $pdo->prepare(
        "SELECT kd.kelas_id FROM krs_detail kd
         INNER JOIN krs k ON k.id = kd.krs_id
         WHERE k.mahasiswa_id = ? AND k.semester = ? AND k.tahun_akademik = ? AND k.id = ?"
    );
    $stmt->execute([$mhs['id'], $semesterAktif, $tahunAktif, $currentKrs['id']]);
    $selectedIds = array_map('intval', array_column($stmt->fetchAll(), 'kelas_id'));
}

$stmt = $pdo->prepare(
    "SELECT k.*, mk.kode, mk.nama AS mata_kuliah, mk.sks, d.nama AS dosen,
            j.hari, j.jam_mulai, j.jam_selesai, j.ruangan
     FROM kelas k
     INNER JOIN mata_kuliah mk ON mk.id = k.mata_kuliah_id
     LEFT JOIN dosen d ON d.id = k.dosen_id
     LEFT JOIN jadwal j ON j.kelas_id = k.id
     WHERE k.semester = ? AND k.tahun_akademik = ? AND k.status = 'aktif' AND mk.status = 'aktif'
     ORDER BY mk.kode, k.nama_kelas"
);
$stmt->execute([$semesterAktif, $tahunAktif]);
$kelas = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT semester, tahun_akademik, total_sks, status FROM krs
     WHERE mahasiswa_id = ? AND NOT (semester = ? AND tahun_akademik = ?)
     ORDER BY tahun_akademik DESC, FIELD(semester,'Ganjil','Genap')"
);
$stmt->execute([$mhs['id'], $semesterAktif, $tahunAktif]);
$riwayatKrs = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="alert alert-info mb-3">
    <i class="bi bi-calendar-check me-1"></i>
    <strong>Semester aktif:</strong> <?= e($semesterAktif) ?> <?= e($tahunAktif) ?>.
    KRS semester lain tetap tersimpan sebagai riwayat.
</div>
<div class="row g-3 mb-3">
    <div class="col-lg-4"><div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">Keuangan (<?= e($semesterAktif) ?>)</div><div class="mt-2"><?= $keuanganLunas ? status_badge('lunas') : status_badge('belum_lunas') ?></div><?php if (!$keuanganLunas): ?><p class="text-danger small mt-2 mb-0">Lunasi tagihan semester aktif.</p><?php endif; ?></div></div></div>
    <div class="col-lg-4"><div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">Status KRS</div><div class="mt-2"><?= $currentKrs ? status_badge($currentKrs['status']) : '<span class="badge text-bg-secondary">Belum diajukan</span>' ?></div><?php if (!empty($currentKrs['catatan'])): ?><small class="text-muted d-block mt-2"><?= e($currentKrs['catatan']) ?></small><?php endif; ?></div></div></div>
    <div class="col-lg-4"><div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">Maks SKS</div><div class="h3 fw-bold mb-0">24</div></div></div></div>
</div>
<?php if ($riwayatKrs): ?>
<div class="card shadow-sm mb-3"><div class="card-header fw-semibold">Riwayat KRS</div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Semester</th><th>SKS</th><th>Status</th></tr></thead><tbody><?php foreach ($riwayatKrs as $r): ?><tr><td><?= e($r['semester'].' '.$r['tahun_akademik']) ?></td><td><?= e($r['total_sks']) ?></td><td><?= status_badge($r['status']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php endif; ?>
<form method="post" class="card shadow-sm">
    <div class="card-header d-flex justify-content-between"><span class="fw-semibold">Kelas — <?= e($semesterAktif) ?> <?= e($tahunAktif) ?></span><button type="submit" class="btn btn-primary" <?= $formDisabled ? 'disabled' : '' ?>><i class="bi bi-send me-1"></i>Ajukan KRS</button></div>
    <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th></th><th>Mata Kuliah</th><th>Dosen</th><th>Jadwal</th><th>SKS</th></tr></thead><tbody>
    <?php foreach ($kelas as $k): ?><tr><td><input type="checkbox" class="form-check-input" name="kelas_id[]" value="<?= (int)$k['id'] ?>" <?= in_array((int)$k['id'], $selectedIds, true) ? 'checked' : '' ?> <?= $formDisabled ? 'disabled' : '' ?>></td><td><div class="fw-semibold"><?= e($k['kode'].' - '.$k['mata_kuliah']) ?></div><small class="text-muted">Kelas <?= e($k['nama_kelas']) ?></small></td><td><?= e($k['dosen'] ?? '-') ?></td><td><?= e($k['hari'].' '.substr((string)$k['jam_mulai'],0,5).'-'.substr((string)$k['jam_selesai'],0,5)) ?><br><small><?= e($k['ruangan']) ?></small></td><td><?= e($k['sks']) ?></td></tr><?php endforeach; ?>
    <?php if (!$kelas): ?><tr><td colspan="5" class="text-center text-muted py-4">Belum ada kelas aktif untuk semester ini. Hubungi admin.</td></tr><?php endif; ?>
    </tbody></table></div>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
