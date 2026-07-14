<?php
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
  page_require_level(1);
  if($_SERVER['REQUEST_METHOD'] !== 'POST' || !warehouse_csrf_is_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')){
    $session->msg('d','Permintaan hapus kategori tidak valid atau sesi sudah kedaluwarsa.');
    redirect('categorie.php', false);
  }
?>
<?php
  $categorie = find_by_id('categories',isset($_POST['id']) ? (int)$_POST['id'] : 0);
  if(!$categorie){
    $session->msg("d","Kategori tidak ditemukan.");
    redirect('categorie.php');
  }
?>
<?php
  $category_products = find_by_sql("SELECT id FROM products WHERE categorie_id='".(int)$categorie['id']."' LIMIT 1");
  if(!empty($category_products)){
    $session->msg('d','Kategori tidak dapat dihapus karena masih dipakai oleh barang. Pindahkan barang ke kategori lain terlebih dahulu agar histori stok tidak ikut terhapus.');
    redirect('categorie.php', false);
  }
  $delete_id = delete_by_id('categories',(int)$categorie['id']);
  if($delete_id){
      $session->msg("s","Kategori berhasil dihapus.");
      redirect('categorie.php');
  } else {
      $session->msg("d","Kategori gagal dihapus.");
      redirect('categorie.php');
  }
?>
