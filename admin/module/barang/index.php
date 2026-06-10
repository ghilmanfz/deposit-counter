<?php
$role = $_SESSION['admin']['role'] ?? ''; // ambil role user

// SOLUSI: Jika role kosong/NULL, anggap sebagai owner
if (empty($role)) {
    $role = 'owner';
}

$successStokParam = filter_input(INPUT_GET, 'success-stok', FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
$showSuccessStok = is_string($successStokParam) && $successStokParam !== '';

$successParam = filter_input(INPUT_GET, 'success', FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
$showSuccess = is_string($successParam) && $successParam !== '';

$removeParam = filter_input(INPUT_GET, 'remove', FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
$showRemove = is_string($removeParam) && $removeParam !== '';

$stokParam = filter_input(INPUT_GET, 'stok', FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
$stokFilter = ($stokParam === 'yes');
?>

<h4>Data Barang</h4>
<br />
<?php if($showSuccessStok){?>
<div class="alert alert-success">
    <p>Tambah Stok Berhasil !</p>
</div>
<?php }?>
<?php if($showSuccess){?>
<div class="alert alert-success">
    <p>Tambah Data Berhasil !</p>
</div>
<?php }?>
<?php if($showRemove){?>
<div class="alert alert-danger">
    <p>Hapus Data Berhasil !</p>
</div>
<?php }?>

<?php 
$sql="SELECT * FROM barang WHERE CAST(stok AS UNSIGNED) <= ?";
$row = $config->prepare($sql);
$row->execute(array($toko['min_stok'] ?? 3));
$r = (int) $row->rowCount();
if($r > 0){
    $rSafe = htmlspecialchars((string) $r, ENT_QUOTES, 'UTF-8');
    $minStokSafe = htmlspecialchars($toko['min_stok'] ?? '3', ENT_QUOTES, 'UTF-8');
    echo "
    <div class='alert alert-warning'>
        <span class='glyphicon glyphicon-info-sign'></span> Ada <span style='color:red'>$rSafe</span> barang yang Stok tersisa sudah kurang dari $minStokSafe items. silahkan pesan lagi !!
        <span class='pull-right'><a href='index.php?page=barang&stok=yes'>Cek Barang <i class='fa fa-angle-double-right'></i></a></span>
    </div>
    ";
}
?>

<!-- Tombol Tambah Barang hanya untuk Admin -->
<?php if($role === 'admin'){ ?>
<button type="button" class="btn btn-primary btn-md mr-2" data-toggle="modal" data-target="#myModal">
    <i class="fa fa-plus"></i> Insert Data
</button>
<?php } ?>

<a href="index.php?page=barang&stok=yes" class="btn btn-warning btn-md mr-2">
    <i class="fa fa-list"></i> Sortir Stok Kurang</a>
<a href="index.php?page=barang" class="btn btn-success btn-md">
    <i class="fa fa-refresh"></i> Refresh Data</a>
<div class="clearfix"></div>
<br />

<div class="card card-body">
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm" id="example1">
            <thead>
                <tr style="background:#DFF0D8;color:#333;">
                    <th>No.</th>
                    <th>ID Barang</th>
                    <th>Kategori</th>
                    <th>Nama Barang</th>
                    <th>Merk</th>
                    <th>Stok</th>
                    <th>Harga Beli</th>
                    <th>Harga Jual</th>
                    <th>Satuan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalBeli = 0;
                $totalJual = 0;
                $totalStok = 0;
                $hasil = $stokFilter ? $lihat->barang_stok() : $lihat->barang();
                $no=1;
                foreach($hasil as $isi) {
                ?>
                <tr>
                    <td><?php echo $no;?></td>
                    <td><?php echo htmlspecialchars($isi['id_barang'], ENT_QUOTES, 'UTF-8');?></td>
                    <td><?php echo htmlspecialchars($isi['nama_kategori'], ENT_QUOTES, 'UTF-8');?></td>
                    <td><?php echo htmlspecialchars($isi['nama_barang'], ENT_QUOTES, 'UTF-8');?></td>
                    <td><?php echo htmlspecialchars($isi['merk'], ENT_QUOTES, 'UTF-8');?></td>
                    <td>
                        <?php if($isi['stok'] == '0'){?>
                        <button class="btn btn-danger">Habis</button>
                        <?php }else{?>
                        <?php echo htmlspecialchars($isi['stok'], ENT_QUOTES, 'UTF-8');?>
                        <?php }?>
                    </td>
                    <td>Rp.<?php echo number_format($isi['harga_beli']);?>,-</td>
                    <td>Rp.<?php echo number_format($isi['harga_jual']);?>,-</td>
                    <td><?php echo htmlspecialchars($isi['satuan_barang'], ENT_QUOTES, 'UTF-8');?></td>
                    <td>
                        <?php if($role === 'owner'){ ?>
                            <?php if($isi['stok'] <= ($toko['min_stok'] ?? 3)){ ?>
                                <form method="POST" action="fungsi/edit/edit.php?stok=edit">
                                    <?php echo csrf_field(); ?>
                                    <input type="text" name="restok" class="form-control">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($isi['id_barang'], ENT_QUOTES, 'UTF-8');?>" class="form-control">
                                    <button class="btn btn-primary btn-sm">Restok</button>
                                    <!-- Tombol Hapus dengan SweetAlert2 - Hanya Owner -->
                                    <button type="button" class="btn btn-danger btn-sm btn-hapus-barang"
                                        data-url="fungsi/hapus/hapus.php?barang=hapus&id=<?php echo urlencode($isi['id_barang']);?>&csrf_token=<?php echo urlencode(csrf_get_token());?>">
                                        <i class="fa fa-trash"></i> Hapus
                                    </button>
                                </form>
                            <?php } else { ?>
                                <a href="index.php?page=barang/details&barang=<?php echo urlencode($isi['id_barang']);?>">
                                    <button class="btn btn-primary btn-xs">Details</button>
                                </a>
                                <!-- Tombol Hapus dengan SweetAlert2 - Hanya Owner -->
                                <button type="button" class="btn btn-danger btn-xs btn-hapus-barang"
                                    data-url="fungsi/hapus/hapus.php?barang=hapus&id=<?php echo urlencode($isi['id_barang']);?>&csrf_token=<?php echo urlencode(csrf_get_token());?>">
                                    <i class="fa fa-trash"></i> Hapus
                                </button>
                            <?php } ?>
                        <?php } elseif($role === 'admin'){ ?>
                            <a href="index.php?page=barang/details&barang=<?php echo urlencode($isi['id_barang']);?>">
                                <button class="btn btn-primary btn-xs">Details</button>
                            </a>
                            <a href="index.php?page=barang/edit&barang=<?php echo urlencode($isi['id_barang']);?>">
                                <button class="btn btn-warning btn-xs">Edit</button>
                            </a>
                            <!-- Tombol Hapus dengan SweetAlert2 - Admin -->
                            <button type="button" class="btn btn-danger btn-xs btn-hapus-barang"
                                data-url="fungsi/hapus/hapus.php?barang=hapus&id=<?php echo urlencode($isi['id_barang']);?>&csrf_token=<?php echo urlencode(csrf_get_token());?>">
                                <i class="fa fa-trash"></i> Hapus
                            </button>
                        <?php }
                        // Catatan: role 'kasir' (User Biasa) tidak mendapat tombol hapus sama sekali
                        ?>
                    </td>
                </tr>
                <?php 
                    $no++; 
                    $totalBeli += $isi['harga_beli'] * $isi['stok']; 
                    $totalJual += $isi['harga_jual'] * $isi['stok'];
                    $totalStok += $isi['stok'];
                }
                ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5">Total </th>
                    <th><?php echo $totalStok;?></th>
                    <th>Rp.<?php echo number_format($totalBeli);?>,-</th>
                    <th>Rp.<?php echo number_format($totalJual);?>,-</th>
                    <th colspan="2" style="background:#ddd"></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Modal Tambah Barang hanya muncul untuk Admin -->
<?php if($role === 'admin'){ ?>
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content" style="border-radius:0px;">
            <div class="modal-header" style="background:#285c64;color:#fff;">
                <h5 class="modal-title"><i class="fa fa-plus"></i> Tambah Barang</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="fungsi/tambah/tambah.php?barang=tambah" method="POST">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <table class="table table-striped bordered">
                        <?php $format = $lihat->barang_id(); ?>
                        <tr>
                            <td>ID Barang</td>
                            <td><input type="text" readonly value="<?= htmlspecialchars($format, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" name="id"></td>
                        </tr>
                        <tr>
                            <td>Kategori</td>
                            <td>
                                <select name="kategori" class="form-control" required>
                                    <option value="#">Pilih Kategori</option>
                                    <?php $kat = $lihat->kategori(); foreach($kat as $isi){ ?>
                                    <option value="<?= htmlspecialchars($isi['id_kategori'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= htmlspecialchars($isi['nama_kategori'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                    <?php } ?>
                                </select>
                            </td>
                        </tr>
                        <tr><td>Nama Barang</td><td><input type="text" placeholder="Nama Barang" required class="form-control" name="nama"></td></tr>
                        <tr><td>Merk Barang</td><td><input type="text" placeholder="Merk Barang" required class="form-control" name="merk"></td></tr>
                        <tr><td>Harga Beli</td><td><input type="number" placeholder="Harga beli" required class="form-control" name="beli"></td></tr>
                        <tr><td>Harga Jual</td><td><input type="number" placeholder="Harga Jual" required class="form-control" name="jual"></td></tr>
                        <tr><td>Satuan Barang</td><td>
                            <select name="satuan" class="form-control" required>
                                <option value="#">Pilih Satuan</option>
                                <option value="PCS">PCS</option>
                            </select>
                        </td></tr>
                        <tr><td>Stok</td><td><input type="number" required placeholder="Stok" class="form-control" name="stok"></td></tr>
                        <tr><td>Tanggal Input</td><td><input type="text" required readonly class="form-control" value="<?php echo date("j F Y, G:i");?>" name="tgl"></td></tr>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> Insert Data</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php } ?>

<!-- ============================================================
     SweetAlert2 - Konfirmasi Hapus Barang
     Hanya owner dan admin yang melihat tombol ini,
     sehingga script ini aman untuk di-render.
     ============================================================ -->
<?php if(in_array($role, ['owner', 'admin'])){ ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-hapus-barang').forEach(function (btn) {
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
