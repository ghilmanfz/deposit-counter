<?php
  $page_title = 'Edit Pengumuman';
  require_once('includes/load.php');
  page_require_level(1);
  $a = find_announcement_by_id((int)(isset($_GET['id']) ? $_GET['id'] : 0));
  if(!$a){
    $session->msg('d', 'Pengumuman tidak ditemukan.');
    redirect('announcements.php', false);
  }
  if(isset($_POST['edit_announcement'])){
    $req_fields = array('title','publish_date');
    validate_fields($req_fields);
    if(empty($errors)){
      $ok = update_announcement($a['id'], array(
        'title'        => $_POST['title'],
        'content'      => isset($_POST['content']) ? $_POST['content'] : '',
        'publish_date' => $_POST['publish_date'],
        'is_active'    => isset($_POST['is_active']) ? 1 : 0
      ));
      $session->msg($ok ? 's' : 'd', $ok ? 'Pengumuman berhasil diperbarui.' : 'Gagal memperbarui pengumuman.');
      redirect('announcements.php', false);
    } else {
      $session->msg('d', $errors);
      redirect('edit_announcement.php?id='.(int)$a['id'], false);
    }
  }
  $msg = $session->msg();
?>
<?php include_once('layouts/header.php'); ?>
<div class="row"><div class="col-md-12"><?php echo display_msg($msg); ?></div></div>
<div class="row">
  <div class="col-md-8">
    <div class="panel panel-default">
      <div class="panel-heading"><strong><span class="glyphicon glyphicon-edit"></span> Edit Pengumuman</strong></div>
      <div class="panel-body">
        <form method="post" action="edit_announcement.php?id=<?php echo (int)$a['id']; ?>">
          <div class="form-group">
            <label>Judul</label>
            <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($a['title']); ?>">
          </div>
          <div class="form-group">
            <label>Tanggal Terbit</label>
            <input type="date" class="form-control" name="publish_date" value="<?php echo htmlspecialchars($a['publish_date']); ?>">
          </div>
          <div class="form-group">
            <label>Isi Pengumuman</label>
            <textarea class="form-control" name="content" rows="5"><?php echo htmlspecialchars($a['content']); ?></textarea>
          </div>
          <div class="form-group">
            <label><input type="checkbox" name="is_active" value="1" <?php echo ((int)$a['is_active'] === 1) ? 'checked' : ''; ?>> Aktif (tampilkan ke pengguna)</label>
          </div>
          <button type="submit" name="edit_announcement" class="btn btn-primary">Update Pengumuman</button>
          <a href="announcements.php" class="btn btn-default">Kembali</a>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include_once('layouts/footer.php'); ?>
