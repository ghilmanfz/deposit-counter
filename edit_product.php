<?php
  $page_title = 'Edit Barang Titipan';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
   page_require_level(2);
?>
<?php
$product = find_by_id('products',(int)$_GET['id']);
$all_categories = find_all('categories');
$all_photo = find_all('media');
$all_clients = find_active_clients();
$msg = $session->msg();
if(!$product){
  $session->msg("d","Barang titipan tidak ditemukan.");
  redirect('product.php');
}
?>
<?php
 if(isset($_POST['product'])){
    $req_fields = array('product-title','product-categorie','product-quantity' );
    validate_fields($req_fields);

   if(empty($errors)){
       $p_name  = remove_junk($db->escape($_POST['product-title']));
       $p_cat   = (int)$_POST['product-categorie'];
       $p_qty   = (int)$db->escape($_POST['product-quantity']);
       $p_buy   = remove_junk($db->escape($_POST['buying-price']));
       $p_sale  = remove_junk($db->escape($_POST['saleing-price']));
       $client_id = isset($_POST['product-client']) && $_POST['product-client'] !== ''
         ? (int)$db->escape($_POST['product-client'])
         : 0;
       $client_value = $client_id > 0 ? "'".$db->escape($client_id)."'" : "NULL";
       if (is_null($_POST['product-photo']) || $_POST['product-photo'] === "") {
         $media_id = '0';
       } else {
         $media_id = remove_junk($db->escape($_POST['product-photo']));
       }
       $query   = "UPDATE products SET";
       $query  .=" name ='{$p_name}', quantity ='{$p_qty}',";
       $query  .=" buy_price ='{$p_buy}', sale_price ='{$p_sale}', categorie_id ='{$p_cat}',client_id={$client_value},media_id='{$media_id}'";
       $query  .=" WHERE id ='{$product['id']}'";
       $result = $db->query($query);

               if($result){
                 if((int)$product['quantity'] !== $p_qty){
                   $movement_id = record_stock_movement($product['id'], 'adjustment', abs($p_qty - (int)$product['quantity']), (int)$product['quantity'], $p_qty, array(
                     'client_id' => $client_id,
                     'reference_type' => 'edit_barang',
                     'reference_id' => $product['id'],
                     'note' => 'Penyesuaian stok manual dari edit barang'
                   ));

                   if(!$movement_id){
                     $old_client_value = !empty($product['client_id']) ? "'".$db->escape((int)$product['client_id'])."'" : "NULL";
                     $rollback  = "UPDATE products SET";
                     $rollback .= " name ='".$db->escape($product['name'])."', quantity ='".$db->escape((int)$product['quantity'])."',";
                     $rollback .= " buy_price ='".$db->escape($product['buy_price'])."', sale_price ='".$db->escape($product['sale_price'])."',";
                     $rollback .= " categorie_id ='".$db->escape((int)$product['categorie_id'])."', client_id={$old_client_value},media_id='".$db->escape($product['media_id'])."'";
                     $rollback .= " WHERE id ='".$db->escape($product['id'])."' LIMIT 1";
                     $db->query($rollback);
                     $session->msg('d',' Riwayat penyesuaian stok gagal disimpan. Perubahan barang dibatalkan.');
                     redirect('edit_product.php?id='.$product['id'], false);
                   }

                   $movement_type = $p_qty > (int)$product['quantity'] ? 'in' : 'out';
                   $delivery_note = $movement_type === 'in'
                     ? 'Surat jalan otomatis dari penambahan stok barang titipan.'
                     : 'Surat jalan otomatis dari pengurangan stok barang titipan.';
                   $delivery_id = create_delivery_order(array(
                     'movement_type' => $movement_type,
                     'client_id' => $client_id,
                     'product_id' => $product['id'],
                     'quantity' => abs($p_qty - (int)$product['quantity']),
                     'document_date' => date('Y-m-d'),
                     'recipient' => $movement_type === 'out' && !empty($product['client_id']) ? 'Client' : 'Gudang',
                     'reference_type' => 'penyesuaian_barang',
                     'reference_id' => $product['id'],
                     'note' => $delivery_note
                   ));

                   if(!$delivery_id){
                     delete_by_id('stock_movements', (int)$movement_id);
                     $old_client_value = !empty($product['client_id']) ? "'".$db->escape((int)$product['client_id'])."'" : "NULL";
                     $rollback  = "UPDATE products SET";
                     $rollback .= " name ='".$db->escape($product['name'])."', quantity ='".$db->escape((int)$product['quantity'])."',";
                     $rollback .= " buy_price ='".$db->escape($product['buy_price'])."', sale_price ='".$db->escape($product['sale_price'])."',";
                     $rollback .= " categorie_id ='".$db->escape((int)$product['categorie_id'])."', client_id={$old_client_value},media_id='".$db->escape($product['media_id'])."'";
                     $rollback .= " WHERE id ='".$db->escape($product['id'])."' LIMIT 1";
                     $db->query($rollback);
                     $session->msg('d',' Surat jalan gagal dibuat. Perubahan barang dibatalkan.');
                     redirect('edit_product.php?id='.$product['id'], false);
                   }
                 }
                 $session->msg('s',"Barang titipan berhasil diperbarui. ");
                 redirect('product.php', false);
               } else {
                 $session->msg('d',' Maaf, barang titipan gagal diperbarui.');
                 redirect('edit_product.php?id='.$product['id'], false);
               }

   } else{
       $session->msg("d", $errors);
       redirect('edit_product.php?id='.$product['id'], false);
   }

 }

