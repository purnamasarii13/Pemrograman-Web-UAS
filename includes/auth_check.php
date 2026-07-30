<?php
require_once __DIR__ . '/../config/database.php';

function is_logged_in(): bool
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('auth/login.php');
    }
}

function require_role($roles): void
{
    require_login();
    $roles = (array)$roles;
    $role = current_user()['role'] ?? '';
    if (!in_array($role, $roles, true)) {
        http_response_code(403);
        echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Akses ditolak</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light"><div class="container py-5"><div class="card shadow-sm border-0"><div class="card-body p-5"><h3>Akses ditolak</h3><p class="text-muted">Role Anda tidak memiliki izin untuk membuka halaman ini.</p><a href="' . base_url('index.php') . '" class="btn btn-primary">Kembali ke Dashboard</a></div></div></div></body></html>';
        exit;
    }
}

function dashboard_path_by_role(string $role): string
{
    return match ($role) {
        'admin' => 'admin/dashboard.php',
        'mahasiswa' => 'mahasiswa/dashboard.php',
        'dosen' => 'dosen/dashboard.php',
        'kaprodi' => 'kaprodi/dashboard.php',
        'keuangan' => 'keuangan/dashboard.php',
        default => 'auth/login.php',
    };
}

function role_label(string $role): string
{
    return match ($role) {
        'admin' => 'Admin',
        'mahasiswa' => 'Mahasiswa',
        'dosen' => 'Dosen',
        'kaprodi' => 'Kaprodi',
        'keuangan' => 'Keuangan',
        default => ucfirst($role),
    };
}

function current_mahasiswa(): ?array
{
    if (!is_logged_in()) return null;
    $stmt = db()->prepare("SELECT m.*, ps.nama AS program_studi, d.nama AS dosen_wali
        FROM mahasiswa m
        LEFT JOIN program_studi ps ON ps.id = m.program_studi_id
        LEFT JOIN dosen d ON d.id = m.dosen_wali_id
        WHERE m.user_id = ? LIMIT 1");
    $stmt->execute([current_user()['id']]);
    $data = $stmt->fetch();
    return $data ?: null;
}

function current_dosen(): ?array
{
    if (!is_logged_in()) return null;
    $stmt = db()->prepare("SELECT * FROM dosen WHERE user_id = ? LIMIT 1");
    $stmt->execute([current_user()['id']]);
    $data = $stmt->fetch();
    return $data ?: null;
}
