<?php
  require_once('includes/load.php');
  page_require_level(1);
  $session->msg('d','Ubah role sekarang dilakukan lewat Kelola Hak Akses.');
  redirect('access_control.php', false);
?>
