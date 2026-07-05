<?php
  $page_title = 'Dashboard Admin';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
  page_require_level(1);
?>
<?php
 $c_categorie     = count_by_id('categories');
 $c_product       = count_by_id('products');
 $c_withdrawal    = count_by_id('withdrawals');
 $c_user          = count_by_id('users');
 $user            = current_user();
 $name            = isset($user['name']) ? remove_junk(ucfirst($user['name'])) : 'User';
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
  <p>Anda masuk sebagai <span>Super Admin</span>. Semoga harimu berkah.</p>
</div>

<!-- Horizontal Stat Cards -->
<div class="row">
  <div class="col-md-3 col-sm-6">
    <a href="users.php">
      <div class="stat-card-horizontal">
        <div class="stat-card-icon blue">
          <i class="glyphicon glyphicon-user"></i>
        </div>
        <div class="stat-card-info">
          <h2><?php echo $c_user['total']; ?></h2>
          <p>Personil Aktif</p>
        </div>
      </div>
    </a>
  </div>
  <div class="col-md-3 col-sm-6">
    <a href="categorie.php">
      <div class="stat-card-horizontal">
        <div class="stat-card-icon green">
          <i class="glyphicon glyphicon-indent-left"></i>
        </div>
        <div class="stat-card-info">
          <h2><?php echo $c_categorie['total']; ?></h2>
          <p>Kategori Aktif</p>
        </div>
      </div>
    </a>
  </div>
  <div class="col-md-3 col-sm-6">
    <a href="product.php">
      <div class="stat-card-horizontal">
        <div class="stat-card-icon yellow">
          <i class="glyphicon glyphicon-th-large"></i>
        </div>
        <div class="stat-card-info">
          <h2><?php echo $c_product['total']; ?></h2>
          <p>Barang Titipan</p>
        </div>
      </div>
    </a>
  </div>
  <div class="col-md-3 col-sm-6">
    <a href="withdrawals.php">
      <div class="stat-card-horizontal">
        <div class="stat-card-icon purple">
          <i class="glyphicon glyphicon-transfer"></i>
        </div>
        <div class="stat-card-info">
          <h2><?php echo $c_withdrawal['total']; ?></h2>
          <p>Pengambilan</p>
        </div>
      </div>
    </a>
  </div>
</div>

<!-- Ringkasan Pribadi & Pengumuman -->
<div class="row">
  <div class="col-md-6">
    <div class="custom-panel">
      <div class="custom-panel-header">
        <h3 class="custom-panel-title"><i class="glyphicon glyphicon-user"></i> Ringkasan Pribadi</h3>
      </div>
      <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:16px; padding:25px; margin-bottom:25px;">
        <h5 style="color:#64748b; font-size:12px; font-weight:700; margin:0 0 8px; text-transform:uppercase;">STATUS OPERASIONAL HARI INI</h5>
        <p style="color:#0b1c3c; font-size:16px; font-weight:600; margin:0 0 15px;">Sistem aktif & siap mencatat mutasi barang titipan.</p>
        <a href="add_product.php" style="color:#10b981; font-weight:700; font-size:15px;">Tambah barang titipan sekarang &rarr;</a>
      </div>
      <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:16px; padding:25px;">
        <h5 style="color:#64748b; font-size:12px; font-weight:700; margin:0 0 8px; text-transform:uppercase;">PENGELOLAAN SISTEM TERAKHIR</h5>
        <p style="color:#0b1c3c; font-size:18px; font-weight:800; margin:0 0 4px;">Log Transaksi Stabil</p>
        <p style="color:#64748b; font-size:13px; margin:0;">Integritas basis data terjaga 100%.</p>
      </div>
    </div>
  </div>
  
  <div class="col-md-6">
    <div class="custom-panel">
      <div class="custom-panel-header">
        <h3 class="custom-panel-title"><i class="glyphicon glyphicon-bullhorn"></i> Pengumuman</h3>
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
