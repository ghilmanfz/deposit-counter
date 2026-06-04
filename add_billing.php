<?php
  $page_title = 'Tambah Tagihan';
  require_once('includes/load.php');
  page_require_level(2);
  ensure_consignment_tables();
  $clients = find_active_clients();
  $products = join_product_table();
  if(isset($_POST['add_billing'])){
    $req_fields = array('client_id','description','amount','issue_date','due_date','status');
    validate_fields($req_fields);
    if(empty($errors)){
      $billing_id = create_billing(array(
        'client_id' => (int)$_POST['client_id'],
        'product_id' => !empty($_POST['product_id']) ? (int)$_POST['product_id'] : 0,
        'description' => $_POST['description'],
        'amount' => (float)$_POST['amount'],
        'issue_date' => $_POST['issue_date'],
        'due_date' => $_POST['due_date'],
        'status' => $_POST['status'],
        'note' => isset($_POST['note']) ? $_POST['note'] : ''
      ));
      $session->msg($billing_id ? 's' : 'd', $billing_id ? 'Tagihan berhasil ditambahkan.' : 'Tagihan gagal ditambahkan.');
      redirect('billings.php', false);
    } else {
      $session->msg('d', $errors);
      redirect('add_billing.php', false);
    }
  }
  $msg = $session->msg();
?>
<?php include_once('layouts/header.php'); ?>
<div class="row"><div class="col-md-12"><?php echo display_msg($msg); ?></div></div>
<div class="row">
  <div class="col-md-8">
    <div class="panel panel-default">
      <div class="panel-heading"><strong><span class="glyphicon glyphicon-list-alt"></span> Tambah Tagihan Manual</strong></div>
      <div class="panel-body">
        <form method="post" action="add_billing.php">
          <div class="form-group"><label>Client</label><select class="form-control" name="client_id"><option value="">Pilih Client</option><?php foreach($clients as $client): ?><option value="<?php echo (int)$client['id']; ?>"><?php echo remove_junk($client['name']); ?></option><?php endforeach; ?></select></div>
          <div class="form-group"><label>Barang (opsional)</label><select class="form-control" name="product_id"><option value="">Tanpa barang tertentu</option><?php foreach($products as $product): ?><option value="<?php echo (int)$product['id']; ?>"><?php echo remove_junk($product['name']); ?> - <?php echo !empty($product['client_name']) ? remove_junk($product['client_name']) : 'Internal'; ?></option><?php endforeach; ?></select></div>
          <div class="form-group"><label>Deskripsi</label><input type="text" class="form-control" name="description" placeholder="Deskripsi tagihan"></div>
          <div class="row"><div class="col-md-6"><div class="form-group"><label>Nominal</label><input type="number" min="0" step="1000" class="form-control" name="amount" value="0"></div></div><div class="col-md-6"><div class="form-group"><label>Status</label><select class="form-control" name="status"><option value="belum_lunas">Belum Lunas</option><option value="lunas">Lunas</option></select></div></div></div>
          <div class="row"><div class="col-md-6"><div class="form-group"><label>Tanggal Tagihan</label><input type="date" class="form-control" name="issue_date" value="<?php echo date('Y-m-d'); ?>"></div></div><div class="col-md-6"><div class="form-group"><label>Jatuh Tempo</label><input type="date" class="form-control" name="due_date" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>"></div></div></div>
          <div class="form-group"><label>Catatan</label><textarea class="form-control" name="note" rows="3"></textarea></div>
          <button type="submit" name="add_billing" class="btn btn-primary">Simpan Tagihan</button>
          <a href="billings.php" class="btn btn-default">Kembali</a>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include_once('layouts/footer.php'); ?>
