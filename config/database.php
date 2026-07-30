<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Jakarta');

define('APP_NAME', 'SIAKAD Kuliah');
define('BASE_URL', '/akademik');
define('DB_HOST', 'localhost');
define('DB_NAME', 'akademik');
define('DB_USER', 'root');
define('DB_PASS', '');
define('CURRENT_SEMESTER', 'Ganjil');
define('CURRENT_TAHUN_AKADEMIK', '2025/2026');

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die('<div style="font-family:Arial;padding:24px"><h3>Koneksi database gagal</h3><p>Pastikan MySQL XAMPP aktif, database <b>akademik</b> sudah dibuat/import, dan konfigurasi di <code>config/database.php</code> benar.</p><pre>' . htmlspecialchars($e->getMessage()) . '</pre></div>');
        }
    }
    return $pdo;
}

function base_url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return BASE_URL . ($path !== '' ? '/' . $path : '');
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path = ''): void
{
    header('Location: ' . base_url($path));
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_messages(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $html = '';
    foreach ($_SESSION['flash'] as $flash) {
        $type = e($flash['type']);
        $msg = e($flash['message']);
        $icon = match ($flash['type']) {
            'success' => 'bi-check-circle-fill',
            'danger' => 'bi-exclamation-triangle-fill',
            'warning' => 'bi-exclamation-circle-fill',
            default => 'bi-info-circle-fill',
        };
        $html .= "<div class=\"alert alert-{$type} alert-dismissible fade show shadow-sm d-flex align-items-start gap-2\" role=\"alert\"><i class=\"bi {$icon} mt-1\"></i><div class=\"flex-grow-1\">{$msg}</div><button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Tutup\"></button></div>";
    }
    unset($_SESSION['flash']);
    return $html;
}

function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}
