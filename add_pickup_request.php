<?php
  $page_title = 'Buat Request Pengambilan Barang';
  require_once('includes/load.php');
  require_permission('pickup','create');

  if(!is_client_user()){
    redirect_by_user_level();
  }

  $user = current_user();
  $client_id = (int)$user['id'];
  $bundle_backend_ready = function_exists('find_available_inventory_bundles') && function_exists('create_multi_bundle_pickup_request');
  $bundles = $bundle_backend_ready ? find_available_inventory_bundles($client_id) : array();
  if(!is_array($bundles)){ $bundles = array(); }
  $product_details_cache = array();
  foreach($bundles as $bundle_index => $bundle){
    $product_id = isset($bundle['product_id']) ? (int)$bundle['product_id'] : 0;
    if($product_id > 0 && (empty($bundle['no_surat_jalan']) || empty($bundle['grade']) || !isset($bundle['tebal']))){
      if(!array_key_exists($product_id, $product_details_cache)){
        $product_details_cache[$product_id] = find_product_details($product_id, $client_id);
      }
      if($product_details_cache[$product_id]){
        $bundle = array_merge($product_details_cache[$product_id], $bundle);
        if(empty($bundle['product_name']) && !empty($bundle['name'])){ $bundle['product_name'] = $bundle['name']; }
        $bundles[$bundle_index] = $bundle;
      }
    }
  }

  if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])){
    $csrf_valid = warehouse_csrf_is_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '');
    if(!$csrf_valid){
      $session->msg('d','Sesi formulir tidak valid atau sudah kedaluwarsa. Silakan coba kembali.');
      redirect('add_pickup_request.php', false);
    }

    $req_fields = array('pickup_date','pickup_time','driver_name','vehicle_no');
    validate_fields($req_fields);
    if(!empty($errors) && !is_array($errors)){
      $errors = array($errors);
    } elseif(empty($errors)){
      $errors = array();
    }

    $bundle_ids = array();
    if(isset($_POST['bundle_ids']) && is_array($_POST['bundle_ids'])){
      foreach($_POST['bundle_ids'] as $bundle_id){
        $bundle_id = (int)$bundle_id;
        if($bundle_id > 0){ $bundle_ids[$bundle_id] = $bundle_id; }
      }
    }
    $bundle_ids = array_values($bundle_ids);

    if(empty($bundle_ids)){
      $errors[] = 'Pilih minimal satu bundle yang akan diambil.';
    }
    if(!$bundle_backend_ready){
      $errors[] = 'Layanan bundle belum siap. Hubungi administrator.';
    }

    if(empty($errors)){
      $request_data = array(
        'client_id' => $client_id,
        'bundle_ids' => $bundle_ids,
        'pickup_date' => $_POST['pickup_date'],
        'pickup_time' => $_POST['pickup_time'],
        'driver_name' => $_POST['driver_name'],
        'vehicle_no' => $_POST['vehicle_no']
      );
      $request_id = create_multi_bundle_pickup_request($request_data, $bundle_ids);

      if($request_id){
        $session->msg('s','Request pengambilan berhasil dikirim. Bundle yang dipilih telah dicatat dan menunggu persetujuan admin.');
        redirect('pickup_requests.php', false);
      }

      $session->msg('d','Request gagal dibuat. Bundle mungkin sudah dipilih request lain atau stok telah berubah. Silakan periksa kembali.');
      redirect('add_pickup_request.php', false);
    }

    $session->msg('d', $errors);
    redirect('add_pickup_request.php', false);
  }

  $msg = $session->msg();
?>
<?php include_once('layouts/header.php'); ?>
<div class="row"><div class="col-md-12"><?php echo display_msg($msg); ?></div></div>

