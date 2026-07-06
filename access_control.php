<?php
  $page_title = 'Kelola Hak Akses';
  require_once('includes/load.php');
  page_require_level(1);

  $modules = access_permission_modules();
  $actions = permission_actions();

  if(isset($_POST['add_role'])){
    $level = create_role(isset($_POST['role_name']) ? $_POST['role_name'] : '');
    $session->msg($level ? 's' : 'd', $level ? 'Role baru berhasil ditambahkan.' : 'Nama role kosong atau sudah dipakai.');
    redirect('access_control.php', false);
  }

  if(isset($_POST['rename_role'])){
    $role_id = (int)$_POST['role_id'];
    $role = find_by_id('user_groups', $role_id);
    if(!$role){
      $session->msg('d','Role tidak ditemukan.');
      redirect('access_control.php', false);
    }
    rename_role($role_id, isset($_POST['role_name']) ? $_POST['role_name'] : '');
    set_role_status($role_id, isset($_POST['role_status']) ? (int)$_POST['role_status'] : 0);
    $session->msg('s','Role berhasil diperbarui.');
    redirect('access_control.php', false);
  }

  if(isset($_POST['delete_role'])){
    $result = delete_role((int)$_POST['role_id']);
    $messages = array(
      'ok'        => array('s','Role berhasil dihapus.'),
      'protected' => array('d','Role Admin dan Pelanggan tidak dapat dihapus.'),
      'inuse'     => array('d','Role masih dipakai user. Pindahkan user ke role lain dulu.'),
      'notfound'  => array('d','Role tidak ditemukan.'),
      'fail'      => array('d','Gagal menghapus role.')
    );
    $message = isset($messages[$result]) ? $messages[$result] : array('d','Gagal menghapus role.');
    $session->msg($message[0], $message[1]);
    redirect('access_control.php', false);
  }

  if(isset($_POST['save_access'])){
    $roles_for_save = find_all_roles();
    foreach($roles_for_save as $role){
      $level = (int)$role['group_level'];
      if(!role_is_internal_staff($level)){ continue; }
      foreach($modules as $module_key => $module){
        if($module_key === 'barang_saya'){ continue; }
        foreach($module['actions'] as $action_key){
          $allowed = isset($_POST['perm'][$level][$module_key][$action_key]) ? 1 : 0;
          set_role_action_permission($level, $module_key, $action_key, $allowed);
        }
      }
    }
    $session->msg('s','Hak akses role berhasil disimpan.');
    redirect('access_control.php', false);
  }

  $roles = find_all_roles();
  $staff_roles = array();
  foreach($roles as $role){
    if(role_is_internal_staff((int)$role['group_level'])){
      $staff_roles[] = $role;
    }
  }
  $map = role_action_permissions_map();
  $msg = $session->msg();
?>
<?php include_once('layouts/header.php'); ?>
<div class="row"><div class="col-md-12"><?php echo display_msg($msg); ?></div></div>

<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading"><strong><span class="glyphicon glyphicon-user"></span> Kelola Role</strong></div>
      <div class="panel-body">
        <p class="text-muted"><strong>Admin</strong> dan <strong>Pelanggan</strong> adalah role bawaan. Role baru otomatis menjadi role staf internal dan dapat diberi hak akses detail di matriks bawah.</p>
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Nama Role</th>
              <th class="text-center">Level</th>
              <th class="text-center">Jml User</th>
              <th class="text-center">Status</th>
              <th class="text-center" style="width:220px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($roles as $role): ?>
              <?php $level = (int)$role['group_level']; $protected = role_is_protected($level); $used = role_user_count($level); ?>
              <tr>
                <form method="post" action="access_control.php">
                  <input type="hidden" name="role_id" value="<?php echo (int)$role['id']; ?>">
                  <td><input type="text" class="form-control input-sm" name="role_name" value="<?php echo htmlspecialchars(ucwords($role['group_name'])); ?>"></td>
                  <td class="text-center">
                    <?php echo $level; ?>
                    <?php if($level === USER_LEVEL_ADMIN): ?><span class="label label-primary">Admin</span><?php endif; ?>
                    <?php if($level === USER_LEVEL_CLIENT): ?><span class="label label-info">Client</span><?php endif; ?>
                  </td>
                  <td class="text-center"><?php echo $used; ?></td>
                  <td class="text-center">
                    <select name="role_status" class="form-control input-sm">
                      <option value="1" <?php echo (string)$role['group_status'] === '1' ? 'selected' : ''; ?>>Aktif</option>
                      <option value="0" <?php echo (string)$role['group_status'] === '0' ? 'selected' : ''; ?>>Nonaktif</option>
                    </select>
                  </td>
                  <td class="text-center">
                    <button type="submit" name="rename_role" class="btn btn-warning btn-xs">Simpan</button>
                    <?php if(!$protected): ?>
                      <button type="submit" name="delete_role" class="btn btn-danger btn-xs" onclick="return confirm('Hapus role ini?');" <?php echo $used > 0 ? 'disabled title="Masih dipakai user"' : ''; ?>>Hapus</button>
                    <?php endif; ?>
                  </td>
                </form>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <form method="post" action="access_control.php" class="form-inline" style="margin-top:10px;">
          <div class="form-group">
            <label>Tambah Role Staf:</label>
            <input type="text" class="form-control" name="role_name" placeholder="cth: Manajer, Kasir, Gudang">
          </div>
          <button type="submit" name="add_role" class="btn btn-primary">+ Tambah Role</button>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading"><strong><span class="glyphicon glyphicon-lock"></span> Hak Akses Staf per Role</strong></div>
      <div class="panel-body">
        <p class="text-muted">Centang aksi yang boleh dilakukan tiap role staf internal. <strong>Admin</strong> selalu penuh. Manajemen user, role, kategori, konten, dan landing page tetap khusus Admin.</p>
        <form method="post" action="access_control.php">
          <table class="table table-striped table-bordered">
            <thead>
              <tr>
                <th style="width:220px;">Modul</th>
                <th style="width:120px;">Aksi</th>
                <?php foreach($staff_roles as $role): ?>
                  <th class="text-center"><?php echo htmlspecialchars(ucwords($role['group_name'])); ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach($modules as $module_key => $module): ?>
                <?php if($module_key === 'barang_saya'){ continue; } ?>
                <?php foreach($module['actions'] as $index => $action_key): ?>
                <tr>
                  <?php if($index === 0): ?>
                    <td rowspan="<?php echo count($module['actions']); ?>"><strong><?php echo htmlspecialchars($module['label']); ?></strong></td>
                  <?php endif; ?>
                  <td><?php echo htmlspecialchars($actions[$action_key]); ?></td>
                  <?php foreach($staff_roles as $role): ?>
                    <?php $level = (int)$role['group_level']; $checked = isset($map[$level][$module_key][$action_key]) && (int)$map[$level][$module_key][$action_key] === 1; ?>
                    <td class="text-center">
                      <input type="checkbox" name="perm[<?php echo $level; ?>][<?php echo htmlspecialchars($module_key); ?>][<?php echo htmlspecialchars($action_key); ?>]" value="1" <?php echo $checked ? 'checked' : ''; ?>>
                    </td>
                  <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </tbody>
          </table>
          <button type="submit" name="save_access" class="btn btn-primary">Simpan Hak Akses</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include_once('layouts/footer.php'); ?>
