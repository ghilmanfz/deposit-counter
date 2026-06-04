<?php
  $page_title = 'Dashboard Client';
  require_once('includes/load.php');
  page_require_level(4);

  if(!is_client_user()){
    redirect_by_user_level();
  }

  $client = current_user();
  $inventory_summary = find_client_inventory_summary($client['id']);
  $movement_summary = find_client_movement_summary($client['id']);
  $recent_products = find_recent_product_added(5, $client['id']);
  $recent_movements = find_stock_movements(5, $client['id']);
  $msg = $session->msg();
?>
<?php include_once('layouts/header.php'); ?>

<div class="row">
  <div class="col-md-12">
    <?php echo display_msg($msg); ?>
  </div>
</div>

<div class="row">
  <div class="col-md-4">
    <div class="panel panel-box clearfix">
      <div class="panel-icon pull-left bg-blue2">
        <i class="glyphicon glyphicon-th-large"></i>
      </div>
      <div class="panel-value pull-right">
        <h2 class="margin-top"><?php echo (int)$inventory_summary['total_products']; ?></h2>
        <p class="text-muted">Barang Terdaftar</p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="panel panel-box clearfix">
      <div class="panel-icon pull-left bg-green">
        <i class="glyphicon glyphicon-shopping-cart"></i>
      </div>
      <div class="panel-value pull-right">
        <h2 class="margin-top"><?php echo (int)$inventory_summary['total_stock']; ?></h2>
        <p class="text-muted">Stok Saat Ini</p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="panel panel-box clearfix">
      <div class="panel-icon pull-left bg-red">
        <i class="glyphicon glyphicon-log-out"></i>
      </div>
      <div class="panel-value pull-right">
        <h2 class="margin-top"><?php echo (int)$movement_summary['total_out']; ?></h2>
        <p class="text-muted">Total Barang Diambil</p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="panel panel-default">
      <div class="panel-heading">
        <strong>
          <span class="glyphicon glyphicon-th"></span>
          <span>Barang Terbaru</span>
        </strong>
      </div>
      <div class="panel-body">
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th class="text-center" style="width: 50px;">#</th>
              <th>Barang</th>
              <th class="text-center">Stok</th>
              <th class="text-center">Satuan</th>
              <th class="text-center">Tanggal Masuk</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent_products as $product): ?>
            <tr>
              <td class="text-center"><?php echo count_id(); ?></td>
              <td><?php echo remove_junk($product['name']); ?></td>
              <td class="text-center"><?php echo (int)$product['quantity']; ?></td>
              <td class="text-center"><?php echo !empty($product['unit_name']) ? remove_junk($product['unit_name']) : '-'; ?></td>
              <td class="text-center"><?php echo read_date($product['date']); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="panel panel-default">
      <div class="panel-heading">
        <strong>
          <span class="glyphicon glyphicon-transfer"></span>
          <span>Riwayat Stok Terbaru</span>
        </strong>
      </div>
      <div class="panel-body">
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th class="text-center" style="width: 50px;">#</th>
              <th>Barang</th>
              <th class="text-center">Jenis</th>
              <th class="text-center">Jumlah</th>
              <th class="text-center">Satuan</th>
              <th class="text-center">Waktu</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent_movements as $movement): ?>
            <tr>
              <td class="text-center"><?php echo count_id(); ?></td>
              <td><?php echo remove_junk($movement['product_name']); ?></td>
              <td class="text-center"><?php echo $movement['movement_type'] === 'in' ? 'Masuk' : ($movement['movement_type'] === 'out' ? 'Keluar' : 'Penyesuaian'); ?></td>
              <td class="text-center"><?php echo (int)$movement['quantity']; ?></td>
              <td class="text-center"><?php echo !empty($movement['unit_name']) ? remove_junk($movement['unit_name']) : '-'; ?></td>
              <td class="text-center"><?php echo read_date($movement['created_at']); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include_once('layouts/footer.php'); ?>
