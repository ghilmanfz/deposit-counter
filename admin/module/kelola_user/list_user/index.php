<?php
// Ambil semua data member
$dataUser = $lihat->member(); 

// Notifikasi sukses / edit / hapus
$successParam = filter_input(INPUT_GET, 'success', FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
$showSuccess = is_string($successParam) && $successParam !== '';

$successEditParam = filter_input(INPUT_GET, 'success-edit', FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
$showSuccessEdit = is_string($successEditParam) && $successEditParam !== '';

$removeParam = filter_input(INPUT_GET, 'remove', FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
$showRemove = is_string($removeParam) && $removeParam !== '';

// Pastikan CSRF token session ada
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ambil uid jika edit
$uidParamRaw = filter_input(INPUT_GET, 'uid', FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
$uidParam = (is_string($uidParamRaw) && ctype_digit($uidParamRaw)) ? $uidParamRaw : '';

// Ambil role user untuk validasi tampilan tombol hapus
$roleListUser = $_SESSION['admin']['role'] ?? '';
if (empty($roleListUser)) {
    $roleListUser = 'owner';
}
$canDeleteUser = in_array($roleListUser, ['owner', 'admin']);
?>

<h4 class="mb-4">Daftar User</h4>

<?php if ($showSuccess): ?>
<div class="alert alert-success"><i class="fa fa-check-circle"></i> Data berhasil disimpan!</div>
<?php endif; ?>

<?php if ($showSuccessEdit): ?>
<div class="alert alert-success"><i class="fa fa-edit"></i> Data berhasil diubah!</div>
<?php endif; ?>

<?php if ($showRemove): ?>
<div class="alert alert-danger"><i class="fa fa-trash"></i> Data berhasil dihapus!</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <span><i class="fa fa-users"></i> List User</span>
        <a href="index.php?page=kelola_user/tambah_user" class="btn btn-light btn-sm">
            <i class="fa fa-user-plus"></i> Tambah User
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped">
                <thead class="table-light">
                    <tr>
                        <th width="5%">#</th>
                        <th>Foto</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Telepon</th>
                        <th>NIK</th>
                        <th>Alamat</th>
                        <th>Role</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($dataUser)): ?>
                    <?php $no = 1; foreach ($dataUser as $row): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td>
                            <img src="assets/img/user/<?= htmlspecialchars($row['gambar'] ?? 'default.png', ENT_QUOTES, 'UTF-8'); ?>" 
                                 class="rounded" width="40" height="40" alt="Foto">
                        </td>
                        <td><?= htmlspecialchars($row['nm_member'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars($row['telepon'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars($row['NIK'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars($row['alamat_member'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars($row['role'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="d-flex gap-1">
                            <!-- Tombol Edit -->
                            <a href="index.php?page=user&id=<?= $row['id_member']; ?>" class="btn btn-sm btn-primary">
                                <i class="fa fa-edit"></i>
                            </a>
                            <?php if($canDeleteUser){ ?>
                            <!-- Tombol Hapus dengan SweetAlert2 - Hanya owner dan admin -->
                            <button type="button"
                                class="btn btn-sm btn-danger btn-hapus-user"
                                data-url="fungsi/hapus/hapus.php?user=hapus&id=<?= urlencode($row['id_member']); ?>&csrf_token=<?= urlencode(csrf_get_token());?>">
                                <i class="fa fa-trash"></i> Hapus
                            </button>
                            <?php }
                            // Catatan: role 'kasir' (User Biasa) tidak mendapat tombol hapus
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center">Belum ada user.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================
     SweetAlert2 - Konfirmasi Hapus User
     Hanya owner dan admin yang melihat tombol ini.
     ============================================================ -->
<?php if($canDeleteUser){ ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-hapus-user').forEach(function (btn) {
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
