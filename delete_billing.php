<?php
  require_once('includes/load.php');
  page_require_level(2);
  ensure_consignment_tables();
  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  if(delete_by_id('billings', $id)){
    $session->msg('s','Tagihan berhasil dihapus.');
  } else {
    $session->msg('d','Tagihan gagal dihapus atau tidak ditemukan.');
  }
  redirect('billings.php', false);
?>
