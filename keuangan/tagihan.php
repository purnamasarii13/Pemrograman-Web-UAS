<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('keuangan');
$pageTitle = 'Kelola Tagihan';
$pdo=db();
try{
    if(is_post()){
        $action=$_POST['action']??'';
        if($action==='create'){
            $pdo->prepare("INSERT INTO tagihan (mahasiswa_id,semester,tahun_akademik,jenis,jumlah,status,jatuh_tempo) VALUES (?,?,?,?,?,?,?)")
                ->execute([(int)$_POST['mahasiswa_id'],$_POST['semester'],trim($_POST['tahun_akademik']),trim($_POST['jenis']),(float)$_POST['jumlah'],$_POST['status'],$_POST['jatuh_tempo']]);
            set_flash('success','Tagihan berhasil dibuat.');
        }elseif($action==='update'){
            $pdo->prepare("UPDATE tagihan SET mahasiswa_id=?, semester=?, tahun_akademik=?, jenis=?, jumlah=?, status=?, jatuh_tempo=? WHERE id=?")
                ->execute([(int)$_POST['mahasiswa_id'],$_POST['semester'],trim($_POST['tahun_akademik']),trim($_POST['jenis']),(float)$_POST['jumlah'],$_POST['status'],$_POST['jatuh_tempo'],(int)$_POST['id']]);
            set_flash('success','Tagihan diperbarui.');
        }elseif($action==='delete'){
            $pdo->prepare("DELETE FROM tagihan WHERE id=?")->execute([(int)$_POST['id']]); set_flash('success','Tagihan dihapus.');
        }
        redirect('keuangan/tagihan.php');
    }
}catch(Throwable $e){ set_flash('danger',$e->getMessage()); redirect('keuangan/tagihan.php'); }
$mahasiswa=$pdo->query("SELECT id,nim,nama FROM mahasiswa ORDER BY nama")->fetchAll();
[$defSem, $defTahun] = semester_aktif_values();
$tagihan=$pdo->query("SELECT t.*,m.nim,m.nama,COALESCE(SUM(p.jumlah_bayar),0) dibayar FROM tagihan t JOIN mahasiswa m ON m.id=t.mahasiswa_id LEFT JOIN pembayaran p ON p.tagihan_id=t.id GROUP BY t.id ORDER BY t.tahun_akademik DESC, FIELD(t.semester,'Ganjil','Genap'), t.id DESC")->fetchAll();
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php';
?>
<div class="alert alert-info small mb-3"><i class="bi bi-info-circle me-1"></i>Semester aktif: <strong><?= e(semester_aktif_label()) ?></strong>. Buat tagihan sesuai semester yang akan dibuka mahasiswa untuk KRS.</div>
<div class="card shadow-sm mb-3"><div class="card-header bg-transparent fw-semibold">Buat Tagihan SPP</div><div class="card-body"><form method="post" class="row g-2 align-items-end"><input type="hidden" name="action" value="create"><div class="col-md-3"><label class="form-label">Mahasiswa</label><select name="mahasiswa_id" class="form-select" required><?php foreach($mahasiswa as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['nim'].' - '.$m['nama']) ?></option><?php endforeach; ?></select></div><div class="col-md-2"><label class="form-label">Semester</label><select name="semester" class="form-select"><option <?= $defSem==='Ganjil'?'selected':'' ?>>Ganjil</option><option <?= $defSem==='Genap'?'selected':'' ?>>Genap</option></select></div><div class="col-md-2"><label class="form-label">Tahun</label><input name="tahun_akademik" class="form-control" value="<?= e($defTahun) ?>"></div><div class="col-md-2"><label class="form-label">Jenis</label><input name="jenis" class="form-control" value="SPP"></div><div class="col-md-2"><label class="form-label">Jumlah</label><input type="number" name="jumlah" class="form-control" value="3500000"></div><div class="col-md-2"><label class="form-label">Jatuh Tempo</label><input type="date" name="jatuh_tempo" class="form-control" value="<?= e(date('Y-m-d', strtotime('+30 days'))) ?>"></div><div class="col-md-1"><label class="form-label">Status</label><select name="status" class="form-select"><option value="belum_lunas">Belum</option><option value="lunas">Lunas</option></select></div><div class="col-md-12"><button type="submit" class="btn btn-primary">Tambah Tagihan</button></div></form></div></div>
<div class="card shadow-sm">
    <div class="card-header bg-transparent fw-semibold">Data Tagihan</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Mahasiswa</th><th>Tagihan</th><th>Nominal</th><th>Dibayar</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($tagihan as $t): ?>
                <tr>
                    <td><div class="fw-semibold"><?= e($t['nama']) ?></div><small class="text-muted"><?= e($t['nim']) ?></small></td>
                    <td><?= e($t['jenis']) ?><br><small><?= e($t['semester'].' '.$t['tahun_akademik'].' | '.$t['jatuh_tempo']) ?></small></td>
                    <td><?= rupiah($t['jumlah']) ?></td>
                    <td><?= rupiah($t['dibayar']) ?></td>
                    <td><?= status_badge($t['status']) ?></td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#edit<?= $t['id'] ?>">Edit</button>
                        <form method="post" class="d-inline" onsubmit="return confirm('Hapus tagihan?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$tagihan): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada tagihan.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php foreach ($tagihan as $t): ?>
<div class="modal fade" id="edit<?= $t['id'] ?>" tabindex="-1" aria-labelledby="editLabel<?= $t['id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $t['id'] ?>">
            <div class="modal-header">
                <h5 class="modal-title" id="editLabel<?= $t['id'] ?>">Edit Tagihan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Mahasiswa</label>
                        <select name="mahasiswa_id" class="form-select">
                            <?php foreach ($mahasiswa as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= $t['mahasiswa_id'] == $m['id'] ? 'selected' : '' ?>><?= e($m['nim'].' - '.$m['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Semester</label>
                        <select name="semester" class="form-select">
                            <option <?= $t['semester'] === 'Ganjil' ? 'selected' : '' ?>>Ganjil</option>
                            <option <?= $t['semester'] === 'Genap' ? 'selected' : '' ?>>Genap</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tahun</label>
                        <input name="tahun_akademik" class="form-control" value="<?= e($t['tahun_akademik']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jenis</label>
                        <input name="jenis" class="form-control" value="<?= e($t['jenis']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jumlah</label>
                        <input type="number" name="jumlah" class="form-control" value="<?= e($t['jumlah']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jatuh Tempo</label>
                        <input type="date" name="jatuh_tempo" class="form-control" value="<?= e($t['jatuh_tempo']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="belum_lunas" <?= $t['status'] === 'belum_lunas' ? 'selected' : '' ?>>Belum Lunas</option>
                            <option value="lunas" <?= $t['status'] === 'lunas' ? 'selected' : '' ?>>Lunas</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
