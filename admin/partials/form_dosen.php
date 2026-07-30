<div class="modal-header"><h5 class="modal-title"><?= $edit ? 'Edit Dosen' : 'Tambah Dosen' ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><div class="row g-3">
    <div class="col-md-6"><label class="form-label">NIDN</label><input name="nidn" class="form-control" value="<?= e($edit['nidn'] ?? '') ?>" required></div>
    <div class="col-md-6"><label class="form-label">Nama</label><input name="nama" class="form-control" value="<?= e($edit['nama'] ?? '') ?>" required></div>
    <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($edit['email'] ?? '') ?>" required></div>
    <div class="col-md-6"><label class="form-label">Password <?= $edit ? '(kosongkan jika tidak diganti)' : '' ?></label><input type="password" name="password" class="form-control" placeholder="Default: dosen123"></div>
    <div class="col-md-6"><label class="form-label">Jabatan</label><input name="jabatan" class="form-control" value="<?= e($edit['jabatan'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select"><option value="aktif" <?= (($edit['status'] ?? '')==='aktif')?'selected':'' ?>>Aktif</option><option value="nonaktif" <?= (($edit['status'] ?? '')==='nonaktif')?'selected':'' ?>>Nonaktif</option></select></div>
    <div class="col-12"><label class="form-label">Mata Kuliah yang Diampu</label><input name="mata_kuliah_diampu" class="form-control" value="<?= e($edit['mata_kuliah_diampu'] ?? '') ?>" placeholder="Contoh: Pemrograman Web, Basis Data"></div>
    <div class="col-12"><label class="form-label">Foto Profil</label><input type="file" name="foto" class="form-control" accept="image/*"></div>
</div></div>
<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
