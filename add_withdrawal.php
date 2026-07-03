<?php
  $page_title = 'Tambah Pengambilan Barang';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
   page_require_level(3);
  $msg = $session->msg();
?>
<?php

  if(isset($_POST['add_sale'])){
    $req_fields = array('s_id','quantity','date','due_date','billing_amount' );
    validate_fields($req_fields);
        if(empty($errors)){
          $p_id      = $db->escape((int)$_POST['s_id']);
          $s_qty     = $db->escape((int)$_POST['quantity']);
          $s_total   = '0.00';
          $date      = $db->escape($_POST['date']);
          $transaction_date = date("Y-m-d", strtotime($date));
          $due_date = date("Y-m-d", strtotime($_POST['due_date']));
          $billing_amount = (float)$db->escape($_POST['billing_amount']);
          $driver_name = $db->escape($_POST['driver_name']);
          $vehicle_no = $db->escape($_POST['vehicle_no']);
          $movement_date = $transaction_date.' '.date('H:i:s');
          $product = find_product_details($p_id);

          if(!$product){
            $session->msg('d',' Barang titipan tidak ditemukan.');
            redirect('add_withdrawal.php', false);
          }

          if((int)$s_qty <= 0){
            $session->msg('d',' Jumlah pengambilan harus lebih dari nol.');
            redirect('add_withdrawal.php', false);
          }

          if((int)$s_qty > (int)$product['quantity']){
            $session->msg('d',' Jumlah pengambilan melebihi stok tersedia.');
            redirect('add_withdrawal.php', false);
          }

          if($billing_amount < 0){
            $session->msg('d',' Biaya penagihan tidak boleh minus.');
            redirect('add_withdrawal.php', false);
          }

          $sql  = "INSERT INTO withdrawals (";
          $sql .= " product_id,qty,price,date";
          $sql .= ") VALUES (";
          $sql .= "'{$p_id}','{$s_qty}','{$s_total}','{$transaction_date}'";
          $sql .= ")";

                if($db->query($sql)){
                  $sale_id = $db->insert_id();
                  $stock_change = update_product_qty($s_qty,$p_id);

                  if(!$stock_change){
                    delete_by_id('withdrawals', $sale_id);
                    $session->msg('d',' Gagal memperbarui stok.');
                    redirect('add_withdrawal.php', false);
                  }

                  $movement_id = record_stock_movement($p_id, 'out', (int)$s_qty, $stock_change['before'], $stock_change['after'], array(
                    'client_id' => (int)$product['client_id'],
                    'reference_type' => 'pengambilan',
                    'reference_id' => $sale_id,
                    'note' => 'Barang diambil dari gudang',
                    'created_at' => $movement_date
                  ));

                  if(!$movement_id){
                    increase_product_qty($s_qty, $p_id);
                    delete_by_id('withdrawals', $sale_id);
                    $session->msg('d',' Gagal menyimpan riwayat stok.');
                    redirect('add_withdrawal.php', false);
                  }

                  $pcs_per_crate = isset($product['pcs_per_crate']) ? (int)$product['pcs_per_crate'] : 0;
                  $crates_taken = $pcs_per_crate > 0 ? (int)round((int)$s_qty / $pcs_per_crate) : 1;
                  if($crates_taken < 1){ $crates_taken = 1; }
                  $storage = calculate_storage_fee($product['date'], $transaction_date, $crates_taken, (int)$product['client_id']);
                  $billing_amount = $storage['fee'];
                  $billing_desc = 'Biaya penyimpanan '.remove_junk($product['name']).' ('.$storage['days'].' hari)';
                  $billing_note = 'Tagihan penyimpanan prorata harian: '.format_rupiah($storage['rate']).' per satuan/bulan, lama titip '.$storage['days'].' hari.';

                  $billing_id = create_billing(array(
                    'client_id' => (int)$product['client_id'],
                    'product_id' => $p_id,
                    'reference_type' => 'pengambilan',
                    'reference_id' => $sale_id,
                    'description' => $billing_desc,
                    'amount' => $billing_amount,
                    'issue_date' => $transaction_date,
                    'due_date' => $due_date,
                    'note' => $billing_note
                  ));

                  if(!$billing_id){
                    delete_by_id('stock_movements', (int)$movement_id);
                    increase_product_qty($s_qty, $p_id);
                    delete_by_id('withdrawals', $sale_id);
                    $session->msg('d',' Gagal membuat penagihan otomatis.');
                    redirect('add_withdrawal.php', false);
                  }

                  $delivery_id = create_delivery_order(array(
                    'movement_type' => 'out',
                    'client_id' => (int)$product['client_id'],
                    'product_id' => $p_id,
                    'quantity' => (int)$s_qty,
                    'document_date' => $transaction_date,
                    'recipient' => !empty($product['client_name']) ? $product['client_name'] : '',
                    'driver_name' => $driver_name,
                    'vehicle_no' => $vehicle_no,
                    'reference_type' => 'pengambilan',
                    'reference_id' => $sale_id,
                    'note' => 'Surat jalan otomatis untuk pengambilan barang.'
                  ));

                  if(!$delivery_id){
                    delete_by_id('billings', (int)$billing_id);
                    delete_by_id('stock_movements', (int)$movement_id);
                    increase_product_qty($s_qty, $p_id);
                    delete_by_id('withdrawals', $sale_id);
                    $session->msg('d',' Gagal membuat surat jalan otomatis.');
                    redirect('add_withdrawal.php', false);
                  }

                  $session->msg('s',"Pengambilan barang berhasil disimpan. ");
                  redirect('add_withdrawal.php', false);
                } else {
                  $session->msg('d',' Maaf, pengambilan barang gagal disimpan.');
                  redirect('add_withdrawal.php', false);
                }
        } else {
           $session->msg("d", $errors);
           redirect('add_withdrawal.php',false);
        }
  }

?>
<?php include_once('layouts/header.php'); ?>
<div class="row">
  <div class="col-md-6">
    <?php echo display_msg($msg); ?>
    <form method="post" action="ajax.php" autocomplete="off" id="sug-form">
        <div class="form-group">
          <div class="input-group">
            <span class="input-group-btn">
              <button type="submit" class="btn btn-primary">Cari</button>
            </span>
            <input type="text" id="sug_input" class="form-control" name="title"  placeholder="Cari barang titipan">
            <input type="hidden" id="selected_product_id" name="selected_product_id" value="">
         </div>
         <div id="result" class="list-group"></div>
        </div>
    </form>
  </div>
</div>
<div class="row">

  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading clearfix">
        <strong>
          <span class="glyphicon glyphicon-th"></span>
            <span>Tambah Pengambilan Barang</span>
       </strong>
      </div>
      <div class="panel-body">
        <form method="post" action="add_withdrawal.php">
         <table class="table table-bordered">
           <thead>
            <th> Barang Titipan </th>
            <th> Jumlah Diambil </th>
            <th> Tanggal</th>
            <th> Biaya Penagihan</th>
            <th> Jatuh Tempo Tagihan</th>
            <th> Supir </th>
            <th> Pelat Kendaraan </th>
            <th> Aksi</th>
           </thead>
             <tbody id="product_info" data-submit-label="Simpan Pengambilan"> </tbody>
         </table>
       </form>
      </div>
    </div>
  </div>

</div>

<?php include_once('layouts/footer.php'); ?>
