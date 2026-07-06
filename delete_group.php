<?php
  require_once('includes/load.php');
  page_require_level(1);
  $result = delete_role(isset($_GET['id']) ? (int)$_GET['id'] : 0);
  $messages = array(
    'ok'        => array('s','Role berhasil dihapus.'),
    'protected' => array('d','Role Admin dan Pelanggan tidak dapat dihapus.'),
    'inuse'     => array('d','Role masih dipakai user.'),
    'notfound'  => array('d','Role tidak ditemukan.'),
    'fail'      => array('d','Gagal menghapus role.')
  );
  $message = isset($messages[$result]) ? $messages[$result] : array('d','Gagal menghapus role.');
  $session->msg($message[0], $message[1]);
  redirect('access_control.php', false);
?>
