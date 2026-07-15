<?php
  $page_title = 'Tambah Barang Titipan';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
  require_permission('barang','create');
  // Initialize delivery/SJ tables before the atomic receipt transaction.
  // Schema DDL must never run midway through a stock mutation.
  ensure_consignment_tables();
  $all_categories = find_all('categories');
  $all_photo = find_all('media');
  $all_clients = find_active_clients();
  $all_units = find_all_units();
  $msg = $session->msg();

  /**
   * Bundle harus dicatat satu per satu karena isi setiap bundle dapat berbeda.
   * products.quantity selalu menyimpan jumlah seluruh unit dasar.
   */
  function parse_submitted_bundle_quantities($raw, &$error){
    $error = '';
    if(!is_array($raw) || empty($raw)){
      $error = 'Tambahkan minimal satu bundle.';
      return array();
    }

    $quantities = array();
    $total = 0;
    foreach($raw as $index => $value){
      $value = trim((string)$value);
      if($value === '' || !preg_match('/^[1-9][0-9]*$/', $value)){
        $error = 'Isi bundle #'.((int)$index + 1).' harus berupa bilangan bulat positif.';
        return array();
      }
      $quantity = (int)$value;
      if($quantity < 1 || $quantity > 2147483647 || $total > 2147483647 - $quantity){
        $error = 'Total isi bundle melebihi batas stok yang didukung.';
        return array();
      }
      $quantities[] = $quantity;
      $total += $quantity;
    }
    return $quantities;
  }

