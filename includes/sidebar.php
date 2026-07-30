<?php
$role = $user['role'] ?? '';
$menus = [
    'admin' => [
        ['Dashboard', 'bi-speedometer2', 'admin/dashboard.php'],
        ['Mahasiswa', 'bi-people', 'admin/mahasiswa.php'],
        ['Dosen', 'bi-person-video3', 'admin/dosen.php'],
        ['Pengguna', 'bi-person-gear', 'admin/pengguna.php'],
        ['Mata Kuliah', 'bi-journal-bookmark', 'admin/mata_kuliah.php'],
        ['Semester Aktif', 'bi-calendar-check', 'admin/semester.php'],
        ['Laporan', 'bi-file-earmark-bar-graph', 'admin/laporan.php'],
    ],
    'mahasiswa' => [
        ['Dashboard', 'bi-speedometer2', 'mahasiswa/dashboard.php'],
        ['KRS', 'bi-card-checklist', 'mahasiswa/krs.php'],
        ['Jadwal', 'bi-calendar-week', 'mahasiswa/jadwal.php'],
        ['Nilai', 'bi-award', 'mahasiswa/nilai.php'],
        ['Transkrip', 'bi-filetype-pdf', 'mahasiswa/transkrip.php'],
        ['Tagihan', 'bi-wallet2', 'mahasiswa/tagihan.php'],
        ['Absensi', 'bi-check2-square', 'mahasiswa/absensi.php'],
        ['E-Learning', 'bi-easel', 'mahasiswa/elearning.php'],
    ],
    'dosen' => [
        ['Dashboard', 'bi-speedometer2', 'dosen/dashboard.php'],
        ['Kelas & KRS', 'bi-collection', 'dosen/kelas.php'],
        ['Input Nilai', 'bi-pencil-square', 'dosen/nilai.php'],
        ['Absensi', 'bi-check2-square', 'dosen/absensi.php'],
        ['Materi', 'bi-upload', 'dosen/materi.php'],
        ['Tugas', 'bi-clipboard-check', 'dosen/tugas.php'],
    ],
    'kaprodi' => [
        ['Dashboard', 'bi-speedometer2', 'kaprodi/dashboard.php'],
        ['Laporan KRS', 'bi-card-checklist', 'kaprodi/laporan_krs.php'],
        ['Laporan Nilai', 'bi-award', 'kaprodi/laporan_nilai.php'],
    ],
    'keuangan' => [
        ['Dashboard', 'bi-speedometer2', 'keuangan/dashboard.php'],
        ['Tagihan', 'bi-receipt', 'keuangan/tagihan.php'],
        ['Pembayaran', 'bi-cash-coin', 'keuangan/pembayaran.php'],
    ],
];
$current = trim(str_replace(BASE_URL, '', parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH)), '/');
$initial = strtoupper(substr($user['name'] ?? 'U', 0, 1));
?>
<aside class="sidebar" id="sidebar" aria-label="Menu utama">
    <div class="sidebar-inner">
        <div class="brand-box">
            <div class="brand-icon"><i class="bi bi-mortarboard-fill"></i></div>
            <div>
                <div class="brand-title">SIAKAD</div>
                <small>Sistem Informasi Akademik</small>
            </div>
        </div>
        <div class="user-panel d-lg-none">
            <div class="avatar-sm"><?= e($initial) ?></div>
            <div class="min-w-0">
                <div class="fw-semibold text-truncate"><?= e($user['name'] ?? 'User') ?></div>
                <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis"><?= e(role_label($role)) ?></span>
            </div>
        </div>
        <nav class="nav flex-column sidebar-nav">
            <?php foreach (($menus[$role] ?? []) as $item): [$label, $icon, $url] = $item; $active = $current === $url ? 'active' : ''; ?>
                <a class="nav-link <?= $active ?>" href="<?= base_url($url) ?>">
                    <i class="bi <?= e($icon) ?>"></i><span><?= e($label) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <a href="<?= base_url('auth/logout.php') ?>" class="btn-logout">
                <i class="bi bi-box-arrow-right"></i> Keluar
            </a>
        </div>
    </div>
</aside>
<main class="main-content">
    <nav class="topbar navbar navbar-expand-lg sticky-top">
        <div class="container-fluid py-2">
            <button class="btn btn-outline-secondary d-lg-none me-2" id="sidebarToggle" type="button" aria-label="Buka menu">
                <i class="bi bi-list"></i>
            </button>
            <div class="page-heading min-w-0">
                <div class="page-subtitle">
                    <i class="bi bi-calendar3 me-1"></i><?= e(semester_aktif_label()) ?>
                </div>
                <h1 class="h5 mb-0 fw-bold text-truncate"><?= e($pageTitle) ?></h1>
            </div>
            <div class="ms-auto d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-secondary" id="darkModeToggle" type="button" data-bs-toggle="tooltip" title="Mode gelap/terang">
                    <i class="bi bi-moon-stars"></i>
                </button>
                <div class="topbar-user d-none d-md-flex">
                    <div class="avatar-sm"><?= e($initial) ?></div>
                    <div class="user-meta min-w-0">
                        <div class="user-name text-truncate"><?= e($user['name'] ?? 'User') ?></div>
                        <div class="user-role"><?= e(role_label($role)) ?></div>
                    </div>
                </div>
                <a href="<?= base_url('auth/logout.php') ?>" class="btn btn-sm btn-danger d-md-none" data-bs-toggle="tooltip" title="Logout">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </div>
    </nav>
    <div class="content-area">
        <?= flash_messages() ?>
