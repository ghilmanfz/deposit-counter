<?php
  require_once('includes/load.php');
  page_require_level(4);
  ensure_consignment_tables();

  $billing_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  $billing = find_billing_details($billing_id);

  if(!$billing){
    $session->msg('d',' Tagihan tidak ditemukan.');
    redirect('billings.php', false);
  }

  $effective_status = $billing['status'];
  if($effective_status !== 'lunas' && strtotime($billing['due_date']) < strtotime(date('Y-m-d'))){
    $effective_status = 'jatuh_tempo';
  }
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Invoice <?php echo remove_junk($billing['invoice_no']); ?></title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css"/>
  <style>
    body { padding: 32px; }
    .invoice-box { max-width: 900px; margin: 0 auto; }
    .invoice-title { border-bottom: 2px solid #222; margin-bottom: 24px; padding-bottom: 12px; }
    .table > tbody > tr > td { vertical-align: middle; }
    @media print { .no-print { display: none; } body { padding: 0; } }
  </style>
</head>
<body>
  <div class="invoice-box">
    <div class="invoice-title clearfix">
      <div class="pull-left">
        <h2>Invoice Penagihan</h2>
        <strong>Sistem Penitipan Barang</strong>
      </div>
      <div class="pull-right text-right">
        <h4><?php echo remove_junk($billing['invoice_no']); ?></h4>
        <p>Status: <?php echo billing_status_label($effective_status); ?></p>
      </div>
    </div>

    <div class="row">
      <div class="col-xs-6">
        <p><strong>Client</strong><br><?php echo !empty($billing['client_name']) ? remove_junk($billing['client_name']) : '-'; ?></p>
      </div>
      <div class="col-xs-6 text-right">
        <p><strong>Tanggal Invoice:</strong> <?php echo remove_junk($billing['issue_date']); ?></p>
        <p><strong>Jatuh Tempo:</strong> <?php echo remove_junk($billing['due_date']); ?></p>
        <?php if(!empty($billing['paid_date'])): ?>
        <p><strong>Tanggal Lunas:</strong> <?php echo remove_junk($billing['paid_date']); ?></p>
        <?php endif; ?>
      </div>
    </div>

    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Deskripsi</th>
          <th>Barang Titipan</th>
          <th class="text-right">Nominal</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><?php echo remove_junk($billing['description']); ?></td>
          <td><?php echo !empty($billing['product_name']) ? remove_junk($billing['product_name']) : '-'; ?></td>
          <td class="text-right"><?php echo format_rupiah($billing['amount']); ?></td>
        </tr>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="2" class="text-right"><strong>Total</strong></td>
          <td class="text-right"><strong><?php echo format_rupiah($billing['amount']); ?></strong></td>
        </tr>
      </tfoot>
    </table>

    <?php if(!empty($billing['note'])): ?>
    <p><strong>Catatan:</strong> <?php echo remove_junk($billing['note']); ?></p>
    <?php endif; ?>

    <div class="no-print">
      <button onclick="window.print()" class="btn btn-primary">Cetak Invoice</button>
      <a href="billings.php" class="btn btn-default">Kembali</a>
    </div>
  </div>
</body>
</html>
<?php if(isset($db)) { $db->db_disconnect(); } ?>
