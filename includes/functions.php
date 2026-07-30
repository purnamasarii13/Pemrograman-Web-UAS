<?php
require_once __DIR__ . '/auth_check.php';

function status_badge(string $status): string
{
    $map = [
        'aktif' => 'success', 'nonaktif' => 'secondary',
        'menunggu' => 'warning', 'disetujui' => 'success', 'ditolak' => 'danger',
        'lunas' => 'success', 'belum_lunas' => 'danger',
        'Hadir' => 'success', 'Izin' => 'info', 'Sakit' => 'warning', 'Alfa' => 'danger',
    ];
    $label = str_replace('_', ' ', $status);
    $class = $map[$status] ?? 'secondary';
    return '<span class="badge text-bg-' . $class . '">' . e(ucwords($label)) . '</span>';
}

function ui_stat_card(string $label, string $value, string $icon, string $variant = 'primary', ?string $subtitle = null): string
{
    $sub = $subtitle ? '<div class="stat-sub">' . e($subtitle) . '</div>' : '';
    return '<div class="col-md-6 col-xl-3">
        <div class="card shadow-sm stat-card h-100 stat-' . e($variant) . '">
            <div class="card-body d-flex align-items-center justify-content-between gap-3">
                <div>
                    <div class="stat-label">' . e($label) . '</div>
                    <div class="stat-value">' . $value . '</div>' . $sub . '
                </div>
                <div class="stat-icon"><i class="bi ' . e($icon) . '"></i></div>
            </div>
        </div>
    </div>';
}

function ui_empty_state(string $message, string $icon = 'bi-inbox', int $colspan = 1): string
{
    return '<tr><td colspan="' . (int)$colspan . '"><div class="empty-state"><i class="bi ' . e($icon) . '"></i><p>' . e($message) . '</p></div></td></tr>';
}

function ui_page_header(?string $description = null): string
{
    global $pageTitle, $breadcrumbs;
    $html = '';
    if (!empty($breadcrumbs) && is_array($breadcrumbs)) {
        $html .= '<nav aria-label="breadcrumb"><ol class="breadcrumb mb-2">';
        $last = count($breadcrumbs) - 1;
        foreach ($breadcrumbs as $i => $crumb) {
            [$label, $url] = array_pad($crumb, 2, null);
            if ($i === $last || !$url) {
                $html .= '<li class="breadcrumb-item active" aria-current="page">' . e($label) . '</li>';
            } else {
                $html .= '<li class="breadcrumb-item"><a href="' . base_url($url) . '">' . e($label) . '</a></li>';
            }
        }
        $html .= '</ol></nav>';
    }
    $desc = $description ?? ($GLOBALS['pageDescription'] ?? null);
    if ($desc) {
        $html .= '<div class="page-header"><p class="lead">' . e($desc) . '</p></div>';
    }
    return $html;
}

function ui_table_search(string $tableId, string $placeholder = 'Cari data...'): string
{
    return '<div class="table-search"><input type="search" class="form-control form-control-sm" placeholder="' . e($placeholder) . '" data-table-target="' . e($tableId) . '" aria-label="Cari"></div>';
}

function upload_file(string $field, string $subdir, array $allowed = ['jpg','jpeg','png','pdf','doc','docx']): ?string
{
    if (empty($_FILES[$field]['name']) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($_FILES[$field]['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload gagal. Periksa ukuran atau format file.');
    }
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException('Format file tidak diizinkan: ' . $ext);
    }
    $targetDir = __DIR__ . '/../assets/uploads/' . trim($subdir, '/');
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }
    $filename = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $targetDir . '/' . $filename;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {
        throw new RuntimeException('File gagal dipindahkan ke folder upload.');
    }
    return 'assets/uploads/' . trim($subdir, '/') . '/' . $filename;
}

function nilai_huruf(float $nilai): string
{
    if ($nilai >= 85) return 'A';
    if ($nilai >= 75) return 'B';
    if ($nilai >= 65) return 'C';
    if ($nilai >= 50) return 'D';
    return 'E';
}

function bobot_nilai(string $huruf): float
{
    return match (strtoupper($huruf)) {
        'A' => 4.0,
        'B' => 3.0,
        'C' => 2.0,
        'D' => 1.0,
        default => 0.0,
    };
}

function hitung_ip(int $mahasiswaId, ?string $semester = null, ?string $tahun = null): float
{
    $sql = "SELECT n.nilai_huruf, mk.sks
            FROM nilai n
            JOIN kelas k ON k.id = n.kelas_id
            JOIN mata_kuliah mk ON mk.id = k.mata_kuliah_id
            WHERE n.mahasiswa_id = ?";
    $params = [$mahasiswaId];
    if ($semester !== null) {
        $sql .= " AND k.semester = ?";
        $params[] = $semester;
    }
    if ($tahun !== null) {
        $sql .= " AND k.tahun_akademik = ?";
        $params[] = $tahun;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $totalBobot = 0;
    $totalSks = 0;
    foreach ($stmt->fetchAll() as $row) {
        $totalBobot += bobot_nilai($row['nilai_huruf']) * (int)$row['sks'];
        $totalSks += (int)$row['sks'];
    }
    return $totalSks > 0 ? round($totalBobot / $totalSks, 2) : 0.0;
}

/** @return array{semester:string,tahun_akademik:string,id?:int}|null */
function semester_aktif(): ?array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache === false ? null : $cache;
    }
    try {
        $stmt = db()->query("SELECT * FROM semester_aktif WHERE status = 'aktif' ORDER BY id DESC LIMIT 1");
        $row = $stmt->fetch();
        $cache = $row ?: false;
    } catch (Throwable $e) {
        $cache = false;
    }
    return $cache === false ? null : $cache;
}

