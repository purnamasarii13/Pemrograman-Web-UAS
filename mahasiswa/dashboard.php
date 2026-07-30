<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('mahasiswa');
$pageTitle = 'Dashboard Mahasiswa';
$mhs = current_mahasiswa();
if (!$mhs) { set_flash('danger','Profil mahasiswa belum dibuat oleh admin.'); redirect('auth/logout.php'); }
$ipk = hitung_ip((int)$mhs['id']);
[$semAktif, $tahunAktif] = semester_aktif_values();
$ips = hitung_ip((int)$mhs['id'], $semAktif, $tahunAktif);
$keuanganLunas = mahasiswa_keuangan_lunas((int)$mhs['id'], $semAktif, $tahunAktif);
$stmt = db()->prepare("SELECT * FROM krs WHERE mahasiswa_id=? AND semester=? AND tahun_akademik=? ORDER BY id DESC LIMIT 1");
$stmt->execute([$mhs['id'], $semAktif, $tahunAktif]);
$krs = $stmt->fetch();
$jadwal = get_approved_classes_for_mahasiswa((int)$mhs['id'], $semAktif, $tahunAktif);
$stmt = db()->prepare("SELECT mk.nama, SUM(a.status='Hadir') hadir, COUNT(a.id) total, (SUM(a.status='Hadir')/COUNT(a.id)) persentase FROM absensi a JOIN kelas k ON k.id=a.kelas_id JOIN mata_kuliah mk ON mk.id=k.mata_kuliah_id WHERE a.mahasiswa_id=? GROUP BY k.id HAVING total > 0 ORDER BY persentase ASC LIMIT 4");
try { $stmt->execute([$mhs['id']]); $absensi = $stmt->fetchAll(); } catch (Throwable $e) { $absensi = []; }
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php';
?>
<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3"><div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">IPK Kumulatif</div><div class="display-6 fw-bold"><?= e($ipk) ?></div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">IPS Semester Aktif</div><div class="display-6 fw-bold"><?= e($ips) ?></div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">Status Keuangan</div><div class="mt-2"><?= $keuanganLunas ? status_badge('lunas') : status_badge('belum_lunas') ?></div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">Status KRS</div><div class="mt-2"><?= $krs ? status_badge($krs['status']) : '<span class="badge text-bg-secondary">Belum KRS</span>' ?></div></div></div></div>
</div>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card shadow-sm h-100"><div class="card-body">
            <h5 class="card-title">Profil Singkat</h5>
            <div class="d-flex align-items-center gap-3 mb-3"><div class="avatar-sm bg-primary text-white"><?= e(strtoupper(substr($mhs['nama'],0,1))) ?></div><div><div class="fw-semibold"><?= e($mhs['nama']) ?></div><small class="text-muted"><?= e($mhs['nim']) ?></small></div></div>
            <div class="small text-muted">Email</div><div class="mb-2"><?= e($mhs['email']) ?></div>
            <div class="small text-muted">Program Studi</div><div class="mb-2"><?= e($mhs['program_studi']) ?></div>
            <div class="small text-muted">Dosen Wali</div><div><?= e($mhs['dosen_wali'] ?? '-') ?></div>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm h-100"><div class="card-header bg-transparent fw-semibold">Jadwal Kuliah</div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Hari</th><th>Jam</th><th>Mata Kuliah</th><th>Ruangan</th></tr></thead><tbody>
            <?php foreach (array_slice($jadwal,0,6) as $j): ?><tr><td><?= e($j['hari']) ?></td><td><?= e(substr($j['jam_mulai'],0,5).' - '.substr($j['jam_selesai'],0,5)) ?></td><td><div class="fw-semibold"><?= e($j['mata_kuliah']) ?></div><small class="text-muted"><?= e($j['dosen']) ?></small></td><td><?= e($j['ruangan']) ?></td></tr><?php endforeach; ?>
            <?php if (!$jadwal): ?><tr><td colspan="4" class="text-center text-muted py-4">Belum ada jadwal disetujui.</td></tr><?php endif; ?>
        </tbody></table></div></div>
    </div>
</div>
<div class="card shadow-sm mt-3"><div class="card-header bg-transparent fw-semibold">Notifikasi Absensi</div><div class="card-body">
    <?php $adaPeringatan=false; foreach ($absensi as $a): $persen = $a['total'] ? round($a['hadir']/$a['total']*100,1) : 0; if ($persen < 85): $adaPeringatan=true; ?>
        <div class="alert alert-warning mb-2"><i class="bi bi-exclamation-triangle me-1"></i>Kehadiran <b><?= e($a['nama']) ?></b> <?= e($persen) ?>%, mendekati/melewati batas minimum 80%.</div>
    <?php endif; endforeach; ?>
    <?php if (!$adaPeringatan): ?><p class="text-muted mb-0">Tidak ada peringatan absensi.</p><?php endif; ?>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
