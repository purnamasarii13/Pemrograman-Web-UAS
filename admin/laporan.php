<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/export.php';
require_role('admin');
$pageTitle = 'Laporan Akademik';
$type = $_GET['type'] ?? 'mahasiswa';
$format = $_GET['format'] ?? '';
[$defSem, $defTahun] = semester_aktif_values();
$filterSem = $_GET['semester'] ?? '';
$filterTahun = $_GET['tahun'] ?? '';
function report_data(string $type, string $semester = '', string $tahun = ''): array {
    $pdo = db();
    $nilaiWhere = '1=1';
    $nilaiParams = [];
    if ($semester !== '') { $nilaiWhere .= ' AND k.semester=?'; $nilaiParams[] = $semester; }
    if ($tahun !== '') { $nilaiWhere .= ' AND k.tahun_akademik=?'; $nilaiParams[] = $tahun; }
    return match ($type) {
        'dosen' => [
            ['NIDN','Nama','Email','Jabatan','Status'],
            array_map(fn($r) => [$r['nidn'],$r['nama'],$r['email'],$r['jabatan'],$r['status']], $pdo->query("SELECT * FROM dosen ORDER BY nama")->fetchAll()),
            'Laporan Dosen'
        ],
        'nilai' => [
            ['NIM','Mahasiswa','Mata Kuliah','Semester','Tugas','UTS','UAS','Akhir','Huruf'],
            array_map(fn($r) => [$r['nim'],$r['nama'],$r['mata_kuliah'],$r['semester'].' '.$r['tahun_akademik'],$r['tugas'],$r['uts'],$r['uas'],$r['nilai_akhir'],$r['nilai_huruf']], (function() use ($pdo, $nilaiWhere, $nilaiParams) {
                $stmt = $pdo->prepare("SELECT m.nim,m.nama,mk.nama mata_kuliah,k.semester,k.tahun_akademik,n.* FROM nilai n JOIN mahasiswa m ON m.id=n.mahasiswa_id JOIN kelas k ON k.id=n.kelas_id JOIN mata_kuliah mk ON mk.id=k.mata_kuliah_id WHERE $nilaiWhere ORDER BY m.nama");
                $stmt->execute($nilaiParams);
                return $stmt->fetchAll();
            })()),
            'Laporan Nilai'
        ],
        'krs' => [
            ['NIM','Mahasiswa','Semester','Total SKS','Status','Catatan'],
            array_map(fn($r) => [$r['nim'],$r['nama'],$r['semester'].' '.$r['tahun_akademik'],$r['total_sks'],$r['status'],$r['catatan']], (function() use ($pdo, $semester, $tahun) {
                $sql = "SELECT kr.*, m.nim, m.nama FROM krs kr JOIN mahasiswa m ON m.id=kr.mahasiswa_id WHERE 1=1";
                $params = [];
                if ($semester !== '') { $sql .= " AND kr.semester=?"; $params[] = $semester; }
                if ($tahun !== '') { $sql .= " AND kr.tahun_akademik=?"; $params[] = $tahun; }
                $sql .= " ORDER BY kr.created_at DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetchAll();
            })()),
            'Laporan KRS'
        ],
        'pembayaran' => [
            ['NIM','Mahasiswa','Jenis','Semester','Nominal','Status','Dibayar'],
            array_map(fn($r) => [$r['nim'],$r['nama'],$r['jenis'],$r['semester'].' '.$r['tahun_akademik'],rupiah($r['jumlah']),$r['status'],rupiah($r['dibayar'])], $pdo->query("SELECT t.*,m.nim,m.nama,COALESCE(SUM(p.jumlah_bayar),0) dibayar FROM tagihan t JOIN mahasiswa m ON m.id=t.mahasiswa_id LEFT JOIN pembayaran p ON p.tagihan_id=t.id GROUP BY t.id ORDER BY t.id DESC")->fetchAll()),
            'Laporan Pembayaran'
        ],
        'absensi' => [
            ['NIM','Mahasiswa','Mata Kuliah','Hadir','Total','Persentase'],
            array_map(fn($r) => [$r['nim'],$r['nama'],$r['mata_kuliah'],$r['hadir'],$r['total'],$r['total'] ? round($r['hadir']/$r['total']*100,1).'%' : '0%'], $pdo->query("SELECT m.nim,m.nama,mk.nama mata_kuliah,SUM(a.status='Hadir') hadir,COUNT(a.id) total FROM absensi a JOIN mahasiswa m ON m.id=a.mahasiswa_id JOIN kelas k ON k.id=a.kelas_id JOIN mata_kuliah mk ON mk.id=k.mata_kuliah_id GROUP BY m.id,k.id ORDER BY m.nama")->fetchAll()),
            'Laporan Absensi'
        ],
        default => [
            ['NIM','Nama','Email','Program Studi','Angkatan','Status'],
            array_map(fn($r) => [$r['nim'],$r['nama'],$r['email'],$r['prodi'],$r['angkatan'],$r['status']], $pdo->query("SELECT m.*,ps.nama prodi FROM mahasiswa m LEFT JOIN program_studi ps ON ps.id=m.program_studi_id ORDER BY m.nama")->fetchAll()),
            'Laporan Mahasiswa'
        ],
    };
}
[$headers,$rows,$title] = report_data($type, $filterSem, $filterTahun);
$qExport = http_build_query(['type' => $type, 'semester' => $filterSem, 'tahun' => $filterTahun]);
if ($format === 'excel') excel_download(strtolower(str_replace(' ','_',$title)).'.xls', $headers, $rows);
if ($format === 'pdf') pdf_download(strtolower(str_replace(' ','_',$title)).'.pdf', $title, $headers, $rows, ['Dicetak: '.date('d-m-Y H:i')]);
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php';
?>
<div class="card shadow-sm mb-3 no-print"><div class="card-body"><form method="get" class="row g-2 align-items-end"><div class="col-md-4"><label class="form-label">Pilih Laporan</label><select name="type" class="form-select"><option value="mahasiswa" <?= $type==='mahasiswa'?'selected':'' ?>>Mahasiswa</option><option value="dosen" <?= $type==='dosen'?'selected':'' ?>>Dosen</option><option value="krs" <?= $type==='krs'?'selected':'' ?>>KRS</option><option value="nilai" <?= $type==='nilai'?'selected':'' ?>>Nilai</option><option value="pembayaran" <?= $type==='pembayaran'?'selected':'' ?>>Pembayaran</option><option value="absensi" <?= $type==='absensi'?'selected':'' ?>>Absensi</option></select></div><div class="col-md-2"><label class="form-label">Semester</label><select name="semester" class="form-select"><option value="">Semua</option><option <?= $filterSem==='Ganjil'?'selected':'' ?>>Ganjil</option><option <?= $filterSem==='Genap'?'selected':'' ?>>Genap</option></select></div><div class="col-md-2"><label class="form-label">Tahun</label><input name="tahun" class="form-control" value="<?= e($filterTahun) ?>" placeholder="<?= e($defTahun) ?>"></div><div class="col-md-2"><button class="btn btn-primary w-100">Tampilkan</button></div><div class="col-md-3"><a class="btn btn-outline-danger w-100" href="?<?= e($qExport) ?>&format=pdf"><i class="bi bi-filetype-pdf me-1"></i>Export PDF</a></div><div class="col-md-3"><a class="btn btn-outline-success w-100" href="?<?= e($qExport) ?>&format=excel"><i class="bi bi-file-earmark-excel me-1"></i>Export Excel</a></div></form></div></div>
<div class="card shadow-sm"><div class="card-header bg-transparent fw-semibold"><?= e($title) ?></div><div class="table-responsive"><table class="table table-striped align-middle mb-0"><thead><tr><?php foreach ($headers as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><?php foreach ($row as $cell): ?><td><?= e($cell) ?></td><?php endforeach; ?></tr><?php endforeach; ?><?php if (!$rows): ?><tr><td colspan="<?= count($headers) ?>" class="text-center text-muted py-4">Tidak ada data.</td></tr><?php endif; ?></tbody></table></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
