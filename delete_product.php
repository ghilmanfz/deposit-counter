<?php
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
  require_permission('barang','delete');

  if($_SERVER['REQUEST_METHOD'] !== 'POST' || !warehouse_csrf_is_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')){
    $session->msg('d','Permintaan hapus barang tidak valid. Silakan gunakan tombol hapus dari daftar barang.');
    redirect('product.php', false);
  }
?>
<?php
  $product = find_by_id('products',isset($_POST['id']) ? (int)$_POST['id'] : 0);
  if(!$product){
    $session->msg("d","Barang titipan tidak ditemukan.");
    redirect('product.php');
  }
?>
<?php
  $p_id = (int)$product['id'];

  $bundle_links = find_by_sql("SELECT id FROM inventory_bundles WHERE product_id='{$p_id}' LIMIT 1");
  $request_item_links = find_by_sql("SELECT id FROM pickup_request_items WHERE product_id='{$p_id}' LIMIT 1");
  $delivery_item_links = find_by_sql("SELECT id FROM delivery_order_items WHERE product_id='{$p_id}' LIMIT 1");
  $request_links = find_by_sql("SELECT id FROM pickup_requests WHERE product_id='{$p_id}' LIMIT 1");
  $delivery_links = find_by_sql("SELECT id FROM delivery_orders WHERE product_id='{$p_id}' LIMIT 1");
  $movement_links = find_by_sql("SELECT id FROM stock_movements WHERE product_id='{$p_id}' LIMIT 1");
  $withdrawal_links = find_by_sql("SELECT id FROM withdrawals WHERE product_id='{$p_id}' LIMIT 1");
  $defect_links = find_by_sql("SELECT id FROM product_defects WHERE product_id='{$p_id}' LIMIT 1");
  $billing_links = find_by_sql("SELECT id FROM billings WHERE product_id='{$p_id}' LIMIT 1");
  if(!empty($bundle_links) || !empty($request_item_links) || !empty($delivery_item_links) || !empty($request_links) || !empty($delivery_links) || !empty($movement_links) || !empty($withdrawal_links) || !empty($defect_links) || !empty($billing_links)){
    $session->msg('d','Barang tidak dapat dihapus karena sudah memiliki stok atau histori transaksi. Data dipertahankan agar audit stok, request, dan Surat Jalan tidak rusak.');
    redirect('product.php', false);
  }
  
  // Hapus semua data terkait untuk menghindari yatim (orphan records)
  $defect_ids = find_by_sql("SELECT id FROM product_defects WHERE product_id='{$p_id}'");
  foreach($defect_ids as $defect){
    $photos = find_defect_photos((int)$defect['id']);
    foreach($photos as $photo){
      $file = SITE_ROOT.DS.'..'.DS.'uploads'.DS.'defects'.DS.$photo['file_name'];
      if(is_file($file)){ @unlink($file); }
    }
    $db->query("DELETE FROM product_defect_photos WHERE defect_id='".(int)$defect['id']."'");
  }
  $db->query("DELETE FROM product_defects WHERE product_id='{$p_id}'");
  $db->query("DELETE FROM pickup_requests WHERE product_id='{$p_id}'");
  // Stock movements
  $db->query("DELETE FROM stock_movements WHERE product_id='{$p_id}'");
  
  // Ambil ID sales (pengambilan) terkait
  $sales_result = $db->query("SELECT id FROM withdrawals WHERE product_id='{$p_id}'");
  while($sale = $db->fetch_assoc($sales_result)){
    $s_id = (int)$sale['id'];
    // Hapus billings terkait sales (catatan: add_sale bikin desc dengan id produk, tapi kita bisa hapus yg nyebut id barang jg kl mau, tp yg plg aman dari desc 'Penagihan pengambilan barang' dll. Di sini billings/delivery_order tidak dihubungkan lgsg by sales_id, jadi kita bisa hapus berdasar reference)
    $db->query("DELETE FROM stock_movements WHERE reference_type IN ('pengambilan', 'hapus_pengambilan', 'penyesuaian_pengambilan') AND reference_id='{$s_id}'");
  }
  
  // Hapus sales
  $db->query("DELETE FROM withdrawals WHERE product_id='{$p_id}'");
  
  // Hapus delivery orders yg berhubungan dg product ini
  $db->query("DELETE FROM delivery_orders WHERE product_id='{$p_id}'");

  // Delete product itself
  $delete_id = delete_by_id('products', $p_id);
  if($delete_id){
      $session->msg("s","Barang titipan berhasil dihapus.");
      redirect('product.php');
  } else {
      $session->msg("d","Barang titipan gagal dihapus.");
      redirect('product.php');
  }
?>
