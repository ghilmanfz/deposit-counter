<?php $user = current_user(); ?>
<!DOCTYPE html>
  <html lang="id">
    <head>
    <meta charset="UTF-8">
    <title><?php if (!empty($page_title))
           echo remove_junk($page_title);
            elseif(!empty($user))
           echo ucfirst($user['name']);
            else echo "Sistem Penitipan Barang";?>
    </title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/css/datepicker3.min.css" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap.min.css" />
    <link rel="stylesheet" href="libs/css/main.css" />
  </head>
  <body>
  <?php  if ($session->isUserLoggedIn(true)): 
      // Generate Initials
      $name = isset($user['name']) ? remove_junk(ucfirst($user['name'])) : 'User';
      $words = explode(' ', $name);
      $initials = '';
      foreach($words as $w) {
        if(!empty($w)) {
          $initials .= strtoupper($w[0]);
        }
      }
      $initials = substr($initials, 0, 2);

      // User Level Name
      $lvl = isset($user['user_level']) ? $user['user_level'] : '';
      $lvl_name = "User";
      if($lvl === '1') $lvl_name = "Super Admin";
      if($lvl === '2') $lvl_name = "Special User";
      if($lvl === '3') $lvl_name = "User Staff";
      if($lvl === '4') $lvl_name = "Client";

      // Indonesian Date Format
      $hari = array("Minggu","Senin","Selasa","Rabu","Kamis","Jumat","Sabtu");
      $bulan = array("","Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember");
      $date_indo = $hari[date("w")] . ", " . date("j") . " " . $bulan[date("n")] . " " . date("Y");
  ?>
    <header id="header">
      <div class="header-left-info">
        <h2><?php echo !empty($page_title) ? remove_junk($page_title) : 'Dashboard'; ?></h2>
        <p><?php echo $date_indo; ?></p>
      </div>
      <div class="header-right-info">
        <div class="dropdown">
          <div class="user-profile-pill" data-toggle="dropdown" aria-expanded="false">
            <div class="user-avatar-circle"><?php echo $initials; ?></div>
            <div class="user-profile-info">
              <h4><?php echo $name; ?></h4>
              <p><?php echo $lvl_name; ?></p>
            </div>
            <i class="caret" style="color:#64748b; margin-left:5px;"></i>
          </div>
          <ul class="dropdown-menu dropdown-menu-right" style="border-radius:16px; border:1px solid #e2e8f0; box-shadow:0 10px 25px -5px rgba(0,0,0,0.1); padding:10px 0; margin-top:10px;">
            <li>
                <a href="profile.php?id=<?php echo (int)$user['id'];?>" style="padding:10px 20px; font-weight:600; color:#475569;">
                    <i class="glyphicon glyphicon-user" style="margin-right:10px; color:#10b981;"></i>
                    Profil Saya
                </a>
            </li>
            <li>
                <a href="edit_account.php" style="padding:10px 20px; font-weight:600; color:#475569;">
                    <i class="glyphicon glyphicon-cog" style="margin-right:10px; color:#10b981;"></i>
                    Pengaturan Akun
                </a>
            </li>
            <li role="separator" class="divider" style="margin:10px 0;"></li>
            <li>
                <a href="logout.php" style="padding:10px 20px; font-weight:600; color:#ef4444;">
                    <i class="glyphicon glyphicon-off" style="margin-right:10px;"></i>
                    Keluar / Logout
                </a>
            </li>
          </ul>
        </div>
      </div>
    </header>
    <div class="sidebar">
      <div class="sidebar-top-brand">
        <img src="<?php echo app_logo_url(); ?>" alt="Brand Logo">
        <h3>Sistem Penitipan<span>SISTEM MANAJEMEN TERPADU</span></h3>
      </div>
      
      <div class="sidebar-menu-container">
        <p class="sidebar-section-title">UTAMA & OPERASIONAL</p>
        <?php if($user['user_level'] === '1'): ?>
          <!-- admin menu -->
        <?php include_once('admin_menu.php');?>

        <?php elseif($user['user_level'] === '2'): ?>
          <!-- Special user -->
        <?php include_once('special_menu.php');?>

        <?php elseif($user['user_level'] === '3'): ?>
          <!-- User menu -->
        <?php include_once('user_menu.php');?>

        <?php elseif($user['user_level'] === '4'): ?>
          <!-- Client menu -->
        <?php include_once('client_menu.php');?>

        <?php endif;?>
      </div>

      <div class="sidebar-bottom-profile">
        <div class="sidebar-profile-flex">
          <div class="user-avatar"><?php echo $initials; ?></div>
          <div>
            <h4><?php echo $name; ?></h4>
            <p><?php echo $lvl_name; ?></p>
          </div>
        </div>
        <a href="logout.php" class="sidebar-logout-btn" title="Keluar">
          <i class="glyphicon glyphicon-log-out"></i>
        </a>
      </div>
    </div>
<?php endif;?>

<div class="page <?php echo ($session->isUserLoggedIn(true)) ? '' : 'page-public'; ?>">
  <div class="container-fluid" <?php echo ($session->isUserLoggedIn(true)) ? '' : 'style="padding:0;"'; ?>>
