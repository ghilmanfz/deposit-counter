<?php
  $page_title = 'Barang Saya';
  require_once('includes/load.php');
  page_require_level(4);

  if(!is_client_user()){
    redirect_by_user_level();
  }

  $products = join_product_table(current_user()['id']);

  // Filter / pencarian (No SJ, No Batch, nama barang, grade)
  $f_q     = isset($_GET['q']) ? trim($_GET['q']) : '';
  $f_grade = isset($_GET['grade']) ? trim($_GET['grade']) : '';
  if($f_q !== '' || $f_grade !== ''){
    $products = array_filter($products, function($p) use ($f_q, $f_grade){
      if($f_grade !== '' && (string)(isset($p['grade']) ? $p['grade'] : '') !== $f_grade){
        return false;
      }
      if($f_q !== ''){
        $hay = strtolower(
          (isset($p['name']) ? $p['name'] : '').' '.
          (isset($p['no_batch']) ? $p['no_batch'] : '').' '.
          (isset($p['no_surat_jalan']) ? $p['no_surat_jalan'] : '')
        );
        if(strpos($hay, strtolower($f_q)) === false){ return false; }
      }
      return true;
    });
  }

  // Ringkasan stok: total crate & total lembar (PC)
  $total_crate = 0;
  $total_lembar = 0;
  foreach($products as $p){
    $qty = (int)$p['quantity'];
    $pcs = isset($p['pcs_per_crate']) ? (int)$p['pcs_per_crate'] : 0;
    if($qty > 0){
      $total_lembar += $qty;
      if($pcs > 0){ $total_crate += (int)round($qty / $pcs); }
    }
  }

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
        <span class="pull-right">
          Total: <strong><?php echo (int)$total_crate; ?></strong> satuan / <strong><?php echo (int)$total_lembar; ?></strong> lembar
        </span>
      </div>
      <div class="panel-body">

        <form method="get" action="my_products.php" class="form-inline" style="margin-bottom:15px;">
          <div class="form-group">
            <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($f_q); ?>" placeholder="Cari nama / no batch / no surat jalan">
          </div>
          <div class="form-group">
            <select class="form-control" name="grade">
              <option value="">Semua Grade</option>
            <?php foreach (product_grades() as $g): ?>
              <option value="<?php echo $g; ?>" <?php if($f_grade === $g): echo 'selected="selected"'; endif; ?>><?php echo $g; ?></option>
            <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">
            <span class="glyphicon glyphicon-search"></span> Cari
          </button>
          <?php if($f_q !== '' || $f_grade !== ''): ?>
            <a href="my_products.php" class="btn btn-default">Reset</a>
          <?php endif; ?>
        </form>

        <div class="table-responsive">
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th class="text-center" style="width: 40px;">#</th>
              <th>Foto Produk</th>
              <th>Barang</th>
              <th>No Surat Jalan</th>
              <th>No Batch</th>
              <th class="text-center">Grade</th>
              <th class="text-center">Ukuran (mm)</th>
              <th class="text-center">M3</th>
              <th class="text-center">Stok Saat Ini</th>
              <th class="text-center">Keluar</th>
              <th class="text-center">Satuan</th>
              <th class="text-center">Cacat</th>
              <th class="text-center">Foto Cacat</th>
              <th class="text-center">Tanggal Masuk</th>
              <th class="text-center">Tanggal Keluar</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($products)): ?>
            <tr>
              <td colspan="15" class="text-center text-muted">Tidak ada barang yang cocok.</td>
            </tr>
            <?php endif; ?>
            <?php foreach ($products as $product): ?>
            <?php
              $qty = (int)$product['quantity'];
              $pcs = isset($product['pcs_per_crate']) ? (int)$product['pcs_per_crate'] : 0;
              $crate = $pcs > 0 ? (int)round($qty / $pcs) : 0;
              $unit_disp = !empty($product['unit_name']) ? remove_junk($product['unit_name']) : 'satuan';
              $m3 = isset($product['m3']) ? $product['m3'] : null;
              $m3_disp = ($m3 !== null && $m3 !== '') ? rtrim(rtrim(number_format((float)$m3, 4, ',', '.'), '0'), ',') : null;
            ?>
            <tr>
              <td class="text-center"><?php echo count_id(); ?></td>
              <td class="text-center">
                <?php if($product['media_id'] === '0'): ?>
                  <img class="img-avatar img-circle" src="uploads/products/no_image.png" alt="no-image" style="width:60px; height:60px;" />
                <?php else: ?>
                  <img class="img-avatar img-circle" src="uploads/products/<?php echo remove_junk($product['image']); ?>" alt="product-image" style="width:60px; height:60px; object-fit:cover;" />
                <?php endif; ?>
              </td>
              <td><?php echo remove_junk($product['name']); ?><br><small class="text-muted"><?php echo remove_junk($product['categorie']); ?></small></td>
              <td>
                <?php echo !empty($product['no_surat_jalan']) ? remove_junk($product['no_surat_jalan']) : '-'; ?>
                <?php if(!empty($product['sj_scan'])): ?>
                  <br><a href="uploads/surat_jalan/<?php echo remove_junk($product['sj_scan']); ?>" target="_blank"><span class="glyphicon glyphicon-paperclip"></span> Scan</a>
                <?php endif; ?>
              </td>
              <td><?php echo !empty($product['no_batch']) ? remove_junk($product['no_batch']) : '-'; ?></td>
              <td class="text-center">
                <?php if(!empty($product['grade'])): ?>
                  <span class="label label-info"><?php echo remove_junk($product['grade']); ?></span>
                <?php else: ?>-<?php endif; ?>
              </td>
              <td class="text-center"><?php echo format_product_size($product); ?></td>
              <td class="text-center"><?php echo $m3_disp !== null ? $m3_disp.' m&sup3;' : '-'; ?></td>
              <td class="text-center">
                <?php if($pcs > 0): ?>
                  <strong><?php echo $crate; ?> <?php echo $unit_disp; ?></strong><br>
                  <span class="text-muted"><?php echo $qty; ?> lembar</span>
                <?php else: ?>
                  <?php echo $qty; ?> <?php echo $unit_disp; ?>
                <?php endif; ?>
              </td>
              <td class="text-center"><a href="stock_history.php?product_id=<?php echo (int)$product['id']; ?>" title="Lihat riwayat keluar lengkap"><?php echo (int)$product['total_out']; ?></a></td>
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
</div>

<?php include_once('layouts/footer.php'); ?>
