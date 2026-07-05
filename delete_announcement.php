<?php
  require_once('includes/load.php');
  page_require_level(1);
  $id = (int)(isset($_GET['id']) ? $_GET['id'] : 0);
  $a = find_announcement_by_id($id);
  if(!$a){
    $session->msg('d', 'Pengumuman tidak ditemukan.');
    redirect('announcements.php', false);
  }
  if(delete_by_id('announcements', $id)){
    $session->msg('s', 'Pengumuman berhasil dihapus.');
  } else {
    $session->msg('d', 'Gagal menghapus pengumuman.');
  }
  redirect('announcements.php', false);
?>
