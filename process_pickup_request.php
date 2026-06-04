<?php
  require_once('includes/load.php');
  page_require_level(2);
  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  $action = isset($_GET['action']) ? $_GET['action'] : '';
  if($action === 'approve' && approve_pickup_request($id)){
    $session->msg('s','Request pengambilan berhasil disetujui. Surat Jalan sudah dibuat, stok belum dipotong sampai Surat Jalan dicetak.');
  } else {
    $session->msg('d','Request gagal diproses. Pastikan status masih menunggu dan stok tersedia.');
  }
  redirect('pickup_requests.php', false);
?>
