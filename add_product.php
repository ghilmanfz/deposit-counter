<?php
  $page_title = 'Tambah Barang Titipan';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
  require_permission('barang','create');
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
     $isi    = (int)$db->escape($_POST['product-quantity']); // isi per satuan (lembar/PC)
     if($isi < 1){ $isi = 1; }
     $jumlah = (isset($_POST['product-krat']) && (int)$_POST['product-krat'] > 0) ? (int)$db->escape($_POST['product-krat']) : 1;
     $p_qty   = $isi * $jumlah; // total lembar
     $pcs_value = $isi > 1 ? "'".$db->escape($isi)."'" : "NULL"; // simpan isi per satuan hanya jika >1 (kontainer)
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
     $sj_scan = save_sj_scan('sj_scan_file');
     $sj_scan_value = $sj_scan !== '' ? "'".$db->escape($sj_scan)."'" : "NULL";
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
     $query .=" name,no_surat_jalan,no_batch,grade,tebal,lebar,panjang,m3,sj_scan,quantity,pcs_per_crate,buy_price,sale_price,categorie_id,client_id,unit_id,media_id,date";
     $query .=") VALUES (";
     $query .=" '{$p_name}', {$no_sj_value}, {$no_batch_value}, {$grade_value}, {$tebal_value}, {$lebar_value}, {$panjang_value}, {$m3_value}, {$sj_scan_value}, '{$p_qty}', {$pcs_value}, '{$p_buy}', '{$p_sale}', '{$p_cat}', {$client_value}, '{$p_unit}', '{$media_id}', '{$date}'";
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
                    <select class="form-control" id="product-unit" name="product-unit">
                      <option value="">Pilih Satuan</option>
                    <?php foreach ($all_units as $unit): ?>
                      <option value="<?php echo (int)$unit['id'] ?>"><?php echo remove_junk($unit['name']); ?></option>
                    <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label>Detail Surat Jalan &amp; Grade</label>
                <div class="row">
                  <div class="col-md-4">
                    <input type="text" class="form-control" name="product-sj" placeholder="No Surat Jalan (cth: 2160006751)">
                  </div>
                  <div class="col-md-4">
                    <input type="text" class="form-control" name="product-batch" placeholder="No Batch (cth: 0002687264)">
                  </div>
                  <div class="col-md-4">
                    <select class="form-control" name="product-grade">
                      <option value="">- Pilih Grade -</option>
                    <?php foreach (product_grades() as $g): ?>
                      <option value="<?php echo $g; ?>"><?php echo $g; ?></option>
                    <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="row" style="margin-top:10px;">
                  <div class="col-md-6">
                    <label>Scan / Foto Surat Jalan (opsional)</label>
                    <input type="file" class="form-control" name="sj_scan_file" accept="image/*,application/pdf">
                    <small class="text-muted">Format: JPG, PNG, atau PDF.</small>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label>Ukuran (mm)</label>
                <div class="row">
                  <div class="col-md-3">
                    <input type="number" step="0.01" min="0" class="form-control" name="product-tebal" placeholder="Tebal / T">
                  </div>
                  <div class="col-md-3">
                    <input type="number" step="0.01" min="0" class="form-control" name="product-lebar" placeholder="Lebar / W">
                  </div>
                  <div class="col-md-3">
                    <input type="number" step="0.01" min="0" class="form-control" name="product-panjang" placeholder="Panjang / L">
                  </div>
                  <div class="col-md-3">
                    <input type="number" step="0.0001" min="0" class="form-control" name="product-m3" placeholder="M3 (opsional)">
                  </div>
                </div>
                <small class="text-muted">Isi angka polos: <strong>1220</strong> bukan 1.220. Contoh plywood: T 3,6 &times; W 1220 &times; L 2440 mm. M3 boleh kosong.</small>
              </div>

              <div class="form-group">
                <div class="row">
                  <div class="col-md-4">
                    <label id="label-krat">Jumlah Krat</label>
                    <input type="number" min="1" class="form-control" id="input-krat" name="product-krat" value="1" placeholder="cth: 16">
                  </div>
                  <div class="col-md-4">
                    <label id="label-pcs">Isi per Krat (lembar/PC)</label>
                    <input type="number" min="0" class="form-control" id="input-pcs" name="product-quantity" placeholder="cth: 330">
                  </div>
                  <div class="col-md-4">
                    <label>Total Lembar</label>
                    <input type="text" class="form-control" id="input-total" value="0" readonly>
                  </div>
                </div>
                <small class="text-muted">Label mengikuti <strong>Satuan</strong> yang dipilih (Krat, Palet, dst). Total lembar = Jumlah &times; Isi per satuan. Isi per satuan boleh 1 untuk barang satuan tunggal.</small>
              </div>
              <script>
              (function(){
                var u=document.getElementById('product-unit'),
                    lk=document.getElementById('label-krat'), lp=document.getElementById('label-pcs'),
                    k=document.getElementById('input-krat'), p=document.getElementById('input-pcs'), t=document.getElementById('input-total');
                function unitName(){
                  if(!u || u.selectedIndex < 0) return 'Satuan';
                  var txt=(u.options[u.selectedIndex].text||'').trim();
                  if(!txt || txt.toLowerCase().indexOf('pilih')!==-1) return 'Satuan';
                  return txt.charAt(0).toUpperCase()+txt.slice(1);
                }
                function labels(){ var n=unitName(); if(lk)lk.textContent='Jumlah '+n; if(lp)lp.textContent='Isi per '+n+' (lembar/PC)'; }
                function calc(){ if(t) t.value=(parseInt(k.value)||0)*(parseInt(p.value)||1); }
                if(u) u.addEventListener('change', labels);
                if(k) k.addEventListener('input', calc);
                if(p) p.addEventListener('input', calc);
                labels(); calc();
              })();
              </script>
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
