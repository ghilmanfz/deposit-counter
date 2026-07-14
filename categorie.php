<?php
  $page_title = 'Kategori Barang';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
  page_require_level(1);
  
  $all_categories = find_all('categories');
?>
<?php
 if(isset($_POST['add_cat'])){
   if(!warehouse_csrf_is_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')){
     $session->msg('d','Sesi aksi tidak valid atau sudah kedaluwarsa. Silakan coba kembali.');
     redirect('categorie.php',false);
   }
   $req_field = array('categorie-name');
   validate_fields($req_field);
   $cat_name = remove_junk($db->escape($_POST['categorie-name']));
   if(empty($errors)){
      $sql  = "INSERT INTO categories (name)";
      $sql .= " VALUES ('{$cat_name}')";
      if($db->query($sql)){
        $session->msg("s", "Kategori barang berhasil ditambahkan.");
        redirect('categorie.php',false);
      } else {
        $session->msg("d", "Kategori barang gagal ditambahkan.");
        redirect('categorie.php',false);
      }
   } else {
     $session->msg("d", $errors);
     redirect('categorie.php',false);
   }
 }
?>
<?php include_once('layouts/header.php'); ?>

  <div class="row">
     <div class="col-md-12">
       <?php echo display_msg($msg); ?>
     </div>
  </div>
   <div class="row">
    <div class="col-md-5">
      <div class="panel panel-default">
        <div class="panel-heading">
          <strong>
            <span class="glyphicon glyphicon-th"></span>
            <span>Tambah Kategori Barang</span>
         </strong>
        </div>
        <div class="panel-body">
          <form method="post" action="categorie.php">
            <?php echo warehouse_csrf_field(); ?>
            <div class="form-group">
                <input type="text" class="form-control" name="categorie-name" placeholder="Nama Kategori">
            </div>
            <button type="submit" name="add_cat" class="btn btn-primary">Simpan Kategori</button>
        </form>
        </div>
      </div>
    </div>
    <div class="col-md-7">
    <div class="panel panel-default">
      <div class="panel-heading">
        <strong>
          <span class="glyphicon glyphicon-th"></span>
          <span>Daftar Kategori</span>
       </strong>
      </div>
        <div class="panel-body">
          <table class="table table-bordered table-striped table-hover">
            <thead>
                <tr>
                    <th class="text-center" style="width: 50px;">#</th>
                    <th>Kategori</th>
                    <th class="text-center" style="width: 100px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
              <?php foreach ($all_categories as $cat):?>
                <tr>
                    <td class="text-center"><?php echo count_id();?></td>
                    <td><?php echo remove_junk(ucfirst($cat['name'])); ?></td>
                    <td class="text-center">
                      <div class="btn-group">
                        <a href="edit_categorie.php?id=<?php echo (int)$cat['id'];?>"  class="btn btn-xs btn-warning" data-toggle="tooltip" title="Edit">
                          <span class="glyphicon glyphicon-edit"></span>
                        </a>
                        <form method="post" action="delete_categorie.php" style="display:inline;" onsubmit="return confirm('Hapus kategori ini? Kategori yang masih dipakai barang tidak dapat dihapus.');">
                          <?php echo warehouse_csrf_field(); ?>
                          <input type="hidden" name="id" value="<?php echo (int)$cat['id']; ?>">
                          <button type="submit" class="btn btn-xs btn-danger" data-toggle="tooltip" title="Hapus">
                            <span class="glyphicon glyphicon-trash"></span>
                          </button>
                        </form>
                      </div>
                    </td>

                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
       </div>
    </div>
    </div>
   </div>
  </div>
  <?php include_once('layouts/footer.php'); ?>
