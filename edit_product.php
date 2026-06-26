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
$all_units = find_all_units();
$msg = $session->msg();
if(!$product){
  $session->msg("d","Barang titipan tidak ditemukan.");
  redirect('product.php');
}
?>
<?php
 if(isset($_POST['product'])){
    $req_fields = array('product-title','product-categorie','product-quantity','product-unit' );
    validate_fields($req_fields);

   if(empty($errors)){
       $p_name  = remove_junk($db->escape($_POST['product-title']));
       $p_cat   = (int)$_POST['product-categorie'];
       $p_qty   = (int)$db->escape($_POST['product-quantity']);
       $p_unit  = (int)$db->escape($_POST['product-unit']);
       // Detail surat jalan, grade & ukuran plywood
       $no_sj    = isset($_POST['product-sj']) ? remove_junk($db->escape($_POST['product-sj'])) : '';
       $no_batch = isset($_POST['product-batch']) ? remove_junk($db->escape($_POST['product-batch'])) : '';
       $grade    = isset($_POST['product-grade']) ? remove_junk($db->escape($_POST['product-grade'])) : '';
       if($grade !== '' && !in_array($grade, product_grades(), true)){ $grade = ''; }
       $tebal    = (isset($_POST['product-tebal'])   && $_POST['product-tebal']   !== '') ? (float)$_POST['product-tebal']   : null;
       $lebar    = (isset($_POST['product-lebar'])   && $_POST['product-lebar']   !== '') ? (float)$_POST['product-lebar']   : null;
       $panjang  = (isset($_POST['product-panjang']) && $_POST['product-panjang'] !== '') ? (float)$_POST['product-panjang'] : null;
       $m3       = (isset($_POST['product-m3'])      && $_POST['product-m3']      !== '') ? (float)$_POST['product-m3']      : null;
       $no_sj_value    = $no_sj    !== '' ? "'{$no_sj}'" : "NULL";
       $no_batch_value = $no_batch !== '' ? "'{$no_batch}'" : "NULL";
       $grade_value    = $grade    !== '' ? "'{$grade}'" : "NULL";
       $tebal_value    = $tebal    !== null ? "'".$db->escape($tebal)."'" : "NULL";
       $lebar_value    = $lebar    !== null ? "'".$db->escape($lebar)."'" : "NULL";
       $panjang_value  = $panjang  !== null ? "'".$db->escape($panjang)."'" : "NULL";
       $m3_value       = $m3       !== null ? "'".$db->escape($m3)."'" : "NULL";
       $sj_scan_new = save_sj_scan('sj_scan_file');
       $existing_scan = isset($product['sj_scan']) ? $product['sj_scan'] : '';
       $sj_scan_final = $sj_scan_new !== '' ? $sj_scan_new : $existing_scan;
       $sj_scan_value = ($sj_scan_final !== '' && $sj_scan_final !== null) ? "'".$db->escape($sj_scan_final)."'" : "NULL";
       $p_buy   = remove_junk($db->escape($_POST['buying-price']));
       $p_sale  = remove_junk($db->escape($_POST['saleing-price']));
       $defect_qty = isset($_POST['defect-quantity']) ? (int)$db->escape($_POST['defect-quantity']) : 0;
       $defect_note = isset($_POST['defect-note']) ? remove_junk($db->escape($_POST['defect-note'])) : '';
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
           redirect('edit_product.php?id='.$product['id'], false);
         }
       } else {
         if (is_null($_POST['product-photo']) || $_POST['product-photo'] === "") {
           $media_id = '0';
         } else {
           $media_id = remove_junk($db->escape($_POST['product-photo']));
         }
       }
       $query   = "UPDATE products SET";
       $query  .=" name ='{$p_name}', no_surat_jalan={$no_sj_value}, no_batch={$no_batch_value}, grade={$grade_value},";
       $query  .=" tebal={$tebal_value}, lebar={$lebar_value}, panjang={$panjang_value}, m3={$m3_value}, sj_scan={$sj_scan_value}, quantity ='{$p_qty}',";
       $query  .=" buy_price ='{$p_buy}', sale_price ='{$p_sale}', categorie_id ='{$p_cat}',client_id={$client_value},unit_id='{$p_unit}',media_id='{$media_id}'";
       $query  .=" WHERE id ='{$product['id']}'";
       $result = $db->query($query);

               if($result){
                 if((int)$product['quantity'] !== $p_qty){
                   $movement_id = record_stock_movement($product['id'], 'adjustment', abs($p_qty - (int)$product['quantity']), (int)$product['quantity'], $p_qty, array(
                     'client_id' => $client_id,
                     'unit_id' => $p_unit,
                     'reference_type' => 'edit_barang',
                     'reference_id' => $product['id'],
                     'note' => 'Penyesuaian stok manual dari edit barang'
                   ));

                   if(!$movement_id){
                     $old_client_value = !empty($product['client_id']) ? "'".$db->escape((int)$product['client_id'])."'" : "NULL";
                     $rollback  = "UPDATE products SET";
                     $rollback .= " name ='".$db->escape($product['name'])."', quantity ='".$db->escape((int)$product['quantity'])."',";
                     $rollback .= " buy_price ='".$db->escape($product['buy_price'])."', sale_price ='".$db->escape($product['sale_price'])."',";
                     $rollback .= " categorie_id ='".$db->escape((int)$product['categorie_id'])."', client_id={$old_client_value},unit_id='".$db->escape(isset($product['unit_id']) ? (int)$product['unit_id'] : 0)."',media_id='".$db->escape($product['media_id'])."'";
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
                     $rollback .= " categorie_id ='".$db->escape((int)$product['categorie_id'])."', client_id={$old_client_value},unit_id='".$db->escape(isset($product['unit_id']) ? (int)$product['unit_id'] : 0)."',media_id='".$db->escape($product['media_id'])."'";
                     $rollback .= " WHERE id ='".$db->escape($product['id'])."' LIMIT 1";
                     $db->query($rollback);
                     $session->msg('d',' Surat jalan gagal dibuat. Perubahan barang dibatalkan.');
                     redirect('edit_product.php?id='.$product['id'], false);
                   }
                 }
                 if($defect_qty > 0){
                   if($defect_note === ''){
                     $defect_note = 'Keterangan cacat tidak disediakan.';
                   }
                   $defect_id = record_product_defect($product['id'], $client_id, $defect_qty, $defect_note);
                   if($defect_id){
                     save_defect_photos($defect_id, 'defect_photos');
                   } else {
                     $session->msg('d',' Barang diperbarui, tapi data cacat gagal disimpan.');
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
  <div class="col-md-12">
    <?php $defect_summary = find_product_defect_summary((int)$product['id']); ?>
    <div class="panel panel-default">
      <div class="panel-body">
        <p><strong>Stok Cacat:</strong> <a href="product_defects.php?product_id=<?php echo (int)$product['id']; ?>"><?php echo (int)$defect_summary['total_defect']; ?> unit</a> | <strong>Total Laporan:</strong> <?php echo (int)$defect_summary['total_report']; ?> </p>
        <p class="text-muted">Barang cacat tetap dihitung dalam stok dan tercatat sebagai informasi kondisi.</p>
      </div>
    </div>
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
           <form method="post" action="edit_product.php?id=<?php echo (int)$product['id'] ?>" enctype="multipart/form-data">
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
                    <label>Foto Barang</label>
                    <select class="form-control" name="product-photo">
                      <option value=""> Tanpa gambar</option>
                      <?php  foreach ($all_photo as $photo): ?>
                        <option value="<?php echo (int)$photo['id'];?>" <?php if($product['media_id'] === $photo['id']): echo "selected"; endif; ?> >
                          <?php echo $photo['file_name'] ?></option>
                      <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Atau unggah foto baru agar gambar produk diperbarui.</small>
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
                    <?php if($product['media_id'] !== '0'): ?>
                    <div class="text-muted" style="margin-top:8px;">
                      <strong>Gambar saat ini:</strong><br>
                      <img src="uploads/products/<?php echo remove_junk($product['image']); ?>" class="img-thumbnail" style="max-width:100%; max-height:140px;" />
                    </div>
                    <?php endif; ?>
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
                  <div class="col-md-6" style="margin-top:10px;">
                    <select class="form-control" name="product-unit">
                      <option value="">Pilih Satuan</option>
                      <?php foreach ($all_units as $unit): ?>
                        <option value="<?php echo (int)$unit['id'];?>" <?php if((int)$product['unit_id'] === (int)$unit['id']): echo 'selected="selected"'; endif; ?>><?php echo remove_junk($unit['name']); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-12" style="margin-top:10px;">
                    <label>Foto Cacat Baru</label>
                    <input type="file" class="form-control defect-photo-input" name="defect_photos[]" multiple accept="image/*">
                    <div class="defect-photo-preview text-muted" style="margin-top:8px;">Belum ada file dipilih.</div>
                    <small class="text-muted">Unggah bukti foto jika ada kondisi cacat baru.</small>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label>Detail Surat Jalan &amp; Grade</label>
                <div class="row">
                  <div class="col-md-4">
                    <input type="text" class="form-control" name="product-sj" value="<?php echo isset($product['no_surat_jalan']) ? remove_junk($product['no_surat_jalan']) : ''; ?>" placeholder="No Surat Jalan">
                  </div>
                  <div class="col-md-4">
                    <input type="text" class="form-control" name="product-batch" value="<?php echo isset($product['no_batch']) ? remove_junk($product['no_batch']) : ''; ?>" placeholder="No Batch">
                  </div>
                  <div class="col-md-4">
                    <select class="form-control" name="product-grade">
                      <option value="">- Pilih Grade -</option>
                    <?php foreach (product_grades() as $g): ?>
                      <option value="<?php echo $g; ?>" <?php if(isset($product['grade']) && $product['grade'] === $g): echo 'selected="selected"'; endif; ?>><?php echo $g; ?></option>
                    <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="row" style="margin-top:10px;">
                  <div class="col-md-6">
                    <label>Scan / Foto Surat Jalan (opsional)</label>
                    <input type="file" class="form-control" name="sj_scan_file" accept="image/*,application/pdf">
                    <?php if(!empty($product['sj_scan'])): ?>
                      <small class="text-muted">Saat ini: <a href="uploads/surat_jalan/<?php echo remove_junk($product['sj_scan']); ?>" target="_blank">lihat scan</a>. Unggah baru untuk mengganti.</small>
                    <?php else: ?>
                      <small class="text-muted">Format: JPG, PNG, atau PDF.</small>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label>Ukuran (mm)</label>
                <div class="row">
                  <div class="col-md-3">
                    <input type="number" step="0.01" min="0" class="form-control" name="product-tebal" value="<?php echo isset($product['tebal']) ? remove_junk($product['tebal']) : ''; ?>" placeholder="Tebal / T">
                  </div>
                  <div class="col-md-3">
                    <input type="number" step="0.01" min="0" class="form-control" name="product-lebar" value="<?php echo isset($product['lebar']) ? remove_junk($product['lebar']) : ''; ?>" placeholder="Lebar / W">
                  </div>
                  <div class="col-md-3">
                    <input type="number" step="0.01" min="0" class="form-control" name="product-panjang" value="<?php echo isset($product['panjang']) ? remove_junk($product['panjang']) : ''; ?>" placeholder="Panjang / L">
                  </div>
                  <div class="col-md-3">
                    <input type="number" step="0.0001" min="0" class="form-control" name="product-m3" value="<?php echo isset($product['m3']) ? remove_junk($product['m3']) : ''; ?>" placeholder="M3 (opsional)">
                  </div>
                </div>
                <small class="text-muted">Isi angka polos (1220, bukan 1.220). M3 boleh kosong.</small>
              </div>

              <div class="form-group">
               <div class="row">
                 <div class="col-md-4">
                  <div class="form-group">
                    <label for="qty">Jumlah Lembar (PC) / Crate</label>
                    <div class="input-group">
                      <span class="input-group-addon">
                       <i class="glyphicon glyphicon-shopping-cart"></i>
                      </span>
                      <input type="number" class="form-control" name="product-quantity" value="<?php echo remove_junk($product['quantity']); ?>">
                   </div>
                  </div>
                 </div>
                 <div class="col-md-4">
                   <div class="form-group">
                     <label for="defect-quantity">Tambah Jumlah Cacat</label>
                     <input type="number" min="0" class="form-control" name="defect-quantity" value="0" placeholder="Jumlah barang cacat tambahan" />
                   </div>
                 </div>
                 <div class="col-md-4">
                   <div class="form-group">
                     <label for="defect-note">Keterangan Cacat</label>
                     <input type="text" class="form-control" name="defect-note" placeholder="Contoh: basah, lembab, rusak" />
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
