<?php
  $page_title = 'Riwayat Stok';
  require_once('includes/load.php');
  page_require_level(4);

  $user = current_user();
  $client_view = is_client_user($user);
  $movements = $client_view ? find_stock_movements(null, $user['id']) : find_stock_movements();
  $msg = $session->msg();
?>
<?php include_once('layouts/header.php'); ?>

<div class="row">
  <div class="col-md-12">
    <?php echo display_msg($msg); ?>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <strong>
          <span class="glyphicon glyphicon-transfer"></span>
          <span><?php echo $client_view ? 'Riwayat Stok Saya' : 'Riwayat Stok Gudang'; ?></span>
        </strong>
      </div>
      <div class="panel-body">
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th class="text-center" style="width: 50px;">#</th>
              <th class="text-center">Waktu</th>
              <th>Barang</th>
              <?php if(!$client_view): ?>
              <th>Client</th>
              <?php endif; ?>
              <th class="text-center">Jenis</th>
              <th class="text-center">Jumlah</th>
              <th class="text-center">Satuan</th>
              <th class="text-center">Sebelum</th>
              <th class="text-center">Sesudah</th>
              <th>Catatan</th>
              <th class="text-center">Dicatat Oleh</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($movements as $movement): ?>
            <tr>
              <td class="text-center"><?php echo count_id(); ?></td>
              <td class="text-center"><?php echo read_date($movement['created_at']); ?></td>
              <td><?php echo remove_junk($movement['product_name']); ?></td>
              <?php if(!$client_view): ?>
              <td><?php echo !empty($movement['client_name']) ? remove_junk($movement['client_name']) : 'Internal'; ?></td>
              <?php endif; ?>
              <td class="text-center"><?php echo $movement['movement_type'] === 'in' ? 'Masuk' : ($movement['movement_type'] === 'out' ? 'Keluar' : 'Penyesuaian'); ?></td>
              <td class="text-center"><?php echo (int)$movement['quantity']; ?></td>
              <td class="text-center"><?php echo !empty($movement['unit_name']) ? remove_junk($movement['unit_name']) : '-'; ?></td>
              <td class="text-center"><?php echo (int)$movement['quantity_before']; ?></td>
              <td class="text-center"><?php echo (int)$movement['quantity_after']; ?></td>
              <td><?php echo !empty($movement['note']) ? remove_junk($movement['note']) : '-'; ?></td>
              <td class="text-center"><?php echo !empty($movement['created_by_name']) ? remove_junk($movement['created_by_name']) : '-'; ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include_once('layouts/footer.php'); ?>
