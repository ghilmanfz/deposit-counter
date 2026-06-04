<?php
  $page_title = 'Surat Jalan';
  require_once('includes/load.php');
  page_require_level(4);
  ensure_consignment_tables();

  $user = current_user();
  $client_view = is_client_user($user);
  $delivery_orders = find_all_delivery_orders($client_view ? (int)$user['id'] : null);
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
      <div class="panel-heading clearfix">
        <strong>
          <span class="glyphicon glyphicon-file"></span>
          <span>Daftar Surat Jalan</span>
        </strong>
      </div>
      <div class="panel-body">
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th class="text-center" style="width: 50px;">#</th>
              <th>Nomor Surat Jalan</th>
              <?php if(!$client_view): ?>
              <th>Client</th>
              <?php endif; ?>
              <th>Barang</th>
              <th class="text-center">Jenis Mutasi</th>
              <th class="text-center">Jumlah</th>
              <th class="text-center">Satuan</th>
              <th class="text-center">Tanggal</th>
              <th class="text-center">Status Stok</th>
              <th>Catatan</th>
              <th class="text-center" style="width: 90px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($delivery_orders as $order): ?>
            <tr>
              <td class="text-center"><?php echo count_id(); ?></td>
              <td><?php echo remove_junk($order['document_no']); ?></td>
              <?php if(!$client_view): ?>
              <td><?php echo !empty($order['client_name']) ? remove_junk($order['client_name']) : '-'; ?></td>
              <?php endif; ?>
              <td><?php echo !empty($order['product_name']) ? remove_junk($order['product_name']) : '-'; ?></td>
              <td class="text-center"><?php echo delivery_movement_label($order['movement_type']); ?></td>
              <td class="text-center"><?php echo (int)$order['quantity']; ?></td>
              <td class="text-center"><?php echo !empty($order['unit_name']) ? remove_junk($order['unit_name']) : '-'; ?></td>
              <td class="text-center"><?php echo remove_junk($order['document_date']); ?></td>
              <td class="text-center"><?php echo ((int)$order['stock_processed'] === 1) ? 'Sudah diproses' : 'Belum dipotong'; ?></td>
              <td><?php echo !empty($order['note']) ? remove_junk($order['note']) : '-'; ?></td>
              <td class="text-center">
                <a href="print_surat_jalan.php?id=<?php echo (int)$order['id']; ?>" class="btn btn-info btn-xs" title="Cetak Surat Jalan" data-toggle="tooltip">
                  <span class="glyphicon glyphicon-print"></span>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($delivery_orders)): ?>
            <tr>
              <td colspan="<?php echo $client_view ? 10 : 11; ?>" class="text-center">Belum ada data surat jalan.</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include_once('layouts/footer.php'); ?>
