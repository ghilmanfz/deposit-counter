<?php
  $page_title = 'Barang Saya';
  require_once('includes/load.php');
  page_require_level(4);

  if(!is_client_user()){
    redirect_by_user_level();
  }

  $products = join_product_table(current_user()['id']);
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
          <span class="glyphicon glyphicon-th-large"></span>
          <span>Barang Saya di Gudang</span>
        </strong>
      </div>
      <div class="panel-body">
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th class="text-center" style="width: 50px;">#</th>
              <th>Foto Produk</th>
              <th>Barang</th>
              <th class="text-center">Kategori</th>
              <th class="text-center">Stok Saat Ini</th>
              <th class="text-center">Keluar</th>
              <th class="text-center">Satuan</th>
              <th class="text-center">Cacat</th>
              <th class="text-center">Foto Cacat</th>
              <th class="text-center">Tanggal Masuk</th>
              <th class="text-center">Tanggal Keluar</th>
              <th class="text-center">Terakhir Dicatat</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($products as $product): ?>
            <tr>
              <td class="text-center"><?php echo count_id(); ?></td>
              <td class="text-center">
                <?php if($product['media_id'] === '0'): ?>
                  <img class="img-avatar img-circle" src="uploads/products/no_image.png" alt="no-image" style="width:60px; height:60px;" />
                <?php else: ?>
                  <img class="img-avatar img-circle" src="uploads/products/<?php echo remove_junk($product['image']); ?>" alt="product-image" style="width:60px; height:60px; object-fit:cover;" />
                <?php endif; ?>
              </td>
              <td><?php echo remove_junk($product['name']); ?></td>
              <td class="text-center"><?php echo remove_junk($product['categorie']); ?></td>
              <td class="text-center"><?php echo (int)$product['quantity']; ?></td>
              <td class="text-center"><?php echo (int)$product['total_out']; ?></td>
              <td class="text-center"><?php echo !empty($product['unit_name']) ? remove_junk($product['unit_name']) : '-'; ?></td>
              <?php $defect_summary = find_product_defect_summary((int)$product['id']); ?>
              <td class="text-center"><a href="product_defects.php?product_id=<?php echo (int)$product['id']; ?>" class="btn btn-default btn-xs"><?php echo (int)$defect_summary['total_defect']; ?></a></td>
              <td class="text-center">
                <?php $defect_photos = find_product_defect_photos_by_product((int)$product['id']); ?>
                <?php if(!empty($defect_photos)): ?>
                  <div style="display:flex; justify-content:center; gap:4px; flex-wrap:wrap;">
                  <?php foreach($defect_photos as $photo): ?>
                    <a href="uploads/defects/<?php echo remove_junk($photo['file_name']); ?>" target="_blank">
                      <img src="uploads/defects/<?php echo remove_junk($photo['file_name']); ?>" class="img-thumbnail" style="width:60px; height:60px; object-fit:cover;" alt="defect-photo" />
                    </a>
                  <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
              <td class="text-center"><?php echo read_date($product['date']); ?></td>
              <td class="text-center"><?php echo !empty($product['last_out_date']) ? read_date($product['last_out_date']) : '-'; ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include_once('layouts/footer.php'); ?>
