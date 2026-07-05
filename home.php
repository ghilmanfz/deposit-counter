<?php
  $page_title = 'Dashboard';
  require_once('includes/load.php');
  $msg = $session->msg();
  if (!$session->isUserLoggedIn(true)) { redirect('index.php', false); }
  if (is_client_user()) { redirect('client_dashboard.php', false); }

  $user = current_user();
  $name = isset($user['name']) ? remove_junk(ucfirst($user['name'])) : 'User';
  $lvl  = isset($user['user_level']) ? (int)$user['user_level'] : 0;
  $role_name = $lvl === 2 ? 'Special User' : ($lvl === 3 ? 'User Staff' : 'Staff');

  $c_product    = count_by_id('products');
  $c_withdrawal = count_by_id('withdrawals');
  $c_delivery   = count_by_id('delivery_orders');
  $c_billing    = count_by_id('billings');

  $recent_do = array_slice(find_all_delivery_orders(), 0, 5);
  $dashboard_announcements = find_all_announcements(true, 3);
?>
<?php include_once('layouts/header.php'); ?>

<div class="row">
  <div class="col-md-12"><?php echo display_msg($msg); ?></div>
</div>

<!-- Welcome Banner -->
<div class="welcome-banner">
  <h1>Selamat Datang, <?php echo $name; ?> 👋</h1>
  <p>Anda masuk sebagai <span><?php echo $role_name; ?></span>. Semoga harimu berkah.</p>
</div>

<!-- Stat Cards (hanya tampil untuk menu yang bisa diakses) -->
<div class="row">
  <?php if(menu_can(2)): ?>
  <div class="col-md-3 col-sm-6">
    <a href="product.php">
      <div class="stat-card-horizontal">
        <div class="stat-card-icon blue"><i class="glyphicon glyphicon-th-large"></i></div>
        <div class="stat-card-info"><h2><?php echo (int)$c_product['total']; ?></h2><p>Barang Titipan</p></div>
      </div>
    </a>
  </div>
  <?php endif; ?>
  <?php if(menu_can(3)): ?>
  <div class="col-md-3 col-sm-6">
    <a href="withdrawals.php">
      <div class="stat-card-horizontal">
        <div class="stat-card-icon green"><i class="glyphicon glyphicon-transfer"></i></div>
        <div class="stat-card-info"><h2><?php echo (int)$c_withdrawal['total']; ?></h2><p>Pengambilan</p></div>
      </div>
    </a>
  </div>
  <?php endif; ?>
  <div class="col-md-3 col-sm-6">
    <a href="delivery_orders.php">
      <div class="stat-card-horizontal">
        <div class="stat-card-icon yellow"><i class="glyphicon glyphicon-file"></i></div>
        <div class="stat-card-info"><h2><?php echo (int)$c_delivery['total']; ?></h2><p>Surat Jalan</p></div>
      </div>
    </a>
  </div>
  <div class="col-md-3 col-sm-6">
    <a href="billings.php">
      <div class="stat-card-horizontal">
        <div class="stat-card-icon purple"><i class="glyphicon glyphicon-list-alt"></i></div>
        <div class="stat-card-info"><h2><?php echo (int)$c_billing['total']; ?></h2><p>Penagihan</p></div>
      </div>
    </a>
  </div>
</div>

<!-- Aksi Cepat & Surat Jalan Terbaru -->
<div class="row">
  <div class="col-md-5">
    <div class="custom-panel">
      <div class="custom-panel-header">
        <h3 class="custom-panel-title"><i class="glyphicon glyphicon-flash"></i> Aksi Cepat</h3>
      </div>
      <div style="display:flex; flex-direction:column; gap:14px;">
        <?php if(menu_can(2)): ?>
        <a href="add_product.php" class="btn btn-primary btn-block" style="text-align:left;"><i class="glyphicon glyphicon-plus"></i> &nbsp;Tambah Barang Titipan</a>
        <?php endif; ?>
        <?php if(menu_can(3)): ?>
        <a href="add_withdrawal.php" class="btn btn-success btn-block" style="text-align:left;"><i class="glyphicon glyphicon-transfer"></i> &nbsp;Tambah Pengambilan Barang</a>
        <?php endif; ?>
        <a href="delivery_orders.php" class="btn btn-default btn-block" style="text-align:left;"><i class="glyphicon glyphicon-file"></i> &nbsp;Lihat Surat Jalan</a>
        <a href="billings.php" class="btn btn-default btn-block" style="text-align:left;"><i class="glyphicon glyphicon-list-alt"></i> &nbsp;Lihat Penagihan</a>
      </div>
    </div>
  </div>
  <div class="col-md-7">
    <div class="custom-panel">
      <div class="custom-panel-header">
        <h3 class="custom-panel-title"><i class="glyphicon glyphicon-time"></i> Surat Jalan Terbaru</h3>
        <a href="delivery_orders.php" class="custom-panel-link">Semua</a>
      </div>
      <?php if(empty($recent_do)): ?>
        <p class="text-muted">Belum ada surat jalan.</p>
      <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>No Surat Jalan</th>
            <th>Client</th>
            <th>Jenis</th>
            <th class="text-right">Tanggal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($recent_do as $do): ?>
          <tr>
            <td><?php echo remove_junk($do['document_no']); ?></td>
            <td><?php echo !empty($do['client_name']) ? remove_junk($do['client_name']) : 'Internal'; ?></td>
            <td><?php echo delivery_movement_label($do['movement_type']); ?></td>
            <td class="text-right"><?php echo remove_junk($do['document_date']); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Pengumuman -->
<div class="row">
  <div class="col-md-12">
    <div class="custom-panel">
      <div class="custom-panel-header">
        <h3 class="custom-panel-title"><i class="glyphicon glyphicon-bullhorn"></i> Pengumuman</h3>
      </div>
      <?php $__n = count($dashboard_announcements); foreach($dashboard_announcements as $__i => $da): ?>
      <div style="<?php echo ($__i < $__n - 1) ? 'border-bottom:1px solid #e2e8f0; padding-bottom:18px; margin-bottom:18px;' : ''; ?>">
        <h4 style="color:#0b1c3c; font-size:16px; font-weight:700; margin:0 0 6px;"><?php echo htmlspecialchars($da['title']); ?></h4>
        <p style="color:#64748b; font-size:14px; margin:0 0 8px; line-height:1.5;"><?php echo nl2br(htmlspecialchars($da['content'])); ?></p>
        <span style="color:#94a3b8; font-size:12px; font-weight:600;"><?php echo !empty($da['publish_date']) ? date('d M Y', strtotime($da['publish_date'])) : ''; ?></span>
      </div>
      <?php endforeach; ?>
      <?php if(empty($dashboard_announcements)): ?><p class="text-muted">Belum ada pengumuman.</p><?php endif; ?>
    </div>
  </div>
</div>

<?php include_once('layouts/footer.php'); ?>
