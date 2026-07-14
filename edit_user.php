<?php
  $page_title = 'Edit User';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
   page_require_level(1);
   ensure_consignment_tables();
?>
<?php
  $e_user = find_by_id('users',(int)$_GET['id']);
  $groups  = find_by_sql("SELECT * FROM user_groups WHERE group_status='1' ORDER BY group_level ASC");
  if(!$e_user){
    $session->msg("d","ID user tidak ditemukan.");
    redirect('users.php');
  }
  if($_SERVER['REQUEST_METHOD'] === 'POST' && !warehouse_csrf_is_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')){
    $session->msg('d','Sesi aksi tidak valid atau sudah kedaluwarsa. Silakan coba kembali.');
    redirect('edit_user.php?id='.(int)$e_user['id'], false);
  }
  $current_group = find_by_groupLevel((int)$e_user['user_level']);
  $current_group_in_list = false;
  foreach($groups as $group){
    if((int)$group['group_level'] === (int)$e_user['user_level']){
      $current_group_in_list = true;
      break;
    }
  }
  if($current_group && !$current_group_in_list){
    $groups[] = $current_group;
  }
?>

<?php
//Update User basic info
  if(isset($_POST['update'])) {
    $req_fields = array('name','username','level');
    validate_fields($req_fields);
    if(empty($errors)){
             $id = (int)$e_user['id'];
           $name = remove_junk($db->escape($_POST['name']));
       $username = remove_junk($db->escape($_POST['username']));
          $level = (int)$db->escape($_POST['level']);
       $status   = remove_junk($db->escape($_POST['status']));
       $storage_rate = (isset($_POST['storage_rate']) && $_POST['storage_rate'] !== '') ? (float)$_POST['storage_rate'] : null;
       $storage_rate_value = $storage_rate !== null ? "'".$db->escape($storage_rate)."'" : "NULL";
       $result = false;
       try{
         $db->begin_transaction();
         $locked_result = $db->query_or_throw("SELECT id,user_level FROM users WHERE id='{$id}' LIMIT 1 FOR UPDATE");
         $locked_user = $db->fetch_assoc($locked_result);
         if(!$locked_user){ throw new RuntimeException('User no longer exists.'); }
         if($level !== (int)$locked_user['user_level'] && $level !== USER_LEVEL_CLIENT && user_has_inventory_ownership_history($id)){
           throw new RuntimeException('User still owns inventory history.');
         }
         $sql = "UPDATE users SET name ='{$name}', username ='{$username}',user_level='{$level}',status='{$status}',storage_rate={$storage_rate_value} WHERE id='{$id}'";
         $db->query_or_throw($sql);
         $db->commit();
         $result = true;
       } catch(Throwable $e){
         if($db->in_transaction()){ $db->rollback(); }
         error_log('[edit_user] account update failed: '.$e->getMessage());
       }
          if($result){
            $session->msg('s',"Akun berhasil diupdate ");
            redirect('edit_user.php?id='.(int)$e_user['id'], false);
          } else {
            $session->msg('d','Akun gagal diperbarui. Role client yang masih memiliki barang atau histori tidak dapat diubah menjadi non-client.');
            redirect('edit_user.php?id='.(int)$e_user['id'], false);
          }
    } else {
      $session->msg("d", $errors);
      redirect('edit_user.php?id='.(int)$e_user['id'],false);
    }
  }
?>
<?php
// Update user password
if(isset($_POST['update-pass'])) {
  $req_fields = array('password');
  validate_fields($req_fields);
  if(empty($errors)){
           $id = (int)$e_user['id'];
     $password = remove_junk($db->escape($_POST['password']));
     $h_pass   = sha1($password);
          $sql = "UPDATE users SET password='{$h_pass}' WHERE id='{$db->escape($id)}'";
       $result = $db->query($sql);
        if($result && $db->affected_rows() === 1){
          $session->msg('s',"Password user berhasil diupdate ");
          redirect('edit_user.php?id='.(int)$e_user['id'], false);
        } else {
          $session->msg('d',' Maaf, gagal mengupdate password user!');
          redirect('edit_user.php?id='.(int)$e_user['id'], false);
        }
  } else {
    $session->msg("d", $errors);
    redirect('edit_user.php?id='.(int)$e_user['id'],false);
  }
}

?>
<?php include_once('layouts/header.php'); ?>
 <div class="row">
   <div class="col-md-12"> <?php echo display_msg($msg); ?> </div>
  <div class="col-md-6">
     <div class="panel panel-default">
       <div class="panel-heading">
        <strong>
          <span class="glyphicon glyphicon-th"></span>
          Update Akun <?php echo remove_junk(ucwords($e_user['name'])); ?>
        </strong>
       </div>
       <div class="panel-body">
          <form method="post" action="edit_user.php?id=<?php echo (int)$e_user['id'];?>" class="clearfix">
            <?php echo warehouse_csrf_field(); ?>
            <div class="form-group">
                  <label for="name" class="control-label">Nama Lengkap</label>
                  <input type="name" class="form-control" name="name" value="<?php echo remove_junk(ucwords($e_user['name'])); ?>">
            </div>
            <div class="form-group">
                  <label for="username" class="control-label">Username</label>
                  <input type="text" class="form-control" name="username" value="<?php echo remove_junk(ucwords($e_user['username'])); ?>">
            </div>
            <div class="form-group">
              <label for="level">Role User</label>
                <select class="form-control" name="level">
                  <?php foreach ($groups as $group ):?>
                   <option <?php if((int)$group['group_level'] === (int)$e_user['user_level']) echo 'selected="selected"';?> value="<?php echo $group['group_level'];?>"><?php echo ucwords($group['group_name']);?></option>
                <?php endforeach;?>
                </select>
            </div>
            <div class="form-group">
              <label for="status">Status</label>
                <select class="form-control" name="status">
                  <option <?php if($e_user['status'] === '1') echo 'selected="selected"';?>value="1">Aktif</option>
                  <option <?php if($e_user['status'] === '0') echo 'selected="selected"';?> value="0">Nonaktif</option>
                </select>
            </div>
            <div class="form-group">
              <label for="storage_rate">Tarif Penyimpanan / Satuan / Bulan (khusus client, opsional)</label>
              <input type="number" min="0" step="1000" class="form-control" name="storage_rate" value="<?php echo (isset($e_user['storage_rate']) && $e_user['storage_rate'] !== null && $e_user['storage_rate'] !== '') ? remove_junk($e_user['storage_rate']) : ''; ?>" placeholder="Kosongkan = pakai tarif global">
            </div>
            <div class="form-group clearfix">
                    <button type="submit" name="update" class="btn btn-info">Update</button>
            </div>
        </form>
       </div>
     </div>
  </div>
  <!-- Change password form -->
  <div class="col-md-6">
    <div class="panel panel-default">
      <div class="panel-heading">
        <strong>
          <span class="glyphicon glyphicon-th"></span>
          Ubah Password <?php echo remove_junk(ucwords($e_user['name'])); ?>
        </strong>
      </div>
      <div class="panel-body">
        <form action="edit_user.php?id=<?php echo (int)$e_user['id'];?>" method="post" class="clearfix">
          <?php echo warehouse_csrf_field(); ?>
          <div class="form-group">
                <label for="password" class="control-label">Password</label>
                <input type="password" class="form-control" name="password" placeholder="Ketik password baru">
          </div>
          <div class="form-group clearfix">
                  <button type="submit" name="update-pass" class="btn btn-danger pull-right">Ubah</button>
          </div>
        </form>
      </div>
    </div>
  </div>

 </div>
<?php include_once('layouts/footer.php'); ?>
