<?php
  $page_title = 'Edit Barang Titipan';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
   require_permission('barang','update');
?>
<?php
$product = find_product_details(isset($_GET['id']) ? (int)$_GET['id'] : 0);
$all_categories = find_all('categories');
$all_photo = find_all('media');
$all_clients = find_active_clients();
$all_units = find_all_units();
$msg = $session->msg();
if(!$product){
  $session->msg("d","Barang titipan tidak ditemukan.");
  redirect('product.php');
}

$bundle_summary = function_exists('find_product_bundle_summary')
  ? find_product_bundle_summary((int)$product['id'])
  : array();
$has_bundle_details = !empty($bundle_summary['total_count']);
$bundle_count = isset($bundle_summary['total_count'])
  ? (int)$bundle_summary['total_count']
  : 0;
$current_bundle_count = isset($bundle_summary['available_count']) || isset($bundle_summary['reserved_count'])
  ? (int)$bundle_summary['available_count'] + (int)$bundle_summary['reserved_count']
  : 0;
if(!$has_bundle_details){ $current_bundle_count = 0; }
$package_unit = !empty($product['unit_id']) ? find_unit_by_id((int)$product['unit_id']) : null;
$base_unit_id = isset($product['base_unit_id']) ? (int)$product['base_unit_id'] : 0;
$base_unit = $base_unit_id > 0 ? find_unit_by_id($base_unit_id) : null;
$package_unit_name = $package_unit ? $package_unit['name'] : 'bundle';
$base_unit_name = $base_unit ? $base_unit['name'] : 'unit dasar';
$product_owner = !empty($product['client_id']) ? find_by_id('users', (int)$product['client_id']) : null;
$product_owner_name = $product_owner ? $product_owner['name'] : 'Stok internal / tanpa pelanggan';
?>
<?php
 if(isset($_POST['product'])){
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null;
    if(!function_exists('warehouse_csrf_is_valid') || !warehouse_csrf_is_valid($csrf_token)){
      $session->msg('d','Token keamanan tidak valid atau sudah kedaluwarsa. Silakan muat ulang form.');
      redirect('edit_product.php?id='.$product['id'], false);
    }
    $req_fields = array('product-title','product-categorie');
    validate_fields($req_fields);

   if(empty($errors)){
       $p_name  = remove_junk($db->escape($_POST['product-title']));
       $p_cat   = (int)$_POST['product-categorie'];
       // Stok dan satuannya tidak boleh diubah dari form metadata. Untuk produk
       // bundle-backed, products.quantity hanya boleh berubah bersama status bundle.
       $p_qty   = (int)$product['quantity'];
       $p_unit  = isset($product['unit_id']) ? (int)$product['unit_id'] : 0;
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
       // Pemilik produk bundle-backed dikunci karena setiap inventory_bundles
       // menyimpan client_id yang sama. Mengubah hanya products.client_id akan
       // membuat ownership dan request pengambilan tidak konsisten.
       if($has_bundle_details){
         $client_id = !empty($product['client_id']) ? (int)$product['client_id'] : 0;
       } else {
         $client_id = isset($_POST['product-client']) && $_POST['product-client'] !== ''
           ? (int)$db->escape($_POST['product-client'])
           : 0;
       }
       $requested_client_id = $client_id;
       if(!$has_bundle_details && $client_id > 0){
         $client_row = find_by_id('users', $client_id);
         if(!$client_row || (int)$client_row['user_level'] !== USER_LEVEL_CLIENT || (int)$client_row['status'] !== 1){
           $session->msg('d','Pemilik barang harus berupa client yang masih aktif.');
           redirect('edit_product.php?id='.(int)$product['id'], false);
         }
       }
       if($client_id > 0 && trim($no_sj) === ''){
         $session->msg('d','Nomor Surat Jalan wajib diisi untuk barang milik client.');
         redirect('edit_product.php?id='.(int)$product['id'], false);
       }
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
       $result = false;
       try{
         $db->begin_transaction();
         $owner_state = lock_product_owner_for_metadata_update((int)$product['id'], $requested_client_id, $has_bundle_details);
         if($owner_state === false){ throw new RuntimeException('Product ownership changed while editing.'); }
         $client_id = (int)$owner_state['client_id'];
         if($client_id > 0 && trim($no_sj) === ''){ throw new RuntimeException('Client product requires a delivery note number.'); }
         $client_value = $client_id > 0 ? "'".$db->escape($client_id)."'" : "NULL";
         $query   = "UPDATE products SET";
         $query  .=" name ='{$p_name}', no_surat_jalan={$no_sj_value}, no_batch={$no_batch_value}, grade={$grade_value},";
         $query  .=" tebal={$tebal_value}, lebar={$lebar_value}, panjang={$panjang_value}, m3={$m3_value}, sj_scan={$sj_scan_value},";
         $query  .=" buy_price ='{$p_buy}', sale_price ='{$p_sale}', categorie_id ='{$p_cat}',client_id={$client_value},media_id='{$media_id}'";
         $query  .=" WHERE id ='".(int)$product['id']."'";
         $db->query_or_throw($query);
         $db->commit();
         $result = true;
       } catch(Throwable $e){
         if($db->in_transaction()){ $db->rollback(); }
         error_log('[edit_product] metadata update failed: '.$e->getMessage());
       }

                if($result){
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
                 $session->msg('d','Barang gagal diperbarui karena data pemilik atau rincian bundle berubah. Muat ulang halaman lalu coba kembali.');
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
              <?php if(function_exists('warehouse_csrf_field')){ echo warehouse_csrf_field(); } ?>
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
                    <label>Pemilik Barang</label>
                    <?php if($has_bundle_details): ?>
                      <p class="form-control-static"><strong><?php echo remove_junk($product_owner_name); ?></strong></p>
                      <small class="text-muted">Dikunci karena rincian bundle sudah terkait dengan pemilik ini.</small>
                    <?php else: ?>
                      <select class="form-control" name="product-client">
                        <option value="">Stok internal / tanpa pelanggan</option>
                        <?php foreach ($all_clients as $client): ?>
                          <option value="<?php echo (int)$client['id'];?>" <?php if((int)$product['client_id'] === (int)$client['id']): echo 'selected="selected"'; endif; ?>>
                            <?php echo remove_junk($client['name']); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                      <small class="text-muted">Pemilik masih dapat diubah sebelum rincian bundle historis diinisialisasi.</small>
                    <?php endif; ?>
                  </div>
                  <div class="col-md-6" style="margin-top:10px;">
                    <label>Satuan Stok</label>
                    <p class="form-control-static">
                      Bundle: <strong><?php echo remove_junk($package_unit_name); ?></strong>
                      &nbsp; / &nbsp;
                      Dasar: <strong><?php echo $base_unit ? remove_junk($base_unit_name) : 'belum ditetapkan'; ?></strong>
                    </p>
                    <small class="text-muted">Satuan dan agregat stok dikelola bersama rincian bundle, bukan dari form metadata ini.</small>
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
                <?php if($has_bundle_details): ?>
                  <div class="alert alert-success" style="margin-bottom:0;">
                    <strong>Stok berbasis bundle aktif.</strong><br>
                    Stok saat ini: <strong><?php echo (int)$product['quantity']; ?> <?php echo remove_junk($base_unit_name); ?></strong>
                    dalam <strong><?php echo (int)$current_bundle_count; ?> <?php echo remove_junk($package_unit_name); ?></strong>
                    (<?php echo (int)$bundle_count; ?> bundle tercatat termasuk yang sudah keluar).
                    <br><small>Nilai agregat dikunci agar tidak berbeda dari status tiap bundle.</small>
                    <div style="margin-top:8px;">
                      <a href="manage_product_bundles.php?id=<?php echo (int)$product['id']; ?>" class="btn btn-success btn-xs">
                        <span class="glyphicon glyphicon-list"></span> Lihat Rincian Bundle
                      </a>
                    </div>
                  </div>
                <?php else: ?>
                  <div class="alert alert-warning" style="margin-bottom:0;">
                    <strong>Produk historis belum memiliki rincian bundle.</strong><br>
                    Stok lama <strong><?php echo (int)$product['quantity']; ?></strong> tidak dikonversi otomatis. Admin harus memasukkan isi setiap bundle, dan jumlahnya wajib sama persis dengan stok tersebut.
                    <div style="margin-top:8px;">
                      <a href="manage_product_bundles.php?id=<?php echo (int)$product['id']; ?>" class="btn btn-warning btn-xs">
                        <span class="glyphicon glyphicon-th-list"></span> Input Rincian Bundle
                      </a>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
              <div class="form-group">
                <div class="row">
                  <div class="col-md-6">
                    <label for="defect-quantity">Tambah Jumlah Cacat (<?php echo $base_unit ? remove_junk($base_unit_name) : 'unit dasar'; ?>)</label>
                    <input type="number" min="0" class="form-control" name="defect-quantity" value="0" placeholder="Jumlah cacat tambahan" />
                  </div>
                  <div class="col-md-6">
                    <label for="defect-note">Keterangan Cacat</label>
                    <input type="text" class="form-control" name="defect-note" placeholder="Contoh: basah, lembab, rusak" />
                  </div>
                </div>
                <input type="hidden" name="buying-price" value="<?php echo remove_junk($product['buy_price']);?>">
                <input type="hidden" name="saleing-price" value="<?php echo remove_junk($product['sale_price']);?>">
              </div>
              <button type="submit" name="product" class="btn btn-danger">Update Barang</button>
          </form>
         </div>
        </div>
      </div>
  </div>

<?php include_once('layouts/footer.php'); ?>
