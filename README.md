# SIAKAD Kuliah Sederhana

SIAKAD Kuliah adalah contoh Sistem Informasi Akademik sederhana berbasis PHP Native, MySQL, HTML, CSS, JavaScript, dan Bootstrap 5. Project ini dibuat untuk tugas kuliah dan dapat dijalankan di server lokal menggunakan XAMPP.

## Teknologi

- Backend: PHP Native
- Database: MySQL
- Frontend: HTML, CSS, JavaScript
- CSS Framework: Bootstrap 5
- Icon: Bootstrap Icons
- Server lokal: XAMPP
- Database manager: phpMyAdmin

## Struktur Folder

```text
/akademik
  /config
    database.php
  /auth
    login.php
    logout.php
  /admin
    dashboard.php
    mahasiswa.php
    dosen.php
    pengguna.php
    mata_kuliah.php
    laporan.php
  /mahasiswa
    dashboard.php
    krs.php
    jadwal.php
    nilai.php
    transkrip.php
    tagihan.php
    absensi.php
    elearning.php
  /dosen
    dashboard.php
    kelas.php
    nilai.php
    absensi.php
    materi.php
    tugas.php
  /kaprodi
    dashboard.php
    laporan_krs.php
    laporan_nilai.php
  /keuangan
    dashboard.php
    tagihan.php
    pembayaran.php
  /assets
    /css
    /js
    /img
    /uploads
  /includes
    header.php
    sidebar.php
    footer.php
    auth_check.php
    functions.php
    export.php
  index.php
  akademik.sql
  README.md
```

## Fitur Utama

### 1. Autentikasi dan Role-Based Access

- Login dan logout.
- Session login.
- Proteksi halaman berdasarkan role.
- Role: Admin, Mahasiswa, Dosen, Kaprodi, Keuangan.
- Password memakai `password_hash()` dan login memakai `password_verify()`.
- Query database menggunakan PDO prepared statement.

### 2. Admin

- Dashboard ringkasan jumlah mahasiswa, dosen, mata kuliah, kelas, dan statistik pembayaran.
- CRUD mahasiswa.
- CRUD dosen.
- CRUD pengguna admin, kaprodi, dan keuangan.
- Kelola mata kuliah, kelas, dan jadwal.
- Laporan mahasiswa, dosen, nilai, pembayaran, dan absensi.
- Export laporan ke PDF sederhana dan Excel sederhana.

### 3. Mahasiswa

- Dashboard profil, IPK, IPS, status keuangan, status KRS, jadwal, dan notifikasi absensi.
- KRS dengan validasi status keuangan, maksimal 24 SKS, dan pengecekan bentrok jadwal.
- Lihat jadwal kuliah yang sudah disetujui.
- Lihat nilai, IPS, dan IPK.
- Cetak transkrip PDF sederhana.
- Lihat tagihan dan riwayat pembayaran.
- Lihat rekap absensi dan peringatan kehadiran.
- E-learning: materi, tugas online, pengumpulan tugas, forum diskusi, dan pengumuman kelas.

### 4. Dosen

- Dashboard kelas yang diampu.
- Approval KRS mahasiswa bimbingan.
- Input nilai tugas, UTS, UAS.
- Perhitungan nilai akhir otomatis.
- Konversi nilai akhir ke nilai huruf otomatis.
- Input absensi mahasiswa.
- Upload materi perkuliahan.
- Buat tugas online.
- Buat pengumuman kelas.

### 5. Kaprodi

- Dashboard statistik mahasiswa per angkatan, status KRS, dan mata kuliah per prodi.
- Laporan KRS.
- Laporan nilai.
- Export PDF dan Excel sederhana.

### 6. Keuangan

- Dashboard total tagihan, pembayaran, dan jumlah tagihan belum lunas.
- Membuat dan mengelola tagihan SPP mahasiswa per semester.
- Mencatat pembayaran.
- Status tagihan otomatis menjadi lunas jika total pembayaran sudah mencukupi.

## Cara Menjalankan di XAMPP

1. Pastikan XAMPP sudah terinstall.
2. Jalankan **Apache** dan **MySQL** dari XAMPP Control Panel.
3. Copy folder `akademik` ke folder `htdocs` XAMPP.

   Contoh lokasi Windows:

   ```text
   C:\xampp\htdocs\akademik
   ```

   Contoh lokasi macOS/Linux:

   ```text
   /Applications/XAMPP/htdocs/akademik
   ```

4. Buka browser:

   ```text
   http://localhost/akademik
   ```

5. Jika muncul error koneksi database, pastikan database sudah dibuat dan file SQL sudah diimport.

## Cara Import Database di phpMyAdmin

1. Buka:

   ```text
   http://localhost/phpmyadmin
   ```

2. Klik **New** atau **Baru**.
3. Buat database dengan nama:

   ```text
   akademik
   ```

4. Pilih database `akademik`.
5. Klik tab **Import**.
6. Pilih file:

   ```text
   akademik.sql
   ```

7. Klik **Go / Kirim**.
8. Setelah import berhasil, buka:

   ```text
   http://localhost/akademik
   ```

## Konfigurasi Database

File konfigurasi ada di:

```text
config/database.php
```

Default konfigurasi XAMPP:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'akademik');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', '/akademik');
```

Jika MySQL Anda memakai password, ubah `DB_PASS` sesuai password lokal.

## Akun Login Dummy

| Role | Email | Password |
|---|---|---|
| Admin | admin@kampus.test | admin123 |
| Mahasiswa | mahasiswa@kampus.test | mahasiswa123 |
| Dosen | dosen@kampus.test | dosen123 |
| Kaprodi | kaprodi@kampus.test | kaprodi123 |
| Keuangan | keuangan@kampus.test | keuangan123 |

Akun tambahan:

| Role | Email | Password |
|---|---|---|
| Mahasiswa | siti@kampus.test | mahasiswa123 |
| Mahasiswa | andi@kampus.test | mahasiswa123 |
| Dosen | rina@kampus.test | dosen123 |

## Catatan Penggunaan

- Semester aktif diset di `config/database.php`:

  ```php
  define('CURRENT_SEMESTER', 'Ganjil');
  define('CURRENT_TAHUN_AKADEMIK', '2025/2026');
  ```

- Mahasiswa tidak bisa mengajukan KRS jika masih punya tagihan `belum_lunas` pada semester aktif.
- KRS yang sudah disetujui tidak bisa diubah oleh mahasiswa.
- Approval KRS dilakukan oleh dosen wali melalui menu **Dosen > Kelas & KRS**.
- Export PDF memakai generator PDF sederhana bawaan project, tanpa library tambahan.
- Export Excel memakai file `.xls` berbasis tabel HTML sederhana.
- Bootstrap dan Bootstrap Icons memakai CDN. Jika komputer tidak terhubung internet, tampilan masih berjalan tetapi Bootstrap CDN sebaiknya diunduh dan disimpan lokal.

## Keamanan yang Sudah Diterapkan

- Password disimpan dalam bentuk hash.
- Login diverifikasi dengan `password_verify()`.
- Query penting memakai PDO prepared statement.
- Halaman dilindungi dengan session dan role.
- Output HTML memakai fungsi escape `e()`.
- Upload file dibatasi berdasarkan ekstensi yang diizinkan.

## Batasan Project

Project ini sengaja dibuat sederhana untuk tugas kuliah. Beberapa fitur dibuat versi minimal agar mudah dipahami dan dijalankan, misalnya PDF sederhana, forum diskusi dasar, dan e-learning dasar.
