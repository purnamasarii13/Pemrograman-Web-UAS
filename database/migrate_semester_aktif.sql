-- Jalankan di phpMyAdmin (database: akademik) atau: mysql -u root akademik < database/migrate_semester_aktif.sql

USE akademik;

-- Tabel semester aktif (hanya satu baris status = aktif)
CREATE TABLE IF NOT EXISTS semester_aktif (
    id INT AUTO_INCREMENT PRIMARY KEY,
    semester ENUM('Ganjil','Genap') NOT NULL,
    tahun_akademik VARCHAR(20) NOT NULL,
    status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'nonaktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Status kelas (soft delete jika sudah dipakai KRS)
-- Abaikan error "Duplicate column" jika kolom sudah ada
ALTER TABLE kelas
    ADD COLUMN IF NOT EXISTS status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif' AFTER kapasitas;

-- Seed semester aktif awal (Ganjil 2025/2026) jika belum ada yang aktif
INSERT INTO semester_aktif (semester, tahun_akademik, status)
SELECT 'Ganjil', '2025/2026', 'aktif'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM semester_aktif WHERE status = 'aktif' LIMIT 1);

-- Pastikan kelas lama tetap aktif
UPDATE kelas SET status = 'aktif' WHERE status IS NULL OR status = '';
