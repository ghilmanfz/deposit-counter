<?php
  require_once('includes/load.php');
  require_permission('pickup','create');

  if(!is_client_user()){
    redirect_by_user_level();
  }

  if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    $session->msg('d','Pembatalan request hanya dapat dilakukan melalui formulir yang sah.');
    redirect('pickup_requests.php', false);
  }

  $csrf_valid = warehouse_csrf_is_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '');
  if(!$csrf_valid){
    $session->msg('d','Sesi aksi tidak valid atau sudah kedaluwarsa. Silakan coba kembali.');
    redirect('pickup_requests.php', false);
  }

  $request_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $user = current_user();
  $client_id = (int)$user['id'];

  if($request_id <= 0 || !function_exists('cancel_pickup_request')){
    $session->msg('d','Request tidak valid atau layanan pembatalan belum tersedia.');
    redirect('pickup_requests.php', false);
  }

  $request = find_pickup_request_details($request_id, $client_id);
  if(!$request || $request['status'] !== 'pending'){
    $session->msg('d','Request hanya dapat dibatalkan oleh pemilik saat statusnya masih menunggu.');
    redirect('pickup_requests.php', false);
  }

  if(cancel_pickup_request($request_id, $client_id)){
    $session->msg('s','Request berhasil dibatalkan dan seluruh bundle kembali tersedia.');
  } else {
    $session->msg('d','Request gagal dibatalkan. Pastikan request milik Anda dan statusnya masih menunggu.');
  }

  redirect('pickup_requests.php', false);
?>
