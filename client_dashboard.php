<?php
  $page_title = 'Dashboard Klien';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
  page_require_level(4);
?>
<?php
 $user            = current_user();
 $user_id         = isset($user['id']) ? (int)$user['id'] : 0;
 $name            = isset($user['name']) ? remove_junk(ucfirst($user['name'])) : 'Client';

 // Custom counts for client
 $c_my_product    = count_by_client_id('products', $user_id);
 $c_my_withdrawal = count_by_client_id('withdrawals', $user_id);
 $c_my_billing    = count_by_client_id('billings', $user_id);
 $c_my_do         = count_by_client_id('delivery_orders', $user_id);
 $msg             = $session->msg();
 $dashboard_announcements = find_all_announcements(true, 3);
?>
<?php include_once('layouts/header.php'); ?>

<div class="row">
   <div class="col-md-12">
     <?php echo display_msg($msg); ?>
   </div>
</div>

<!-- Welcome Banner -->
<div class="welcome-banner">
  <h1>Selamat Datang, <?php echo $name; ?> 👋</h1>
  <p>Anda masuk sebagai <span>Klien Eksternal</span>. Semoga harimu berkah.</p>
</div>

<!-- Horizontal Stat Cards -->
<div class="row">
  <div class="col-md-3 col-sm-6">
    <a href="my_products.php">
      <div class="stat-card-horizontal">
        <div class="stat-card-icon blue">
          <i class="glyphicon glyphicon-th-large"></i>
        </div>
        <div class="stat-card-info">
          <h2><?php echo $c_my_product['total']; ?></h2>
          <p>Barang Saya</p>
        </div>
      </div>
    </a>
  </div>
  <div class="col-md-3 col-sm-6">
    <a href="stock_history.php">
      <div class="stat-card-horizontal">
        <div class="stat-card-icon green">
          <i class="glyphicon glyphicon-transfer"></i>
        </div>
        <div class="stat-card-info">
          <h2><?php echo $c_my_withdrawal['total']; ?></h2>
          <p>Pengambilan</p>
        </div>
      </div>
    </a>
  </div>
  <div class="col-md-3 col-sm-6">
    <a href="billings.php">
      <div class="stat-card-horizontal">
        <div class="stat-card-icon yellow">
          <i class="glyphicon glyphicon-list-alt"></i>
        </div>
        <div class="stat-card-info">
          <h2><?php echo $c_my_billing['total']; ?></h2>
          <p>Tagihan Aktif</p>
        </div>
      </div>
    </a>
  </div>
  <div class="col-md-3 col-sm-6">
    <a href="delivery_orders.php">
      <div class="stat-card-horizontal">
        <div class="stat-card-icon purple">
          <i class="glyphicon glyphicon-file"></i>
        </div>
        <div class="stat-card-info">
          <h2><?php echo $c_my_do['total']; ?></h2>
          <p>Surat Jalan</p>
        </div>
      </div>
    </a>
  </div>
</div>

<!-- Ringkasan Inventori & Papan Pengumuman -->
<div class="row">
  <div class="col-md-6">
    <div class="custom-panel">
      <div class="custom-panel-header">
        <h3 class="custom-panel-title"><i class="glyphicon glyphicon-tasks"></i> Ringkasan Inventori</h3>
      </div>
      <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:16px; padding:25px; margin-bottom:25px;">
        <h5 style="color:#64748b; font-size:12px; font-weight:700; margin:0 0 8px; text-transform:uppercase;">STATUS STOK TERKINI</h5>
        <p style="color:#0b1c3c; font-size:16px; font-weight:600; margin:0 0 15px;">Seluruh barang titipan Anda tersimpan aman di gudang utama.</p>
        <a href="pickup_requests.php" style="color:#10b981; font-weight:700; font-size:15px;">Ajukan request pengambilan barang &rarr;</a>
      </div>
      <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:16px; padding:25px;">
        <h5 style="color:#64748b; font-size:12px; font-weight:700; margin:0 0 8px; text-transform:uppercase;">INFORMASI TAGIHAN BULANAN</h5>
        <p style="color:#0b1c3c; font-size:18px; font-weight:800; margin:0 0 4px;">Pembayaran Terverifikasi</p>
        <p style="color:#64748b; font-size:13px; margin:0;">Tidak ada tagihan tertunggak bulan ini.</p>
      </div>
    </div>
  </div>
  
  <div class="col-md-6">
    <div class="custom-panel">
      <div class="custom-panel-header">
        <h3 class="custom-panel-title"><i class="glyphicon glyphicon-bullhorn"></i> Papan Pengumuman</h3>
        <a href="#" class="custom-panel-link">Semua</a>
      </div>
      <?php $__n = count($dashboard_announcements); foreach($dashboard_announcements as $__i => $da): ?>
      <div style="<?php echo ($__i < $__n - 1) ? 'border-bottom:1px solid #e2e8f0; padding-bottom:20px; margin-bottom:20px;' : ''; ?>">
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
