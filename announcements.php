<?php
  $page_title = 'Kelola Pengumuman';
  require_once('includes/load.php');
  page_require_level(1);
  $announcements = find_all_announcements();
  $msg = $session->msg();
?>
<?php include_once('layouts/header.php'); ?>
<div class="row"><div class="col-md-12"><?php echo display_msg($msg); ?></div></div>
<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading clearfix">
        <strong><span class="glyphicon glyphicon-bullhorn"></span> Daftar Pengumuman</strong>
        <div class="pull-right"><a href="add_announcement.php" class="btn btn-primary">Tambah Pengumuman</a></div>
      </div>
      <div class="panel-body">
        <p class="text-muted">Pengumuman aktif tampil di landing page dan dashboard semua pengguna.</p>
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th class="text-center" style="width:50px;">#</th>
              <th>Judul</th>
              <th class="text-center">Tanggal</th>
              <th class="text-center">Status</th>
              <th class="text-center" style="width:110px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($announcements as $a): ?>
            <tr>
              <td class="text-center"><?php echo count_id(); ?></td>
              <td><?php echo htmlspecialchars($a['title']); ?></td>
              <td class="text-center"><?php echo htmlspecialchars($a['publish_date']); ?></td>
              <td class="text-center">
                <?php if((int)$a['is_active'] === 1): ?>
                  <span class="label label-success">Aktif</span>
                <?php else: ?>
                  <span class="label label-default">Nonaktif</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <a href="edit_announcement.php?id=<?php echo (int)$a['id']; ?>" class="btn btn-warning btn-xs" title="Edit Pengumuman"><span class="glyphicon glyphicon-edit"></span></a>
                <a href="delete_announcement.php?id=<?php echo (int)$a['id']; ?>" class="btn btn-danger btn-xs" title="Hapus Pengumuman"><span class="glyphicon glyphicon-trash"></span></a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($announcements)): ?>
            <tr><td colspan="5" class="text-center">Belum ada pengumuman.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include_once('layouts/footer.php'); ?>
