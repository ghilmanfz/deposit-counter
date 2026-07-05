<?php
  $page_title = 'Kelola Landing Page';
  require_once('includes/load.php');
  page_require_level(1);

  $fields = array(
    'landing_hero_badge'     => array('label' => 'Badge Hero', 'type' => 'text'),
    'landing_hero_title'     => array('label' => 'Judul Hero', 'type' => 'text'),
    'landing_hero_subtitle'  => array('label' => 'Subjudul Hero', 'type' => 'textarea'),
    'landing_footer_address' => array('label' => 'Alamat (Footer)', 'type' => 'textarea'),
    'landing_footer_email'   => array('label' => 'Email (Footer)', 'type' => 'text'),
    'landing_footer_phone'   => array('label' => 'No. Sekretariat (Footer)', 'type' => 'text'),
    'landing_footer_hotline' => array('label' => 'No. Layanan 24/7 (Footer)', 'type' => 'text')
  );

  if(isset($_POST['save_landing'])){
    foreach($fields as $key => $meta){
      $val = isset($_POST[$key]) ? trim($_POST[$key]) : '';
      set_setting($key, $val);
    }
    $logo_new = save_app_logo('app_logo_file');
    if($logo_new !== ''){
      set_setting('app_logo', $logo_new);
    }
    $session->msg('s', 'Konfigurasi berhasil disimpan.');
    redirect('landing_settings.php', false);
  }
  $msg = $session->msg();
?>
<?php include_once('layouts/header.php'); ?>
<div class="row"><div class="col-md-12"><?php echo display_msg($msg); ?></div></div>
<div class="row">
  <div class="col-md-9">
    <div class="panel panel-default">
      <div class="panel-heading">
        <strong><span class="glyphicon glyphicon-cog"></span> Kelola Landing Page &amp; Branding</strong>
      </div>
      <div class="panel-body">
        <p class="text-muted">Ubah teks utama halaman depan (hero) dan informasi kontak di footer. Kosongkan untuk memakai teks bawaan.</p>
        <form method="post" action="landing_settings.php" enctype="multipart/form-data">
          <div class="form-group">
            <label>Logo Aplikasi (tampil di seluruh halaman)</label>
            <div style="display:flex; align-items:center; gap:16px; margin-bottom:8px;">
              <img src="<?php echo app_logo_url(); ?>" alt="Logo" style="width:56px; height:56px; border-radius:12px; object-fit:contain; background:#f1f5f9; padding:4px;">
              <input type="file" class="form-control" name="app_logo_file" accept="image/*" style="max-width:400px;">
            </div>
            <small class="text-muted">Format: PNG, JPG, SVG, atau WEBP. Disarankan gambar persegi. Kosongkan jika tidak ingin mengganti.</small>
          </div>
          <?php foreach($fields as $key => $meta): ?>
            <?php $current = landing_setting($key); ?>
            <div class="form-group">
              <label><?php echo htmlspecialchars($meta['label']); ?></label>
              <?php if($meta['type'] === 'textarea'): ?>
                <textarea class="form-control" name="<?php echo $key; ?>" rows="3"><?php echo htmlspecialchars($current); ?></textarea>
              <?php else: ?>
                <input type="text" class="form-control" name="<?php echo $key; ?>" value="<?php echo htmlspecialchars($current); ?>">
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
          <button type="submit" name="save_landing" class="btn btn-primary">Simpan Konfigurasi</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include_once('layouts/footer.php'); ?>
