<?php
  $page_title = 'Penagihan';
  require_once('includes/load.php');
  page_require_level(4);
  ensure_consignment_tables();

  $user = current_user();
  $client_view = is_client_user($user);
  $billings = find_all_billings($client_view ? (int)$user['id'] : null);
  $msg = $session->msg();
?>
<?php include_once('layouts/header.php'); ?>

<div class="row">
  <div class="col-md-12">
    <?php echo display_msg($msg); ?>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading clearfix">
        <strong>
          <span class="glyphicon glyphicon-list-alt"></span>
          <span><?php echo $client_view ? 'Tagihan Saya' : 'Daftar Penagihan'; ?></span>
        </strong>
        <?php if(!$client_view): ?>
        <div class="pull-right">
          <a href="add_billing.php" class="btn btn-primary">Tambah Tagihan</a>
        </div>
        <?php endif; ?>
      </div>
      <div class="panel-body">
        <p class="text-muted">Status tagihan: Belum Lunas, Jatuh Tempo, atau Lunas.</p>
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th class="text-center" style="width: 50px;">#</th>
              <th>No Invoice</th>
              <?php if(!$client_view): ?>
              <th>Pelanggan</th>
              <?php endif; ?>
              <th>Barang</th>
              <th>Deskripsi</th>
              <th class="text-center">Nominal</th>
              <th class="text-center">Tanggal</th>
              <th class="text-center">Jatuh Tempo</th>
              <th class="text-center">Status</th>
              <th class="text-center" style="width: 130px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($billings as $billing): ?>
            <?php
              $effective_status = $billing['status'];
              if($effective_status !== 'lunas' && strtotime($billing['due_date']) < strtotime(date('Y-m-d'))){
                $effective_status = 'jatuh_tempo';
              }
              $label_class = $effective_status === 'lunas' ? 'success' : ($effective_status === 'jatuh_tempo' ? 'danger' : 'warning');
            ?>
            <tr>
              <td class="text-center"><?php echo count_id(); ?></td>
              <td><?php echo remove_junk($billing['invoice_no']); ?></td>
              <?php if(!$client_view): ?>
              <td><?php echo !empty($billing['client_name']) ? remove_junk($billing['client_name']) : '-'; ?></td>
              <?php endif; ?>
              <td><?php echo !empty($billing['product_name']) ? remove_junk($billing['product_name']) : '-'; ?></td>
              <td><?php echo remove_junk($billing['description']); ?></td>
              <td class="text-center"><?php echo format_rupiah($billing['amount']); ?></td>
              <td class="text-center"><?php echo remove_junk($billing['issue_date']); ?></td>
              <td class="text-center"><?php echo remove_junk($billing['due_date']); ?></td>
              <td class="text-center">
                <span class="label label-<?php echo $label_class; ?>"><?php echo billing_status_label($effective_status); ?></span>
              </td>
              <td class="text-center">
                <div class="btn-group">
                  <a href="print_invoice.php?id=<?php echo (int)$billing['id']; ?>" class="btn btn-info btn-xs" title="Cetak Invoice" data-toggle="tooltip">
                    <span class="glyphicon glyphicon-print"></span>
                  </a>
                  <?php if(!$client_view && $billing['status'] !== 'lunas'): ?>
                  <a href="mark_billing_paid.php?id=<?php echo (int)$billing['id']; ?>" class="btn btn-success btn-xs" title="Tandai Lunas" data-toggle="tooltip">
                    <span class="glyphicon glyphicon-ok"></span>
                  </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($billings)): ?>
            <tr>
              <td colspan="<?php echo $client_view ? 9 : 10; ?>" class="text-center">Belum ada data penagihan.</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include_once('layouts/footer.php'); ?>
