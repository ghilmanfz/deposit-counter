<?php
  require_once('includes/load.php');
  page_require_level(3);
  ensure_consignment_tables();

  $billing_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  $billing = find_billing_details($billing_id);

  if(!$billing){
    $session->msg('d',' Tagihan tidak ditemukan.');
    redirect('billings.php', false);
  }

  if(update_billing_status($billing_id, 'lunas', date('Y-m-d'))){
    $session->msg('s',' Tagihan berhasil ditandai lunas.');
    redirect('billings.php', false);
  }

  $session->msg('d',' Status tagihan gagal diperbarui.');
  redirect('billings.php', false);
?>
