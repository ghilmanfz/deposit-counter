<?php
  require_once('includes/load.php');
  require_permission('surat_jalan','print');
  ensure_consignment_tables();

  $delivery_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  $order = find_delivery_order_details($delivery_id);

  if(!$order){
    $session->msg('d',' Surat jalan tidak ditemukan.');
    redirect('delivery_orders.php', false);
  }

  if($order['movement_type'] === 'out' && (int)$order['stock_processed'] === 0){
    require_permission('surat_jalan','process');
    if(!process_delivery_order_stock($delivery_id)){
      $session->msg('d',' Surat jalan tidak bisa diproses karena stok tidak mencukupi atau data tidak valid.');
      redirect('delivery_orders.php', false);
    }
    $order = find_delivery_order_details($delivery_id);
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
    .doc-box { max-width: 900px; margin: 0 auto; }
    .doc-title { border-bottom: 2px solid #222; margin-bottom: 24px; padding-bottom: 12px; }
    .signature { margin-top: 56px; }
    .signature .box { min-height: 92px; border-bottom: 1px solid #333; }
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
      </div>
    </div>

    <div class="row">
      <div class="col-xs-6">
        <p><strong>Client:</strong> <?php echo !empty($order['client_name']) ? remove_junk($order['client_name']) : '-'; ?></p>
        <p><strong>Jenis Mutasi:</strong> <?php echo delivery_movement_label($order['movement_type']); ?></p>
      </div>
      <div class="col-xs-6">
        <p><strong>Penerima:</strong> <?php echo !empty($order['recipient']) ? remove_junk($order['recipient']) : '-'; ?></p>
        <p><strong>Driver/Kendaraan:</strong> <?php echo !empty($order['driver_name']) ? remove_junk($order['driver_name']) : '-'; ?> <?php echo !empty($order['vehicle_no']) ? ' / '.remove_junk($order['vehicle_no']) : ''; ?></p>
      </div>
    </div>

    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Barang Titipan</th>
          <th>No Batch</th>
          <th class="text-center">Grade</th>
          <th class="text-center">Ukuran (mm)</th>
          <th class="text-center">Jumlah</th>
          <th class="text-center">Satuan</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><?php echo !empty($order['product_name']) ? remove_junk($order['product_name']) : '-'; ?></td>
          <td><?php echo !empty($order['no_batch']) ? remove_junk($order['no_batch']) : '-'; ?></td>
          <td class="text-center"><?php echo !empty($order['grade']) ? remove_junk($order['grade']) : '-'; ?></td>
          <td class="text-center"><?php echo format_product_size($order); ?></td>
          <td class="text-center"><?php echo (int)$order['quantity']; ?></td>
          <td class="text-center"><?php echo !empty($order['unit_name']) ? remove_junk($order['unit_name']) : '-'; ?></td>
        </tr>
      </tbody>
    </table>
    <?php if(!empty($order['note'])): ?>
    <p><strong>Catatan:</strong> <?php echo remove_junk($order['note']); ?></p>
    <?php endif; ?>

    <?php
      $page_url = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    ?>
    <div class="row signature">
      <div class="col-xs-4 text-center">
        <div class="box" style="border: none; display: flex; justify-content: center; align-items: center; min-height: 92px;">
          <img src="https://api.qrserver.com/v1/create-qr-code/?size=85x85&data=<?php echo urlencode($page_url); ?>" alt="QR Code" />
        </div>
        <p style="margin-top: 5px;">Dibuat Oleh<br><small>Admin</small></p>
      </div>
      <div class="col-xs-4 text-center">
        <div class="box"></div>
        <p>Dikeluarkan Oleh<br><small>Kasi Gudang</small></p>
      </div>
      <div class="col-xs-4 text-center">
        <div class="box"></div>
        <p>Diperiksa Oleh<br><small>Kabag Gudang</small></p>
      </div>
    </div>
    <div class="row signature" style="margin-top: 24px;">
      <div class="col-xs-4 text-center">
        <div class="box"></div>
        <p>Disetujui Oleh<br><small>Head Warehouse</small></p>
      </div>
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
      <button onclick="window.print()" class="btn btn-primary">Cetak Surat Jalan</button>
      <a href="delivery_orders.php" class="btn btn-default">Kembali</a>
    </div>
  </div>
</body>
</html>
<?php if(isset($db)) { $db->db_disconnect(); } ?>
