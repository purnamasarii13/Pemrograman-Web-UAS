<div class="modal-header"><h5 class="modal-title"><?= $edit ? 'Edit Mahasiswa' : 'Tambah Mahasiswa' ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">NIM</label><input name="nim" class="form-control" value="<?= e($edit['nim'] ?? '') ?>" required></div>
        <div class="col-md-6"><label class="form-label">Nama</label><input name="nama" class="form-control" value="<?= e($edit['nama'] ?? '') ?>" required></div>
        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($edit['email'] ?? '') ?>" required></div>
        <div class="col-md-6"><label class="form-label">Password <?= $edit ? '(kosongkan jika tidak diganti)' : '' ?></label><input type="password" name="password" class="form-control" placeholder="Default: mahasiswa123"></div>
        <div class="col-md-6"><label class="form-label">Program Studi</label><select name="program_studi_id" class="form-select" required><option value="">Pilih</option><?php foreach ($prodiList as $p): ?><option value="<?= $p['id'] ?>" <?= (($edit['program_studi_id'] ?? '') == $p['id']) ? 'selected' : '' ?>><?= e($p['nama']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label class="form-label">Angkatan</label><input name="angkatan" class="form-control" value="<?= e($edit['angkatan'] ?? date('Y')) ?>"></div>
        <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="aktif" <?= (($edit['status'] ?? '')==='aktif')?'selected':'' ?>>Aktif</option><option value="nonaktif" <?= (($edit['status'] ?? '')==='nonaktif')?'selected':'' ?>>Nonaktif</option></select></div>
        <div class="col-md-6"><label class="form-label">Dosen Wali</label><select name="dosen_wali_id" class="form-select"><option value="">Belum ditentukan</option><?php foreach ($dosenList as $d): ?><option value="<?= $d['id'] ?>" <?= (($edit['dosen_wali_id'] ?? '') == $d['id']) ? 'selected' : '' ?>><?= e($d['nama']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6"><label class="form-label">Foto Profil</label><input type="file" name="foto" class="form-control" accept="image/*"><small class="text-muted">Format jpg/png. Opsional.</small></div>
    </div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
