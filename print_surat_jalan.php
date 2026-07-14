<?php
  require_once('includes/load.php');
  require_permission('surat_jalan','print');
  ensure_consignment_tables();

  $delivery_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  $order = find_delivery_order_details($delivery_id);

  if(!$order){
    $session->msg('d','Surat jalan tidak ditemukan.');
    redirect('delivery_orders.php', false);
  }

  if($order['movement_type'] === 'out' && (int)$order['stock_processed'] !== 1){
    $session->msg('d','Pengambilan belum diproses. Proses pengambilan dari halaman Request sebelum mencetak Surat Jalan.');
    redirect('pickup_requests.php', false);
  }

  $viewer = current_user();
  $viewer_client_id = is_client_user($viewer) ? (int)$viewer['id'] : null;
  $order_items = array();
  if(isset($order['items']) && is_array($order['items'])){
    $order_items = $order['items'];
  } elseif(function_exists('find_delivery_order_items')){
    $order_items = find_delivery_order_items($delivery_id, $viewer_client_id);
  }
  if(!is_array($order_items)){ $order_items = array(); }
  $delivery_product_cache = array();
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
      'no_batch' => isset($order['no_batch']) ? $order['no_batch'] : null,
      'grade' => isset($order['grade']) ? $order['grade'] : null,
      'tebal' => isset($order['tebal']) ? $order['tebal'] : null,
      'lebar' => isset($order['lebar']) ? $order['lebar'] : null,
      'panjang' => isset($order['panjang']) ? $order['panjang'] : null,
      'legacy_quantity' => isset($order['quantity']) ? (int)$order['quantity'] : 0
    );
  }
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Surat Jalan <?php echo remove_junk($order['document_no']); ?></title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css"/>
  <style>
    body { padding: 32px; }
    .doc-box { max-width: 980px; margin: 0 auto; }
    .doc-title { border-bottom: 2px solid #222; margin-bottom: 24px; padding-bottom: 12px; }
    .signature { margin-top: 56px; }
    .signature .box { min-height: 92px; border-bottom: 1px solid #333; }
    .item-note { color: #666; font-size: 11px; }
    @media print { .no-print { display: none; } body { padding: 0; } }
  </style>
</head>
<body>
  <div class="doc-box">
    <div class="doc-title clearfix">
      <div class="pull-left">
        <h2>Surat Jalan</h2>
        <strong>Sistem Penitipan Barang</strong>
      </div>
      <div class="pull-right text-right">
        <h4><?php echo remove_junk($order['document_no']); ?></h4>
        <p><?php echo remove_junk($order['document_date']); ?></p>
        <?php if(!empty($order['request_no'])): ?><p>Request: <?php echo remove_junk($order['request_no']); ?></p><?php endif; ?>
      </div>
    </div>

    <div class="row">
      <div class="col-xs-6">
        <p><strong>Client:</strong> <?php echo !empty($order['client_name']) ? remove_junk($order['client_name']) : '-'; ?></p>
        <p><strong>Jenis Mutasi:</strong> <?php echo delivery_movement_label($order['movement_type']); ?></p>
      </div>
      <div class="col-xs-6">
        <p><strong>Penerima:</strong> <?php echo !empty($order['recipient']) ? remove_junk($order['recipient']) : '-'; ?></p>
        <p><strong>Driver/Kendaraan:</strong> <?php echo !empty($order['driver_name']) ? remove_junk($order['driver_name']) : '-'; ?><?php echo !empty($order['vehicle_no']) ? ' / '.remove_junk($order['vehicle_no']) : ''; ?></p>
      </div>
    </div>

    <table class="table table-bordered">
      <thead>
        <tr>
          <th style="width:45px;" class="text-center">No</th>
          <th>No Surat Jalan / Barang</th>
          <th>No Batch</th>
          <th class="text-center">Grade</th>
          <th class="text-center">Ukuran (mm)</th>
          <th class="text-center">Kemasan</th>
          <th class="text-center">Isi Bundle</th>
        </tr>
      </thead>
      <tbody>
        <?php $item_no = 1; ?>
        <?php foreach($order_items as $item): ?>
          <?php
            $package_unit = !empty($item['package_unit_name']) ? $item['package_unit_name'] : (!empty($item['unit_name']) ? $item['unit_name'] : 'bundle');
            $base_quantity = isset($item['base_quantity']) ? (int)$item['base_quantity'] : (isset($item['quantity']) ? (int)$item['quantity'] : 0);
            $base_unit = !empty($item['base_unit_name']) ? $item['base_unit_name'] : 'lembar/pcs';
            $product_name = !empty($item['product_name']) ? $item['product_name'] : (isset($item['name']) ? $item['name'] : '-');
          ?>
          <tr>
            <td class="text-center"><?php echo $item_no++; ?></td>
            <td>
              <strong><?php echo !empty($item['no_surat_jalan']) ? remove_junk($item['no_surat_jalan']) : '-'; ?></strong><br>
              <?php echo remove_junk($product_name); ?>
            </td>
            <td><?php echo !empty($item['no_batch']) ? remove_junk($item['no_batch']) : '-'; ?></td>
            <td class="text-center"><?php echo !empty($item['grade']) ? remove_junk($item['grade']) : '-'; ?></td>
            <td class="text-center"><?php echo format_product_size($item); ?></td>
            <?php if(isset($item['legacy_quantity'])): ?>
              <td class="text-center">-</td>
              <td class="text-center"><strong><?php echo (int)$item['legacy_quantity']; ?></strong><br><span class="item-note">satuan data lama</span></td>
            <?php else: ?>
              <td class="text-center">1 <?php echo remove_junk($package_unit); ?></td>
              <td class="text-center"><strong><?php echo $base_quantity; ?></strong> <?php echo remove_junk($base_unit); ?></td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
        <?php if(empty($order_items)): ?>
          <tr><td colspan="7" class="text-center">Detail item Surat Jalan tidak tersedia.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <?php if(!empty($order['note'])): ?>
      <p><strong>Catatan:</strong> <?php echo remove_junk($order['note']); ?></p>
    <?php endif; ?>

    <?php $page_url = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>
    <div class="row signature">
      <div class="col-xs-4 text-center">
        <div class="box" style="border:none; display:flex; justify-content:center; align-items:center; min-height:92px;">
          <img src="https://api.qrserver.com/v1/create-qr-code/?size=85x85&amp;data=<?php echo urlencode($page_url); ?>" alt="QR Code" />
        </div>
        <p style="margin-top:5px;">Dibuat Oleh<br><small>Admin</small></p>
      </div>
      <div class="col-xs-4 text-center"><div class="box"></div><p>Dikeluarkan Oleh<br><small>Kasi Gudang</small></p></div>
      <div class="col-xs-4 text-center"><div class="box"></div><p>Diperiksa Oleh<br><small>Kabag Gudang</small></p></div>
    </div>
    <div class="row signature" style="margin-top:24px;">
      <div class="col-xs-4 text-center"><div class="box"></div><p>Disetujui Oleh<br><small>Head Warehouse</small></p></div>
      <div class="col-xs-4 text-center">
        <div class="box"></div>
        <p>Sopir / No Mobil<br><small><?php echo !empty($order['driver_name']) ? remove_junk($order['driver_name']) : '&nbsp;'; ?><?php echo !empty($order['vehicle_no']) ? ' / '.remove_junk($order['vehicle_no']) : ''; ?></small></p>
      </div>
      <div class="col-xs-4 text-center">
        <div class="box"></div>
        <p>Diterima Oleh<br><small><?php echo !empty($order['recipient']) ? remove_junk($order['recipient']) : (!empty($order['client_name']) ? remove_junk($order['client_name']) : '&nbsp;'); ?></small></p>
      </div>
    </div>

    <div class="no-print">
      <button type="button" onclick="window.print()" class="btn btn-primary">Cetak Surat Jalan</button>
      <a href="delivery_orders.php" class="btn btn-default">Kembali</a>
    </div>
  </div>
</body>
</html>
<?php if(isset($db)) { $db->db_disconnect(); } ?>
