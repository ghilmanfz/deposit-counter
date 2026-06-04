<?php
  $page_title = 'Buat Request Pengambilan Barang';
  require_once('includes/load.php');
  page_require_level(4);
  if(!is_client_user()){
    redirect_by_user_level();
  }
  $user = current_user();
  $products = join_product_table((int)$user['id']);
  if(isset($_POST['submit_request'])){
    $req_fields = array('product_id','quantity','pickup_date','pickup_time','driver_name','vehicle_no');
    validate_fields($req_fields);
    if(empty($errors)){
      $request_id = create_pickup_request(array(
        'client_id' => (int)$user['id'],
        'product_id' => (int)$_POST['product_id'],
        'quantity' => (int)$_POST['quantity'],
        'pickup_date' => $_POST['pickup_date'],
        'pickup_time' => $_POST['pickup_time'],
        'driver_name' => $_POST['driver_name'],
        'vehicle_no' => $_POST['vehicle_no']
      ));
      if($request_id){
        $request = find_pickup_request_details($request_id, (int)$user['id']);
        if($request && $request['status'] === 'auto_rejected'){
          $session->msg('d','Request otomatis ditolak: '.$request['admin_note']);
        } else {
          $session->msg('s','Request pengambilan berhasil dikirim dan menunggu persetujuan admin.');
        }
        redirect('pickup_requests.php', false);
      }
      $session->msg('d','Request gagal dibuat. Pastikan barang milik Anda dan data sudah benar.');
      redirect('add_pickup_request.php', false);
    } else {
      $session->msg('d', $errors);
      redirect('add_pickup_request.php', false);
    }
  }
  $msg = $session->msg();
?>
<?php include_once('layouts/header.php'); ?>
<div class="row"><div class="col-md-12"><?php echo display_msg($msg); ?></div></div>
<div class="row">
  <div class="col-md-8">
    <div class="panel panel-default">
      <div class="panel-heading"><strong><span class="glyphicon glyphicon-send"></span> Buat Request Pengambilan Barang</strong></div>
      <div class="panel-body">
        <form method="post" action="add_pickup_request.php">
          <div class="form-group">
            <label>Nama Barang</label>
            <select class="form-control" name="product_id">
              <option value="">Pilih Barang</option>
              <?php foreach($products as $product): ?>
              <option value="<?php echo (int)$product['id']; ?>"><?php echo remove_junk($product['name']); ?> - Stok <?php echo (int)$product['quantity']; ?> <?php echo !empty($product['unit_name']) ? remove_junk($product['unit_name']) : ''; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Jumlah yang Diambil</label><input type="number" min="1" class="form-control" name="quantity" placeholder="Jumlah"></div>
          <div class="row">
            <div class="col-md-6"><div class="form-group"><label>Tanggal Rencana Penjemputan</label><input type="date" class="form-control" name="pickup_date" value="<?php echo date('Y-m-d'); ?>"></div></div>
            <div class="col-md-6"><div class="form-group"><label>Jam Rencana Penjemputan</label><input type="time" class="form-control" name="pickup_time" value="09:00"></div></div>
          </div>
          <div class="row">
            <div class="col-md-6"><div class="form-group"><label>Nama Supir</label><input type="text" class="form-control" name="driver_name" placeholder="Nama supir"></div></div>
            <div class="col-md-6"><div class="form-group"><label>Pelat Nomor Kendaraan</label><input type="text" class="form-control" name="vehicle_no" placeholder="Contoh: B 1234 CD"></div></div>
          </div>
          <button type="submit" name="submit_request" class="btn btn-primary">Kirim Request</button>
          <a href="pickup_requests.php" class="btn btn-default">Kembali</a>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include_once('layouts/footer.php'); ?>
