<?php
  $page_title = 'Tambah Barang Titipan';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
  page_require_level(2);
  $all_categories = find_all('categories');
  $all_photo = find_all('media');
  $all_clients = find_active_clients();
  $all_units = find_all_units();
  $msg = $session->msg();
?>
<?php
 if(isset($_POST['add_product'])){
   $req_fields = array('product-title','product-categorie','product-quantity','product-unit' );
   validate_fields($req_fields);
   if(empty($errors)){
     $p_name  = remove_junk($db->escape($_POST['product-title']));
     $p_cat   = remove_junk($db->escape($_POST['product-categorie']));
     $p_qty   = (int)$db->escape($_POST['product-quantity']);
     $p_unit  = (int)$db->escape($_POST['product-unit']);
     $defect_qty = isset($_POST['defect-quantity']) ? (int)$db->escape($_POST['defect-quantity']) : 0;
     $defect_note = isset($_POST['defect-note']) ? remove_junk($db->escape($_POST['defect-note'])) : '';
     $p_buy   = '0.00';
     $p_sale  = '0.00';
     $client_id = isset($_POST['product-client']) && $_POST['product-client'] !== ''
       ? (int)$db->escape($_POST['product-client'])
       : 0;
     $client_value = $client_id > 0 ? "'".$db->escape($client_id)."'" : "NULL";
     $media_id = '0';
     if(!empty($_FILES['product_image']) && isset($_FILES['product_image']['name']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE){
       $photo = new Media();
       if($photo->upload($_FILES['product_image']) && $photo->process_media()){
         $media_row = find_by_sql("SELECT id FROM media WHERE file_name='".$db->escape($photo->fileName)."' LIMIT 1");
         if(!empty($media_row)){
           $media_id = (int)$media_row[0]['id'];
         }
       } else {
         $session->msg('d', join($photo->errors));
         redirect('add_product.php', false);
       }
     } else {
       if (is_null($_POST['product-photo']) || $_POST['product-photo'] === "") {
         $media_id = '0';
       } else {
         $media_id = remove_junk($db->escape($_POST['product-photo']));
       }
     }
     $date    = make_date();
     $query  = "INSERT INTO products (";
     $query .=" name,quantity,buy_price,sale_price,categorie_id,client_id,unit_id,media_id,date";
     $query .=") VALUES (";
     $query .=" '{$p_name}', '{$p_qty}', '{$p_buy}', '{$p_sale}', '{$p_cat}', {$client_value}, '{$p_unit}', '{$media_id}', '{$date}'";
     $query .=")";
     if($db->query($query)){
       $product_id = $db->insert_id();
       if($p_qty > 0){
         $movement_id = record_stock_movement($product_id, 'in', $p_qty, 0, $p_qty, array(
           'client_id' => $client_id,
           'reference_type' => 'product',
           'reference_id' => $product_id,
           'note' => 'Stok awal barang titipan',
           'created_at' => $date,
           'unit_id' => $p_unit
         ));

         if(!$movement_id){
           delete_by_id('products', $product_id);
           $session->msg('d',' Barang tersimpan, tetapi riwayat stok gagal disimpan.');
           redirect('add_product.php', false);
         }

         $delivery_id = create_delivery_order(array(
           'movement_type' => 'in',
           'client_id' => $client_id,
           'product_id' => $product_id,
           'quantity' => $p_qty,
           'document_date' => date('Y-m-d', strtotime($date)),
           'recipient' => $client_id > 0 ? 'Gudang' : '',
           'reference_type' => 'barang_masuk',
           'reference_id' => $product_id,
           'note' => 'Surat jalan otomatis saat barang titipan masuk.'
         ));

         if(!$delivery_id){
           delete_by_id('stock_movements', (int)$movement_id);
           delete_by_id('products', $product_id);
           $session->msg('d',' Barang tersimpan, tetapi surat jalan gagal dibuat.');
           redirect('add_product.php', false);
         }
       }
       if($defect_qty > 0){
         if($defect_qty > $p_qty){
           delete_by_id('products', $product_id);
           $session->msg('d',' Jumlah barang cacat tidak boleh melebihi jumlah barang masuk.');
           redirect('add_product.php', false);
         }
         $defect_id = record_product_defect($product_id, $client_id, $defect_qty, $defect_note);
         if($defect_id){
           save_defect_photos($defect_id, 'defect_photos');
         }
       }
       $session->msg('s',"Barang titipan berhasil ditambahkan. ");
       redirect('add_product.php', false);
     } else {
       $session->msg('d',' Maaf, barang titipan gagal ditambahkan. Cek nama barang atau struktur database.');
       redirect('product.php', false);
     }

   } else{
     $session->msg("d", $errors);
     redirect('add_product.php',false);
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
  <div class="col-md-8">
      <div class="panel panel-default">
        <div class="panel-heading">
          <strong>
            <span class="glyphicon glyphicon-th"></span>
            <span>Tambah Barang Titipan</span>
         </strong>
        </div>
        <div class="panel-body">
         <div class="col-md-12">
          <form method="post" action="add_product.php" class="clearfix" enctype="multipart/form-data">
              <div class="form-group">
                <div class="input-group">
                  <span class="input-group-addon">
                   <i class="glyphicon glyphicon-th-large"></i>
                  </span>
                  <input type="text" class="form-control" name="product-title" placeholder="Nama Barang Titipan">
               </div>
              </div>
              <div class="form-group">
                <div class="row">
                  <div class="col-md-6">
                    <select class="form-control" name="product-categorie">
                      <option value="">Pilih Kategori Barang</option>
                    <?php  foreach ($all_categories as $cat): ?>
                      <option value="<?php echo (int)$cat['id'] ?>">
                        <?php echo $cat['name'] ?></option>
                    <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label>Foto Barang (opsional)</label>
                    <select class="form-control" name="product-photo">
                      <option value="">Pilih Foto Barang dari Daftar</option>
                    <?php  foreach ($all_photo as $photo): ?>
                      <option value="<?php echo (int)$photo['id'] ?>">
                        <?php echo $photo['file_name'] ?></option>
                    <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Atau unggah foto baru di bawah ini.</small>
                  </div>
                  <div class="col-md-6" style="margin-top:10px;">
                    <label>Unggah Foto Baru</label>
                    <div class="input-group">
                      <span class="input-group-btn">
                        <span class="btn btn-primary" style="position: relative; overflow: hidden;">
                          Pilih Foto
                          <input type="file" name="product_image" class="photo-input product-photo-input" accept="image/*" style="position: absolute; top: 0; right: 0; min-width: 100%; min-height: 100%; font-size: 100px; text-align: right; opacity: 0; outline: none; background: white; cursor: pointer; display: block;" />
                        </span>
                      </span>
                      <input type="text" class="form-control" readonly placeholder="Tidak wajib" />
                    </div>
                    <div class="product-photo-preview photo-preview text-muted" style="margin-top:8px;">Belum ada file dipilih.</div>
                  </div>
                  <div class="col-md-6" style="margin-top:10px;">
                    <select class="form-control" name="product-client">
                      <option value="">Stok internal / tanpa pelanggan</option>
                    <?php  foreach ($all_clients as $client): ?>
                      <option value="<?php echo (int)$client['id'] ?>">
                        <?php echo remove_junk($client['name']) ?></option>
                    <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6" style="margin-top:10px;">
                    <select class="form-control" name="product-unit">
                      <option value="">Pilih Satuan</option>
                    <?php foreach ($all_units as $unit): ?>
                      <option value="<?php echo (int)$unit['id'] ?>"><?php echo remove_junk($unit['name']); ?></option>
                    <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </div>

              <div class="form-group">
               <div class="row">
                 <div class="col-md-4">
                   <div class="input-group">
                     <span class="input-group-addon">
                      <i class="glyphicon glyphicon-shopping-cart"></i>
                     </span>
                     <input type="number" class="form-control" name="product-quantity" placeholder="Jumlah Stok Awal">
                  </div>
                 </div>
               </div>
              </div>
              <div class="form-group">
                <div class="row">
                  <div class="col-md-4">
                    <label>Jumlah Barang Cacat</label>
                    <input type="number" min="0" class="form-control" name="defect-quantity" value="0" placeholder="Jumlah cacat">
                  </div>
                  <div class="col-md-8">
                    <label>Keterangan Cacat</label>
                    <input type="text" class="form-control" name="defect-note" placeholder="Contoh: basah, lembab, rusak, penyok">
                  </div>
                  <div class="col-md-12" style="margin-top:10px;">
                    <label>Bukti Foto Cacat</label>
                    <input type="file" class="form-control defect-photo-input" name="defect_photos[]" multiple accept="image/*">
                    <div class="defect-photo-preview text-muted" style="margin-top:8px;">Belum ada file dipilih.</div>
                    <small class="text-muted">Bisa unggah lebih dari satu foto. Barang cacat tetap dihitung ke stok.</small>
                  </div>
                </div>
              </div>
              <button type="submit" name="add_product" class="btn btn-danger">Simpan Barang</button>
          </form>
         </div>
        </div>
      </div>
    </div>
  </div>

<?php include_once('layouts/footer.php'); ?>
