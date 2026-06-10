<!--sidebar end-->
      
<!-- **********************************************************************************************************************************************************
MAIN CONTENT
*********************************************************************************************************************************************************** -->
<!--main content start-->
<?php
$successParam = filter_input(INPUT_GET, 'success', FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
$showSuccess = is_string($successParam) && $successParam !== '';

$successAddParam = filter_input(INPUT_GET, 'success-add', FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
$showSuccessAdd = is_string($successAddParam) && $successAddParam !== '';

// Ambil role user untuk validasi tampilan tombol hapus
$roleCustomer = $_SESSION['admin']['role'] ?? '';
if (empty($roleCustomer)) {
    $roleCustomer = 'owner';
}
$canDelete = in_array($roleCustomer, ['owner', 'admin']);
?>
<h4>Data Customer / Member Pelanggan</h4>
<br>
<?php if($showSuccess){?>
<div class="alert alert-success">
    <p>Edit Data Berhasil !</p>
</div>
<?php }?>
<?php if($showSuccessAdd){?>
<div class="alert alert-success">
    <p>Tambah Data Berhasil !</p>
</div>
<?php }?>

<div class="card shadow-sm col-md-8 offset-md-2 p-0 mb-3">
    <div class="card-header bg-primary text-white">
        <i class="fa fa-user-plus"></i> Form Tambah Customer
    </div>
    <div class="card-body">
        <form method="post" action="fungsi/tambah/tambah.php?customer=tambah">
            <?php echo csrf_field(); ?>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Nama Customer *</label>
                    <input type="text" class="form-control" name="nama_customer" required>
                </div>
                <div class="form-group col-md-6">
                    <label>No Telepon (WhatsApp) *</label>
                    <input type="text" class="form-control" name="no_telepon" required placeholder="Contoh: 628123456789">
                    <small class="text-muted">Format: 62xxx (tanpa 0 di depan)</small>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-8">
                    <label>Alamat</label>
                    <textarea class="form-control" name="alamat" rows="2"></textarea>
                </div>
                <div class="form-group col-md-4">
                    <label>Email</label>
                    <input type="email" class="form-control" name="email">
                </div>
            </div>
            <div class="card bg-light p-3 mb-3">
                <h6 class="text-primary"><i class="fa fa-bell"></i> Pengaturan Reminder WhatsApp</h6>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Aktifkan Reminder</label>
                        <select class="form-control" name="reminder_aktif">
                            <option value="ya" selected>Ya</option>
                            <option value="tidak">Tidak</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Interval Hari</label>
                        <input type="number" class="form-control" name="reminder_interval" placeholder="Kosongkan = Pakai Default">
                        <small class="text-muted">Kosongkan untuk pakai setting global</small>
                    </div>
                </div>
                <div class="form-group">
                    <label>Pesan Custom (Opsional)</label>
                    <textarea class="form-control" name="pesan_custom" rows="3" placeholder="Kosongkan untuk pakai template default. Tag: {nama}, {barang}, {toko}, {phone}, {stok}, {harga}"></textarea>
                    <small class="text-muted">Contoh: Halo {nama}, stok {barang} sudah tersedia lagi! Hubungi {toko} di {phone}</small>
                </div>
            </div>
            <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Simpan Customer</button>
        </form>
    </div>
</div>

 <div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="example1">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Customer</th>
                        <th>No Telepon</th>
                        <th>Alamat</th>
                        <th>Email</th>
                        <th>Total Belanja</th>
                        <th>Poin Diskon</th>
                        <th>Tgl Daftar</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    $customers = $lihat->customer();
                    foreach($customers as $customer){
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$no++, ENT_QUOTES, 'UTF-8');?></td>
                        <td><?= htmlspecialchars($customer['nama_customer'], ENT_QUOTES, 'UTF-8');?></td>
                        <td><?= htmlspecialchars($customer['no_telepon'], ENT_QUOTES, 'UTF-8');?></td>
                        <td><?= htmlspecialchars($customer['alamat'] ?? '-', ENT_QUOTES, 'UTF-8');?></td>
                        <td><?= htmlspecialchars($customer['email'] ?? '-', ENT_QUOTES, 'UTF-8');?></td>
                        <td>Rp. <?= number_format($customer['total_belanja'], 0, ',', '.');?></td>
                        <td><?= htmlspecialchars((string)$customer['poin_diskon'], ENT_QUOTES, 'UTF-8');?> Poin</td>
                        <td><?= htmlspecialchars($customer['tgl_daftar'], ENT_QUOTES, 'UTF-8');?></td>
                        <td>
                            <?php if($customer['status'] == 'aktif'){?>
                                <span class="badge badge-success">Aktif</span>
                            <?php } else {?>
                                <span class="badge badge-danger">Non-Aktif</span>
                            <?php }?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" 
                                data-target="#modalEditCustomer<?= htmlspecialchars((string)$customer['id_customer'], ENT_QUOTES, 'UTF-8');?>">
                                <i class="fa fa-edit"></i>
                            </button>
                            <?php if($canDelete){ ?>
                            <!-- Tombol Hapus dengan SweetAlert2 - Hanya owner dan admin -->
                            <button type="button"
                                class="btn btn-danger btn-sm btn-hapus-customer"
                                data-url="fungsi/hapus/hapus.php?customer=<?= htmlspecialchars((string)$customer['id_customer'], ENT_QUOTES, 'UTF-8');?>&csrf_token=<?php echo urlencode(csrf_get_token());?>">
                                <i class="fa fa-trash"></i>
                            </button>
                            <?php }
                            // Catatan: role 'kasir' (User Biasa) tidak mendapat tombol hapus
                            ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php foreach($customers as $customer){ ?>
<!-- Modal Edit Customer -->
<div class="modal fade" id="modalEditCustomer<?= htmlspecialchars((string)$customer['id_customer'], ENT_QUOTES, 'UTF-8');?>" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Customer</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" action="fungsi/edit/edit.php?customer=update">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id_customer" value="<?= htmlspecialchars((string)$customer['id_customer'], ENT_QUOTES, 'UTF-8');?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Customer *</label>
                        <input type="text" class="form-control" name="nama_customer" 
                            value="<?= htmlspecialchars($customer['nama_customer'], ENT_QUOTES, 'UTF-8');?>" required>
                    </div>
                    <div class="form-group">
                        <label>No Telepon (WhatsApp) *</label>
                        <input type="text" class="form-control" name="no_telepon" 
                            value="<?= htmlspecialchars($customer['no_telepon'], ENT_QUOTES, 'UTF-8');?>" required 
                            placeholder="Contoh: 628123456789">
                        <small class="text-muted">Format: 628xxx (gunakan 62 untuk kode Indonesia)</small>
                    </div>
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea class="form-control" name="alamat" rows="3"><?= htmlspecialchars($customer['alamat'] ?? '', ENT_QUOTES, 'UTF-8');?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" 
                            value="<?= htmlspecialchars($customer['email'] ?? '', ENT_QUOTES, 'UTF-8');?>">
                    </div>
                    <div class="form-group">
                        <label>Poin Diskon</label>
                        <input type="number" class="form-control" name="poin_diskon" 
                            value="<?= htmlspecialchars((string)$customer['poin_diskon'], ENT_QUOTES, 'UTF-8');?>">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="status">
                            <option value="aktif" <?= $customer['status'] == 'aktif' ? 'selected' : '';?>>Aktif</option>
                            <option value="nonaktif" <?= $customer['status'] == 'nonaktif' ? 'selected' : '';?>>Non-Aktif</option>
                        </select>
                    </div>
                    <hr>
                    <h6 class="text-primary"><i class="fa fa-bell"></i> Pengaturan Reminder WhatsApp</h6>
                    <div class="form-group">
                        <label>Aktifkan Reminder</label>
                        <select class="form-control" name="reminder_aktif">
                            <option value="ya" <?= ($customer['reminder_aktif'] ?? 'ya') == 'ya' ? 'selected' : '';?>>Ya</option>
                            <option value="tidak" <?= ($customer['reminder_aktif'] ?? 'ya') == 'tidak' ? 'selected' : '';?>>Tidak</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Interval Hari (Opsional)</label>
                        <input type="number" class="form-control" name="reminder_interval" 
                            value="<?= htmlspecialchars((string)($customer['reminder_interval'] ?? ''), ENT_QUOTES, 'UTF-8');?>"
                            placeholder="Kosongkan = Pakai Default">
                        <small class="text-muted">Kosongkan untuk pakai setting global</small>
                    </div>
                    <div class="form-group">
                        <label>Pesan Custom (Opsional)</label>
                        <textarea class="form-control" name="pesan_custom" rows="3" 
                            placeholder="Kosongkan untuk pakai template default. Tag: {nama}, {barang}, {toko}, {phone}, {stok}, {harga}"><?= htmlspecialchars($customer['pesan_custom'] ?? '', ENT_QUOTES, 'UTF-8');?></textarea>
                        <small class="text-muted">Contoh: Halo {nama}, stok {barang} sudah tersedia!</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php } ?>

<!-- ============================================================
     SweetAlert2 - Konfirmasi Hapus Customer
     Hanya owner dan admin yang melihat tombol ini.
     ============================================================ -->
<?php if($canDelete){ ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-hapus-customer').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var url = this.getAttribute('data-url');
            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Apakah Anda yakin ingin menghapus data ini? Data yang sudah dihapus tidak dapat dikembalikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74a3b',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then(function (result) {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });
});
</script>
<?php } ?>
