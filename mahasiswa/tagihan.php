<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('mahasiswa');
$pageTitle = 'Tagihan dan Pembayaran';
$mhs = current_mahasiswa();
$stmt = db()->prepare("SELECT t.*, COALESCE(SUM(p.jumlah_bayar),0) dibayar FROM tagihan t LEFT JOIN pembayaran p ON p.tagihan_id=t.id WHERE t.mahasiswa_id=? GROUP BY t.id ORDER BY t.tahun_akademik DESC,t.semester DESC,t.id DESC");
$stmt->execute([$mhs['id']]); $tagihan = $stmt->fetchAll();
$stmt = db()->prepare("SELECT p.*, t.jenis, t.semester, t.tahun_akademik FROM pembayaran p JOIN tagihan t ON t.id=p.tagihan_id WHERE p.mahasiswa_id=? ORDER BY p.tanggal_bayar DESC,p.id DESC");
$stmt->execute([$mhs['id']]); $pembayaran = $stmt->fetchAll();
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php';
?>
<div class="card shadow-sm mb-3"><div class="card-header bg-transparent fw-semibold">Daftar Tagihan</div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Jenis</th><th>Semester</th><th>Nominal</th><th>Dibayar</th><th>Jatuh Tempo</th><th>Status</th></tr></thead><tbody><?php foreach ($tagihan as $t): ?><tr><td><?= e($t['jenis']) ?></td><td><?= e($t['semester'].' '.$t['tahun_akademik']) ?></td><td><?= rupiah($t['jumlah']) ?></td><td><?= rupiah($t['dibayar']) ?></td><td><?= e($t['jatuh_tempo']) ?></td><td><?= status_badge($t['status']) ?></td></tr><?php endforeach; ?><?php if (!$tagihan): ?><tr><td colspan="6" class="text-center text-muted py-4">Belum ada tagihan.</td></tr><?php endif; ?></tbody></table></div></div>
<div class="card shadow-sm"><div class="card-header bg-transparent fw-semibold">Riwayat Pembayaran</div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Tanggal</th><th>Tagihan</th><th>Jumlah</th><th>Metode</th><th>Keterangan</th></tr></thead><tbody><?php foreach ($pembayaran as $p): ?><tr><td><?= e($p['tanggal_bayar']) ?></td><td><?= e($p['jenis'].' - '.$p['semester'].' '.$p['tahun_akademik']) ?></td><td><?= rupiah($p['jumlah_bayar']) ?></td><td><?= e($p['metode']) ?></td><td><?= e($p['keterangan']) ?></td></tr><?php endforeach; ?><?php if (!$pembayaran): ?><tr><td colspan="5" class="text-center text-muted py-4">Belum ada pembayaran.</td></tr><?php endif; ?></tbody></table></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
