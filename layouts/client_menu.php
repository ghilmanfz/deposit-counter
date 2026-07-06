<ul>
  <li>
    <a href="client_dashboard.php">
      <i class="glyphicon glyphicon-home"></i>
      <span>Dashboard</span>
    </a>
  </li>
  <?php if(role_can('barang_saya')): ?>
  <li>
    <a href="my_products.php">
      <i class="glyphicon glyphicon-th-large"></i>
      <span>Barang Saya</span>
    </a>
  </li>
  <?php endif; ?>
  <?php if(role_can('barang_saya')): ?>
  <li>
    <a href="stock_history.php">
      <i class="glyphicon glyphicon-transfer"></i>
      <span>Riwayat Stok</span>
    </a>
  </li>
  <?php endif; ?>
  <?php if(role_can('penagihan')): ?>
  <li>
    <a href="billings.php">
      <i class="glyphicon glyphicon-list-alt"></i>
      <span>Tagihan Saya</span>
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
  <?php if(role_can('pickup')): ?>
  <li>
    <a href="pickup_requests.php">
      <i class="glyphicon glyphicon-send"></i>
      <span>Request Pengambilan Barang</span>
    </a>
  </li>
  <?php endif; ?>
</ul>
