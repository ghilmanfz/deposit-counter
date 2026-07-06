<?php
  $page_title = 'Data Barang Cacat';
  require_once('includes/load.php');
  if(is_client_user()){
    require_permission('barang_saya','view');
  } else {
    require_permission('barang','view');
  }

  $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
  $product = find_product_details($product_id);
  if(!$product){
    $session->msg('d',' Barang tidak ditemukan atau bukan milik Anda.');
    redirect(is_client_user() ? 'my_products.php' : 'product.php', false);
  }
  $defects = find_product_defects($product_id);
  $msg = $session->msg();
?>
<?php include_once('layouts/header.php'); ?>
<div class="row">
  <div class="col-md-12"><?php echo display_msg($msg); ?></div>
</div>
<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading clearfix">
        <strong><span class="glyphicon glyphicon-warning-sign"></span> <span>Informasi Barang Cacat</span></strong>
        <div class="pull-right">
          <a href="<?php echo is_client_user() ? 'my_products.php' : 'product.php'; ?>" class="btn btn-default btn-sm">Kembali</a>
        </div>
      </div>
      <div class="panel-body">
        <div class="row" style="margin-bottom:15px;">
          <div class="col-md-4 text-center">
            <strong>Foto Produk</strong>
            <div style="margin-top:10px;">
              <?php if($product['media_id'] === '0'): ?>
                <img src="uploads/products/no_image.png" class="img-thumbnail" style="width:100%; max-height:220px;" alt="No image">
              <?php else: ?>
                <img src="uploads/products/<?php echo remove_junk($product['image']); ?>" class="img-thumbnail" style="width:100%; max-height:220px;" alt="Foto produk">
              <?php endif; ?>
            </div>
          </div>
          <div class="col-md-8">
            <p><strong>Barang:</strong> <?php echo remove_junk($product['name']); ?></p>
            <p><strong>Stok:</strong> <?php echo (int)$product['quantity']; ?> <?php echo !empty($product['unit_name']) ? remove_junk($product['unit_name']) : ''; ?></p>
            <p class="text-muted">Barang cacat tetap dihitung ke stok. Informasi ini hanya sebagai catatan kondisi barang.</p>
          </div>
        </div>
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th class="text-center" style="width:50px;">#</th>
              <?php if(!is_client_user()): ?><th>Client</th><?php endif; ?>
              <th class="text-center">Jumlah Cacat</th>
              <th>Keterangan</th>
              <th>Foto Bukti</th>
              <th class="text-center">Tanggal</th>
              <th class="text-center">Petugas</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($defects as $defect): ?>
            <?php $photos = find_defect_photos((int)$defect['id']); ?>
            <tr>
              <td class="text-center"><?php echo count_id(); ?></td>
              <?php if(!is_client_user()): ?><td><?php echo !empty($defect['client_name']) ? remove_junk($defect['client_name']) : 'Internal'; ?></td><?php endif; ?>
              <td class="text-center"><?php echo (int)$defect['defect_qty']; ?></td>
              <td><?php echo !empty($defect['note']) ? remove_junk($defect['note']) : '-'; ?></td>
              <td>
                <?php if(!empty($photos)): ?>
                  <?php foreach($photos as $photo): ?>
                    <a href="uploads/defects/<?php echo remove_junk($photo['file_name']); ?>" target="_blank">
                      <img src="uploads/defects/<?php echo remove_junk($photo['file_name']); ?>" class="img-thumbnail" style="height:60px;margin:2px;" />
                    </a>
                  <?php endforeach; ?>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
              <td class="text-center"><?php echo read_date($defect['created_at']); ?></td>
              <td class="text-center"><?php echo !empty($defect['created_by_name']) ? remove_junk($defect['created_by_name']) : '-'; ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($defects)): ?>
            <tr><td colspan="<?php echo is_client_user() ? 6 : 7; ?>" class="text-center">Belum ada catatan barang cacat.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include_once('layouts/footer.php'); ?>
