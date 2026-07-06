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
    $status = role_is_protected((int)$role['group_level']) ? 1 : (isset($_POST['role_status']) ? (int)$_POST['role_status'] : 0);
    set_role_status($role_id, $status);
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
  $total_roles = count($roles);
  $staff_role_count = count($staff_roles);
  $module_count = count($modules) - 1;
?>
<?php include_once('layouts/header.php'); ?>
<div class="access-control-page">
  <div class="row"><div class="col-md-12"><?php echo display_msg($msg); ?></div></div>

  <div class="access-summary-grid">
    <div class="access-summary-item">
      <span>Total Role</span>
      <strong><?php echo (int)$total_roles; ?></strong>
    </div>
    <div class="access-summary-item">
      <span>Role Staf</span>
      <strong><?php echo (int)$staff_role_count; ?></strong>
    </div>
    <div class="access-summary-item">
      <span>Modul Diatur</span>
      <strong><?php echo (int)$module_count; ?></strong>
    </div>
  </div>

  <div class="access-panel">
    <div class="access-panel-header">
      <div>
        <h3><span class="glyphicon glyphicon-user"></span> Kelola Role</h3>
        <p>Role staf baru dapat langsung diberi hak akses detail pada matriks di bawah.</p>
      </div>
      <form method="post" action="access_control.php" class="access-add-role-form">
        <input type="text" class="form-control" name="role_name" placeholder="cth: Manajer, Kasir, Gudang">
        <button type="submit" name="add_role" class="btn btn-primary">+ Tambah Role</button>
      </form>
    </div>

    <div class="access-role-table-wrap">
      <?php foreach($roles as $role): ?>
        <form id="access-role-form-<?php echo (int)$role['id']; ?>" method="post" action="access_control.php">
          <input type="hidden" name="role_id" value="<?php echo (int)$role['id']; ?>">
        </form>
      <?php endforeach; ?>

      <table class="table access-role-table">
        <thead>
          <tr>
            <th>Nama Role</th>
            <th class="text-center">Level</th>
            <th class="text-center">User</th>
            <th>Status</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($roles as $role): ?>
            <?php $level = (int)$role['group_level']; $protected = role_is_protected($level); $used = role_user_count($level); ?>
            <?php $role_form_id = 'access-role-form-'.(int)$role['id']; ?>
            <tr>
              <td>
                <input type="text" class="form-control input-sm access-role-name" form="<?php echo $role_form_id; ?>" name="role_name" value="<?php echo htmlspecialchars(ucwords($role['group_name'])); ?>">
              </td>
              <td class="text-center">
                <span class="access-level-number"><?php echo $level; ?></span>
                <?php if($level === USER_LEVEL_ADMIN): ?><span class="access-role-badge admin">Admin</span><?php endif; ?>
                <?php if($level === USER_LEVEL_CLIENT): ?><span class="access-role-badge client">Client</span><?php endif; ?>
              </td>
              <td class="text-center"><span class="access-user-count"><?php echo $used; ?></span></td>
              <td>
                <select name="role_status" form="<?php echo $role_form_id; ?>" class="form-control input-sm access-status-select">
                  <option value="1" <?php echo (string)$role['group_status'] === '1' ? 'selected' : ''; ?>>Aktif</option>
                  <option value="0" <?php echo (string)$role['group_status'] === '0' ? 'selected' : ''; ?>>Nonaktif</option>
                </select>
              </td>
              <td class="text-center">
                <div class="access-action-group">
                  <button type="submit" form="<?php echo $role_form_id; ?>" name="rename_role" class="btn btn-warning btn-xs">Simpan</button>
                  <?php if(!$protected): ?>
                    <button type="submit" form="<?php echo $role_form_id; ?>" name="delete_role" class="btn btn-danger btn-xs" onclick="return confirm('Hapus role ini?');" <?php echo $used > 0 ? 'disabled title="Masih dipakai user"' : ''; ?>>Hapus</button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="access-panel">
    <div class="access-panel-header">
      <div>
        <h3><span class="glyphicon glyphicon-lock"></span> Hak Akses Staf per Role</h3>
        <p>Admin selalu penuh. Manajemen user, kategori, konten, dan landing page tetap khusus Admin.</p>
      </div>
      <div class="access-legend">
        <span><i class="access-dot on"></i> Diizinkan</span>
        <span><i class="access-dot off"></i> Tidak aktif</span>
      </div>
    </div>

    <form method="post" action="access_control.php">
      <div class="access-matrix-wrap">
        <table class="table access-matrix-table">
          <thead>
            <tr>
              <th class="access-col-module">Modul</th>
              <th class="access-col-action">Aksi</th>
              <?php foreach($staff_roles as $role): ?>
                <th class="text-center access-col-role"><?php echo htmlspecialchars(ucwords($role['group_name'])); ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach($modules as $module_key => $module): ?>
              <?php if($module_key === 'barang_saya'){ continue; } ?>
              <?php foreach($module['actions'] as $index => $action_key): ?>
              <tr class="<?php echo $index === 0 ? 'access-module-start' : ''; ?>">
                <?php if($index === 0): ?>
                  <td class="access-module-cell" rowspan="<?php echo count($module['actions']); ?>">
                    <strong><?php echo htmlspecialchars($module['label']); ?></strong>
                    <small><?php echo count($module['actions']); ?> aksi</small>
                  </td>
                <?php endif; ?>
                <td class="access-action-cell">
                  <span class="access-action-badge"><?php echo htmlspecialchars($actions[$action_key]); ?></span>
                </td>
                <?php foreach($staff_roles as $role): ?>
                  <?php $level = (int)$role['group_level']; $checked = isset($map[$level][$module_key][$action_key]) && (int)$map[$level][$module_key][$action_key] === 1; ?>
                  <td class="text-center access-check-cell">
                    <label class="access-check">
                      <input type="checkbox" name="perm[<?php echo $level; ?>][<?php echo htmlspecialchars($module_key); ?>][<?php echo htmlspecialchars($action_key); ?>]" value="1" <?php echo $checked ? 'checked' : ''; ?>>
                      <span></span>
                    </label>
                  </td>
                <?php endforeach; ?>
              </tr>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="access-save-bar">
        <span><?php echo (int)$staff_role_count; ?> role staf dalam matriks</span>
        <button type="submit" name="save_access" class="btn btn-primary">Simpan Hak Akses</button>
      </div>
    </form>
  </div>
</div>
<?php include_once('layouts/footer.php'); ?>