<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <strong><span class="glyphicon glyphicon-send"></span> Buat Request Pengambilan Barang</strong>
      </div>
      <div class="panel-body">
        <?php if(!$bundle_backend_ready): ?>
          <div class="alert alert-warning">Layanan bundle belum tersedia. Request baru belum dapat dibuat.</div>
        <?php elseif(empty($bundles)): ?>
          <div class="alert alert-info">Tidak ada bundle yang tersedia untuk diminta. Bundle yang sudah masuk request aktif tidak ditampilkan.</div>
        <?php endif; ?>

        <form method="post" action="add_pickup_request.php" id="pickup-request-form">
          <?php echo warehouse_csrf_field(); ?>

          <div class="form-group">
            <strong>Pilih Bundle yang Akan Diambil</strong>
            <p class="help-block">Satu checkbox mewakili satu bundle utuh. Isi tiap bundle dapat berbeda dan tidak dihitung dari rata-rata.</p>
          </div>

          <div class="table-responsive" style="max-height:420px; overflow-y:auto; border:1px solid #e5e7eb; margin-bottom:12px;">
            <table class="table table-bordered table-striped" id="bundle-table" style="margin-bottom:0;">
              <thead>
                <tr>
                  <th class="text-center" style="width:55px;">Pilih</th>
                  <th>No Surat Jalan</th>
                  <th>Barang / Batch</th>
                  <th class="text-center">Grade</th>
                  <th class="text-center">Ukuran (mm)</th>
                  <th class="text-center">Kemasan</th>
                  <th class="text-center">Isi Bundle</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($bundles as $bundle): ?>
                  <?php
                    $bundle_id = isset($bundle['bundle_id']) ? (int)$bundle['bundle_id'] : (isset($bundle['id']) ? (int)$bundle['id'] : 0);
                    $no_surat_jalan = !empty($bundle['no_surat_jalan']) ? $bundle['no_surat_jalan'] : '-';
                    $product_name = !empty($bundle['product_name']) ? $bundle['product_name'] : (isset($bundle['name']) ? $bundle['name'] : '-');
                    $no_batch = !empty($bundle['no_batch']) ? $bundle['no_batch'] : '-';
                    $grade = !empty($bundle['grade']) ? $bundle['grade'] : '-';
                    $package_unit = !empty($bundle['package_unit_name']) ? $bundle['package_unit_name'] : (!empty($bundle['unit_name']) ? $bundle['unit_name'] : 'bundle');
                    $base_quantity = isset($bundle['base_quantity']) ? (int)$bundle['base_quantity'] : (isset($bundle['quantity']) ? (int)$bundle['quantity'] : 0);
                    $base_unit = !empty($bundle['base_unit_name']) ? $bundle['base_unit_name'] : 'lembar/pcs';
                  ?>
                  <?php if($bundle_id > 0 && $base_quantity > 0): ?>
                  <tr>
                    <td class="text-center" style="vertical-align:middle;">
                      <input type="checkbox" class="bundle-checkbox" name="bundle_ids[]" value="<?php echo $bundle_id; ?>" data-base-quantity="<?php echo $base_quantity; ?>" data-base-unit="<?php echo htmlspecialchars($base_unit, ENT_QUOTES, 'UTF-8'); ?>">
                    </td>
                    <td><strong><?php echo remove_junk($no_surat_jalan); ?></strong></td>
                    <td><?php echo remove_junk($product_name); ?><br><small class="text-muted">Batch: <?php echo remove_junk($no_batch); ?></small></td>
                    <td class="text-center"><?php echo remove_junk($grade); ?></td>
                    <td class="text-center"><?php echo format_product_size($bundle); ?></td>
                    <td class="text-center">1 <?php echo remove_junk($package_unit); ?></td>
                    <td class="text-center"><strong><?php echo $base_quantity; ?></strong> <?php echo remove_junk($base_unit); ?></td>
                  </tr>
                  <?php endif; ?>
                <?php endforeach; ?>
                <?php if(empty($bundles)): ?>
                  <tr><td colspan="7" class="text-center text-muted">Belum ada bundle tersedia.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <div class="alert alert-info" id="selection-summary" style="padding:10px 15px;">Belum ada bundle dipilih.</div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Tanggal Rencana Penjemputan</label>
                <input type="date" class="form-control" name="pickup_date" value="<?php echo date('Y-m-d'); ?>" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Jam Rencana Penjemputan</label>
                <input type="time" class="form-control" name="pickup_time" value="09:00" required>
              </div>
            </div>
          </div>
          <p class="help-block">Jadwal bersifat rencana dan akan dikonfirmasi oleh pengelola gudang.</p>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Nama Supir</label>
                <input type="text" class="form-control" name="driver_name" placeholder="Nama supir" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Pelat Nomor Kendaraan</label>
                <input type="text" class="form-control" name="vehicle_no" placeholder="Contoh: B 1234 CD" required>
              </div>
            </div>
          </div>

          <button type="submit" name="submit_request" class="btn btn-primary" <?php echo empty($bundles) || !$bundle_backend_ready ? 'disabled="disabled"' : ''; ?>>Kirim Request</button>
          <a href="pickup_requests.php" class="btn btn-default">Kembali</a>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  var checks = document.querySelectorAll('#bundle-table .bundle-checkbox');
  var summary = document.getElementById('selection-summary');

  function updateSummary(){
    var selected = 0;
    var totals = {};
    for(var i = 0; i < checks.length; i++){
      if(!checks[i].checked){ continue; }
      selected++;
      var unit = checks[i].getAttribute('data-base-unit') || 'lembar/pcs';
      var qty = parseInt(checks[i].getAttribute('data-base-quantity'), 10) || 0;
      totals[unit] = (totals[unit] || 0) + qty;
    }
    if(selected === 0){
      summary.textContent = 'Belum ada bundle dipilih.';
      return;
    }
    var parts = [];
    for(var unitName in totals){
      if(Object.prototype.hasOwnProperty.call(totals, unitName)){
        parts.push(totals[unitName] + ' ' + unitName);
      }
    }
    summary.textContent = selected + ' bundle dipilih' + (parts.length ? ' - Total isi: ' + parts.join(', ') : '');
  }

  for(var i = 0; i < checks.length; i++){
    checks[i].addEventListener('change', updateSummary);
  }
  updateSummary();
})();
</script>
<?php include_once('layouts/footer.php'); ?>
