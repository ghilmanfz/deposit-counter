<?php
  $page_title = 'Tolak Request Pengambilan';
  require_once('includes/load.php');
  page_require_level(2);
  $request = find_pickup_request_details(isset($_GET['id']) ? (int)$_GET['id'] : 0);
  if(!$request || $request['status'] !== 'pending'){
    $session->msg('d','Request tidak ditemukan atau sudah diproses.');
    redirect('pickup_requests.php', false);
  }
  if(isset($_POST['reject_request'])){
    $req_fields = array('reason');
    validate_fields($req_fields);
    if(empty($errors) && reject_pickup_request((int)$request['id'], $_POST['reason'])){
      $session->msg('s','Request pengambilan berhasil ditolak.');
      redirect('pickup_requests.php', false);
    }
    $session->msg('d', empty($errors) ? 'Request gagal ditolak.' : $errors);
    redirect('reject_pickup_request.php?id='.(int)$request['id'], false);
  }
  $msg = $session->msg();
?>
<?php include_once('layouts/header.php'); ?>
<div class="row"><div class="col-md-12"><?php echo display_msg($msg); ?></div></div>
<div class="row">
  <div class="col-md-6">
    <div class="panel panel-default">
      <div class="panel-heading"><strong><span class="glyphicon glyphicon-remove"></span> Tolak Request Pengambilan</strong></div>
      <div class="panel-body">
        <p><strong>No Request:</strong> <?php echo remove_junk($request['request_no']); ?></p>
        <p><strong>Client:</strong> <?php echo remove_junk($request['client_name']); ?></p>
        <p><strong>Barang:</strong> <?php echo remove_junk($request['product_name']); ?> | <strong>Jumlah:</strong> <?php echo (int)$request['quantity']; ?> <?php echo !empty($request['unit_name']) ? remove_junk($request['unit_name']) : ''; ?></p>
        <form method="post" action="reject_pickup_request.php?id=<?php echo (int)$request['id']; ?>">
          <div class="form-group"><label>Alasan Penolakan</label><textarea class="form-control" name="reason" rows="4" placeholder="Wajib isi alasan penolakan"></textarea></div>
          <button type="submit" name="reject_request" class="btn btn-danger">Tolak Request</button>
          <a href="pickup_requests.php" class="btn btn-default">Kembali</a>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include_once('layouts/footer.php'); ?>
