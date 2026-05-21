<?php
  $page_title = 'Dashboard';
  require_once('includes/load.php');
  $msg = $session->msg();
  if (!$session->isUserLoggedIn(true)) { redirect('index.php', false);}
  if (is_client_user()) { redirect('client_dashboard.php', false); }
?>
<?php include_once('layouts/header.php'); ?>
<div class="row">
  <div class="col-md-12">
    <?php echo display_msg($msg); ?>
  </div>
 <div class="col-md-12">
    <div class="panel">
      <div class="jumbotron text-center">
         <h1>Selamat Datang <hr> Sistem Penitipan Barang</h1>
         <p>Silakan pilih menu yang tersedia sesuai hak akses akun.</p>
      </div>
    </div>
 </div>
</div>
<?php include_once('layouts/footer.php'); ?>
