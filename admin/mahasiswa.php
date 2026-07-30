<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$pageTitle = 'Manajemen Mahasiswa';
$pdo = db();
try {
    if (is_post()) {
        $action = $_POST['action'] ?? '';
        if ($action === 'create') {
            $nama = trim($_POST['nama'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $nim = trim($_POST['nim'] ?? '');
            $prodi = (int)($_POST['program_studi_id'] ?? 0);
            $angkatan = trim($_POST['angkatan'] ?? '');
            $status = $_POST['status'] ?? 'aktif';
            if ($nama === '' || $email === '' || $nim === '' || $prodi === 0) throw new RuntimeException('Nama, email, NIM, dan program studi wajib diisi.');
            $foto = upload_file('foto', 'profil', ['jpg','jpeg','png']);
            $password = $_POST['password'] !== '' ? $_POST['password'] : 'mahasiswa123';
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role,status) VALUES (?,?,?,?,?)");
            $stmt->execute([$nama, $email, password_hash($password, PASSWORD_DEFAULT), 'mahasiswa', $status]);
            $userId = (int)$pdo->lastInsertId();
            $stmt = $pdo->prepare("INSERT INTO mahasiswa (user_id,nim,nama,email,program_studi_id,angkatan,foto,status,dosen_wali_id) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$userId,$nim,$nama,$email,$prodi,$angkatan,$foto,$status,($_POST['dosen_wali_id'] ?: null)]);
            $pdo->commit();
            set_flash('success', 'Data mahasiswa berhasil ditambahkan.');
        } elseif ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM mahasiswa WHERE id=?");
            $stmt->execute([$id]);
            $m = $stmt->fetch();
            if (!$m) throw new RuntimeException('Data mahasiswa tidak ditemukan.');
            $nama = trim($_POST['nama'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $nim = trim($_POST['nim'] ?? '');
            $prodi = (int)($_POST['program_studi_id'] ?? 0);
            $status = $_POST['status'] ?? 'aktif';
            if ($nama === '' || $email === '' || $nim === '' || $prodi === 0) throw new RuntimeException('Nama, email, NIM, dan program studi wajib diisi.');
            $foto = upload_file('foto', 'profil', ['jpg','jpeg','png']) ?: $m['foto'];
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE users SET name=?, email=?, status=? WHERE id=?")->execute([$nama,$email,$status,$m['user_id']]);
            if (!empty($_POST['password'])) {
                $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($_POST['password'], PASSWORD_DEFAULT),$m['user_id']]);
            }
            $pdo->prepare("UPDATE mahasiswa SET nim=?, nama=?, email=?, program_studi_id=?, angkatan=?, foto=?, status=?, dosen_wali_id=? WHERE id=?")
                ->execute([$nim,$nama,$email,$prodi,trim($_POST['angkatan'] ?? ''),$foto,$status,($_POST['dosen_wali_id'] ?: null),$id]);
            $pdo->commit();
            set_flash('success', 'Data mahasiswa berhasil diperbarui.');
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT user_id FROM mahasiswa WHERE id=?");
            $stmt->execute([$id]);
            $m = $stmt->fetch();
            if ($m) {
                $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$m['user_id']]);
                set_flash('success', 'Data mahasiswa berhasil dihapus.');
            }
        }
        redirect('admin/mahasiswa.php');
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    set_flash('danger', $e->getMessage());
    redirect('admin/mahasiswa.php');
}
$prodiList = $pdo->query("SELECT * FROM program_studi ORDER BY nama")->fetchAll();
$dosenList = $pdo->query("SELECT id,nama FROM dosen WHERE status='aktif' ORDER BY nama")->fetchAll();
$mahasiswa = $pdo->query("SELECT m.*, ps.nama AS prodi, d.nama AS dosen_wali FROM mahasiswa m LEFT JOIN program_studi ps ON ps.id=m.program_studi_id LEFT JOIN dosen d ON d.id=m.dosen_wali_id ORDER BY m.id DESC")->fetchAll();
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <div><p class="text-muted mb-0">Kelola data mahasiswa, akun login, foto profil, dan status aktif/nonaktif.</p></div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="bi bi-plus-circle me-1"></i>Tambah Mahasiswa</button>
</div>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Mahasiswa</th><th>Email</th><th>Program Studi</th><th>Angkatan</th><th>Dosen Wali</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($mahasiswa as $m): ?>
                <tr>
                    <td><div class="d-flex align-items-center gap-2"><div class="avatar-sm bg-primary-subtle text-primary"><?= e(strtoupper(substr($m['nama'],0,1))) ?></div><div><div class="fw-semibold"><?= e($m['nama']) ?></div><small class="text-muted"><?= e($m['nim']) ?></small></div></div></td>
                    <td><?= e($m['email']) ?></td><td><?= e($m['prodi']) ?></td><td><?= e($m['angkatan']) ?></td><td><?= e($m['dosen_wali'] ?? '-') ?></td><td><?= status_badge($m['status']) ?></td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#edit<?= $m['id'] ?>">Edit</button>
                        <form method="post" class="d-inline" onsubmit="return confirm('Hapus mahasiswa ini?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$mahasiswa): ?><tr><td colspan="7" class="text-center text-muted py-4">Belum ada data mahasiswa.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php foreach ($mahasiswa as $m): ?>
<div class="modal fade" id="edit<?= $m['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" enctype="multipart/form-data" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $m['id'] ?>">
            <?php $edit = $m; include __DIR__ . '/partials/form_mahasiswa.php'; ?>
        </form>
    </div>
</div>
<?php endforeach; ?>
<div class="modal fade" id="modalTambah" tabindex="-1"><div class="modal-dialog modal-lg"><form method="post" enctype="multipart/form-data" class="modal-content"><input type="hidden" name="action" value="create"><?php $edit=null; include __DIR__ . '/partials/form_mahasiswa.php'; ?></form></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
