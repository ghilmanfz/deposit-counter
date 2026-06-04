<?php
  $page_title = 'Data Barang Titipan';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
   page_require_level(2);
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
         <div class="pull-right">
           <a href="add_product.php" class="btn btn-primary">Tambah Barang</a>
         </div>
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
                <th class="text-center" style="width: 10%;"> Stok </th>
                <th class="text-center" style="width: 8%;"> Satuan </th>
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
                <td class="text-center"> <?php echo remove_junk($product['quantity']); ?></td>
                <td class="text-center"> <?php echo !empty($product['unit_name']) ? remove_junk($product['unit_name']) : '-'; ?></td>
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
                    <a href="edit_product.php?id=<?php echo (int)$product['id'];?>" class="btn btn-info btn-xs"  title="Edit" data-toggle="tooltip">
                      <span class="glyphicon glyphicon-edit"></span>
                    </a>
                    <a href="delete_product.php?id=<?php echo (int)$product['id'];?>" class="btn btn-danger btn-xs"  title="Hapus" data-toggle="tooltip">
                      <span class="glyphicon glyphicon-trash"></span>
                    </a>
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
