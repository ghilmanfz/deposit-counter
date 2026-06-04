<?php
  require_once('includes/load.php');
  page_require_level(2);
  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  if(delete_unit_safe($id)){
    $session->msg('s','Satuan berhasil dihapus.');
  } else {
    $session->msg('d','Satuan gagal dihapus karena masih dipakai barang atau tidak ditemukan.');
  }
  redirect('units.php', false);
?>
