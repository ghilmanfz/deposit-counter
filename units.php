<?php
  $page_title = 'Satuan Barang';
  require_once('includes/load.php');
  require_permission('satuan','view');

  if(isset($_POST['add_unit'])){
    require_permission('satuan','create');
    $req_fields = array('unit-name');
    validate_fields($req_fields);
    if(empty($errors)){
      $id = create_unit($_POST['unit-name'], isset($_POST['unit-description']) ? $_POST['unit-description'] : '');
      $session->msg($id ? 's' : 'd', $id ? 'Satuan barang berhasil ditambahkan.' : 'Satuan barang gagal ditambahkan atau sudah ada.');
      redirect('units.php', false);
    } else {
      $session->msg('d', $errors);
      redirect('units.php', false);
    }
  }

  $units = find_all_units();
  $msg = $session->msg();
?>
<?php include_once('layouts/header.php'); ?>
<div class="row"><div class="col-md-12"><?php echo display_msg($msg); ?></div></div>
<div class="row">
  <?php if(role_can_action('satuan','create')): ?>
  <div class="col-md-4">
    <div class="panel panel-default">
      <div class="panel-heading"><strong><span class="glyphicon glyphicon-plus"></span> Tambah Satuan</strong></div>
      <div class="panel-body">
        <form method="post" action="units.php">
          <div class="form-group">
            <input type="text" class="form-control" name="unit-name" placeholder="Nama satuan, contoh: palet">
          </div>
          <div class="form-group">
            <input type="text" class="form-control" name="unit-description" placeholder="Keterangan">
          </div>
          <button type="submit" name="add_unit" class="btn btn-primary">Simpan</button>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>
  <div class="<?php echo role_can_action('satuan','create') ? 'col-md-8' : 'col-md-12'; ?>">
    <div class="panel panel-default">
      <div class="panel-heading"><strong><span class="glyphicon glyphicon-tags"></span> Data Satuan Barang</strong></div>
      <div class="panel-body">
        <table class="table table-bordered table-striped">
          <thead><tr><th class="text-center" style="width:50px;">#</th><th>Nama Satuan</th><th>Keterangan</th><th class="text-center" style="width:100px;">Aksi</th></tr></thead>
          <tbody>
            <?php foreach($units as $unit): ?>
            <tr>
              <td class="text-center"><?php echo count_id(); ?></td>
              <td><?php echo remove_junk($unit['name']); ?></td>
              <td><?php echo !empty($unit['description']) ? remove_junk($unit['description']) : '-'; ?></td>
              <td class="text-center">
                <div class="btn-group">
                  <?php if(role_can_action('satuan','update')): ?>
                    <a href="edit_unit.php?id=<?php echo (int)$unit['id']; ?>" class="btn btn-info btn-xs" title="Edit"><span class="glyphicon glyphicon-edit"></span></a>
                  <?php endif; ?>
                  <?php if(role_can_action('satuan','delete')): ?>
                    <a href="delete_unit.php?id=<?php echo (int)$unit['id']; ?>" class="btn btn-danger btn-xs" title="Hapus"><span class="glyphicon glyphicon-trash"></span></a>
                  <?php endif; ?>
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
<?php include_once('layouts/footer.php'); ?>
