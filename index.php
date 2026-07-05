<?php
  ob_start();
  require_once('includes/load.php');
  $msg = $session->msg();
  if($session->isUserLoggedIn(true)) { redirect_by_user_level(); }
  $landing_announcements = find_all_announcements(true, 3);
?>
<?php include_once('layouts/header.php'); ?>

<!-- Navigation Bar Landing Page -->
<nav class="landing-navbar">
  <div class="brand">
    <img src="<?php echo app_logo_url(); ?>" alt="Brand Logo">
    Sistem Penitipan Barang<span>.</span>
  </div>
  <div class="landing-nav-links">
    <a href="#profil">Profil</a>
    <a href="#fitur">Fitur Utama</a>
    <a href="#pengumuman">Pengumuman</a>
    <a href="#kontak">Kontak</a>
    <a href="login.php" class="landing-btn-portal">Masuk Portal &rarr;</a>
  </div>
</nav>

<!-- Hero Section -->
<section class="landing-hero">
  <div class="container" style="max-width: 1300px;">
    <?php echo display_msg($msg); ?>
    <div class="row" style="display:flex; align-items:center; flex-wrap:wrap;">
      <div class="col-md-7">
        <div class="hero-badge">&bull; <?php echo htmlspecialchars(landing_setting('landing_hero_badge')); ?></div>
        <h1><?php echo htmlspecialchars(landing_setting('landing_hero_title')); ?></h1>
        <p><?php echo htmlspecialchars(landing_setting('landing_hero_subtitle')); ?></p>
        <div class="landing-hero-actions">
          <a href="login.php" class="landing-btn-primary">Buka Dashboard &rarr;</a>
          <a href="#fitur" class="landing-btn-outline">Pelajari Selengkapnya</a>
        </div>
      </div>
      <div class="col-md-5">
        <div class="hero-glass-card">
          <div class="hero-glass-item">
            <div class="title"><i class="glyphicon glyphicon-user"></i> Personil Internal</div>
            <div class="badge">Terverifikasi</div>
          </div>
          <div class="hero-glass-item">
            <div class="title"><i class="glyphicon glyphicon-th-large"></i> Barang Terdaftar</div>
            <div class="badge">350+ Aktif</div>
          </div>
          <div class="hero-glass-item">
            <div class="title"><i class="glyphicon glyphicon-eye-open"></i> Transparansi Klien</div>
            <div class="badge">Real-time</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Floating Stats Bar -->
<div class="container" style="max-width: 1200px;">
  <div class="hero-stats-bar">
    <div class="hero-stat-item">
      <h2>4+</h2>
      <p>Level User & Staff</p>
    </div>
    <div class="hero-stat-item">
      <h2>350+</h2>
      <p>Barang Titipan</p>
    </div>
    <div class="hero-stat-item">
      <h2>15+</h2>
      <p>Kategori Aktif</p>
    </div>
    <div class="hero-stat-item">
      <h2>100%</h2>
      <p>Data Terpusat</p>
    </div>
  </div>
</div>

<!-- Profil Section -->
<section id="profil" style="padding: 60px 0 100px;">
  <div class="container" style="max-width: 1200px;">
    <div class="section-tag">PROFIL SISTEM</div>
    <h2 class="section-title">Sistem Penitipan Barang Terpadu</h2>
    <p class="section-subtitle">Platform digitalisasi pengelolaan gudang dan penitipan barang yang berfokus pada kecepatan layanan, akurasi tinggi, serta transparansi antara pengelola dan klien melalui optimalisasi teknologi modern.</p>
    
    <div class="row">
      <div class="col-md-4">
        <div class="feature-grid-card">
          <div class="feature-icon-box"><i class="glyphicon glyphicon-flash"></i></div>
          <h3>Kecepatan Layanan</h3>
          <p>Meminimalisir waktu administrasi dengan sistem otomatis yang responsif, menghemat jam kerja staf operasional setiap hari.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-grid-card green">
          <div class="feature-icon-box"><i class="glyphicon glyphicon-ok-circle"></i></div>
          <h3>Akurasi & Integritas</h3>
          <p>Mencegah terjadinya selisih stok dan kehilangan berkas dengan pencatatan mutasi yang terstruktur dan mutakhir.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-grid-card yellow">
          <div class="feature-icon-box"><i class="glyphicon glyphicon-lock"></i></div>
          <h3>Akses Multi-level</h3>
          <p>Keamanan tingkat tinggi dengan pembagian hak akses terperinci dari Super Admin, Staf, hingga Klien eksternal.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Fitur Aplikasi Section -->
