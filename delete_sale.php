<?php
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
  page_require_level(3);
?>
<?php
  $d_sale = find_by_id('sales',(int)$_GET['id']);
  if(!$d_sale){
    $session->msg("d","Missing sale id.");
    redirect('sales.php');
  }
?>
<?php
  $product = find_product_details($d_sale['product_id']);
  if(!$product){
    $session->msg("d","Missing related product.");
    redirect('sales.php');
  }

  $stock_change = increase_product_qty($d_sale['qty'], $d_sale['product_id']);
  if(!$stock_change){
    $session->msg("d","Failed to restore stock.");
    redirect('sales.php');
  }

  $movement_id = record_stock_movement($d_sale['product_id'], 'in', (int)$d_sale['qty'], $stock_change['before'], $stock_change['after'], array(
    'client_id' => (int)$product['client_id'],
    'reference_type' => 'sale_delete',
    'reference_id' => (int)$d_sale['id'],
    'note' => 'Sale deleted and stock restored'
  ));

  if(!$movement_id){
    update_product_qty($d_sale['qty'], $d_sale['product_id']);
    $session->msg("d","Failed to save stock history.");
    redirect('sales.php');
  }

  $delete_id = delete_by_id('sales',(int)$d_sale['id']);
  if($delete_id){
      $session->msg("s","sale deleted.");
      redirect('sales.php');
  } else {
      delete_by_id('stock_movements', (int)$movement_id);
      update_product_qty($d_sale['qty'], $d_sale['product_id']);
      $session->msg("d","sale deletion failed.");
      redirect('sales.php');
  }
?>
