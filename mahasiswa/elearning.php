<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('mahasiswa');
$pageTitle = 'E-Learning';
$pdo = db();
$mhs = current_mahasiswa();
try {
    if (is_post()) {
        $action = $_POST['action'] ?? '';
        if ($action === 'submit_task') {
            $tugasId = (int)$_POST['tugas_id'];
            $stmt = $pdo->prepare("SELECT t.*, k.id kelas_id FROM tugas t JOIN kelas k ON k.id=t.kelas_id JOIN krs_detail kd ON kd.kelas_id=k.id JOIN krs kr ON kr.id=kd.krs_id AND kr.status='disetujui' WHERE t.id=? AND kr.mahasiswa_id=?");
            $stmt->execute([$tugasId,$mhs['id']]); if (!$stmt->fetch()) throw new RuntimeException('Tugas tidak valid.');
            $file = upload_file('file','pengumpulan',['pdf','doc','docx','jpg','jpeg','png']);
            $pdo->prepare("INSERT INTO pengumpulan_tugas (tugas_id,mahasiswa_id,file_path,link_url,catatan) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE file_path=VALUES(file_path), link_url=VALUES(link_url), catatan=VALUES(catatan), submitted_at=NOW()")
                ->execute([$tugasId,$mhs['id'],$file,trim($_POST['link_url'] ?? ''),trim($_POST['catatan'] ?? '')]);
            set_flash('success','Tugas berhasil dikumpulkan.');
        } elseif ($action === 'forum') {
            $kelasId = (int)$_POST['kelas_id']; $isi = trim($_POST['isi'] ?? '');
            if ($isi === '') throw new RuntimeException('Isi diskusi tidak boleh kosong.');
            $pdo->prepare("INSERT INTO forum_diskusi (kelas_id,user_id,isi) VALUES (?,?,?)")->execute([$kelasId,current_user()['id'],$isi]);
            set_flash('success','Pesan diskusi dikirim.');
        }
        redirect('mahasiswa/elearning.php');
    }
} catch (Throwable $e) { set_flash('danger',$e->getMessage()); redirect('mahasiswa/elearning.php'); }
$kelas = get_approved_classes_for_mahasiswa((int)$mhs['id']);
$seen=[]; $kelasUnique=[]; foreach ($kelas as $k) { if (!isset($seen[$k['kelas_id']])) { $seen[$k['kelas_id']]=1; $kelasUnique[]=$k; } }
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php';
?>
<div class="accordion" id="elearningAccordion">
<?php foreach ($kelasUnique as $idx=>$k): ?>
    <?php
    $materi = $pdo->prepare("SELECT * FROM materi WHERE kelas_id=? ORDER BY created_at DESC"); $materi->execute([$k['kelas_id']]); $materi=$materi->fetchAll();
    $tugas = $pdo->prepare("SELECT t.*, p.id pengumpulan_id, p.submitted_at, p.nilai FROM tugas t LEFT JOIN pengumpulan_tugas p ON p.tugas_id=t.id AND p.mahasiswa_id=? WHERE t.kelas_id=? ORDER BY t.deadline DESC"); $tugas->execute([$mhs['id'],$k['kelas_id']]); $tugas=$tugas->fetchAll();
    $forum = $pdo->prepare("SELECT f.*, u.name FROM forum_diskusi f JOIN users u ON u.id=f.user_id WHERE f.kelas_id=? ORDER BY f.created_at DESC LIMIT 10"); $forum->execute([$k['kelas_id']]); $forum=$forum->fetchAll();
    $pengumuman = $pdo->prepare("SELECT * FROM pengumuman WHERE kelas_id=? ORDER BY created_at DESC LIMIT 5"); $pengumuman->execute([$k['kelas_id']]); $pengumuman=$pengumuman->fetchAll();
    ?>
    <div class="accordion-item mb-3 border-0 shadow-sm rounded-4 overflow-hidden">
        <h2 class="accordion-header"><button class="accordion-button <?= $idx?'collapsed':'' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#kelas<?= $k['kelas_id'] ?>"><b><?= e($k['kode'].' - '.$k['mata_kuliah']) ?></b><span class="ms-2 text-muted">Kelas <?= e($k['nama_kelas']) ?></span></button></h2>
        <div id="kelas<?= $k['kelas_id'] ?>" class="accordion-collapse collapse <?= $idx?'':'show' ?>" data-bs-parent="#elearningAccordion"><div class="accordion-body">
            <div class="row g-3">
                <div class="col-lg-6"><h6>Materi</h6><?php foreach ($materi as $m): ?><div class="border rounded-3 p-3 mb-2"><div class="fw-semibold"><?= e($m['judul']) ?></div><small class="text-muted"><?= e($m['tipe']) ?> | <?= e($m['created_at']) ?></small><p class="mb-1"><?= e($m['deskripsi']) ?></p><?php if ($m['file_path']): ?><a href="<?= base_url($m['file_path']) ?>" target="_blank">Download file</a><?php endif; ?><?php if ($m['link_url']): ?><a class="ms-2" href="<?= e($m['link_url']) ?>" target="_blank">Buka link</a><?php endif; ?></div><?php endforeach; ?><?php if (!$materi): ?><p class="text-muted">Belum ada materi.</p><?php endif; ?></div>
                <div class="col-lg-6"><h6>Pengumuman</h6><?php foreach ($pengumuman as $p): ?><div class="alert alert-info"><b><?= e($p['judul']) ?></b><br><?= e($p['isi']) ?></div><?php endforeach; ?><?php if (!$pengumuman): ?><p class="text-muted">Belum ada pengumuman.</p><?php endif; ?></div>
                <div class="col-12"><h6>Tugas Online</h6><div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Judul</th><th>Deadline</th><th>Status</th><th>Aksi</th></tr></thead><tbody><?php foreach ($tugas as $t): ?><tr><td><b><?= e($t['judul']) ?></b><br><small><?= e($t['deskripsi']) ?></small></td><td><?= e($t['deadline']) ?></td><td><?= $t['pengumpulan_id'] ? '<span class="badge text-bg-success">Terkumpul</span>' : '<span class="badge text-bg-warning">Belum</span>' ?> <?= $t['nilai'] !== null ? '<span class="badge text-bg-primary">Nilai '.e($t['nilai']).'</span>' : '' ?></td><td><button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#submit<?= $t['id'] ?>">Kumpulkan</button></td></tr><tr class="collapse" id="submit<?= $t['id'] ?>"><td colspan="4"><form method="post" enctype="multipart/form-data" class="row g-2"><input type="hidden" name="action" value="submit_task"><input type="hidden" name="tugas_id" value="<?= $t['id'] ?>"><div class="col-md-4"><input type="file" name="file" class="form-control"></div><div class="col-md-4"><input name="link_url" class="form-control" placeholder="Link tugas (opsional)"></div><div class="col-md-3"><input name="catatan" class="form-control" placeholder="Catatan"></div><div class="col-md-1"><button class="btn btn-primary w-100">Kirim</button></div></form></td></tr><?php endforeach; ?><?php if (!$tugas): ?><tr><td colspan="4" class="text-center text-muted">Belum ada tugas.</td></tr><?php endif; ?></tbody></table></div></div>
                <div class="col-12"><h6>Forum Diskusi</h6><form method="post" class="input-group mb-3"><input type="hidden" name="action" value="forum"><input type="hidden" name="kelas_id" value="<?= $k['kelas_id'] ?>"><input name="isi" class="form-control" placeholder="Tulis pesan diskusi..."><button class="btn btn-primary">Kirim</button></form><?php foreach ($forum as $f): ?><div class="border-start border-3 ps-3 py-2 mb-2"><div class="fw-semibold"><?= e($f['name']) ?> <small class="text-muted fw-normal"><?= e($f['created_at']) ?></small></div><div><?= e($f['isi']) ?></div></div><?php endforeach; ?><?php if (!$forum): ?><p class="text-muted">Belum ada diskusi.</p><?php endif; ?></div>
            </div>
        </div></div>
    </div>
<?php endforeach; ?>
<?php if (!$kelasUnique): ?><div class="alert alert-info">Belum ada kelas aktif dari KRS yang disetujui.</div><?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
