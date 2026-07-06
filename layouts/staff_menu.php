<ul>
  <li>
    <a href="home.php">
      <i class="glyphicon glyphicon-home"></i>
      <span>Dashboard</span>
    </a>
  </li>
  <?php if(role_can('barang')): ?>
  <li>
    <a href="#" class="submenu-toggle">
      <i class="glyphicon glyphicon-th-large"></i>
      <span>Barang Titipan</span>
    </a>
    <ul class="nav submenu">
      <li><a href="product.php">Data Barang Titipan</a></li>
      <?php if(role_can_action('barang','create')): ?>
      <li><a href="add_product.php">Tambah Barang Titipan</a></li>
      <?php endif; ?>
    </ul>
  </li>
  <?php endif; ?>
  <?php if(role_can('satuan')): ?>
  <li>
    <a href="units.php">
      <i class="glyphicon glyphicon-tags"></i>
      <span>Satuan Barang</span>
    </a>
  </li>
  <?php endif; ?>
  <?php if(role_can('media')): ?>
  <li>
    <a href="media.php">
      <i class="glyphicon glyphicon-picture"></i>
      <span>Media</span>
    </a>
  </li>
  <?php endif; ?>
  <?php if(role_can('transaksi') || role_can('pickup')): ?>
  <li>
    <a href="#" class="submenu-toggle">
      <i class="glyphicon glyphicon-th-list"></i>
      <span>Transaksi Barang</span>
    </a>
    <ul class="nav submenu">
      <?php if(role_can('pickup')): ?>
      <li><a href="pickup_requests.php">Request Pengambilan</a></li>
      <?php endif; ?>
      <?php if(role_can('transaksi')): ?>
      <li><a href="withdrawals.php">Pengambilan Barang</a></li>
      <?php if(role_can_action('transaksi','create')): ?>
      <li><a href="add_withdrawal.php">Tambah Pengambilan Barang</a></li>
      <?php endif; ?>
      <li><a href="stock_history.php">Riwayat Stok</a></li>
      <?php endif; ?>
    </ul>
  </li>
  <?php endif; ?>
  <?php if(role_can('penagihan')): ?>
  <li>
    <a href="billings.php">
      <i class="glyphicon glyphicon-list-alt"></i>
      <span>Penagihan</span>
    </a>
  </li>
  <?php endif; ?>
  <?php if(role_can('surat_jalan')): ?>
  <li>
    <a href="delivery_orders.php">
      <i class="glyphicon glyphicon-file"></i>
      <span>Surat Jalan</span>
    </a>
  </li>
  <?php endif; ?>
  <?php if(role_can('laporan')): ?>
  <li>
    <a href="#" class="submenu-toggle">
      <i class="glyphicon glyphicon-signal"></i>
      <span>Laporan Barang</span>
    </a>
    <ul class="nav submenu">
      <li><a href="withdrawals_report.php">Laporan Periode</a></li>
      <li><a href="monthly_withdrawals.php">Laporan Bulanan</a></li>
      <li><a href="daily_withdrawals.php">Laporan Harian</a></li>
    </ul>
  </li>
  <?php endif; ?>
</ul>
