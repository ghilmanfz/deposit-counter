<?php
  require_once('includes/load.php');
  require_permission('satuan','delete');
  if($_SERVER['REQUEST_METHOD'] !== 'POST' || !warehouse_csrf_is_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')){
    $session->msg('d','Permintaan hapus satuan tidak valid atau sesi sudah kedaluwarsa.');
    redirect('units.php', false);
  }
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  if(delete_unit_safe($id)){
    $session->msg('s','Satuan berhasil dihapus.');
  } else {
    $session->msg('d','Satuan gagal dihapus karena masih dipakai barang atau tidak ditemukan.');
  }
  redirect('units.php', false);
?>
