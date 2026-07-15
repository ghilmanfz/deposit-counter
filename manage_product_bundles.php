<?php
  $page_title = 'Rincian Bundle Barang';
  require_once('includes/load.php');
  require_permission('barang','update');

  $product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  $product = $product_id > 0 ? find_by_id('products', $product_id) : null;
  if(!$product){
    $session->msg('d','Barang titipan tidak ditemukan.');
    redirect('product.php', false);
  }

  $all_units = find_all_units();

  function parse_historical_bundle_quantities($raw, &$error){
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

  $bundle_details = function_exists('find_product_inventory_bundles')
    ? find_product_inventory_bundles($product_id)
    : array();
  $bundle_summary = function_exists('find_product_bundle_summary')
    ? find_product_bundle_summary($product_id)
    : array();
  $has_bundle_details = !empty($bundle_details) || !empty($bundle_summary['total_count']);
  $product_client = !empty($product['client_id']) ? find_by_id('users', (int)$product['client_id']) : null;

  if(isset($_POST['initialize_bundles'])){
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null;
    if(!function_exists('warehouse_csrf_is_valid') || !warehouse_csrf_is_valid($csrf_token)){
      $session->msg('d','Token keamanan tidak valid atau sudah kedaluwarsa. Silakan muat ulang form.');
      redirect('manage_product_bundles.php?id='.$product_id, false);
    }
    if($has_bundle_details){
      $session->msg('d','Rincian bundle sudah tersedia dan tidak dapat diinisialisasi ulang.');
      redirect('manage_product_bundles.php?id='.$product_id, false);
    }
    if(empty($product['client_id'])){
      $session->msg('d','Tetapkan client aktif dari halaman Edit Barang sebelum menginisialisasi rincian bundle. Stok internal lama tetap memakai alur transaksi lama.');
      redirect('manage_product_bundles.php?id='.$product_id, false);
    }
    if(!$product_client || (int)$product_client['user_level'] !== USER_LEVEL_CLIENT || (int)$product_client['status'] !== 1){
      $session->msg('d','Client pemilik harus aktif saat rincian bundle diinisialisasi. Perbarui pemilik dari halaman Edit Barang.');
      redirect('manage_product_bundles.php?id='.$product_id, false);
    }
    if(trim((string)$product['no_surat_jalan']) === ''){
      $session->msg('d','Isi Nomor Surat Jalan dari halaman Edit Barang sebelum menginisialisasi bundle milik client.');
      redirect('manage_product_bundles.php?id='.$product_id, false);
    }
    if(!isset($_POST['confirm-bundle-details']) || $_POST['confirm-bundle-details'] !== '1'){
      $session->msg('d','Konfirmasi bahwa seluruh isi bundle sudah dicocokkan dengan barang fisik dan Surat Jalan.');
      redirect('manage_product_bundles.php?id='.$product_id, false);
    }

    $package_unit_id = isset($_POST['package-unit']) ? (int)$_POST['package-unit'] : 0;
    $base_unit_id = isset($_POST['base-unit']) ? (int)$_POST['base-unit'] : 0;
    $parse_error = '';
    $quantities = parse_historical_bundle_quantities(isset($_POST['bundle-quantity']) ? $_POST['bundle-quantity'] : null, $parse_error);
    $submitted_total = array_sum($quantities);

    if($package_unit_id <= 0 || $base_unit_id <= 0 || !find_unit_by_id($package_unit_id) || !find_unit_by_id($base_unit_id)){
      $session->msg('d','Satuan bundle dan satuan dasar wajib dipilih.');
      redirect('manage_product_bundles.php?id='.$product_id, false);
    }
    if($parse_error !== ''){
      $session->msg('d',$parse_error);
      redirect('manage_product_bundles.php?id='.$product_id, false);
    }
    if($submitted_total !== (int)$product['quantity']){
      $session->msg('d','Total rincian bundle harus sama persis dengan stok historis '.(int)$product['quantity'].'. Tidak ada data yang diubah.');
      redirect('manage_product_bundles.php?id='.$product_id, false);
    }
    if(!function_exists('initialize_historical_inventory_bundles')){
      $session->msg('d','Fitur inisialisasi bundle historis belum tersedia. Hubungi administrator sistem.');
      redirect('manage_product_bundles.php?id='.$product_id, false);
    }

    $actor = current_user();
    $initialized = initialize_historical_inventory_bundles(
      $product_id,
      $quantities,
      $package_unit_id,
      $base_unit_id,
      array(
        'client_id' => !empty($product['client_id']) ? (int)$product['client_id'] : 0,
        'created_by' => $actor ? (int)$actor['id'] : 0,
        'created_at' => make_date()
      )
    );
    if($initialized){
      $session->msg('s','Rincian bundle berhasil dicatat tanpa mengubah total stok.');
    } else {
      $session->msg('d','Rincian bundle gagal dicatat. Pastikan stok belum berubah dan produk belum memiliki rincian bundle.');
    }
    redirect('manage_product_bundles.php?id='.$product_id, false);
  }

  $package_unit_id = isset($bundle_summary['package_unit_id'])
    ? (int)$bundle_summary['package_unit_id']
    : (isset($product['unit_id']) ? (int)$product['unit_id'] : 0);
  $base_unit_id = isset($bundle_summary['base_unit_id'])
    ? (int)$bundle_summary['base_unit_id']
    : (isset($product['base_unit_id']) ? (int)$product['base_unit_id'] : 0);
  $package_unit = $package_unit_id > 0 ? find_unit_by_id($package_unit_id) : null;
  $base_unit = $base_unit_id > 0 ? find_unit_by_id($base_unit_id) : null;
  $package_unit_name = $package_unit ? $package_unit['name'] : 'bundle';
  $base_unit_name = $base_unit ? $base_unit['name'] : 'unit dasar';
  $msg = $session->msg();
?>
<?php include_once('layouts/header.php'); ?>

<div class="row"><div class="col-md-12"><?php echo display_msg($msg); ?></div></div>
<div class="row">
  <div class="col-md-10">
    <div class="panel panel-default">
      <div class="panel-heading clearfix">
        <strong><span class="glyphicon glyphicon-th-list"></span> Rincian Bundle: <?php echo remove_junk($product['name']); ?></strong>
        <div class="pull-right">
          <a href="edit_product.php?id=<?php echo $product_id; ?>" class="btn btn-default btn-xs">Kembali ke Edit</a>
          <a href="product.php" class="btn btn-default btn-xs">Daftar Barang</a>
        </div>
      </div>
      <div class="panel-body">
        <div class="row" style="margin-bottom:15px;">
          <div class="col-sm-4"><strong>Stok saat ini</strong><br><?php echo (int)$product['quantity']; ?> <?php echo remove_junk($base_unit_name); ?></div>
          <div class="col-sm-4"><strong>Satuan bundle</strong><br><?php echo remove_junk($package_unit_name); ?></div>
          <div class="col-sm-4"><strong>Satuan dasar</strong><br><?php echo $base_unit ? remove_junk($base_unit_name) : 'Belum ditetapkan'; ?></div>
        </div>

        <?php if($has_bundle_details): ?>
          <div class="alert alert-info">
            Rincian ini menjadi sumber kebenaran stok. Isi bundle tidak dapat diedit dari halaman ini agar agregat dan histori tidak desinkron.
          </div>
          <div class="table-responsive">
            <table class="table table-bordered table-striped">
              <thead>
                <tr><th class="text-center" style="width:80px;">Bundle</th><th class="text-center">Isi</th><th class="text-center">Status</th><th class="text-center">ID Internal</th></tr>
              </thead>
              <tbody>
              <?php foreach($bundle_details as $index => $bundle): ?>
                <?php
                  $bundle_label = !empty($bundle['bundle_no']) ? $bundle['bundle_no'] : '#'.($index + 1);
                  $quantity = isset($bundle['quantity']) ? (int)$bundle['quantity'] : 0;
                  $status = isset($bundle['status']) ? $bundle['status'] : 'available';
                  $status_label = $status === 'reserved' ? 'Dipesan' : ($status === 'out' ? 'Keluar' : 'Tersedia');
                  $status_class = $status === 'reserved' ? 'warning' : ($status === 'out' ? 'default' : 'success');
                ?>
                <tr>
                  <td class="text-center"><?php echo remove_junk($bundle_label); ?></td>
                  <td class="text-center"><strong><?php echo $quantity; ?></strong> <?php echo remove_junk($base_unit_name); ?></td>
                  <td class="text-center"><span class="label label-<?php echo $status_class; ?>"><?php echo $status_label; ?></span></td>
                  <td class="text-center"><?php echo isset($bundle['id']) ? (int)$bundle['id'] : '-'; ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php elseif(empty($product['client_id'])): ?>
          <div class="alert alert-warning">
            <strong>Stok internal tidak dikonversi ke bundle.</strong><br>
            Produk historis ini belum memiliki client pemilik. Gunakan alur transaksi lama, atau tetapkan client aktif dari halaman Edit Barang terlebih dahulu jika barang memang merupakan titipan client.
          </div>
        <?php elseif(!$product_client || (int)$product_client['user_level'] !== USER_LEVEL_CLIENT || (int)$product_client['status'] !== 1): ?>
          <div class="alert alert-warning">
            Client pemilik tidak aktif atau tidak valid. Perbarui pemilik dari halaman Edit Barang sebelum menginisialisasi rincian bundle.
          </div>
        <?php elseif((int)$product['quantity'] <= 0): ?>
          <div class="alert alert-warning">
            Produk historis ini tidak memiliki stok tersisa. Sistem tidak membuat bundle kosong atau menebak rincian lama secara otomatis.
          </div>
        <?php else: ?>
          <div class="alert alert-warning">
            <strong>Produk historis: rincian bundle belum tersedia.</strong><br>
            Masukkan isi aktual setiap bundle. Total wajib tepat <strong><?php echo (int)$product['quantity']; ?></strong>. Proses ini tidak menambah atau mengurangi stok dan tidak dapat dijalankan dua kali.
            <?php if(!empty($product['pcs_per_crate'])): ?>
              <br><small>Data lama mencatat angka <?php echo (int)$product['pcs_per_crate']; ?> per bundle. Angka ini hanya referensi dan tidak diisikan otomatis.</small>
            <?php endif; ?>
          </div>
          <form method="post" action="manage_product_bundles.php?id=<?php echo $product_id; ?>" id="legacy-bundle-form" data-app-confirm="Rincian ini akan menjadi sumber kebenaran stok. Pastikan semua isi bundle sudah dicek secara fisik. Lanjutkan?" data-confirm-title="Konfirmasi Rincian Bundle" data-confirm-button="Ya, Simpan Rincian" data-confirm-class="btn-warning">
            <?php if(function_exists('warehouse_csrf_field')){ echo warehouse_csrf_field(); } ?>
            <div class="row">
              <div class="col-sm-6 form-group">
                <label>Satuan Bundle</label>
                <select class="form-control" name="package-unit" required>
                  <option value="">Pilih satuan bundle</option>
                  <?php foreach($all_units as $unit): ?>
                    <option value="<?php echo (int)$unit['id']; ?>" <?php if($package_unit_id === (int)$unit['id']): echo 'selected="selected"'; endif; ?>><?php echo remove_junk($unit['name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-sm-6 form-group">
                <label>Satuan Dasar Stok</label>
                <select class="form-control" name="base-unit" id="legacy-base-unit" required>
                  <option value="">Pilih satuan dasar</option>
                  <?php foreach($all_units as $unit): ?>
                    <option value="<?php echo (int)$unit['id']; ?>" <?php if($base_unit_id === (int)$unit['id']): echo 'selected="selected"'; endif; ?>><?php echo remove_junk($unit['name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <label>Isi Setiap Bundle</label>
            <div class="panel panel-info">
              <div class="panel-body" id="legacy-bundle-rows">
                <div class="row legacy-bundle-row" style="margin-bottom:8px;">
                  <div class="col-sm-2"><p class="form-control-static legacy-bundle-label"><strong>Bundle #1</strong></p></div>
                  <div class="col-sm-8"><input type="number" min="1" step="1" class="form-control legacy-bundle-quantity" name="bundle-quantity[]" required></div>
                  <div class="col-sm-2"><button type="button" class="btn btn-default remove-legacy-bundle" disabled><span class="glyphicon glyphicon-trash"></span></button></div>
                </div>
              </div>
              <div class="panel-footer clearfix">
                <button type="button" class="btn btn-info btn-sm" id="add-legacy-bundle"><span class="glyphicon glyphicon-plus"></span> Tambah Bundle</button>
                <span class="pull-right">Total: <strong id="legacy-bundle-total">0</strong> / <?php echo (int)$product['quantity']; ?> <span id="legacy-base-summary">unit dasar</span></span>
              </div>
            </div>
            <div class="checkbox">
              <label><input type="checkbox" name="confirm-bundle-details" value="1" required> Saya sudah mencocokkan total, satuan, dan isi setiap bundle dengan barang fisik serta Surat Jalan.</label>
            </div>
            <button type="submit" name="initialize_bundles" class="btn btn-warning" id="initialize-bundles" disabled>Konfirmasi Rincian Bundle</button>
            <small class="text-muted">Tombol aktif hanya jika total sama persis dengan stok saat ini.</small>
          </form>
          <script>
          (function(){
            var target=<?php echo (int)$product['quantity']; ?>,
                rows=document.getElementById('legacy-bundle-rows'),
                add=document.getElementById('add-legacy-bundle'),
                total=document.getElementById('legacy-bundle-total'),
                submit=document.getElementById('initialize-bundles'),
                base=document.getElementById('legacy-base-unit'),
                baseSummary=document.getElementById('legacy-base-summary');
            if(!rows || !add) return;
            function baseName(){
              if(!base || base.selectedIndex < 0) return 'unit dasar';
              var text=(base.options[base.selectedIndex].text||'').trim();
              return !text || text.toLowerCase().indexOf('pilih')===0 ? 'unit dasar' : text;
            }
            function refresh(){
              var list=rows.querySelectorAll('.legacy-bundle-row'), sum=0;
              for(var i=0;i<list.length;i++){
                var label=list[i].querySelector('.legacy-bundle-label'), input=list[i].querySelector('.legacy-bundle-quantity'), remove=list[i].querySelector('.remove-legacy-bundle');
                if(label) label.innerHTML='<strong>Bundle #'+(i+1)+'</strong>';
                if(input) sum+=(parseInt(input.value,10)||0);
                if(remove) remove.disabled=list.length===1;
              }
              total.textContent=sum;
              total.parentNode.className='pull-right '+(sum===target ? 'text-success' : 'text-danger');
              submit.disabled=sum!==target || !base || !base.value;
              baseSummary.textContent=baseName();
            }
            add.addEventListener('click', function(){
              var row=document.createElement('div');
              row.className='row legacy-bundle-row';
              row.style.marginBottom='8px';
              row.innerHTML='<div class="col-sm-2"><p class="form-control-static legacy-bundle-label"></p></div>'+
                '<div class="col-sm-8"><input type="number" min="1" step="1" class="form-control legacy-bundle-quantity" name="bundle-quantity[]" required></div>'+
                '<div class="col-sm-2"><button type="button" class="btn btn-default remove-legacy-bundle"><span class="glyphicon glyphicon-trash"></span></button></div>';
              rows.appendChild(row);
              row.querySelector('input').focus();
              refresh();
            });
            rows.addEventListener('input', function(event){ if(event.target.classList.contains('legacy-bundle-quantity')) refresh(); });
            rows.addEventListener('click', function(event){
              var button=event.target.closest ? event.target.closest('.remove-legacy-bundle') : null;
              if(button && !button.disabled){ button.closest('.legacy-bundle-row').remove(); refresh(); }
            });
            if(base) base.addEventListener('change', refresh);
            refresh();
          })();
          </script>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include_once('layouts/footer.php'); ?>
