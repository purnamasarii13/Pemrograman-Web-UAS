<?php
require_once __DIR__ . '/../includes/auth_check.php';
if (is_logged_in()) {
    redirect(dashboard_path_by_role(current_user()['role']));
}
$error = '';
if (is_post()) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = db()->prepare("SELECT * FROM users WHERE email = ? AND status = 'aktif' LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ];
            redirect(dashboard_path_by_role($user['role']));
        } else {
            $error = 'Email atau password salah, atau akun tidak aktif.';
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="login-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="card login-card">
                    <div class="row g-0">
                        <div class="col-lg-6 login-brand d-none d-lg-flex flex-column justify-content-center">
                            <div class="position-relative">
                                <div class="login-logo mb-4"><i class="bi bi-mortarboard-fill"></i></div>
                                <h1 class="display-6 login-brand-title mb-3">Sistem Informasi Akademik</h1>
                                <p class="login-brand-lead mb-0">Platform terpadu untuk KRS, nilai, keuangan, absensi, dan e-learning kampus Anda.</p>
                                <ul class="feature-list">
                                    <li><i class="bi bi-check-circle-fill"></i> Manajemen akademik terintegrasi</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Monitoring real-time untuk dosen &amp; kaprodi</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Laporan keuangan &amp; pembayaran mahasiswa</li>
                                </ul>
                                <div class="d-flex gap-2 flex-wrap mt-4">
                                    <span class="badge login-tech-badge">PHP Native</span>
                                    <span class="badge login-tech-badge">MySQL</span>
                                    <span class="badge login-tech-badge">Bootstrap 5</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 login-form-panel">
                            <div class="login-logo d-lg-none"><i class="bi bi-mortarboard-fill"></i></div>
                            <h2 class="h3 fw-bold mb-1">Selamat Datang</h2>
                            <p class="text-muted mb-4">Masuk ke akun Anda untuk melanjutkan.</p>
                            <?php if ($error): ?>
                                <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    <div><?= e($error) ?></div>
                                </div>
                            <?php endif; ?>
                            <form method="post" id="loginForm" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label class="form-label" for="email">Email</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                                        <input type="email" name="email" id="email" class="form-control border-start-0" placeholder="nama@kampus.test" required autofocus>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label" for="password">Password</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                                        <input type="password" name="password" id="password" class="form-control border-start-0" placeholder="Masukkan password" required>
                                    </div>
                                </div>
                                <button class="btn btn-primary btn-lg w-100" type="submit">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Masuk ke Sistem
                                </button>
                            </form>
                            <div class="mt-4 p-3 rounded-3 bg-light small text-muted">
                                <strong class="text-dark">Akun demo:</strong><br>
                                admin@kampus.test / admin123<br>
                                mahasiswa@kampus.test / mahasiswa123
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url('assets/js/app.js') ?>"></script>
</body>
</html>
