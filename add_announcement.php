<?php
  $page_title = 'Tambah Pengumuman';
  require_once('includes/load.php');
  page_require_level(1);
  if(isset($_POST['add_announcement'])){
    $req_fields = array('title','publish_date');
    validate_fields($req_fields);
    if(empty($errors)){
      $id = create_announcement(array(
        'title'        => $_POST['title'],
        'content'      => isset($_POST['content']) ? $_POST['content'] : '',
        'publish_date' => $_POST['publish_date'],
        'is_active'    => isset($_POST['is_active']) ? 1 : 0
      ));
      $session->msg($id ? 's' : 'd', $id ? 'Pengumuman berhasil ditambahkan.' : 'Gagal menambah pengumuman.');
      redirect('announcements.php', false);
    } else {
      $session->msg('d', $errors);
      redirect('add_announcement.php', false);
    }
  }
  $msg = $session->msg();
?>
<?php include_once('layouts/header.php'); ?>
<div class="row"><div class="col-md-12"><?php echo display_msg($msg); ?></div></div>
<div class="row">
  <div class="col-md-8">
    <div class="panel panel-default">
      <div class="panel-heading"><strong><span class="glyphicon glyphicon-plus"></span> Tambah Pengumuman</strong></div>
      <div class="panel-body">
        <form method="post" action="add_announcement.php">
          <div class="form-group">
            <label>Judul</label>
            <input type="text" class="form-control" name="title" placeholder="Judul pengumuman">
          </div>
          <div class="form-group">
            <label>Tanggal Terbit</label>
            <input type="date" class="form-control" name="publish_date" value="<?php echo date('Y-m-d'); ?>">
          </div>
          <div class="form-group">
            <label>Isi Pengumuman</label>
            <textarea class="form-control" name="content" rows="5" placeholder="Isi pengumuman"></textarea>
          </div>
          <div class="form-group">
            <label><input type="checkbox" name="is_active" value="1" checked> Aktif (tampilkan ke pengguna)</label>
          </div>
          <button type="submit" name="add_announcement" class="btn btn-primary">Simpan Pengumuman</button>
          <a href="announcements.php" class="btn btn-default">Kembali</a>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include_once('layouts/footer.php'); ?>
