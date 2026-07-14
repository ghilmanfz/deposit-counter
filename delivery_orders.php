<?php
  $page_title = 'Surat Jalan';
  require_once('includes/load.php');
  require_permission('surat_jalan','view');
  ensure_consignment_tables();

  $user = current_user();
  $client_view = is_client_user($user);
  $viewer_client_id = $client_view ? (int)$user['id'] : null;
  $delivery_orders = find_all_delivery_orders($viewer_client_id);
  if(!is_array($delivery_orders)){ $delivery_orders = array(); }
  $delivery_product_cache = array();
  $msg = $session->msg();
?>
<?php include_once('layouts/header.php'); ?>

<div class="row"><div class="col-md-12"><?php echo display_msg($msg); ?></div></div>

<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading clearfix">
        <strong><span class="glyphicon glyphicon-file"></span> <span>Daftar Surat Jalan</span></strong>
      </div>
      <div class="panel-body">
        <div class="alert alert-info" style="padding:10px 15px;">
          Surat Jalan hanya dapat dicetak setelah pengambilan selesai diproses. Membuka dokumen tidak mengubah stok.
        </div>
        <div class="table-responsive">
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th class="text-center" style="width:50px;">#</th>
                <th>Nomor Surat Jalan</th>
                <?php if(!$client_view): ?><th>Client</th><?php endif; ?>
                <th>Bundle / Barang</th>
                <th class="text-center">Jenis Mutasi</th>
                <th class="text-center">Kemasan &amp; Isi</th>
                <th class="text-center">Tanggal</th>
                <th class="text-center">Status Stok</th>
                <th>Catatan</th>
                <th class="text-center" style="width:90px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($delivery_orders as $order): ?>
                <?php
                  $order_items = array();
                  if(isset($order['items']) && is_array($order['items'])){
                    $order_items = $order['items'];
                  } elseif(function_exists('find_delivery_order_items')){
                    $order_items = find_delivery_order_items((int)$order['id'], $viewer_client_id);
                  }
                  if(!is_array($order_items)){ $order_items = array(); }
                  foreach($order_items as $order_item_index => $order_item){
                    $item_product_id = isset($order_item['product_id']) ? (int)$order_item['product_id'] : 0;
                    if($item_product_id > 0 && empty($order_item['product_name'])){
                      if(!array_key_exists($item_product_id, $delivery_product_cache)){
                        $delivery_product_cache[$item_product_id] = find_product_details($item_product_id, $viewer_client_id);
                      }
                      if($delivery_product_cache[$item_product_id]){
                        $order_item = array_merge($delivery_product_cache[$item_product_id], $order_item);
                        if(empty($order_item['product_name']) && !empty($order_item['name'])){ $order_item['product_name'] = $order_item['name']; }
                        $order_items[$order_item_index] = $order_item;
                      }
                    }
                  }

                  if(empty($order_items) && !empty($order['product_name'])){
                    $order_items[] = array(
                      'product_name' => $order['product_name'],
                      'legacy_quantity' => isset($order['quantity']) ? (int)$order['quantity'] : 0
                    );
                  }
                  $stock_processed = isset($order['stock_processed']) ? (int)$order['stock_processed'] : 0;
                ?>
                <tr>
                  <td class="text-center"><?php echo count_id(); ?></td>
                  <td>
                    <strong><?php echo remove_junk($order['document_no']); ?></strong>
                    <?php if(!empty($order['request_no'])): ?><br><small class="text-muted">Request: <?php echo remove_junk($order['request_no']); ?></small><?php endif; ?>
                  </td>
                  <?php if(!$client_view): ?><td><?php echo !empty($order['client_name']) ? remove_junk($order['client_name']) : '-'; ?></td><?php endif; ?>
                  <td>
                    <?php foreach($order_items as $item): ?>
                      <?php
                        $product_name = !empty($item['product_name']) ? $item['product_name'] : (isset($item['name']) ? $item['name'] : '-');
                      ?>
                      <div style="margin-bottom:6px;">
                        <?php echo remove_junk($product_name); ?>
                        <br><small class="text-muted">No SJ: <?php echo !empty($item['no_surat_jalan']) ? remove_junk($item['no_surat_jalan']) : '-'; ?></small>
                      </div>
                    <?php endforeach; ?>
                    <?php if(empty($order_items)): ?><span class="text-muted">Detail item tidak tersedia.</span><?php endif; ?>
                  </td>
                  <td class="text-center"><?php echo delivery_movement_label($order['movement_type']); ?></td>
                  <td class="text-center">
                    <?php foreach($order_items as $item): ?>
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
                  <td class="text-center"><?php echo remove_junk($order['document_date']); ?></td>
                  <td class="text-center">
                    <?php if($order['movement_type'] !== 'out'): ?>
                      <span class="label label-success">Sudah dicatat</span>
                    <?php elseif($stock_processed === 1): ?>
                      <span class="label label-primary">Pengambilan selesai</span>
                    <?php else: ?>
                      <span class="label label-warning">Menunggu proses</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo !empty($order['note']) ? remove_junk($order['note']) : '-'; ?></td>
                  <td class="text-center">
                    <?php $can_print_order = role_can_action('surat_jalan','print') && ($order['movement_type'] !== 'out' || $stock_processed === 1); ?>
                    <?php if($can_print_order): ?>
                      <a href="print_surat_jalan.php?id=<?php echo (int)$order['id']; ?>" class="btn btn-info btn-xs" title="Lihat / Cetak Surat Jalan" data-toggle="tooltip"><span class="glyphicon glyphicon-print"></span></a>
                    <?php else: ?>
                      <span class="text-muted" title="Proses pengambilan dari halaman Request terlebih dahulu">-</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if(empty($delivery_orders)): ?>
                <tr><td colspan="<?php echo $client_view ? 9 : 10; ?>" class="text-center">Belum ada data surat jalan.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once('layouts/footer.php'); ?>
