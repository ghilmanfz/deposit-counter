<?php
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
   page_require_level(1);
   ensure_consignment_tables();
   if($_SERVER['REQUEST_METHOD'] !== 'POST' || !warehouse_csrf_is_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')){
     $session->msg('d','Permintaan hapus user tidak valid atau sesi sudah kedaluwarsa.');
     redirect('users.php', false);
   }
?>
<?php
  $user_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $target_user = find_by_id('users', $user_id);
  if(!$target_user){
    $session->msg('d','User tidak ditemukan.');
    redirect('users.php', false);
  }
  $actor = current_user();
  if($actor && (int)$actor['id'] === $user_id){
    $session->msg('d','Akun yang sedang digunakan tidak dapat dihapus.');
    redirect('users.php', false);
  }

  if(user_has_inventory_ownership_history($user_id)){
    $session->msg('d','User tidak dapat dihapus karena masih memiliki barang atau histori transaksi, terlepas dari role saat ini. Nonaktifkan akun agar kepemilikan stok tetap utuh.');
    redirect('users.php', false);
  }

  $delete_id = delete_by_id('users',$user_id);
  if($delete_id){
      $session->msg("s","User berhasil dihapus.");
      redirect('users.php');
  } else {
      $session->msg("d","Gagal menghapus user.");
      redirect('users.php');
  }
?>
