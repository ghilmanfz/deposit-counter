<?php
  $page_title = 'Edit Pengambilan Barang';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
   require_permission('transaksi','update');
  $msg = $session->msg();
?>
<?php
$sale = find_by_id('withdrawals',(int)$_GET['id']);
if(!$sale){
  $session->msg("d","Data pengambilan tidak ditemukan.");
  redirect('withdrawals.php');
}
?>
<?php $product = find_product_details($sale['product_id']); ?>
<?php
if($product && function_exists('product_has_bundle_details') && product_has_bundle_details((int)$product['id'])){
  $session->msg('d','Pengambilan lama tidak dapat diedit setelah stok produk dikelola per bundle. Buat Request Pengambilan baru untuk transaksi berikutnya.');
  redirect('withdrawals.php', false);
}
?>
<?php

  if(isset($_POST['update_sale'])){
    if(!warehouse_csrf_is_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')){
      $session->msg('d','Sesi formulir tidak valid atau sudah kedaluwarsa. Silakan coba kembali.');
      redirect('edit_withdrawal.php?id='.(int)$sale['id'], false);
    }
    $req_fields = array('title','quantity', 'date' );
    validate_fields($req_fields);
        if(empty($errors)){
          $p_id      = $db->escape((int)$product['id']);
          $s_qty     = $db->escape((int)$_POST['quantity']);
          $s_total   = '0.00';
          $date      = $db->escape($_POST['date']);
          $s_date    = date("Y-m-d", strtotime($date));
          $movement_date = $s_date.' '.date('H:i:s');
          $old_qty = (int)$sale['qty'];
          $new_qty = (int)$s_qty;
          $available_qty = (int)$product['quantity'] + $old_qty;
          $stock_change = false;
          $movement_type = '';
          $movement_qty = 0;
          $movement_note = '';

          if($new_qty <= 0){
            $session->msg('d',' Jumlah pengambilan harus lebih dari nol.');
            redirect('edit_withdrawal.php?id='.(int)$sale['id'],false);
          }

          if($new_qty > $available_qty){
            $session->msg('d',' Jumlah pengambilan melebihi stok tersedia.');
            redirect('edit_withdrawal.php?id='.(int)$sale['id'],false);
          }

          if($new_qty > $old_qty){
            $movement_qty = $new_qty - $old_qty;
            $stock_change = update_product_qty($movement_qty, $p_id);
            $movement_type = 'out';
            $movement_note = 'Jumlah pengambilan ditambah';
          } elseif($new_qty < $old_qty){
            $movement_qty = $old_qty - $new_qty;
            $stock_change = increase_product_qty($movement_qty, $p_id);
            $movement_type = 'in';
            $movement_note = 'Jumlah pengambilan dikurangi';
          }

          if($movement_qty > 0 && !$stock_change){
            $session->msg('d',' Gagal memperbarui stok.');
            redirect('edit_withdrawal.php?id='.(int)$sale['id'],false);
          }

          $sql  = "UPDATE withdrawals SET";
          $sql .= " product_id= '{$p_id}',qty={$s_qty},price='{$s_total}',date='{$s_date}'";
          $sql .= " WHERE id ='{$sale['id']}'";
          $result = $db->query($sql);
          if($result){
                    if($movement_qty > 0){
                      $movement_id = record_stock_movement($p_id, $movement_type, $movement_qty, $stock_change['before'], $stock_change['after'], array(
                        'client_id' => (int)$product['client_id'],
                        'reference_type' => 'penyesuaian_pengambilan',
                        'reference_id' => (int)$sale['id'],
                        'note' => $movement_note,
                        'created_at' => $movement_date
                      ));

                      if(!$movement_id){
                        if($movement_type === 'out'){
                          increase_product_qty($movement_qty, $p_id);
                        } else {
                          update_product_qty($movement_qty, $p_id);
                        }

                        $rollback  = "UPDATE withdrawals SET";
                        $rollback .= " product_id='".$db->escape((int)$sale['product_id'])."',qty='".$db->escape((int)$sale['qty'])."',";
                        $rollback .= "price='".$db->escape($sale['price'])."',date='".$db->escape($sale['date'])."'";
                        $rollback .= " WHERE id='".$db->escape((int)$sale['id'])."' LIMIT 1";
                        $db->query($rollback);
                        $session->msg('d',' Gagal menyimpan riwayat stok.');
                        redirect('edit_withdrawal.php?id='.(int)$sale['id'], false);
                      }
                    }
                    $session->msg('s',"Pengambilan barang berhasil diperbarui.");
                    redirect('edit_withdrawal.php?id='.$sale['id'], false);
                  } else {
                    if($movement_qty > 0){
                      if($movement_type === 'out'){
                        increase_product_qty($movement_qty, $p_id);
                      } else {
                        update_product_qty($movement_qty, $p_id);
                      }
                    }
                    $session->msg('d',' Maaf, pengambilan barang gagal diperbarui.');
                    redirect('withdrawals.php', false);
                  }
        } else {
           $session->msg("d", $errors);
           redirect('edit_withdrawal.php?id='.(int)$sale['id'],false);
        }
  }

?>
<?php include_once('layouts/header.php'); ?>
<div class="row">
  <div class="col-md-6">
    <?php echo display_msg($msg); ?>
  </div>
</div>
<div class="row">

  <div class="col-md-12">
  <div class="panel">
    <div class="panel-heading clearfix">
      <strong>
        <span class="glyphicon glyphicon-th"></span>
        <span>Edit Pengambilan Barang</span>
     </strong>
     <div class="pull-right">
       <a href="withdrawals.php" class="btn btn-primary">Lihat Semua Pengambilan</a>
     </div>
    </div>
    <div class="panel-body">
       <table class="table table-bordered">
         <thead>
          <th> Barang Titipan </th>
          <th> Jumlah </th>
          <th> Tanggal</th>
          <th> Aksi</th>
         </thead>
           <tbody  id="product_info">
              <tr>
              <form method="post" action="edit_withdrawal.php?id=<?php echo (int)$sale['id']; ?>">
                <?php echo warehouse_csrf_field(); ?>
                <td id="s_name">
                  <input type="text" class="form-control" id="sug_input" name="title" value="<?php echo remove_junk($product['name']); ?>" readonly>
                  <small class="help-block">
                    <?php echo !empty($product['client_name']) ? 'Client: '.remove_junk($product['client_name']).' | ' : ''; ?>Stok saat ini: <?php echo (int)$product['quantity']; ?>
                  </small>
                  <input type="hidden" name="price" value="0.00">
                  <input type="hidden" name="total" value="0.00">
                </td>
                <td id="s_qty">
                  <input type="text" class="form-control" name="quantity" value="<?php echo (int)$sale['qty']; ?>">
                </td>
                <td id="s_date">
                  <input type="date" class="form-control datepicker" name="date" data-date-format="" value="<?php echo remove_junk($sale['date']); ?>">
                </td>
                <td>
                  <button type="submit" name="update_sale" class="btn btn-primary">Update Pengambilan</button>
                </td>
              </form>
              </tr>
           </tbody>
       </table>

    </div>
  </div>
  </div>

</div>

<?php include_once('layouts/footer.php'); ?>
