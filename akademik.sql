-- Database: akademik
-- Import file ini melalui phpMyAdmin. Database akan dibuat otomatis jika belum ada.

CREATE DATABASE IF NOT EXISTS akademik CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE akademik;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS forum_diskusi;
DROP TABLE IF EXISTS pengumpulan_tugas;
DROP TABLE IF EXISTS tugas;
DROP TABLE IF EXISTS materi;
DROP TABLE IF EXISTS pengumuman;
DROP TABLE IF EXISTS absensi;
DROP TABLE IF EXISTS pembayaran;
DROP TABLE IF EXISTS tagihan;
DROP TABLE IF EXISTS nilai;
DROP TABLE IF EXISTS krs_detail;
DROP TABLE IF EXISTS krs;
DROP TABLE IF EXISTS jadwal;
DROP TABLE IF EXISTS kelas;
DROP TABLE IF EXISTS mata_kuliah;
DROP TABLE IF EXISTS mahasiswa;
DROP TABLE IF EXISTS dosen;
DROP TABLE IF EXISTS program_studi;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','mahasiswa','dosen','kaprodi','keuangan') NOT NULL,
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE program_studi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(20) NOT NULL UNIQUE,
  nama VARCHAR(120) NOT NULL,
  jenjang VARCHAR(20) NOT NULL DEFAULT 'S1',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE dosen (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  nidn VARCHAR(30) NOT NULL UNIQUE,
  nama VARCHAR(120) NOT NULL,
  email VARCHAR(120) NOT NULL,
  jabatan VARCHAR(100) DEFAULT NULL,
  mata_kuliah_diampu TEXT DEFAULT NULL,
  foto VARCHAR(255) DEFAULT NULL,
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_dosen_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mahasiswa (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  nim VARCHAR(30) NOT NULL UNIQUE,
  nama VARCHAR(120) NOT NULL,
  email VARCHAR(120) NOT NULL,
  program_studi_id INT NOT NULL,
  angkatan VARCHAR(10) NOT NULL,
  foto VARCHAR(255) DEFAULT NULL,
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  dosen_wali_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_mahasiswa_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_mahasiswa_prodi FOREIGN KEY (program_studi_id) REFERENCES program_studi(id) ON DELETE RESTRICT,
  CONSTRAINT fk_mahasiswa_dosen_wali FOREIGN KEY (dosen_wali_id) REFERENCES dosen(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mata_kuliah (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(30) NOT NULL UNIQUE,
  nama VARCHAR(150) NOT NULL,
  sks INT NOT NULL,
  semester INT NOT NULL,
  program_studi_id INT NOT NULL,
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_mk_prodi FOREIGN KEY (program_studi_id) REFERENCES program_studi(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE kelas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mata_kuliah_id INT NOT NULL,
  dosen_id INT DEFAULT NULL,
  nama_kelas VARCHAR(20) NOT NULL DEFAULT 'A',
  semester ENUM('Ganjil','Genap') NOT NULL,
  tahun_akademik VARCHAR(20) NOT NULL,
  kapasitas INT NOT NULL DEFAULT 40,
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_kelas_mk FOREIGN KEY (mata_kuliah_id) REFERENCES mata_kuliah(id) ON DELETE CASCADE,
  CONSTRAINT fk_kelas_dosen FOREIGN KEY (dosen_id) REFERENCES dosen(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE semester_aktif (
  id INT AUTO_INCREMENT PRIMARY KEY,
  semester ENUM('Ganjil','Genap') NOT NULL,
  tahun_akademik VARCHAR(20) NOT NULL,
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'nonaktif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE jadwal (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kelas_id INT NOT NULL,
  hari ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
  jam_mulai TIME NOT NULL,
  jam_selesai TIME NOT NULL,
  ruangan VARCHAR(50) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_jadwal_kelas FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE krs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mahasiswa_id INT NOT NULL,
  semester ENUM('Ganjil','Genap') NOT NULL,
  tahun_akademik VARCHAR(20) NOT NULL,
  total_sks INT NOT NULL DEFAULT 0,
  status ENUM('menunggu','disetujui','ditolak') NOT NULL DEFAULT 'menunggu',
  catatan TEXT DEFAULT NULL,
  approved_by INT DEFAULT NULL,
  approved_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_krs_mahasiswa FOREIGN KEY (mahasiswa_id) REFERENCES mahasiswa(id) ON DELETE CASCADE,
  CONSTRAINT fk_krs_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE krs_detail (
  id INT AUTO_INCREMENT PRIMARY KEY,
  krs_id INT NOT NULL,
  kelas_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_krs_kelas (krs_id, kelas_id),
  CONSTRAINT fk_krs_detail_krs FOREIGN KEY (krs_id) REFERENCES krs(id) ON DELETE CASCADE,
  CONSTRAINT fk_krs_detail_kelas FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE nilai (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mahasiswa_id INT NOT NULL,
  kelas_id INT NOT NULL,
  tugas DECIMAL(5,2) NOT NULL DEFAULT 0,
  uts DECIMAL(5,2) NOT NULL DEFAULT 0,
  uas DECIMAL(5,2) NOT NULL DEFAULT 0,
  nilai_akhir DECIMAL(5,2) NOT NULL DEFAULT 0,
  nilai_huruf VARCHAR(2) NOT NULL DEFAULT 'E',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_nilai_mahasiswa_kelas (mahasiswa_id, kelas_id),
  CONSTRAINT fk_nilai_mahasiswa FOREIGN KEY (mahasiswa_id) REFERENCES mahasiswa(id) ON DELETE CASCADE,
  CONSTRAINT fk_nilai_kelas FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tagihan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mahasiswa_id INT NOT NULL,
  semester ENUM('Ganjil','Genap') NOT NULL,
  tahun_akademik VARCHAR(20) NOT NULL,
  jenis VARCHAR(100) NOT NULL DEFAULT 'SPP',
  jumlah DECIMAL(12,2) NOT NULL,
  status ENUM('belum_lunas','lunas') NOT NULL DEFAULT 'belum_lunas',
  jatuh_tempo DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_tagihan_mahasiswa FOREIGN KEY (mahasiswa_id) REFERENCES mahasiswa(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pembayaran (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tagihan_id INT NOT NULL,
  mahasiswa_id INT NOT NULL,
  tanggal_bayar DATE NOT NULL,
  jumlah_bayar DECIMAL(12,2) NOT NULL,
  metode VARCHAR(60) NOT NULL,
  keterangan VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pembayaran_tagihan FOREIGN KEY (tagihan_id) REFERENCES tagihan(id) ON DELETE CASCADE,
  CONSTRAINT fk_pembayaran_mahasiswa FOREIGN KEY (mahasiswa_id) REFERENCES mahasiswa(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE absensi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mahasiswa_id INT NOT NULL,
  kelas_id INT NOT NULL,
  tanggal DATE NOT NULL,
  pertemuan INT NOT NULL,
  status ENUM('Hadir','Izin','Sakit','Alfa') NOT NULL DEFAULT 'Hadir',
  keterangan VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_absensi (mahasiswa_id, kelas_id, tanggal, pertemuan),
  CONSTRAINT fk_absensi_mahasiswa FOREIGN KEY (mahasiswa_id) REFERENCES mahasiswa(id) ON DELETE CASCADE,
  CONSTRAINT fk_absensi_kelas FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE materi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kelas_id INT NOT NULL,
  dosen_id INT DEFAULT NULL,
  judul VARCHAR(150) NOT NULL,
  tipe ENUM('PDF','Video','Website') NOT NULL DEFAULT 'PDF',
  file_path VARCHAR(255) DEFAULT NULL,
  link_url VARCHAR(255) DEFAULT NULL,
  deskripsi TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_materi_kelas FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE,
  CONSTRAINT fk_materi_dosen FOREIGN KEY (dosen_id) REFERENCES dosen(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tugas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kelas_id INT NOT NULL,
  dosen_id INT DEFAULT NULL,
  judul VARCHAR(150) NOT NULL,
  deskripsi TEXT DEFAULT NULL,
  deadline DATETIME NOT NULL,
  file_path VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tugas_kelas FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE,
  CONSTRAINT fk_tugas_dosen FOREIGN KEY (dosen_id) REFERENCES dosen(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pengumpulan_tugas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tugas_id INT NOT NULL,
  mahasiswa_id INT NOT NULL,
  file_path VARCHAR(255) DEFAULT NULL,
  link_url VARCHAR(255) DEFAULT NULL,
  catatan TEXT DEFAULT NULL,
  nilai DECIMAL(5,2) DEFAULT NULL,
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pengumpulan (tugas_id, mahasiswa_id),
  CONSTRAINT fk_pengumpulan_tugas FOREIGN KEY (tugas_id) REFERENCES tugas(id) ON DELETE CASCADE,
  CONSTRAINT fk_pengumpulan_mahasiswa FOREIGN KEY (mahasiswa_id) REFERENCES mahasiswa(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE forum_diskusi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kelas_id INT NOT NULL,
  user_id INT NOT NULL,
  parent_id INT DEFAULT NULL,
  isi TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_forum_kelas FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE,
  CONSTRAINT fk_forum_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_forum_parent FOREIGN KEY (parent_id) REFERENCES forum_diskusi(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pengumuman (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kelas_id INT NOT NULL,
  dosen_id INT DEFAULT NULL,
  judul VARCHAR(150) NOT NULL,
  isi TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pengumuman_kelas FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE,
  CONSTRAINT fk_pengumuman_dosen FOREIGN KEY (dosen_id) REFERENCES dosen(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (id,name,email,password,role,status) VALUES
(1,'Administrator Kampus','admin@kampus.test','$2y$12$VzT4GmBDMjm/KhvSnHYn9ew5fSWHNoLMYuVa3MXEnQRSTE/sludHu','admin','aktif'),
(2,'Budi Santoso','mahasiswa@kampus.test','$2y$12$UfEGeMpFbvuiE3duplakrekcXeRZy3M5G1U23mRZ28FQUtwK1f98q','mahasiswa','aktif'),
(3,'Siti Aminah','siti@kampus.test','$2y$12$UfEGeMpFbvuiE3duplakrekcXeRZy3M5G1U23mRZ28FQUtwK1f98q','mahasiswa','aktif'),
(4,'Andi Pratama','andi@kampus.test','$2y$12$UfEGeMpFbvuiE3duplakrekcXeRZy3M5G1U23mRZ28FQUtwK1f98q','mahasiswa','aktif'),
(5,'Dr. Ahmad Fauzi','dosen@kampus.test','$2y$12$IKAI9io.t1oEYxgBviZZ7Oho9l72CTYTLsSUhtNM0pxO0ExLVDN4K','dosen','aktif'),
(6,'Rina Lestari, M.Kom','rina@kampus.test','$2y$12$IKAI9io.t1oEYxgBviZZ7Oho9l72CTYTLsSUhtNM0pxO0ExLVDN4K','dosen','aktif'),
(7,'Kaprodi Informatika','kaprodi@kampus.test','$2y$12$QLcXbaizYmF14DKUolSmwuQItQ09h39SUfE6PIJxP5cA4t4R2fWB2','kaprodi','aktif'),
(8,'Staf Keuangan','keuangan@kampus.test','$2y$12$/szrQSoplPKtveQSP052s.wdIqnrUfcqUPjBYoHnEq4rPST7gWceK','keuangan','aktif');

INSERT INTO program_studi (id,kode,nama,jenjang) VALUES
(1,'IF','Informatika','S1'),
(2,'SI','Sistem Informasi','S1');

INSERT INTO dosen (id,user_id,nidn,nama,email,jabatan,mata_kuliah_diampu,status) VALUES
(1,5,'0123456789','Dr. Ahmad Fauzi','dosen@kampus.test','Dosen Tetap','Pemrograman Web, Basis Data, Interaksi Manusia Komputer','aktif'),
(2,6,'0987654321','Rina Lestari, M.Kom','rina@kampus.test','Dosen Tetap','Algoritma dan Pemrograman, Sistem Informasi','aktif');

INSERT INTO mahasiswa (id,user_id,nim,nama,email,program_studi_id,angkatan,status,dosen_wali_id) VALUES
(1,2,'2401001','Budi Santoso','mahasiswa@kampus.test',1,'2024','aktif',1),
(2,3,'2401002','Siti Aminah','siti@kampus.test',1,'2024','aktif',1),
(3,4,'2302001','Andi Pratama','andi@kampus.test',2,'2023','aktif',2);

INSERT INTO mata_kuliah (id,kode,nama,sks,semester,program_studi_id,status) VALUES
(1,'IF301','Pemrograman Web',3,3,1,'aktif'),
(2,'IF302','Basis Data',3,3,1,'aktif'),
(3,'IF303','Algoritma dan Struktur Data',3,3,1,'aktif'),
(4,'SI301','Sistem Informasi Manajemen',2,3,2,'aktif'),
(5,'IF304','Interaksi Manusia dan Komputer',2,3,1,'aktif');

INSERT INTO kelas (id,mata_kuliah_id,dosen_id,nama_kelas,semester,tahun_akademik,kapasitas) VALUES
(1,1,1,'A','Ganjil','2025/2026',40),
(2,2,1,'A','Ganjil','2025/2026',40),
(3,3,2,'A','Ganjil','2025/2026',40),
(4,4,2,'A','Ganjil','2025/2026',35),
(5,5,1,'A','Ganjil','2025/2026',35);

INSERT INTO jadwal (id,kelas_id,hari,jam_mulai,jam_selesai,ruangan) VALUES
(1,1,'Senin','08:00:00','09:40:00','R101'),
(2,2,'Senin','10:00:00','11:40:00','Lab Basis Data'),
(3,3,'Selasa','08:00:00','09:40:00','R102'),
(4,4,'Rabu','10:00:00','11:40:00','R201'),
(5,5,'Kamis','13:00:00','14:40:00','R103');

INSERT INTO semester_aktif (id,semester,tahun_akademik,status) VALUES
(1,'Ganjil','2025/2026','aktif');

INSERT INTO krs (id,mahasiswa_id,semester,tahun_akademik,total_sks,status,catatan,approved_by,approved_at) VALUES
(1,1,'Ganjil','2025/2026',8,'disetujui','KRS disetujui. Pertahankan performa akademik.',5,'2025-08-20 09:00:00'),
(2,2,'Ganjil','2025/2026',6,'menunggu',NULL,NULL,NULL),
(3,3,'Ganjil','2025/2026',5,'ditolak','Jadwal perlu dikonsultasikan ulang dengan dosen wali.',6,'2025-08-21 10:00:00');

INSERT INTO krs_detail (krs_id,kelas_id) VALUES
(1,1),(1,2),(1,4),
(2,1),(2,3),
(3,2),(3,5);

INSERT INTO nilai (mahasiswa_id,kelas_id,tugas,uts,uas,nilai_akhir,nilai_huruf) VALUES
(1,1,88,84,90,87.60,'A'),
(1,2,78,80,82,80.20,'B'),
(1,4,82,75,78,78.30,'B'),
(3,2,70,72,75,72.70,'C');

INSERT INTO tagihan (id,mahasiswa_id,semester,tahun_akademik,jenis,jumlah,status,jatuh_tempo) VALUES
(1,1,'Ganjil','2025/2026','SPP Semester Ganjil',3500000,'lunas','2025-08-31'),
(2,2,'Ganjil','2025/2026','SPP Semester Ganjil',3500000,'belum_lunas','2025-08-31'),
(3,3,'Ganjil','2025/2026','SPP Semester Ganjil',3500000,'lunas','2025-08-31'),
(4,1,'Genap','2024/2025','SPP Semester Genap',3200000,'lunas','2025-02-28');

INSERT INTO pembayaran (tagihan_id,mahasiswa_id,tanggal_bayar,jumlah_bayar,metode,keterangan) VALUES
(1,1,'2025-08-10',3500000,'Transfer Bank','Pembayaran lunas'),
(3,3,'2025-08-12',3500000,'Tunai','Pembayaran di loket'),
(4,1,'2025-02-10',3200000,'Transfer Bank','Pembayaran semester sebelumnya');

INSERT INTO absensi (mahasiswa_id,kelas_id,tanggal,pertemuan,status,keterangan) VALUES
(1,1,'2025-09-01',1,'Hadir',''),
(1,1,'2025-09-08',2,'Hadir',''),
(1,1,'2025-09-15',3,'Izin','Kegiatan kampus'),
(1,2,'2025-09-01',1,'Hadir',''),
(1,2,'2025-09-08',2,'Alfa',''),
(1,2,'2025-09-15',3,'Hadir',''),
(1,4,'2025-09-03',1,'Hadir',''),
(2,1,'2025-09-01',1,'Hadir',''),
(2,3,'2025-09-02',1,'Sakit','Surat dokter');

INSERT INTO materi (kelas_id,dosen_id,judul,tipe,file_path,link_url,deskripsi) VALUES
(1,1,'Kontrak Kuliah Pemrograman Web','Website',NULL,'https://developer.mozilla.org/','Referensi awal HTML, CSS, dan JavaScript.'),
(2,1,'Materi ERD dan Normalisasi','Website',NULL,'https://www.mysql.com/','Pengantar desain basis data dan MySQL.'),
(4,2,'Pengantar Sistem Informasi','Video',NULL,'https://www.youtube.com/','Video pengantar materi perkuliahan.');

INSERT INTO tugas (id,kelas_id,dosen_id,judul,deskripsi,deadline,file_path) VALUES
(1,1,1,'Tugas 1 - Landing Page','Buat landing page kampus sederhana menggunakan HTML dan Bootstrap.','2025-10-01 23:59:00',NULL),
(2,2,1,'Tugas ERD','Buat ERD untuk sistem akademik sederhana.','2025-10-05 23:59:00',NULL);

INSERT INTO pengumpulan_tugas (tugas_id,mahasiswa_id,file_path,link_url,catatan,nilai) VALUES
(1,1,NULL,'https://github.com/contoh/landing-page','Sudah dikumpulkan melalui repository.',88);

INSERT INTO forum_diskusi (kelas_id,user_id,isi) VALUES
(1,5,'Silakan diskusikan kendala instalasi XAMPP pada forum ini.'),
(1,2,'Pak, apakah boleh menggunakan Bootstrap CDN untuk tugas?'),
(2,5,'Kumpulkan pertanyaan terkait ERD sebelum pertemuan berikutnya.');

INSERT INTO pengumuman (kelas_id,dosen_id,judul,isi) VALUES
(1,1,'Pertemuan Minggu Depan','Bawa laptop dan pastikan XAMPP sudah terpasang.'),
(2,1,'Quiz Basis Data','Quiz singkat tentang normalisasi akan dilakukan minggu depan.');

COMMIT;