function semester_aktif_or_fail(): array
{
    $sa = semester_aktif();
    if (!$sa) {
        throw new RuntimeException('Semester aktif belum diatur. Hubungi admin untuk membuka semester baru.');
    }
    return $sa;
}

/** @return array{0:string,1:string} [semester, tahun_akademik] */
function semester_aktif_values(): array
{
    $sa = semester_aktif();
    if ($sa) {
        return [$sa['semester'], $sa['tahun_akademik']];
    }
    return [CURRENT_SEMESTER, CURRENT_TAHUN_AKADEMIK];
}

function semester_aktif_label(): string
{
    [$s, $t] = semester_aktif_values();
    return $s . ' · ' . $t;
}

function mahasiswa_keuangan_lunas(int $mahasiswaId, ?string $semester = null, ?string $tahun = null): bool
{
    if ($semester === null || $tahun === null) {
        [$semester, $tahun] = semester_aktif_values();
    }
    $stmt = db()->prepare("SELECT status FROM tagihan WHERE mahasiswa_id = ? AND semester = ? AND tahun_akademik = ?");
    $stmt->execute([$mahasiswaId, $semester, $tahun]);
    $tagihan = $stmt->fetchAll();
    if (!$tagihan) {
        return false;
    }
    foreach ($tagihan as $t) {
        if ($t['status'] === 'belum_lunas') {
            return false;
        }
    }
    return true;
}

function kelas_dipakai_krs(int $kelasId): bool
{
    $stmt = db()->prepare("SELECT COUNT(*) AS total FROM krs_detail WHERE kelas_id = ?");
    $stmt->execute([$kelasId]);
    return (int)$stmt->fetch()['total'] > 0;
}

function time_overlap(string $startA, string $endA, string $startB, string $endB): bool
{
    return $startA < $endB && $startB < $endA;
}

function cek_konflik_jadwal(array $kelasIds): array
{
    if (count($kelasIds) < 2) return [];
    $placeholders = implode(',', array_fill(0, count($kelasIds), '?'));
    $sql = "SELECT j.*, mk.nama AS mata_kuliah, k.nama_kelas
            FROM jadwal j
            JOIN kelas k ON k.id = j.kelas_id
            JOIN mata_kuliah mk ON mk.id = k.mata_kuliah_id
            WHERE j.kelas_id IN ($placeholders)
            ORDER BY FIELD(j.kelas_id, $placeholders), j.hari, j.jam_mulai";
    $params = array_merge($kelasIds, $kelasIds);
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $jadwal = $stmt->fetchAll();
    $conflicts = [];
    for ($i = 0; $i < count($jadwal); $i++) {
        for ($j = $i + 1; $j < count($jadwal); $j++) {
            if ($jadwal[$i]['kelas_id'] == $jadwal[$j]['kelas_id']) continue;
            if ($jadwal[$i]['hari'] === $jadwal[$j]['hari'] && time_overlap($jadwal[$i]['jam_mulai'], $jadwal[$i]['jam_selesai'], $jadwal[$j]['jam_mulai'], $jadwal[$j]['jam_selesai'])) {
                $conflicts[] = $jadwal[$i]['mata_kuliah'] . ' bentrok dengan ' . $jadwal[$j]['mata_kuliah'] . ' pada ' . $jadwal[$i]['hari'];
            }
        }
    }
    return array_values(array_unique($conflicts));
}

function get_approved_classes_for_mahasiswa(int $mahasiswaId, ?string $semester = null, ?string $tahun = null): array
{
    $sql = "SELECT kd.kelas_id, k.nama_kelas, k.semester, k.tahun_akademik, mk.kode, mk.nama AS mata_kuliah, mk.sks, d.nama AS dosen, j.hari, j.jam_mulai, j.jam_selesai, j.ruangan
        FROM krs_detail kd
        JOIN krs kr ON kr.id = kd.krs_id AND kr.status = 'disetujui'
        JOIN kelas k ON k.id = kd.kelas_id
        JOIN mata_kuliah mk ON mk.id = k.mata_kuliah_id
        LEFT JOIN dosen d ON d.id = k.dosen_id
        LEFT JOIN jadwal j ON j.kelas_id = k.id
        WHERE kr.mahasiswa_id = ?";
    $params = [$mahasiswaId];
    if ($semester !== null) {
        $sql .= " AND kr.semester = ?";
        $params[] = $semester;
    }
    if ($tahun !== null) {
        $sql .= " AND kr.tahun_akademik = ?";
        $params[] = $tahun;
    }
    $sql .= " ORDER BY FIELD(j.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), j.jam_mulai";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function count_rows(string $table, string $where = '1=1'): int
{
    $allowed = ['users','mahasiswa','dosen','program_studi','mata_kuliah','kelas','jadwal','krs','krs_detail','nilai','tagihan','pembayaran','absensi','materi','tugas','pengumpulan_tugas','forum_diskusi','pengumuman','semester_aktif'];
    if (!in_array($table, $allowed, true)) return 0;
    $stmt = db()->query("SELECT COUNT(*) AS total FROM {$table} WHERE {$where}");
    return (int)$stmt->fetch()['total'];
}

function rupiah($angka): string
{
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}
