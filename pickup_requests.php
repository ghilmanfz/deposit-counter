<?php
  $page_title = 'Request Pengambilan Barang';
  require_once('includes/load.php');
  require_permission('pickup','view');

  $user = current_user();
  $client_view = is_client_user($user);
  $requests = find_pickup_requests($client_view ? (int)$user['id'] : null);
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
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th class="text-center" style="width:50px;">#</th>
              <th>No Request</th>
              <?php if(!$client_view): ?><th>Client</th><?php endif; ?>
              <th>Barang</th>
              <th class="text-center">Jumlah</th>
              <th class="text-center">Jadwal Jemput</th>
              <th>Supir / Kendaraan</th>
              <th class="text-center">Status</th>
              <th>Keterangan</th>
              <th class="text-center" style="width:150px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($requests as $req): ?>
            <tr>
              <td class="text-center"><?php echo count_id(); ?></td>
              <td><?php echo remove_junk($req['request_no']); ?></td>
              <?php if(!$client_view): ?><td><?php echo remove_junk($req['client_name']); ?></td><?php endif; ?>
              <?php
                $product_name = !empty($req['product_name']) ? remove_junk($req['product_name']) : '';
                if($product_name === '' && !empty($req['product_id'])){
                  $product = find_product_details((int)$req['product_id']);
                  $product_name = $product ? remove_junk($product['name']) : 'Produk tidak tersedia';
                }
              ?>
              <td><?php echo $product_name; ?><br><small class="text-muted">Stok: <?php echo (int)$req['current_stock']; ?> <?php echo !empty($req['unit_name']) ? remove_junk($req['unit_name']) : ''; ?></small></td>
              <td class="text-center"><?php echo (int)$req['quantity']; ?> <?php echo !empty($req['unit_name']) ? remove_junk($req['unit_name']) : ''; ?></td>
              <td class="text-center"><?php echo remove_junk($req['pickup_date']); ?> <?php echo substr($req['pickup_time'],0,5); ?></td>
              <td><?php echo remove_junk($req['driver_name']); ?> / <?php echo remove_junk($req['vehicle_no']); ?></td>
              <td class="text-center"><span class="label label-<?php echo pickup_status_class($req['status']); ?>"><?php echo pickup_status_label($req['status']); ?></span></td>
              <td><?php echo !empty($req['admin_note']) ? remove_junk($req['admin_note']) : '-'; ?></td>
              <td class="text-center">
                <div class="btn-group">
                  <?php if(!$client_view && $req['status'] === 'pending' && role_can_action('pickup','process')): ?>
                    <a href="process_pickup_request.php?action=approve&id=<?php echo (int)$req['id']; ?>" class="btn btn-success btn-xs" title="Approve" data-toggle="tooltip"><span class="glyphicon glyphicon-ok"></span></a>
                    <a href="reject_pickup_request.php?id=<?php echo (int)$req['id']; ?>" class="btn btn-danger btn-xs" title="Reject" data-toggle="tooltip"><span class="glyphicon glyphicon-remove"></span></a>
                  <?php endif; ?>
                  <?php $can_print_request_order = !empty($req['delivery_id']) && role_can_action('surat_jalan','print') && ((int)$req['stock_processed'] === 1 || role_can_action('surat_jalan','process')); ?>
                  <?php if($can_print_request_order): ?>
                    <a href="print_surat_jalan.php?id=<?php echo (int)$req['delivery_id']; ?>" class="btn btn-info btn-xs" title="Cetak Surat Jalan" data-toggle="tooltip"><span class="glyphicon glyphicon-print"></span></a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($requests)): ?>
            <tr><td colspan="<?php echo $client_view ? 9 : 10; ?>" class="text-center">Belum ada request pengambilan barang.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include_once('layouts/footer.php'); ?>
