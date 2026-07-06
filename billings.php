<?php
  $page_title = 'Penagihan';
  require_once('includes/load.php');
  require_permission('penagihan','view');
  ensure_consignment_tables();

  $user = current_user();
  $client_view = is_client_user($user);

  if(!$client_view && isset($_POST['save_storage_rate'])){
    require_permission('penagihan','process');
    $rate = (float)$_POST['storage_rate'];
    if($rate < 0){ $rate = 0; }
    set_setting('storage_rate_per_crate_month', $rate);
    $session->msg('s','Tarif penyimpanan global diperbarui.');
    redirect('billings.php', false);
  }

  $billings = find_all_billings($client_view ? (int)$user['id'] : null);
  $msg = $session->msg();
?>
<?php include_once('layouts/header.php'); ?>

<div class="row">
  <div class="col-md-12">
    <?php echo display_msg($msg); ?>
  </div>
</div>

<?php if(!$client_view && role_can_action('penagihan','process')): ?>
<div class="row">
  <div class="col-md-12">
    <div class="panel panel-info">
      <div class="panel-heading"><strong><span class="glyphicon glyphicon-cog"></span> Tarif Penyimpanan Global</strong></div>
      <div class="panel-body">
        <form method="post" action="billings.php" class="form-inline">
          <div class="form-group">
            <label>Tarif per Satuan (krat/palet) / Bulan</label>
            <div class="input-group">
              <span class="input-group-addon">Rp</span>
              <input type="number" min="0" step="1000" class="form-control" name="storage_rate" value="<?php echo (int)storage_rate_global(); ?>">
            </div>
          </div>
          <button type="submit" name="save_storage_rate" class="btn btn-info">Simpan Tarif</button>
          <span class="text-muted" style="margin-left:10px;">Dihitung prorata harian: tarif &divide; 30 &times; jumlah hari titip &times; jumlah satuan (krat/palet). Override per client di menu Edit User.</span>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading clearfix">
        <strong>
          <span class="glyphicon glyphicon-list-alt"></span>
          <span><?php echo $client_view ? 'Tagihan Saya' : 'Daftar Penagihan'; ?></span>
        </strong>
        <?php if(!$client_view && role_can_action('penagihan','create')): ?>
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
                  <?php if(role_can_action('penagihan','print')): ?>
                    <a href="print_invoice.php?id=<?php echo (int)$billing['id']; ?>" class="btn btn-info btn-xs" title="Cetak Invoice" data-toggle="tooltip">
                      <span class="glyphicon glyphicon-print"></span>
                    </a>
                  <?php endif; ?>
                  <?php if(!$client_view && role_can_action('penagihan','update')): ?>
                    <a href="edit_billing.php?id=<?php echo (int)$billing['id']; ?>" class="btn btn-warning btn-xs" title="Edit Tagihan" data-toggle="tooltip">
                      <span class="glyphicon glyphicon-edit"></span>
                    </a>
                  <?php endif; ?>
                  <?php if(!$client_view && $billing['status'] !== 'lunas' && role_can_action('penagihan','process')): ?>
                    <a href="mark_billing_paid.php?id=<?php echo (int)$billing['id']; ?>" class="btn btn-success btn-xs" title="Tandai Lunas" data-toggle="tooltip">
                      <span class="glyphicon glyphicon-ok"></span>
                    </a>
                  <?php endif; ?>
                  <?php if(!$client_view && role_can_action('penagihan','delete')): ?>
                    <a href="delete_billing.php?id=<?php echo (int)$billing['id']; ?>" class="btn btn-danger btn-xs" title="Hapus Tagihan" data-toggle="tooltip">
                      <span class="glyphicon glyphicon-trash"></span>
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
