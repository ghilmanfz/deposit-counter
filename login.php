<?php
  ob_start();
  require_once('includes/load.php');
  $msg = $session->msg();
  if($session->isUserLoggedIn(true)) { redirect_by_user_level(); }
?>
<?php include_once('layouts/header.php'); ?>

<div class="login-container-wrapper">
  <!-- Top Bar -->
  <div class="login-top-bar">
    <a href="index.php" class="brand">
      <img src="https://ui-avatars.com/api/?name=PB&background=10b981&color=ffffff&bold=true" alt="Brand Logo">
      <div>
        Sistem Penitipan Barang
        <span class="subtitle">KEMBALI KE BERANDA</span>
      </div>
    </a>
    <a href="index.php" class="back-link">&larr; Beranda Publik</a>
  </div>

  <!-- Split Card -->
  <div class="login-split-card">
    <!-- Left Dark Panel -->
    <div class="login-left">
      <div>
        <div class="login-left-tag">PORTAL STAF</div>
        <h2>Sistem Manajemen Terintegrasi</h2>
        <p>Kelola administrasi inventori, data barang titipan, pencatatan mutasi terverifikasi, penagihan, dan laporan strategis dalam satu atap digital yang aman.</p>
      </div>
      
      <div>
        <div class="login-info-box">
          <h4><i class="glyphicon glyphicon-tasks"></i> PENCATATAN MUTASI AKTIF</h4>
          <p>Check-in & check-out terverifikasi otomatis oleh sistem pencatatan terpadu gudang secara akurat dan real-time.</p>
        </div>
        <div class="login-info-box" style="margin-top: 20px;">
          <h4><i class="glyphicon glyphicon-barcode"></i> PELACAKAN BARANG REAL-TIME</h4>
          <p>Setiap barang titipan dilengkapi pelacakan status menyeluruh dari awal masuk hingga pengambilan akhir.</p>
        </div>
      </div>
      
      <div style="font-size: 13px; color: #64748b; margin-top: 40px;">
        Layanan Bantuan IT: info@penitipanbarang.com
      </div>
    </div>

    <!-- Right Login Form -->
    <div class="login-right">
      <h2>Selamat Datang Kembali</h2>
      <p class="sub">Silakan masukkan akun Anda untuk mengakses dashboard manajemen.</p>
      
      <?php echo display_msg($msg); ?>
      
      <form id="loginForm" method="post" action="auth.php" class="login-form">
        <div class="form-group">
          <label for="username">Username / Email</label>
          <input type="text" class="form-control" name="username" placeholder="staf@penitipanbarang.com" required>
        </div>
        <div class="form-group">
          <label for="password">Kata Sandi</label>
          <input type="password" name="password" class="form-control" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" required>
        </div>
        <div class="form-group" style="display:flex; align-items:center; gap:10px; margin-bottom: 30px;">
          <input type="checkbox" id="remember" name="remember" style="width:18px; height:18px; accent-color:#10b981;">
          <label for="remember" style="margin:0; font-weight:600; text-transform:none; font-size:14px; color:#475569;">Ingat Sesi Masuk Saya</label>
        </div>
        <button type="submit" class="login-btn-submit">Masuk Aplikasi &rarr;</button>
      </form>

      <!-- Uji Coba Cepat (Demo Access) -->
      <div class="demo-access-section">
        <div class="demo-access-title">&bull; UJI COBA CEPAT (AKSES DEMO)</div>
        <div class="demo-grid">
          <button type="button" class="demo-btn-card" onclick="demoLogin('admin','admin')">
            <h4>Super Admin</h4>
            <p>Pengaturan Sistem</p>
          </button>
          <button type="button" class="demo-btn-card" onclick="demoLogin('special','special')">
            <h4>Admin Operasional</h4>
            <p>Petugas Data</p>
          </button>
          <button type="button" class="demo-btn-card" onclick="demoLogin('user','user')">
            <h4>Staf Gudang</h4>
            <p>Pegawai Operasional</p>
          </button>
          <button type="button" class="demo-btn-card" onclick="demoLogin('client','client')">
            <h4>Klien</h4>
            <p>Monitoring Barang</p>
          </button>
        </div>
      </div>

    </div>
  </div>
  
  <div style="margin-top: 40px; font-size: 13px; font-weight: 600; color: #94a3b8; text-align: center;">
    &copy; <?php echo date('Y'); ?> SISTEM PENITIPAN BARANG. SELURUH HAK CIPTA DILINDUNGI.
  </div>
</div>

<script>
function demoLogin(username, password) {
  document.querySelector('input[name="username"]').value = username;
  document.querySelector('input[name="password"]').value = password;
  document.getElementById('loginForm').submit();
}
</script>

<?php include_once('layouts/footer.php'); ?>
