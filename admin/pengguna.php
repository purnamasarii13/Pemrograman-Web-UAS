<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$pageTitle = 'Manajemen Pengguna';
$pdo = db();
try {
    if (is_post()) {
        $action = $_POST['action'] ?? '';
        $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? ''); $role = $_POST['role'] ?? 'admin'; $status = $_POST['status'] ?? 'aktif';
        if ($action === 'create') {
            if ($name === '' || $email === '' || !in_array($role, ['admin','kaprodi','keuangan'], true)) throw new RuntimeException('Data pengguna belum lengkap.');
            $password = $_POST['password'] !== '' ? $_POST['password'] : $role . '123';
            $pdo->prepare("INSERT INTO users (name,email,password,role,status) VALUES (?,?,?,?,?)")->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$role,$status]);
            set_flash('success','Pengguna berhasil ditambahkan.');
        } elseif ($action === 'update') {
            $id = (int)$_POST['id'];
            if ($name === '' || $email === '' || !in_array($role, ['admin','kaprodi','keuangan'], true)) throw new RuntimeException('Data pengguna belum lengkap.');
            $pdo->prepare("UPDATE users SET name=?, email=?, role=?, status=? WHERE id=? AND role IN ('admin','kaprodi','keuangan')")->execute([$name,$email,$role,$status,$id]);
            if (!empty($_POST['password'])) $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($_POST['password'], PASSWORD_DEFAULT),$id]);
            set_flash('success','Pengguna berhasil diperbarui.');
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            if ($id === current_user()['id']) throw new RuntimeException('Akun yang sedang login tidak boleh dihapus.');
            $pdo->prepare("DELETE FROM users WHERE id=? AND role IN ('admin','kaprodi','keuangan')")->execute([$id]); set_flash('success','Pengguna berhasil dihapus.');
        }
        redirect('admin/pengguna.php');
    }
} catch (Throwable $e) { set_flash('danger',$e->getMessage()); redirect('admin/pengguna.php'); }
$users = $pdo->query("SELECT * FROM users WHERE role IN ('admin','kaprodi','keuangan') ORDER BY role,name")->fetchAll();
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php';
?>
<?php
$pageDescription = 'Kelola akun admin, kaprodi, dan keuangan.';
$breadcrumbs = [['Dashboard', 'admin/dashboard.php'], ['Pengguna', null]];
echo ui_page_header();
?>
<div class="page-toolbar no-print">
    <p class="toolbar-desc mb-0">Tambah, ubah, atau hapus akun pengguna sistem.</p>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambah"><i class="bi bi-plus-circle me-1"></i>Tambah Pengguna</button>
</div>
<div class="card shadow-sm">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-people me-1"></i> Daftar Pengguna</span>
        <?= ui_table_search('tblPengguna') ?>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tblPengguna">
            <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td class="fw-semibold"><?= e($u['name']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><span class="badge text-bg-primary"><?= e(role_label($u['role'])) ?></span></td>
                    <td><?= status_badge($u['status']) ?></td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#edit<?= $u['id'] ?>">Edit</button>
                        <form class="d-inline" method="post" onsubmit="return confirm('Hapus pengguna ini?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$users): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data pengguna.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php foreach ($users as $u): ?>
<div class="modal fade" id="edit<?= $u['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $u['id'] ?>">
            <?php $edit = $u; include __DIR__ . '/partials/form_pengguna.php'; ?>
        </form>
    </div>
</div>
<?php endforeach; ?>
<div class="modal fade" id="tambah" tabindex="-1"><div class="modal-dialog"><form method="post" class="modal-content"><input type="hidden" name="action" value="create"><?php $edit=null; include __DIR__ . '/partials/form_pengguna.php'; ?></form></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
