<?php
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
  page_require_level(3);
?>
<?php
  $d_sale = find_by_id('withdrawals',(int)$_GET['id']);
  if(!$d_sale){
    $session->msg("d","Data pengambilan tidak ditemukan.");
    redirect('withdrawals.php');
  }
?>
<?php
  $product = find_product_details($d_sale['product_id']);
  if(!$product){
    $session->msg("d","Barang titipan terkait tidak ditemukan.");
    redirect('withdrawals.php');
  }

  $stock_change = increase_product_qty($d_sale['qty'], $d_sale['product_id']);
  if(!$stock_change){
    $session->msg("d","Gagal mengembalikan stok.");
    redirect('withdrawals.php');
  }

  $movement_id = record_stock_movement($d_sale['product_id'], 'in', (int)$d_sale['qty'], $stock_change['before'], $stock_change['after'], array(
    'client_id' => (int)$product['client_id'],
    'reference_type' => 'hapus_pengambilan',
    'reference_id' => (int)$d_sale['id'],
    'note' => 'Pengambilan dihapus dan stok dikembalikan'
  ));

  if(!$movement_id){
    update_product_qty($d_sale['qty'], $d_sale['product_id']);
    $session->msg("d","Gagal menyimpan riwayat stok.");
    redirect('withdrawals.php');
  }

  $delete_id = delete_by_id('withdrawals',(int)$d_sale['id']);
  if($delete_id){
      // Hapus billings terkait pengambilan ini
      $db->query("DELETE FROM billings WHERE reference_type='pengambilan' AND reference_id='".(int)$d_sale['id']."'");
      
      // Hapus delivery_orders terkait pengambilan ini
      $db->query("DELETE FROM delivery_orders WHERE reference_type='pengambilan' AND reference_id='".(int)$d_sale['id']."'");
      
      $session->msg("s","Pengambilan barang berhasil dihapus.");
      redirect('withdrawals.php');
  } else {
      delete_by_id('stock_movements', (int)$movement_id);
      update_product_qty($d_sale['qty'], $d_sale['product_id']);
      $session->msg("d","Pengambilan barang gagal dihapus.");
      redirect('withdrawals.php');
  }
?>
