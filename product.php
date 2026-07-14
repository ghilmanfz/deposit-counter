<?php
  $page_title = 'Data Barang Titipan';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
   require_permission('barang','view');
  $products = join_product_table();
  $msg = $session->msg();
?>
<?php include_once('layouts/header.php'); ?>
  <div class="row">
     <div class="col-md-12">
       <?php echo display_msg($msg); ?>
     </div>
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading clearfix">
         <?php if(role_can_action('barang','create')): ?>
           <div class="pull-right">
             <a href="add_product.php" class="btn btn-primary">Tambah Barang</a>
           </div>
         <?php endif; ?>
        </div>
        <div class="panel-body">
          <table class="table table-bordered">
            <thead>
              <tr>
                <th class="text-center" style="width: 50px;">#</th>
                <th> Foto</th>
                <th> Nama Barang </th>
                <th class="text-center" style="width: 12%;"> Client </th>
                <th class="text-center" style="width: 10%;"> Kategori </th>
                <th class="text-center" style="width: 13%;"> Stok Unit Dasar </th>
                <th class="text-center" style="width: 10%;"> Bundle / Dasar </th>
                <th class="text-center" style="width: 8%;"> Keluar </th>
                <th class="text-center" style="width: 8%;"> Cacat </th>
                <th class="text-center" style="width: 12%;"> Foto Cacat </th>
                <th class="text-center" style="width: 12%;"> Tanggal Masuk</th>
                <th class="text-center" style="width: 12%;"> Tanggal Keluar </th>
                <th class="text-center" style="width: 100px;"> Aksi </th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($products as $product):?>
              <?php
                $bundle_summary = function_exists('find_product_bundle_summary')
                  ? find_product_bundle_summary((int)$product['id'])
                  : array();
                $has_bundle_details = !empty($bundle_summary['total_count']);
                $current_bundle_count = isset($bundle_summary['available_count']) || isset($bundle_summary['reserved_count'])
                  ? (int)$bundle_summary['available_count'] + (int)$bundle_summary['reserved_count']
                  : 0;
                if(!isset($bundle_summary['available_count']) && !isset($bundle_summary['reserved_count'])){
                  foreach($bundle_rows as $bundle_row){
                    if(!isset($bundle_row['status']) || $bundle_row['status'] !== 'out'){ $current_bundle_count++; }
                  }
                }
                $base_unit_name = isset($product['base_unit_name']) ? $product['base_unit_name'] : '';
                if($base_unit_name === '' && !empty($product['base_unit_id'])){
                  $base_unit_row = find_unit_by_id((int)$product['base_unit_id']);
                  $base_unit_name = $base_unit_row ? $base_unit_row['name'] : '';
                }
                $package_unit_name = !empty($product['unit_name']) ? $product['unit_name'] : 'bundle';
              ?>
              <tr>
                <td class="text-center"><?php echo count_id();?></td>
                <td>
                  <?php if($product['media_id'] === '0'): ?>
                    <img class="img-avatar img-circle" src="uploads/products/no_image.png" alt="">
                  <?php else: ?>
                  <img class="img-avatar img-circle" src="uploads/products/<?php echo $product['image']; ?>" alt="">
                <?php endif; ?>
                </td>
                <td> <?php echo remove_junk($product['name']); ?></td>
                <td class="text-center"><?php echo !empty($product['client_name']) ? remove_junk($product['client_name']) : 'Internal'; ?></td>
                <td class="text-center"> <?php echo remove_junk($product['categorie']); ?></td>
                <td class="text-center">
                  <strong><?php echo (int)$product['quantity']; ?> <?php echo $base_unit_name !== '' ? remove_junk($base_unit_name) : 'unit dasar'; ?></strong>
                  <?php if($has_bundle_details): ?>
                    <br><small class="text-muted"><?php echo (int)$current_bundle_count; ?> <?php echo remove_junk($package_unit_name); ?> belum keluar</small>
                  <?php else: ?>
                    <br><span class="label label-warning">Rincian bundle belum ada</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <?php echo remove_junk($package_unit_name); ?> /
                  <?php echo $base_unit_name !== '' ? remove_junk($base_unit_name) : '<span class="text-muted">belum ditetapkan</span>'; ?>
                </td>
                <td class="text-center"> <?php echo (int)$product['total_out']; ?></td>
                <?php $defect_summary = find_product_defect_summary((int)$product['id']); ?>
                <td class="text-center">
                  <a href="product_defects.php?product_id=<?php echo (int)$product['id']; ?>" class="btn btn-default btn-xs">
                    <?php echo (int)$defect_summary['total_defect']; ?>
                  </a>
                </td>
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
                <td class="text-center"> <?php echo read_date($product['date']); ?></td>
                <td class="text-center"> <?php echo !empty($product['last_out_date']) ? read_date($product['last_out_date']) : '-'; ?></td>
                <td class="text-center">
                  <div class="btn-group">
                    <?php if(role_can_action('barang','update')): ?>
                      <a href="edit_product.php?id=<?php echo (int)$product['id'];?>" class="btn btn-info btn-xs"  title="Edit" data-toggle="tooltip">
                        <span class="glyphicon glyphicon-edit"></span>
                      </a>
                      <a href="manage_product_bundles.php?id=<?php echo (int)$product['id'];?>" class="btn btn-warning btn-xs" title="<?php echo $has_bundle_details ? 'Lihat rincian bundle' : 'Input rincian bundle'; ?>" data-toggle="tooltip">
                        <span class="glyphicon glyphicon-th-list"></span>
                      </a>
                    <?php endif; ?>
                    <?php if(role_can_action('barang','delete')): ?>
                      <?php if(!$has_bundle_details): ?>
                        <form method="post" action="delete_product.php" style="display:inline;" onsubmit="return confirm('Hapus barang ini?');">
                          <?php echo warehouse_csrf_field(); ?>
                          <input type="hidden" name="id" value="<?php echo (int)$product['id']; ?>">
                          <button type="submit" class="btn btn-danger btn-xs" title="Hapus" data-toggle="tooltip"><span class="glyphicon glyphicon-trash"></span></button>
                        </form>
                      <?php else: ?>
                        <button type="button" class="btn btn-default btn-xs" disabled title="Produk dengan histori bundle tidak dapat dihapus">
                          <span class="glyphicon glyphicon-lock"></span>
                        </button>
                      <?php endif; ?>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
             <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <?php include_once('layouts/footer.php'); ?>