?>
<?php include_once('layouts/header.php'); ?>
<div class="row">
  <div class="col-md-12">
    <?php echo display_msg($msg); ?>
  </div>
</div>
  <div class="row">
      <div class="panel panel-default">
        <div class="panel-heading">
          <strong>
            <span class="glyphicon glyphicon-th"></span>
            <span>Edit Barang Titipan</span>
         </strong>
        </div>
        <div class="panel-body">
         <div class="col-md-7">
           <form method="post" action="edit_product.php?id=<?php echo (int)$product['id'] ?>">
              <div class="form-group">
                <div class="input-group">
                  <span class="input-group-addon">
                   <i class="glyphicon glyphicon-th-large"></i>
                  </span>
                  <input type="text" class="form-control" name="product-title" value="<?php echo remove_junk($product['name']);?>">
               </div>
              </div>
              <div class="form-group">
                <div class="row">
                  <div class="col-md-6">
                    <select class="form-control" name="product-categorie">
                    <option value=""> Pilih kategori</option>
                   <?php  foreach ($all_categories as $cat): ?>
                     <option value="<?php echo (int)$cat['id']; ?>" <?php if($product['categorie_id'] === $cat['id']): echo "selected"; endif; ?> >
                       <?php echo remove_junk($cat['name']); ?></option>
                   <?php endforeach; ?>
                 </select>
                  </div>
                  <div class="col-md-6">
                    <select class="form-control" name="product-photo">
                      <option value=""> Tanpa gambar</option>
                      <?php  foreach ($all_photo as $photo): ?>
                        <option value="<?php echo (int)$photo['id'];?>" <?php if($product['media_id'] === $photo['id']): echo "selected"; endif; ?> >
                          <?php echo $photo['file_name'] ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6" style="margin-top:10px;">
                    <select class="form-control" name="product-client">
                      <option value="">Stok internal / tanpa pelanggan</option>
                      <?php foreach ($all_clients as $client): ?>
                        <option value="<?php echo (int)$client['id'];?>" <?php if((int)$product['client_id'] === (int)$client['id']): echo 'selected="selected"'; endif; ?>>
                          <?php echo remove_junk($client['name']); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </div>

              <div class="form-group">
               <div class="row">
                 <div class="col-md-4">
                  <div class="form-group">
                    <label for="qty">Jumlah Stok</label>
                    <div class="input-group">
                      <span class="input-group-addon">
                       <i class="glyphicon glyphicon-shopping-cart"></i>
                      </span>
                      <input type="number" class="form-control" name="product-quantity" value="<?php echo remove_junk($product['quantity']); ?>">
                   </div>
                  </div>
                 </div>
                 <input type="hidden" name="buying-price" value="<?php echo remove_junk($product['buy_price']);?>">
                 <input type="hidden" name="saleing-price" value="<?php echo remove_junk($product['sale_price']);?>">
               </div>
              </div>
              <button type="submit" name="product" class="btn btn-danger">Update Barang</button>
          </form>
         </div>
        </div>
      </div>
  </div>

<?php include_once('layouts/footer.php'); ?>
