<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('keuangan');
$pageTitle = 'Pembayaran';
$pdo=db();
try{
    if(is_post()){
        $tagihanId=(int)$_POST['tagihan_id'];
        $stmt=$pdo->prepare("SELECT * FROM tagihan WHERE id=?"); $stmt->execute([$tagihanId]); $t=$stmt->fetch(); if(!$t) throw new RuntimeException('Tagihan tidak ditemukan.');
        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO pembayaran (tagihan_id,mahasiswa_id,tanggal_bayar,jumlah_bayar,metode,keterangan) VALUES (?,?,?,?,?,?)")
            ->execute([$tagihanId,$t['mahasiswa_id'],$_POST['tanggal_bayar'],(float)$_POST['jumlah_bayar'],trim($_POST['metode']),trim($_POST['keterangan'] ?? '')]);
        $sum=$pdo->prepare("SELECT COALESCE(SUM(jumlah_bayar),0) total FROM pembayaran WHERE tagihan_id=?"); $sum->execute([$tagihanId]); $dibayar=(float)$sum->fetch()['total'];
        if($dibayar >= (float)$t['jumlah']) $pdo->prepare("UPDATE tagihan SET status='lunas' WHERE id=?")->execute([$tagihanId]);
        $pdo->commit(); set_flash('success','Pembayaran berhasil dicatat.'); redirect('keuangan/pembayaran.php');
    }
}catch(Throwable $e){ if($pdo->inTransaction()) $pdo->rollBack(); set_flash('danger',$e->getMessage()); redirect('keuangan/pembayaran.php'); }
$tagihan=$pdo->query("SELECT t.*,m.nim,m.nama,COALESCE(SUM(p.jumlah_bayar),0) dibayar FROM tagihan t JOIN mahasiswa m ON m.id=t.mahasiswa_id LEFT JOIN pembayaran p ON p.tagihan_id=t.id GROUP BY t.id ORDER BY t.status,t.id DESC")->fetchAll();
$pembayaran=$pdo->query("SELECT p.*,m.nim,m.nama,t.jenis,t.semester,t.tahun_akademik FROM pembayaran p JOIN mahasiswa m ON m.id=p.mahasiswa_id JOIN tagihan t ON t.id=p.tagihan_id ORDER BY p.tanggal_bayar DESC,p.id DESC")->fetchAll();
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php';
?>
<div class="card shadow-sm mb-3"><div class="card-header bg-transparent fw-semibold">Catat Pembayaran</div><div class="card-body"><form method="post" class="row g-2 align-items-end"><div class="col-md-5"><label class="form-label">Tagihan</label><select name="tagihan_id" class="form-select" required><?php foreach($tagihan as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['nim'].' - '.$t['nama'].' | '.$t['jenis'].' '.$t['semester'].' '.$t['tahun_akademik'].' | Sisa '.rupiah(max(0,$t['jumlah']-$t['dibayar']))) ?></option><?php endforeach; ?></select></div><div class="col-md-2"><label class="form-label">Tanggal</label><input type="date" name="tanggal_bayar" class="form-control" value="<?= e(date('Y-m-d')) ?>"></div><div class="col-md-2"><label class="form-label">Jumlah</label><input type="number" name="jumlah_bayar" class="form-control" required></div><div class="col-md-2"><label class="form-label">Metode</label><input name="metode" class="form-control" value="Transfer"></div><div class="col-md-1"><button class="btn btn-primary w-100">Simpan</button></div><div class="col-12"><input name="keterangan" class="form-control" placeholder="Keterangan opsional"></div></form></div></div>
<div class="card shadow-sm"><div class="card-header bg-transparent fw-semibold">Riwayat Pembayaran</div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Mahasiswa</th><th>Tagihan</th><th>Tanggal</th><th>Jumlah</th><th>Metode</th><th>Keterangan</th></tr></thead><tbody><?php foreach($pembayaran as $p): ?><tr><td><div class="fw-semibold"><?= e($p['nama']) ?></div><small class="text-muted"><?= e($p['nim']) ?></small></td><td><?= e($p['jenis'].' '.$p['semester'].' '.$p['tahun_akademik']) ?></td><td><?= e($p['tanggal_bayar']) ?></td><td><?= rupiah($p['jumlah_bayar']) ?></td><td><?= e($p['metode']) ?></td><td><?= e($p['keterangan']) ?></td></tr><?php endforeach; ?><?php if(!$pembayaran): ?><tr><td colspan="6" class="text-center text-muted py-4">Belum ada pembayaran.</td></tr><?php endif; ?></tbody></table></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
