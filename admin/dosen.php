<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$pageTitle = 'Manajemen Dosen';
$pdo = db();
try {
    if (is_post()) {
        $action = $_POST['action'] ?? '';
        if ($action === 'create') {
            $nama = trim($_POST['nama'] ?? ''); $email = trim($_POST['email'] ?? ''); $nidn = trim($_POST['nidn'] ?? '');
            if ($nama === '' || $email === '' || $nidn === '') throw new RuntimeException('Nama, email, dan NIDN wajib diisi.');
            $foto = upload_file('foto','profil',['jpg','jpeg','png']);
            $password = $_POST['password'] !== '' ? $_POST['password'] : 'dosen123';
            $pdo->beginTransaction();
            $pdo->prepare("INSERT INTO users (name,email,password,role,status) VALUES (?,?,?,?,?)")->execute([$nama,$email,password_hash($password,PASSWORD_DEFAULT),'dosen',$_POST['status']]);
            $userId = (int)$pdo->lastInsertId();
            $pdo->prepare("INSERT INTO dosen (user_id,nidn,nama,email,jabatan,mata_kuliah_diampu,foto,status) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$userId,$nidn,$nama,$email,trim($_POST['jabatan'] ?? ''),trim($_POST['mata_kuliah_diampu'] ?? ''),$foto,$_POST['status']]);
            $pdo->commit(); set_flash('success','Data dosen berhasil ditambahkan.');
        } elseif ($action === 'update') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("SELECT * FROM dosen WHERE id=?"); $stmt->execute([$id]); $d = $stmt->fetch();
            if (!$d) throw new RuntimeException('Data dosen tidak ditemukan.');
            $foto = upload_file('foto','profil',['jpg','jpeg','png']) ?: $d['foto'];
            $nama = trim($_POST['nama'] ?? ''); $email = trim($_POST['email'] ?? ''); $nidn = trim($_POST['nidn'] ?? '');
            if ($nama === '' || $email === '' || $nidn === '') throw new RuntimeException('Nama, email, dan NIDN wajib diisi.');
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE users SET name=?, email=?, status=? WHERE id=?")->execute([$nama,$email,$_POST['status'],$d['user_id']]);
            if (!empty($_POST['password'])) $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($_POST['password'], PASSWORD_DEFAULT),$d['user_id']]);
            $pdo->prepare("UPDATE dosen SET nidn=?, nama=?, email=?, jabatan=?, mata_kuliah_diampu=?, foto=?, status=? WHERE id=?")
                ->execute([$nidn,$nama,$email,trim($_POST['jabatan'] ?? ''),trim($_POST['mata_kuliah_diampu'] ?? ''),$foto,$_POST['status'],$id]);
            $pdo->commit(); set_flash('success','Data dosen berhasil diperbarui.');
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id']; $stmt = $pdo->prepare("SELECT user_id FROM dosen WHERE id=?"); $stmt->execute([$id]); $d = $stmt->fetch();
            if ($d) $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$d['user_id']]);
            set_flash('success','Data dosen berhasil dihapus.');
        }
        redirect('admin/dosen.php');
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack(); set_flash('danger',$e->getMessage()); redirect('admin/dosen.php');
}
$dosen = $pdo->query("SELECT * FROM dosen ORDER BY id DESC")->fetchAll();
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <p class="text-muted mb-0">Kelola data dosen, jabatan, mata kuliah yang diampu, dan akun login.</p>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="bi bi-plus-circle me-1"></i>Tambah Dosen</button>
</div>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Dosen</th><th>Email</th><th>Jabatan</th><th>Mata Kuliah</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($dosen as $d): ?>
                <tr>
                    <td><div class="fw-semibold"><?= e($d['nama']) ?></div><small class="text-muted"><?= e($d['nidn']) ?></small></td>
                    <td><?= e($d['email']) ?></td>
                    <td><?= e($d['jabatan']) ?></td>
                    <td><?= e($d['mata_kuliah_diampu']) ?></td>
                    <td><?= status_badge($d['status']) ?></td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#edit<?= $d['id'] ?>">Edit</button>
                        <form method="post" class="d-inline" onsubmit="return confirm('Hapus dosen ini?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $d['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$dosen): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data dosen.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php foreach ($dosen as $d): ?>
<div class="modal fade" id="edit<?= $d['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" enctype="multipart/form-data" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $d['id'] ?>">
            <?php $edit = $d; include __DIR__ . '/partials/form_dosen.php'; ?>
        </form>
    </div>
</div>
<?php endforeach; ?>
<div class="modal fade" id="modalTambah" tabindex="-1"><div class="modal-dialog modal-lg"><form method="post" enctype="multipart/form-data" class="modal-content"><input type="hidden" name="action" value="create"><?php $edit=null; include __DIR__ . '/partials/form_dosen.php'; ?></form></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
