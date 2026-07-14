<?php
  $page_title = 'Tolak Request Pengambilan';
  require_once('includes/load.php');
  require_permission('pickup','process');

  $request_id = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (isset($_POST['id']) ? (int)$_POST['id'] : 0)
    : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
  $request = find_pickup_request_details($request_id);

  if(!$request || !in_array($request['status'], array('pending','approved'), true)){
    $session->msg('d','Request tidak ditemukan atau sudah diproses.');
    redirect('pickup_requests.php', false);
  }
  $revoke_approval = $request['status'] === 'approved';

  $request_items = function_exists('find_pickup_request_items')
    ? find_pickup_request_items((int)$request['id'])
    : array();
  if(!is_array($request_items)){ $request_items = array(); }
  $request_product_cache = array();
  foreach($request_items as $request_item_index => $request_item){
    $item_product_id = isset($request_item['product_id']) ? (int)$request_item['product_id'] : 0;
    if($item_product_id > 0 && empty($request_item['product_name'])){
      if(!array_key_exists($item_product_id, $request_product_cache)){
        $request_product_cache[$item_product_id] = find_product_details($item_product_id);
      }
      if($request_product_cache[$item_product_id]){
        $request_item = array_merge($request_product_cache[$item_product_id], $request_item);
        if(empty($request_item['product_name']) && !empty($request_item['name'])){ $request_item['product_name'] = $request_item['name']; }
        $request_items[$request_item_index] = $request_item;
      }
    }
  }

  if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_request'])){
    $csrf_valid = warehouse_csrf_is_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '');
    if(!$csrf_valid){
      $session->msg('d','Sesi aksi tidak valid atau sudah kedaluwarsa. Silakan coba kembali.');
      redirect('pickup_requests.php', false);
    }

    $req_fields = array('reason');
    validate_fields($req_fields);
    if(empty($errors) && reject_pickup_request((int)$request['id'], $_POST['reason'])){
      $session->msg('s',$revoke_approval ? 'Persetujuan dibatalkan, Surat Jalan yang belum diproses dibatalkan, dan seluruh bundle dilepaskan.' : 'Request pengambilan berhasil ditolak dan seluruh bundle telah dilepaskan.');
      redirect('pickup_requests.php', false);
    }

    $session->msg('d', empty($errors) ? 'Request gagal ditolak. Tidak ada status atau stok yang diubah.' : $errors);
    redirect('reject_pickup_request.php?id='.(int)$request['id'], false);
  }

  $msg = $session->msg();
?>
<?php include_once('layouts/header.php'); ?>
<div class="row"><div class="col-md-12"><?php echo display_msg($msg); ?></div></div>
<div class="row">
  <div class="col-md-8">
    <div class="panel panel-default">
      <div class="panel-heading"><strong><span class="glyphicon glyphicon-remove"></span> <?php echo $revoke_approval ? 'Batalkan Persetujuan Pengambilan' : 'Tolak Request Pengambilan'; ?></strong></div>
      <div class="panel-body">
        <?php if($revoke_approval): ?>
          <div class="alert alert-warning">Aksi ini hanya untuk pengambilan yang batal sebelum tombol <strong>Proses Pengambilan</strong> ditekan. Surat Jalan keluar yang belum diproses akan dibatalkan dan reservasi bundle dilepas.</div>
        <?php endif; ?>
        <p><strong>No Request:</strong> <?php echo remove_junk($request['request_no']); ?></p>
        <p><strong>Client:</strong> <?php echo remove_junk($request['client_name']); ?></p>

        <div class="table-responsive">
          <table class="table table-bordered table-condensed">
            <thead>
              <tr>
                <th>No Surat Jalan</th>
                <th>Barang</th>
                <th class="text-center">Kemasan</th>
                <th class="text-center">Isi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($request_items as $item): ?>
                <?php
                  $package_unit = !empty($item['package_unit_name']) ? $item['package_unit_name'] : (!empty($item['unit_name']) ? $item['unit_name'] : 'bundle');
                  $base_quantity = isset($item['base_quantity']) ? (int)$item['base_quantity'] : (isset($item['quantity']) ? (int)$item['quantity'] : 0);
                  $base_unit = !empty($item['base_unit_name']) ? $item['base_unit_name'] : 'lembar/pcs';
                ?>
                <tr>
                  <td><?php echo !empty($item['no_surat_jalan']) ? remove_junk($item['no_surat_jalan']) : '-'; ?></td>
                  <td><?php echo !empty($item['product_name']) ? remove_junk($item['product_name']) : '-'; ?></td>
                  <td class="text-center">1 <?php echo remove_junk($package_unit); ?></td>
                  <td class="text-center"><?php echo $base_quantity; ?> <?php echo remove_junk($base_unit); ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if(empty($request_items)): ?>
                <tr><td colspan="4" class="text-center text-muted">Detail bundle tidak tersedia. Periksa data request sebelum menolak.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <form method="post" action="reject_pickup_request.php">
          <?php echo warehouse_csrf_field(); ?>
          <input type="hidden" name="id" value="<?php echo (int)$request['id']; ?>">
          <div class="form-group">
            <label><?php echo $revoke_approval ? 'Alasan Pembatalan Persetujuan' : 'Alasan Penolakan'; ?></label>
            <textarea class="form-control" name="reason" rows="4" placeholder="Wajib isi alasan" required></textarea>
          </div>
          <button type="submit" name="reject_request" class="btn btn-danger"><?php echo $revoke_approval ? 'Batalkan Persetujuan' : 'Tolak Request'; ?></button>
          <a href="pickup_requests.php" class="btn btn-default">Kembali</a>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include_once('layouts/footer.php'); ?>
