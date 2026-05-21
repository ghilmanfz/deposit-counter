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
              <th>Barang</th>
              <th class="text-center">Kategori</th>
              <th class="text-center">Stok Saat Ini</th>
              <th class="text-center">Terakhir Dicatat</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($products as $product): ?>
            <tr>
              <td class="text-center"><?php echo count_id(); ?></td>
              <td><?php echo remove_junk($product['name']); ?></td>
              <td class="text-center"><?php echo remove_junk($product['categorie']); ?></td>
              <td class="text-center"><?php echo (int)$product['quantity']; ?></td>
              <td class="text-center"><?php echo read_date($product['date']); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include_once('layouts/footer.php'); ?>