<section id="fitur" style="padding: 100px 0; background-color: #f1f5f9;">
  <div class="container" style="max-width: 1200px;">
    <div class="section-tag">FITUR APLIKASI</div>
    <h2 class="section-title">Modul Sistem Terintegrasi</h2>
    <p class="section-subtitle">Sistem dirancang untuk menyelesaikan masalah pencatatan manual, mencegah blunder sinkronisasi, dan memudahkan pimpinan memantau kondisi gudang secara transparan.</p>
    
    <div class="row" style="margin-bottom: 30px;">
      <div class="col-md-4">
        <div class="feature-grid-card">
          <div class="feature-icon-box"><i class="glyphicon glyphicon-user"></i></div>
          <h3>Role & Permission</h3>
          <p>Hak akses detail Super Admin, Special User, Staf, dan Klien. Mengontrol penuh siapa yang dapat melihat dan mengubah data.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-grid-card green">
          <div class="feature-icon-box"><i class="glyphicon glyphicon-transfer"></i></div>
          <h3>Manajemen Stok Real-time</h3>
          <p>Pantau arus barang masuk dan keluar secara langsung dengan pencatatan otomatis, akurat, dan terorganisir dengan baik.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-grid-card purple">
          <div class="feature-icon-box"><i class="glyphicon glyphicon-file"></i></div>
          <h3>Pencatatan Surat Jalan</h3>
          <p>Cetak dan kelola surat jalan untuk setiap pengiriman atau pengambilan barang secara resmi dan tercatat rapi.</p>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-4">
        <div class="feature-grid-card yellow">
          <div class="feature-icon-box"><i class="glyphicon glyphicon-list-alt"></i></div>
          <h3>Tagihan & Invoice</h3>
          <p>Sistem penagihan periodik dan riwayat tagihan klien yang terintegrasi langsung dengan aktivitas penitipan barang.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-grid-card">
          <div class="feature-icon-box"><i class="glyphicon glyphicon-warning-sign"></i></div>
          <h3>Pemeriksaan Barang Cacat</h3>
          <p>Pencatatan dan pelaporan barang rusak atau cacat saat kedatangan untuk menjaga kualitas dan kepercayaan klien.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-grid-card green">
          <div class="feature-icon-box"><i class="glyphicon glyphicon-signal"></i></div>
          <h3>Laporan Strategis</h3>
          <p>Dashboard pemantauan dan rekap data mutasi harian, bulanan, serta laporan khusus untuk pimpinan manajemen.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Papan Informasi Section -->
<section id="pengumuman" style="padding: 100px 0;">
  <div class="container" style="max-width: 1200px;">
    <div class="section-tag">PAPAN INFORMASI</div>
    <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:50px;">
      <div>
        <h2 class="section-title" style="text-align:left;">Pengumuman & Agenda Terbaru</h2>
        <p class="section-subtitle" style="text-align:left; margin:0;">Ikuti perkembangan kabar terkini dan jadwal kegiatan penting di Sistem Penitipan Barang.</p>
      </div>
      <a href="login.php" style="color:#10b981; font-weight:700; font-size:15px;">Semua Pengumuman &rarr;</a>
    </div>
    
    <div class="row">
      <?php if(empty($landing_announcements)): ?>
        <div class="col-md-12"><p class="section-subtitle" style="text-align:left;">Belum ada pengumuman.</p></div>
      <?php else: ?>
        <?php foreach($landing_announcements as $la): ?>
        <div class="col-md-4">
          <div class="news-card">
            <div class="news-header">
              <span class="badge">INFO</span>
              <span class="date"><?php echo !empty($la['publish_date']) ? strtoupper(date('d M Y', strtotime($la['publish_date']))) : ''; ?></span>
            </div>
            <div class="news-body">
              <h3><?php echo htmlspecialchars($la['title']); ?></h3>
              <p><?php echo nl2br(htmlspecialchars($la['content'])); ?></p>
              <a href="login.php">Baca selengkapnya &rarr;</a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Footer Section -->
<footer id="kontak" class="landing-footer">
  <div class="container" style="max-width: 1200px;">
    <div class="row">
      <div class="col-md-5">
        <div class="landing-footer-brand">
          <img src="<?php echo app_logo_url(); ?>" alt="Brand Logo">
          Sistem Penitipan Barang<span>.</span>
        </div>
        <p style="max-width: 380px; line-height: 1.7; margin-top: 20px;">
          Sistem informasi modern berbasis nilai integritas dan pengelolaan digital yang transparan serta kredibel. Terdepan melahirkan manajemen inventori unggul.
        </p>
      </div>
      <div class="col-md-3">
        <h4>NAVIGASI CEPAT</h4>
        <ul>
          <li><a href="#profil">Profil Sistem</a></li>
          <li><a href="#fitur">Modul Manajemen</a></li>
          <li><a href="#pengumuman">Informasi Papan</a></li>
          <li><a href="login.php">Login Staf & Pimpinan</a></li>
        </ul>
      </div>
      <div class="col-md-4">
        <h4>SEKRETARIAT & HUBUNGAN</h4>
        <p style="margin-bottom: 12px; display:flex; gap:12px;">
          <i class="glyphicon glyphicon-map-marker" style="color:#10b981; font-size:18px;"></i>
          <span><?php echo htmlspecialchars(landing_setting('landing_footer_address')); ?></span>
        </p>
        <p style="display:flex; gap:12px;">
          <i class="glyphicon glyphicon-envelope" style="color:#10b981; font-size:16px;"></i>
          <span><?php echo htmlspecialchars(landing_setting('landing_footer_email')); ?></span>
        </p>
      </div>
    </div>
    <div class="landing-footer-copy">
      <div>SEKRETARIAT: <strong><?php echo htmlspecialchars(landing_setting('landing_footer_phone')); ?></strong> &nbsp;&nbsp;|&nbsp;&nbsp; LAYANAN 24/7: <strong style="color:#10b981;"><?php echo htmlspecialchars(landing_setting('landing_footer_hotline')); ?></strong></div>
      <div>&copy; <?php echo date('Y'); ?> Sistem Penitipan Barang. Hak Cipta Dilindungi Undang-Undang.</div>
    </div>
  </div>
</footer>

<?php include_once('layouts/footer.php'); ?>
