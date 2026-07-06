<?php
  $page_title = 'Edit Tagihan';
  require_once('includes/load.php');
  require_permission('penagihan','update');
  ensure_consignment_tables();
  $billing = find_billing_details(isset($_GET['id']) ? (int)$_GET['id'] : 0);
  if(!$billing){
    $session->msg('d','Tagihan tidak ditemukan.');
    redirect('billings.php', false);
  }
  $clients = find_active_clients();
  $products = join_product_table();
  if(isset($_POST['update_billing'])){
    $req_fields = array('client_id','description','amount','issue_date','due_date','status');
    validate_fields($req_fields);
    if(empty($errors)){
      $client_id = (int)$_POST['client_id'];
      $product_id = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
      $product_value = $product_id > 0 ? "'".$db->escape($product_id)."'" : "NULL";
      $note_value = !empty($_POST['note']) ? "'".$db->escape($_POST['note'])."'" : "NULL";
      $paid_value = $_POST['status'] === 'lunas' ? "'".date('Y-m-d')."'" : "NULL";
      $sql  = "UPDATE billings SET client_id='".$db->escape($client_id)."',product_id={$product_value},description='".$db->escape($_POST['description'])."',";
      $sql .= "amount='".$db->escape(number_format((float)$_POST['amount'],2,'.',''))."',issue_date='".$db->escape($_POST['issue_date'])."',due_date='".$db->escape($_POST['due_date'])."',";
      $sql .= "status='".$db->escape($_POST['status'])."',paid_date={$paid_value},note={$note_value} WHERE id='".(int)$billing['id']."' LIMIT 1";
      if($db->query($sql)){
        $session->msg('s','Tagihan berhasil diperbarui.');
        redirect('billings.php', false);
      }
      $session->msg('d','Tagihan gagal diperbarui.');
      redirect('edit_billing.php?id='.(int)$billing['id'], false);
    } else {
      $session->msg('d', $errors);
      redirect('edit_billing.php?id='.(int)$billing['id'], false);
    }
  }
  $msg = $session->msg();
?>
<?php include_once('layouts/header.php'); ?>
<div class="row"><div class="col-md-12"><?php echo display_msg($msg); ?></div></div>
<div class="row">
  <div class="col-md-8">
    <div class="panel panel-default">
      <div class="panel-heading"><strong><span class="glyphicon glyphicon-edit"></span> Edit Tagihan</strong></div>
      <div class="panel-body">
        <form method="post" action="edit_billing.php?id=<?php echo (int)$billing['id']; ?>">
          <div class="form-group"><label>Client</label><select class="form-control" name="client_id"><option value="">Pilih Client</option><?php foreach($clients as $client): ?><option value="<?php echo (int)$client['id']; ?>" <?php if((int)$billing['client_id'] === (int)$client['id']) echo 'selected'; ?>><?php echo remove_junk($client['name']); ?></option><?php endforeach; ?></select></div>
          <div class="form-group"><label>Barang (opsional)</label><select class="form-control" name="product_id"><option value="">Tanpa barang tertentu</option><?php foreach($products as $product): ?><option value="<?php echo (int)$product['id']; ?>" <?php if((int)$billing['product_id'] === (int)$product['id']) echo 'selected'; ?>><?php echo remove_junk($product['name']); ?> - <?php echo !empty($product['client_name']) ? remove_junk($product['client_name']) : 'Internal'; ?></option><?php endforeach; ?></select></div>
          <div class="form-group"><label>Deskripsi</label><input type="text" class="form-control" name="description" value="<?php echo remove_junk($billing['description']); ?>"></div>
          <div class="row"><div class="col-md-6"><div class="form-group"><label>Nominal</label><input type="number" min="0" step="1000" class="form-control" name="amount" value="<?php echo (float)$billing['amount']; ?>"></div></div><div class="col-md-6"><div class="form-group"><label>Status</label><select class="form-control" name="status"><option value="belum_lunas" <?php if($billing['status']==='belum_lunas') echo 'selected'; ?>>Belum Lunas</option><option value="lunas" <?php if($billing['status']==='lunas') echo 'selected'; ?>>Lunas</option></select></div></div></div>
          <div class="row"><div class="col-md-6"><div class="form-group"><label>Tanggal Tagihan</label><input type="date" class="form-control" name="issue_date" value="<?php echo remove_junk($billing['issue_date']); ?>"></div></div><div class="col-md-6"><div class="form-group"><label>Jatuh Tempo</label><input type="date" class="form-control" name="due_date" value="<?php echo remove_junk($billing['due_date']); ?>"></div></div></div>
          <div class="form-group"><label>Catatan</label><textarea class="form-control" name="note" rows="3"><?php echo !empty($billing['note']) ? remove_junk($billing['note']) : ''; ?></textarea></div>
          <button type="submit" name="update_billing" class="btn btn-primary">Update Tagihan</button>
          <a href="billings.php" class="btn btn-default">Kembali</a>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include_once('layouts/footer.php'); ?>
