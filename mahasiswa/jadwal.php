<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('mahasiswa');
$pageTitle = 'Jadwal Kuliah';
$mhs = current_mahasiswa();
$jadwal = $mhs ? get_approved_classes_for_mahasiswa((int)$mhs['id']) : [];
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php';
?>
<div class="card shadow-sm"><div class="card-header bg-transparent fw-semibold">Jadwal Kuliah yang Disetujui</div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Hari</th><th>Jam</th><th>Mata Kuliah</th><th>Dosen</th><th>Ruangan</th><th>Semester</th></tr></thead><tbody>
<?php foreach ($jadwal as $j): ?><tr><td><?= e($j['hari']) ?></td><td><?= e(substr($j['jam_mulai'],0,5).' - '.substr($j['jam_selesai'],0,5)) ?></td><td><div class="fw-semibold"><?= e($j['kode'].' - '.$j['mata_kuliah']) ?></div><small class="text-muted"><?= e($j['sks']) ?> SKS | Kelas <?= e($j['nama_kelas']) ?></small></td><td><?= e($j['dosen']) ?></td><td><?= e($j['ruangan']) ?></td><td><?= e($j['semester'].' '.$j['tahun_akademik']) ?></td></tr><?php endforeach; ?>
<?php if (!$jadwal): ?><tr><td colspan="6" class="text-center text-muted py-4">Belum ada jadwal. Ajukan KRS terlebih dahulu dan tunggu approval.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
