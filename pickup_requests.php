<?php
  $page_title = 'Request Pengambilan Barang';
  require_once('includes/load.php');
  require_permission('pickup','view');

  $user = current_user();
  $client_view = is_client_user($user);
  $viewer_client_id = $client_view ? (int)$user['id'] : null;
  $requests = find_pickup_requests($viewer_client_id);
  if(!is_array($requests)){ $requests = array(); }
  $request_product_cache = array();

  $msg = $session->msg();
?>
<?php include_once('layouts/header.php'); ?>
<div class="row"><div class="col-md-12"><?php echo display_msg($msg); ?></div></div>

<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading clearfix">
        <strong><span class="glyphicon glyphicon-send"></span> <span><?php echo $client_view ? 'Request Pengambilan Barang Saya' : 'Request Pengambilan Barang'; ?></span></strong>
        <?php if($client_view && role_can_action('pickup','create')): ?>
          <div class="pull-right"><a href="add_pickup_request.php" class="btn btn-primary">Buat Request</a></div>
        <?php endif; ?>
      </div>
      <div class="panel-body">
        <div class="alert alert-info" style="padding:10px 15px;">
          Stok hanya berubah saat admin memproses pengambilan atau pengiriman. Membuka atau mencetak Surat Jalan tidak memotong stok.
        </div>
        <div class="table-responsive">
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th class="text-center" style="width:50px;">#</th>
                <th>No Request</th>
                <?php if(!$client_view): ?><th>Client</th><?php endif; ?>
                <th>Bundle / Barang</th>
                <th class="text-center">Kemasan &amp; Isi</th>
                <th class="text-center">Metode</th>
                <th class="text-center">Jadwal</th>
                <th>Transportasi / Tujuan</th>
                <th class="text-center">Status</th>
                <th>Keterangan</th>
                <th class="text-center" style="width:185px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($requests as $req): ?>
                <?php
                  $request_items = array();
                  if(isset($req['items']) && is_array($req['items'])){
                    $request_items = $req['items'];
                  } elseif(function_exists('find_pickup_request_items')){
                    $request_items = find_pickup_request_items((int)$req['id'], $viewer_client_id);
                  }
                  if(!is_array($request_items)){ $request_items = array(); }
                  foreach($request_items as $request_item_index => $request_item){
                    $item_product_id = isset($request_item['product_id']) ? (int)$request_item['product_id'] : 0;
                    if($item_product_id > 0 && empty($request_item['product_name'])){
                      if(!array_key_exists($item_product_id, $request_product_cache)){
                        $request_product_cache[$item_product_id] = find_product_details($item_product_id, $viewer_client_id);
                      }
                      if($request_product_cache[$item_product_id]){
                        $request_item = array_merge($request_product_cache[$item_product_id], $request_item);
                        if(empty($request_item['product_name']) && !empty($request_item['name'])){ $request_item['product_name'] = $request_item['name']; }
                        $request_items[$request_item_index] = $request_item;
                      }
                    }
                  }

                  $legacy_item = false;
                  if(empty($request_items) && !empty($req['product_name'])){
                    $legacy_item = true;
                    $request_items[] = array(
                      'product_name' => $req['product_name'],
                      'legacy_quantity' => isset($req['quantity']) ? (int)$req['quantity'] : 0
                    );
                  }

                  $status = isset($req['status']) ? $req['status'] : 'pending';
                  $status_label = pickup_status_label($status);
                  $status_class = pickup_status_class($status);
                  if($status === 'cancelled'){
                    $status_label = 'Dibatalkan';
                    $status_class = 'default';
                  }
                  $fulfillment_method = normalize_pickup_fulfillment_method(isset($req['fulfillment_method']) ? $req['fulfillment_method'] : 'self_pickup');
                  if($fulfillment_method === null){ $fulfillment_method = 'self_pickup'; }
                  $is_delivery = $fulfillment_method === 'delivery';
                  $process_form_id = 'process-request-'.(int)$req['id'];
                ?>
                <tr>
                  <td class="text-center"><?php echo count_id(); ?></td>
                  <td>
                    <strong><?php echo remove_junk($req['request_no']); ?></strong>
                    <?php if(!empty($req['document_no'])): ?><br><small class="text-muted">SJ: <?php echo remove_junk($req['document_no']); ?></small><?php endif; ?>
                  </td>
                  <?php if(!$client_view): ?><td><?php echo remove_junk($req['client_name']); ?></td><?php endif; ?>
                  <td>
                    <?php foreach($request_items as $item): ?>
                      <?php
                        $no_surat_jalan = !empty($item['no_surat_jalan']) ? $item['no_surat_jalan'] : '-';
                        $product_name = !empty($item['product_name']) ? $item['product_name'] : (isset($item['name']) ? $item['name'] : 'Barang tidak tersedia');
                        $item_grade = !empty($item['grade']) ? $item['grade'] : '';
                        $item_batch = !empty($item['no_batch']) ? $item['no_batch'] : '';
                      ?>
                      <div style="margin-bottom:6px;">
                        <strong><?php echo remove_junk($product_name); ?></strong>
                        <br><small class="text-muted">No SJ: <?php echo remove_junk($no_surat_jalan); ?></small>
                        <?php if($item_grade !== '' || $item_batch !== ''): ?>
                          <br><small class="text-muted"><?php echo $item_grade !== '' ? 'Grade '.remove_junk($item_grade) : ''; ?><?php echo ($item_grade !== '' && $item_batch !== '') ? ' / ' : ''; ?><?php echo $item_batch !== '' ? 'Batch '.remove_junk($item_batch) : ''; ?></small>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                    <?php if(empty($request_items)): ?><span class="text-muted">Detail bundle tidak tersedia.</span><?php endif; ?>
                  </td>
                  <td class="text-center">
                    <?php foreach($request_items as $item): ?>
                      <?php
                        $package_unit = !empty($item['package_unit_name']) ? $item['package_unit_name'] : (!empty($item['unit_name']) ? $item['unit_name'] : 'bundle');
                        $base_quantity = isset($item['base_quantity']) ? (int)$item['base_quantity'] : (isset($item['quantity']) ? (int)$item['quantity'] : 0);
                        $base_unit = !empty($item['base_unit_name']) ? $item['base_unit_name'] : 'lembar/pcs';
                      ?>
                      <div style="margin-bottom:6px;">
                        <?php if(isset($item['legacy_quantity'])): ?>
                          <strong><?php echo (int)$item['legacy_quantity']; ?></strong> <span class="text-muted">(satuan data lama)</span>
                        <?php else: ?>
                          <strong>1 <?php echo remove_junk($package_unit); ?></strong><br>
                          <small class="text-muted">Isi: <?php echo $base_quantity; ?> <?php echo remove_junk($base_unit); ?></small>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </td>
                  <td class="text-center"><span class="label label-<?php echo $is_delivery ? 'info' : 'default'; ?>"><?php echo pickup_fulfillment_label($fulfillment_method); ?></span></td>
                  <td class="text-center"><?php echo remove_junk($req['pickup_date']); ?><br><?php echo substr($req['pickup_time'],0,5); ?></td>
                  <td>
                    <?php if($is_delivery): ?>
                      <strong>Alamat:</strong> <?php echo !empty($req['delivery_address']) ? nl2br(remove_junk($req['delivery_address'])) : '-'; ?>
                      <?php if(!$client_view && $status === 'approved'): ?>
                        <div style="margin-top:8px;">
                          <input type="text" class="form-control input-sm" name="driver_name" form="<?php echo $process_form_id; ?>" placeholder="Supir pengiriman" value="<?php echo !empty($req['driver_name']) ? remove_junk($req['driver_name']) : ''; ?>" required>
                          <input type="text" class="form-control input-sm" name="vehicle_no" form="<?php echo $process_form_id; ?>" placeholder="Pelat kendaraan" value="<?php echo !empty($req['vehicle_no']) ? remove_junk($req['vehicle_no']) : ''; ?>" required style="margin-top:5px;">
                        </div>
                      <?php elseif(!empty($req['driver_name']) || !empty($req['vehicle_no'])): ?>
                        <br><small class="text-muted"><?php echo remove_junk($req['driver_name']); ?><?php echo (!empty($req['driver_name']) && !empty($req['vehicle_no'])) ? ' / ' : ''; ?><?php echo remove_junk($req['vehicle_no']); ?></small>
                      <?php else: ?>
                        <br><small class="text-muted">Transportasi diisi gudang saat proses pengiriman.</small>
                      <?php endif; ?>
                    <?php else: ?>
                      <?php echo !empty($req['driver_name']) ? remove_junk($req['driver_name']) : '-'; ?><br><small class="text-muted"><?php echo !empty($req['vehicle_no']) ? remove_junk($req['vehicle_no']) : '-'; ?></small>
                    <?php endif; ?>
                  </td>
                  <td class="text-center"><span class="label label-<?php echo $status_class; ?>"><?php echo $status_label; ?></span></td>
                  <td><?php echo !empty($req['admin_note']) ? remove_junk($req['admin_note']) : '-'; ?></td>
                  <td class="text-center">
                    <div class="btn-group">
                      <?php if(!$client_view && $status === 'pending' && role_can_action('pickup','process')): ?>
                        <form method="post" action="process_pickup_request.php" style="display:inline;">
                          <?php echo warehouse_csrf_field(); ?>
                          <input type="hidden" name="id" value="<?php echo (int)$req['id']; ?>">
                          <input type="hidden" name="action" value="approve">
                          <button type="submit" class="btn btn-success btn-xs" title="Setujui" data-toggle="tooltip"><span class="glyphicon glyphicon-ok"></span></button>
                        </form>
                        <a href="reject_pickup_request.php?id=<?php echo (int)$req['id']; ?>" class="btn btn-danger btn-xs" title="Tolak" data-toggle="tooltip"><span class="glyphicon glyphicon-remove"></span></a>
                      <?php endif; ?>

                      <?php if(!$client_view && $status === 'approved' && role_can_action('pickup','process')): ?>
                        <form method="post" action="process_pickup_request.php" id="<?php echo $process_form_id; ?>" style="display:inline;" onsubmit="return confirm('<?php echo $is_delivery ? 'Proses pengiriman' : 'Proses pengambilan'; ?> bundle terpilih dan potong stok sekarang?');">
                          <?php echo warehouse_csrf_field(); ?>
                          <input type="hidden" name="id" value="<?php echo (int)$req['id']; ?>">
                          <input type="hidden" name="action" value="process">
                          <button type="submit" class="btn btn-warning btn-xs" title="<?php echo $is_delivery ? 'Proses Pengiriman' : 'Proses Pengambilan'; ?>"><span class="glyphicon glyphicon-log-out"></span> <?php echo $is_delivery ? 'Proses Pengiriman' : 'Proses Pengambilan'; ?></button>
                        </form>
                        <a href="reject_pickup_request.php?id=<?php echo (int)$req['id']; ?>" class="btn btn-danger btn-xs" title="Batalkan persetujuan dan lepaskan bundle"><span class="glyphicon glyphicon-remove"></span> Batalkan</a>
                      <?php endif; ?>

                      <?php if($client_view && $status === 'pending'): ?>
                        <form method="post" action="cancel_pickup_request.php" style="display:inline;" onsubmit="return confirm('Batalkan request ini dan lepaskan seluruh bundle?');">
                          <?php echo warehouse_csrf_field(); ?>
                          <input type="hidden" name="id" value="<?php echo (int)$req['id']; ?>">
                          <button type="submit" class="btn btn-default btn-xs" title="Batalkan Request" data-toggle="tooltip"><span class="glyphicon glyphicon-ban-circle"></span></button>
                        </form>
                      <?php endif; ?>

                      <?php $can_print = !empty($req['delivery_id']) && (int)$req['stock_processed'] === 1 && role_can_action('surat_jalan','print'); ?>
                      <?php if($can_print): ?>
                        <a href="print_surat_jalan.php?id=<?php echo (int)$req['delivery_id']; ?>" class="btn btn-info btn-xs" title="Lihat / Cetak Surat Jalan" data-toggle="tooltip"><span class="glyphicon glyphicon-print"></span></a>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if(empty($requests)): ?>
                <tr><td colspan="<?php echo $client_view ? 10 : 11; ?>" class="text-center">Belum ada request pengambilan barang.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include_once('layouts/footer.php'); ?>