?>
<?php
 if(isset($_POST['add_product'])){
   $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null;
   if(!function_exists('warehouse_csrf_is_valid') || !warehouse_csrf_is_valid($csrf_token)){
     $session->msg('d','Token keamanan tidak valid atau sudah kedaluwarsa. Silakan muat ulang form.');
     redirect('add_product.php', false);
   }
   $req_fields = array('product-title','product-categorie','product-client','product-package-unit','product-base-unit','product-sj');
   validate_fields($req_fields);
   if(!is_array($errors)){ $errors = empty($errors) ? array() : array($errors); }
   $bundle_error = '';
   $bundle_quantities = parse_submitted_bundle_quantities(isset($_POST['bundle-quantity']) ? $_POST['bundle-quantity'] : null, $bundle_error);
   if($bundle_error !== ''){ $errors[] = $bundle_error; }
   if(!function_exists('create_inventory_bundles')){
     $errors[] = 'Fitur rincian bundle belum tersedia. Hubungi administrator sistem.';
   }
   if(!isset($_POST['confirm-bundle-details']) || $_POST['confirm-bundle-details'] !== '1'){
     $errors[] = 'Konfirmasi bahwa pemilik, satuan, dan isi setiap bundle sudah sesuai fisik dan Surat Jalan.';
   }
   if(empty($errors)){
     $p_name  = remove_junk($db->escape($_POST['product-title']));
     $p_cat   = remove_junk($db->escape($_POST['product-categorie']));
     $p_qty   = array_sum($bundle_quantities);
     $p_unit  = (int)$db->escape($_POST['product-package-unit']);
     $base_unit_id = (int)$db->escape($_POST['product-base-unit']);
     if($p_unit <= 0 || $base_unit_id <= 0 || !find_unit_by_id($p_unit) || !find_unit_by_id($base_unit_id)){
       $session->msg('d','Satuan bundle dan satuan dasar wajib dipilih.');
       redirect('add_product.php', false);
     }
     // Detail surat jalan, grade & ukuran plywood
     $no_sj    = isset($_POST['product-sj']) ? remove_junk($db->escape($_POST['product-sj'])) : '';
     $client_id = isset($_POST['product-client']) && $_POST['product-client'] !== ''
       ? (int)$db->escape($_POST['product-client'])
       : 0;
     $client_row = $client_id > 0 ? find_by_id('users', $client_id) : null;
     if($client_id <= 0 || !$client_row || (int)$client_row['user_level'] !== USER_LEVEL_CLIENT || (int)$client_row['status'] !== 1){
       $session->msg('d','Client aktif wajib dipilih agar bundle mempunyai jalur request pengambilan.');
       redirect('add_product.php', false);
     }
     if(trim($no_sj) === ''){
       $session->msg('d','Nomor Surat Jalan wajib diisi untuk barang titipan.');
       redirect('add_product.php', false);
     }
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
     if($defect_qty < 0 || $defect_qty > $p_qty){
       $session->msg('d','Jumlah barang cacat harus berada di antara 0 dan total unit dasar.');
       redirect('add_product.php', false);
     }
     $p_buy   = '0.00';
     $p_sale  = '0.00';
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
       if (!isset($_POST['product-photo']) || $_POST['product-photo'] === "") {
         $media_id = '0';
       } else {
         $media_id = remove_junk($db->escape($_POST['product-photo']));
       }
     }
     $date    = make_date();
     $query  = "INSERT INTO products (";
     $query .=" name,no_surat_jalan,no_batch,grade,tebal,lebar,panjang,m3,sj_scan,quantity,pcs_per_crate,buy_price,sale_price,categorie_id,client_id,unit_id,base_unit_id,media_id,date";
     $query .=") VALUES (";
     $query .=" '{$p_name}', {$no_sj_value}, {$no_batch_value}, {$grade_value}, {$tebal_value}, {$lebar_value}, {$panjang_value}, {$m3_value}, {$sj_scan_value}, '{$p_qty}', NULL, '{$p_buy}', '{$p_sale}', '{$p_cat}', {$client_value}, '{$p_unit}', '{$base_unit_id}', '{$media_id}', '{$date}'";
      $query .=")";
      $product_id = 0;
      try{
        // Produk, agregat stok, rincian fisik setiap bundle, mutasi masuk, dan
        // rincian SJ masuk harus berhasil seluruhnya atau tidak tersimpan sama sekali.
        $db->begin_transaction();
        $owner_lock = $db->query_or_throw("SELECT id FROM users WHERE id='{$client_id}' AND user_level='".(int)USER_LEVEL_CLIENT."' AND status='1' LIMIT 1 FOR UPDATE");
        if(!$db->fetch_assoc($owner_lock)){ throw new RuntimeException('Client owner changed before receipt commit.'); }
        $db->query_or_throw($query);
        $product_id = $db->insert_id();

        $movement_id = record_stock_movement($product_id, 'in', $p_qty, 0, $p_qty, array(
          'client_id' => $client_id,
          'reference_type' => 'product',
          'reference_id' => $product_id,
          'event_key' => 'product-receipt:'.$product_id,
          'note' => 'Stok awal barang titipan',
          'created_at' => $date,
          'unit_id' => $base_unit_id
        ));
        if(!$movement_id){ throw new RuntimeException('Riwayat stok masuk gagal dicatat.'); }

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
        if(!$delivery_id){ throw new RuntimeException('Surat jalan masuk gagal dibuat.'); }

        $actor = current_user();
        $bundles_created = create_inventory_bundles(
          $product_id,
          $bundle_quantities,
          $p_unit,
          $base_unit_id,
          array(
            'client_id' => $client_id,
            'created_by' => $actor ? (int)$actor['id'] : 0,
            'created_at' => $date
          )
        );
        if(!$bundles_created){ throw new RuntimeException('Rincian bundle gagal dicatat.'); }
        if(!function_exists('create_inbound_delivery_order_items') || !create_inbound_delivery_order_items($delivery_id, $product_id, $bundles_created)){
          throw new RuntimeException('Rincian surat jalan masuk gagal dicatat.');
        }
        $db->commit();
      } catch(Throwable $e){
        if($db->in_transaction()){ $db->rollback(); }
        error_log('[add_product] atomic receipt failed: '.$e->getMessage());
        $session->msg('d','Barang gagal disimpan. Produk, stok, bundle, dan Surat Jalan tidak mengalami perubahan.');
        redirect('add_product.php', false);
      }

      if($defect_qty > 0){
        $defect_id = record_product_defect($product_id, $client_id, $defect_qty, $defect_note);
        if($defect_id){ save_defect_photos($defect_id, 'defect_photos'); }
      }
      $session->msg('s',"Barang titipan berhasil ditambahkan. ");
      redirect('add_product.php', false);

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
          <form method="post" action="add_product.php" class="clearfix" enctype="multipart/form-data" data-app-confirm="Pastikan client, satuan dasar, dan isi setiap bundle sudah sama dengan barang fisik serta Surat Jalan. Simpan data sekarang?" data-confirm-title="Konfirmasi Barang Masuk" data-confirm-button="Ya, Simpan" data-confirm-class="btn-primary">
              <?php if(function_exists('warehouse_csrf_field')){ echo warehouse_csrf_field(); } ?>
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
                    <label>Client Pemilik</label>
                    <select class="form-control" name="product-client" required>
                      <option value="">Pilih client aktif</option>
                    <?php  foreach ($all_clients as $client): ?>
                      <option value="<?php echo (int)$client['id'] ?>">
                        <?php echo remove_junk($client['name']) ?></option>
                    <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6" style="margin-top:10px;">
                    <label>Satuan Bundle</label>
                    <select class="form-control" id="product-package-unit" name="product-package-unit" required>
                      <option value="">Pilih satuan bundle</option>
                    <?php foreach ($all_units as $unit): ?>
                      <option value="<?php echo (int)$unit['id'] ?>"><?php echo remove_junk($unit['name']); ?></option>
                    <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Contoh: krat, palet, atau dus.</small>
                  </div>
                  <div class="col-md-6" style="margin-top:10px;">
                    <label>Satuan Dasar Stok</label>
                    <select class="form-control" id="product-base-unit" name="product-base-unit" required>
                      <option value="">Pilih satuan dasar</option>
                    <?php foreach ($all_units as $unit): ?>
                      <option value="<?php echo (int)$unit['id'] ?>"><?php echo remove_junk($unit['name']); ?></option>
                    <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Contoh: lembar atau pcs. Total stok disimpan dalam satuan ini.</small>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label>Detail Surat Jalan &amp; Grade</label>
                <div class="row">
                  <div class="col-md-4">
                    <input type="text" class="form-control" name="product-sj" placeholder="No Surat Jalan (cth: 2160006751)" required>
                    <small class="text-muted">Wajib. Nomor SJ yang sama boleh dipakai untuk beberapa jenis barang.</small>
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
                <label>Rincian Isi Setiap Bundle</label>
                <div class="panel panel-info" style="margin-bottom:8px;">
                  <div class="panel-body" id="bundle-rows">
                    <div class="row bundle-row" style="margin-bottom:8px;">
                      <div class="col-sm-2"><p class="form-control-static bundle-label"><strong>Bundle #1</strong></p></div>
                      <div class="col-sm-8"><input type="number" min="1" step="1" class="form-control bundle-quantity" name="bundle-quantity[]" placeholder="Isi bundle dalam satuan dasar" required></div>
                      <div class="col-sm-2"><button type="button" class="btn btn-default remove-bundle" disabled><span class="glyphicon glyphicon-trash"></span></button></div>
                    </div>
                  </div>
                  <div class="panel-footer clearfix">
                    <button type="button" class="btn btn-info btn-sm" id="add-bundle"><span class="glyphicon glyphicon-plus"></span> Tambah Bundle</button>
                    <span class="pull-right">Jumlah bundle: <strong id="bundle-count">1</strong> &nbsp; Total stok: <strong id="bundle-total">0</strong> <span id="base-unit-summary">unit dasar</span></span>
                  </div>
                </div>
                <small class="text-muted">Masukkan isi sebenarnya untuk tiap bundle. Contoh: 70, 68, dan 72 dicatat sebagai tiga bundle terpisah; sistem tidak menganggap semua bundle berisi sama.</small>
              </div>
              <script>
              (function(){
                var rows=document.getElementById('bundle-rows'),
                    add=document.getElementById('add-bundle'),
                    count=document.getElementById('bundle-count'),
                    total=document.getElementById('bundle-total'),
                    base=document.getElementById('product-base-unit'),
                    baseSummary=document.getElementById('base-unit-summary');
                if(!rows || !add) return;
                function selectedBaseUnit(){
                  if(!base || base.selectedIndex < 0) return 'unit dasar';
                  var text=(base.options[base.selectedIndex].text||'').trim();
                  return !text || text.toLowerCase().indexOf('pilih')===0 ? 'unit dasar' : text;
                }
                function refresh(){
                  var list=rows.querySelectorAll('.bundle-row'), sum=0;
                  for(var i=0;i<list.length;i++){
                    var label=list[i].querySelector('.bundle-label'), input=list[i].querySelector('.bundle-quantity'), remove=list[i].querySelector('.remove-bundle');
                    if(label) label.innerHTML='<strong>Bundle #'+(i+1)+'</strong>';
                    if(input) sum+=(parseInt(input.value,10)||0);
                    if(remove) remove.disabled=list.length===1;
                  }
                  count.textContent=list.length;
                  total.textContent=sum;
                  baseSummary.textContent=selectedBaseUnit();
                }
                function addRow(){
                  var wrapper=document.createElement('div');
                  wrapper.className='row bundle-row';
                  wrapper.style.marginBottom='8px';
                  wrapper.innerHTML='<div class="col-sm-2"><p class="form-control-static bundle-label"></p></div>'+
                    '<div class="col-sm-8"><input type="number" min="1" step="1" class="form-control bundle-quantity" name="bundle-quantity[]" placeholder="Isi bundle dalam satuan dasar" required></div>'+
                    '<div class="col-sm-2"><button type="button" class="btn btn-default remove-bundle"><span class="glyphicon glyphicon-trash"></span></button></div>';
                  rows.appendChild(wrapper);
                  wrapper.querySelector('input').focus();
                  refresh();
                }
                add.addEventListener('click', addRow);
                rows.addEventListener('input', function(event){ if(event.target.classList.contains('bundle-quantity')) refresh(); });
                rows.addEventListener('click', function(event){
                  var button=event.target.closest ? event.target.closest('.remove-bundle') : null;
                  if(button && !button.disabled){ button.closest('.bundle-row').remove(); refresh(); }
                });
                if(base) base.addEventListener('change', refresh);
                refresh();
              })();
              </script>
              <div class="form-group">
                <div class="row">
                  <div class="col-md-4">
                    <label>Jumlah Barang Cacat (satuan dasar)</label>
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
              <div class="checkbox">
                <label>
                  <input type="checkbox" name="confirm-bundle-details" value="1" required>
                  Saya sudah mencocokkan client, Surat Jalan, satuan dasar, dan isi aktual setiap bundle. Data bundle menjadi dasar mutasi stok dan tidak dapat diedit sembarangan.
                </label>
              </div>
              <button type="submit" name="add_product" class="btn btn-danger">Simpan Barang</button>
          </form>
         </div>
        </div>
      </div>
    </div>
  </div>

<?php include_once('layouts/footer.php'); ?>
