<?php
  $page_title = 'Edit Satuan Barang';
  require_once('includes/load.php');
  page_require_level(2);
  $unit = find_unit_by_id(isset($_GET['id']) ? (int)$_GET['id'] : 0);
  if(!$unit){
    $session->msg('d','Satuan tidak ditemukan.');
    redirect('units.php', false);
  }
  if(isset($_POST['update_unit'])){
    $req_fields = array('unit-name');
    validate_fields($req_fields);
    if(empty($errors)){
      $ok = update_unit((int)$unit['id'], $_POST['unit-name'], isset($_POST['unit-description']) ? $_POST['unit-description'] : '');
      $session->msg($ok ? 's' : 'd', $ok ? 'Satuan berhasil diperbarui.' : 'Satuan gagal diperbarui.');
      redirect('units.php', false);
    } else {
      $session->msg('d', $errors);
      redirect('edit_unit.php?id='.(int)$unit['id'], false);
    }
  }
  $msg = $session->msg();
?>
<?php include_once('layouts/header.php'); ?>
<div class="row"><div class="col-md-12"><?php echo display_msg($msg); ?></div></div>
<div class="row">
  <div class="col-md-6">
    <div class="panel panel-default">
      <div class="panel-heading"><strong><span class="glyphicon glyphicon-edit"></span> Edit Satuan Barang</strong></div>
      <div class="panel-body">
        <form method="post" action="edit_unit.php?id=<?php echo (int)$unit['id']; ?>">
          <div class="form-group"><input type="text" class="form-control" name="unit-name" value="<?php echo remove_junk($unit['name']); ?>"></div>
          <div class="form-group"><input type="text" class="form-control" name="unit-description" value="<?php echo !empty($unit['description']) ? remove_junk($unit['description']) : ''; ?>"></div>
          <button type="submit" name="update_unit" class="btn btn-primary">Update</button>
          <a href="units.php" class="btn btn-default">Kembali</a>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include_once('layouts/footer.php'); ?>
