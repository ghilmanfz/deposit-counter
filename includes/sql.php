<?php
  require_once('includes/load.php');

defined('USER_LEVEL_ADMIN') ?: define('USER_LEVEL_ADMIN', 1);
defined('USER_LEVEL_SPECIAL') ?: define('USER_LEVEL_SPECIAL', 2);
defined('USER_LEVEL_USER') ?: define('USER_LEVEL_USER', 3);
defined('USER_LEVEL_CLIENT') ?: define('USER_LEVEL_CLIENT', 4);

/*--------------------------------------------------------------*/
/* Function for find all database table rows by table name
/*--------------------------------------------------------------*/
function find_all($table) {
   global $db;
   if(tableExists($table))
   {
     return find_by_sql("SELECT * FROM ".$db->escape($table));
   }
}
/*--------------------------------------------------------------*/
/* Function for Perform queries
/*--------------------------------------------------------------*/
function find_by_sql($sql)
{
  global $db;
  $result = $db->query($sql);
  $result_set = $db->while_loop($result);
 return $result_set;
}
/*--------------------------------------------------------------*/
/*  Function for Find data from table by id
/*--------------------------------------------------------------*/
function find_by_id($table,$id)
{
  global $db;
  $id = (int)$id;
    if(tableExists($table)){
          $sql = $db->query("SELECT * FROM {$db->escape($table)} WHERE id='{$db->escape($id)}' LIMIT 1");
          if($result = $db->fetch_assoc($sql))
            return $result;
          else
            return null;
     }
}
/*--------------------------------------------------------------*/
/* Function for Delete data from table by id
/*--------------------------------------------------------------*/
function delete_by_id($table,$id)
{
  global $db;
  if(tableExists($table))
   {
    $sql = "DELETE FROM ".$db->escape($table);
    $sql .= " WHERE id=". $db->escape($id);
    $sql .= " LIMIT 1";
    $db->query($sql);
    return ($db->affected_rows() === 1) ? true : false;
   }
}
/*--------------------------------------------------------------*/
/* Function for Count id  By table name
/*--------------------------------------------------------------*/

function count_by_id($table){
  global $db;
  if(tableExists($table))
  {
    if($table === 'withdrawals'){
      $sql = "SELECT ((SELECT COUNT(id) FROM withdrawals) + (SELECT COUNT(id) FROM stock_movements WHERE movement_type='out' AND reference_type='surat_jalan')) AS total";
    } else {
      $sql = "SELECT COUNT(id) AS total FROM ".$db->escape($table);
    }
    $result = $db->query($sql);
     return($db->fetch_assoc($result));
  }
}
/*--------------------------------------------------------------*/
/* Count baris milik satu client (berdasarkan client_id).
/* Catatan: tabel withdrawals tidak punya client_id -> join via products.
/*--------------------------------------------------------------*/
function count_by_client_id($table, $client_id){
  global $db;
  $client_id = (int)$client_id;
  if(!tableExists($table)){ return array('total' => 0); }
  if($table === 'withdrawals'){
    $sql = "SELECT ((SELECT COUNT(w.id) FROM withdrawals w LEFT JOIN products p ON p.id=w.product_id WHERE p.client_id='{$client_id}') + (SELECT COUNT(sm.id) FROM stock_movements sm LEFT JOIN products p2 ON p2.id=sm.product_id WHERE sm.movement_type='out' AND sm.reference_type='surat_jalan' AND COALESCE(sm.client_id,p2.client_id)='{$client_id}')) AS total";
  } elseif(column_exists($table, 'client_id')){
    $sql = "SELECT COUNT(id) AS total FROM ".$db->escape($table)." WHERE client_id='{$client_id}'";
  } else {
    return array('total' => 0);
  }
  $result = $db->query($sql);
  $row = $db->fetch_assoc($result);
  return $row ? $row : array('total' => 0);
}
/*--------------------------------------------------------------*/
/* Determine if database table exists
/*--------------------------------------------------------------*/
function tableExists($table){
  global $db;
  $table_exit = $db->query('SHOW TABLES FROM '.DB_NAME.' LIKE "'.$db->escape($table).'"');
      if($table_exit) {
        if($db->num_rows($table_exit) > 0)
              return true;
         else
              return false;
      }
  }
 /*--------------------------------------------------------------*/
 /* Login with the data provided in $_POST,
 /* coming from the login form.
/*--------------------------------------------------------------*/
  function authenticate($username='', $password='') {
    global $db;
    $username = $db->escape($username);
    $password = $db->escape($password);
    $sql  = sprintf("SELECT id,username,password,user_level FROM users WHERE username ='%s' LIMIT 1", $username);
    $result = $db->query($sql);
    if($db->num_rows($result)){
      $user = $db->fetch_assoc($result);
      $password_request = sha1($password);
      if($password_request === $user['password'] ){
        return $user['id'];
      }
    }
   return false;
  }
  /*--------------------------------------------------------------*/
  /* Login with the data provided in $_POST,
  /* coming from the login_v2.php form.
  /* If you used this method then remove authenticate function.
 /*--------------------------------------------------------------*/
   function authenticate_v2($username='', $password='') {
     global $db;
     $username = $db->escape($username);
     $password = $db->escape($password);
     $sql  = sprintf("SELECT id,username,password,user_level FROM users WHERE username ='%s' LIMIT 1", $username);
     $result = $db->query($sql);
     if($db->num_rows($result)){
       $user = $db->fetch_assoc($result);
       $password_request = sha1($password);
       if($password_request === $user['password'] ){
         return $user;
       }
     }
    return false;
   }


  /*--------------------------------------------------------------*/
  /* Find current log in user by session id
  /*--------------------------------------------------------------*/
  function current_user(){
      static $current_user;
      global $db;
      if(!$current_user){
         if(isset($_SESSION['user_id'])):
             $user_id = intval($_SESSION['user_id']);
             $current_user = find_by_id('users',$user_id);
        endif;
      }
    return $current_user;
  }
  /*--------------------------------------------------------------*/
  /* Redirect user based on role
  /*--------------------------------------------------------------*/
  function redirect_by_user_level($user = null){
    if(!$user){
      $user = current_user();
    }

    if(!$user){
      redirect('index.php', false);
    }

    $user_level = (int)$user['user_level'];

    if($user_level === USER_LEVEL_ADMIN){
      redirect('admin.php', false);
    }

    if($user_level === USER_LEVEL_CLIENT){
      redirect('client_dashboard.php', false);
    }

    redirect('home.php', false);
  }
  /*--------------------------------------------------------------*/
  /* Find all user by
  /* Joining users table and user groups table
  /*--------------------------------------------------------------*/
  function find_all_user(){
      global $db;
      $sql = "SELECT u.id,u.name,u.username,u.user_level,u.status,u.last_login,";
      $sql .="g.group_name ";
      $sql .="FROM users u ";
      $sql .="LEFT JOIN user_groups g ";
      $sql .="ON g.group_level=u.user_level ORDER BY u.name ASC";
      $result = find_by_sql($sql);
      return $result;
  }
  /*--------------------------------------------------------------*/
  /* Function to update the last log in of a user
  /*--------------------------------------------------------------*/

 function updateLastLogIn($user_id)
	{
		global $db;
    $date = make_date();
    $sql = "UPDATE users SET last_login='{$date}' WHERE id ='{$user_id}' LIMIT 1";
    $result = $db->query($sql);
    return ($result && $db->affected_rows() === 1 ? true : false);
	}

  /*--------------------------------------------------------------*/
  /* Find all Group name
  /*--------------------------------------------------------------*/
  function find_by_groupName($val)
  {
    global $db;
    $sql = "SELECT group_name FROM user_groups WHERE group_name = '{$db->escape($val)}' LIMIT 1 ";
    $result = $db->query($sql);
    return($db->num_rows($result) === 0 ? true : false);
  }
  /*--------------------------------------------------------------*/
  /* Find group level
  /*--------------------------------------------------------------*/
  function find_by_groupLevel($level)
  {
    global $db;
    $sql = "SELECT * FROM user_groups WHERE group_level = '{$db->escape($level)}' LIMIT 1 ";
    $result = $db->query($sql);
    return $db->fetch_assoc($result);
  }
  /*--------------------------------------------------------------*/
  /* Function for checking which user level has access to page
  /*--------------------------------------------------------------*/
   function page_require_level($require_level){
     global $session;
     if (!$session->isUserLoggedIn(true)):
            $session->msg('d','Please login...');
            redirect('index.php', false);
      endif;

     $current_user = current_user();
     $login_level = find_by_groupLevel($current_user['user_level']);

     if(!$login_level):
           $session->msg('d','This user level is not registered.');
           redirect('home.php',false);
     elseif($login_level['group_status'] === '0'):
           $session->msg('d','This level user has been band!');
           redirect('home.php',false);
     elseif((int)$current_user['user_level'] <= (int)$require_level):
              return true;
      else:
            $session->msg("d", "Sorry! you dont have permission to view the page.");
            redirect('home.php', false);
        endif;

     }
   /*--------------------------------------------------------------*/
   /* User scope helpers
   /*--------------------------------------------------------------*/
  function is_client_user($user = null){
    if(!$user){
      $user = current_user();
    }

    return ($user && (int)$user['user_level'] === USER_LEVEL_CLIENT);
  }

  /*--------------------------------------------------------------*/
  /* Cek apakah user saat ini boleh mengakses halaman selevel $required_level.
  /* Dipakai untuk menyembunyikan item menu yang aksesnya ditolak.
  /* Aturan sama dengan page_require_level: level user <= required_level.
  /*--------------------------------------------------------------*/
  function menu_can($required_level){
    $user = current_user();
    return ($user && (int)$user['user_level'] <= (int)$required_level);
  }

  /*--------------------------------------------------------------*/
  /* Action-level RBAC helpers
  /*--------------------------------------------------------------*/
  function permission_actions(){
    return array(
      'view'    => 'Lihat',
      'create'  => 'Tambah',
      'update'  => 'Ubah',
      'delete'  => 'Hapus',
      'print'   => 'Cetak',
      'process' => 'Proses'
    );
  }

  function access_permission_modules(){
    return array(
      'satuan' => array(
        'label' => 'Satuan Barang',
        'actions' => array('view','create','update','delete')
      ),
      'barang' => array(
        'label' => 'Barang Titipan',
        'actions' => array('view','create','update','delete')
      ),
      'media' => array(
        'label' => 'Media',
        'actions' => array('view','create','delete')
      ),
      'transaksi' => array(
        'label' => 'Transaksi Barang',
        'actions' => array('view','create','update','delete')
      ),
      'pickup' => array(
        'label' => 'Request Pengambilan',
        'actions' => array('view','create','process')
      ),
      'penagihan' => array(
        'label' => 'Penagihan',
        'actions' => array('view','create','update','delete','print','process')
      ),
      'surat_jalan' => array(
        'label' => 'Surat Jalan',
        // Stok diproses dari Request Pengambilan. Surat Jalan hanya boleh
        // dilihat/dicetak agar konfigurasi role tidak menawarkan izin semu.
        'actions' => array('view','print')
      ),
      'laporan' => array(
        'label' => 'Laporan Barang',
        'actions' => array('view','print')
      ),
      'barang_saya' => array(
        'label' => 'Barang Saya',
        'actions' => array('view')
      )
    );
  }

  function admin_only_modules(){
    return array('kategori','konten','user_mgmt','role_mgmt','landing');
  }

  function role_is_internal_staff($level){
    $level = (int)$level;
    return ($level > 0 && $level !== USER_LEVEL_ADMIN && $level !== USER_LEVEL_CLIENT);
  }

  function role_is_protected($level){
    $level = (int)$level;
    return ($level === USER_LEVEL_ADMIN || $level === USER_LEVEL_CLIENT);
  }

  function find_all_roles(){
    ensure_warehouse_schema();
    return find_by_sql("SELECT * FROM user_groups ORDER BY group_level ASC");
  }

  function role_user_count($level){
    global $db;
    $level = (int)$level;
    $res = $db->query("SELECT COUNT(id) AS c FROM users WHERE user_level='{$level}'");
    $row = $res ? $db->fetch_assoc($res) : null;
    return $row ? (int)$row['c'] : 0;
  }

  function create_role($name){
    global $db;
    $name = remove_junk(trim($name));
    if($name === ''){ return false; }
    $safe_name = $db->escape($name);
    $dup = $db->query("SELECT id FROM user_groups WHERE group_name='{$safe_name}' LIMIT 1");
    if($dup && $db->num_rows($dup) > 0){ return false; }
    $res = $db->query("SELECT MAX(group_level) AS m FROM user_groups");
    $row = $res ? $db->fetch_assoc($res) : null;
    $next = ($row && $row['m'] !== null) ? ((int)$row['m'] + 1) : 5;
    if($next < 5){ $next = 5; }
    $sql = "INSERT INTO user_groups (group_name, group_level, group_status) VALUES ('{$safe_name}','{$next}','1')";
    return $db->query($sql) ? $next : false;
  }

  function rename_role($id, $name){
    global $db;
    $id = (int)$id;
    $name = remove_junk(trim($name));
    if($id <= 0 || $name === ''){ return false; }
    $safe_name = $db->escape($name);
    return $db->query("UPDATE user_groups SET group_name='{$safe_name}' WHERE id='{$id}' LIMIT 1");
  }

  function set_role_status($id, $status){
    global $db;
    $id = (int)$id;
    $status = ((int)$status === 1) ? 1 : 0;
    if($id <= 0){ return false; }
    return $db->query("UPDATE user_groups SET group_status='{$status}' WHERE id='{$id}' LIMIT 1");
  }

  function delete_role($id){
    global $db;
    $id = (int)$id;
    $role = find_by_id('user_groups', $id);
    if(!$role){ return 'notfound'; }
    $level = (int)$role['group_level'];
    if(role_is_protected($level)){ return 'protected'; }
    if(role_user_count($level) > 0){ return 'inuse'; }
    $db->query("DELETE FROM role_action_permissions WHERE role_level='{$level}'");
    return delete_by_id('user_groups', $id) ? 'ok' : 'fail';
  }

  function permission_action_is_valid($module_key, $action_key){
    $modules = access_permission_modules();
    return isset($modules[$module_key]) && in_array($action_key, $modules[$module_key]['actions'], true);
  }

  function role_action_permissions_map(){
    static $map = null;
    global $db;
    if($map === null){
      ensure_warehouse_schema();
      $map = array();
      $res = $db->query("SELECT role_level, module_key, action_key, allowed FROM role_action_permissions");
      if($res){
        while($row = $db->fetch_assoc($res)){
          $level = (int)$row['role_level'];
          $module = $row['module_key'];
          $action = $row['action_key'];
          if(!isset($map[$level])){ $map[$level] = array(); }
          if(!isset($map[$level][$module])){ $map[$level][$module] = array(); }
          $map[$level][$module][$action] = (int)$row['allowed'];
        }
      }
    }
    return $map;
  }

  function role_can_action($module_key, $action_key, $level = null){
    if($level === null){
      $user = current_user();
      $level = $user ? (int)$user['user_level'] : 0;
    }
    $level = (int)$level;
    if($level === USER_LEVEL_ADMIN){ return true; }
    if(in_array($module_key, admin_only_modules(), true)){ return false; }
    if(!permission_action_is_valid($module_key, $action_key)){ return false; }
    $map = role_action_permissions_map();
    return isset($map[$level][$module_key][$action_key]) && (int)$map[$level][$module_key][$action_key] === 1;
  }

  function role_can($module_key, $level = null){
    return role_can_action($module_key, 'view', $level);
  }

  function require_permission($module_key, $action_key = 'view'){
    global $session;
    if(!$session->isUserLoggedIn(true)){
      $session->msg('d','Silakan login...');
      redirect('index.php', false);
    }
    $current_user = current_user();
    $login_level = $current_user ? find_by_groupLevel($current_user['user_level']) : null;
    if(!$login_level){
      $session->msg('d','Level user tidak terdaftar.');
      redirect('home.php', false);
    }
    if((string)$login_level['group_status'] === '0'){
      $session->msg('d','Role user sedang nonaktif.');
      redirect('home.php', false);
    }
    if(!role_can_action($module_key, $action_key, (int)$current_user['user_level'])){
      $session->msg('d','Anda tidak memiliki hak akses untuk aksi ini.');
      redirect_by_user_level();
    }
    return true;
  }

  function require_module($module_key){
    return require_permission($module_key, 'view');
  }

  function require_login(){
    global $session;
    if(!$session->isUserLoggedIn(true)){
      $session->msg('d','Silakan login...');
      redirect('index.php', false);
    }
    return true;
  }

  function set_role_action_permission($level, $module_key, $action_key, $allowed){
    global $db;
    ensure_warehouse_schema();
    $level = (int)$level;
    if($level === USER_LEVEL_ADMIN || !permission_action_is_valid($module_key, $action_key)){ return false; }
    $module_key = $db->escape($module_key);
    $action_key = $db->escape($action_key);
    $allowed = ((int)$allowed === 1) ? 1 : 0;
    $sql = "INSERT INTO role_action_permissions (role_level, module_key, action_key, allowed) VALUES ('{$level}','{$module_key}','{$action_key}','{$allowed}') ON DUPLICATE KEY UPDATE allowed='{$allowed}'";
    return $db->query($sql);
  }

  function current_client_id($user = null){
    if(!$user){
      $user = current_user();
    }

    return is_client_user($user) ? (int)$user['id'] : null;
  }

  function find_active_clients(){
    global $db;
    $sql  = "SELECT id,name,username ";
    $sql .= "FROM users ";
    $sql .= "WHERE user_level='".USER_LEVEL_CLIENT."' AND status='1' ";
    $sql .= "ORDER BY name ASC";
    return find_by_sql($sql);
  }

  function find_product_details($product_id, $client_id = null){
    global $db;
    $product_id = (int)$product_id;
    $viewer_client_id = current_client_id();

    if($viewer_client_id !== null){
      $client_id = $viewer_client_id;
    }

    $sql  = "SELECT p.*,c.name AS categorie,m.file_name AS image,u.name AS client_name,un.name AS unit_name,bu.name AS base_unit_name ";
    $sql .= "FROM products p ";
    $sql .= "LEFT JOIN categories c ON c.id = p.categorie_id ";
    $sql .= "LEFT JOIN media m ON m.id = p.media_id ";
    $sql .= "LEFT JOIN users u ON u.id = p.client_id ";
    $sql .= "LEFT JOIN units un ON un.id = p.unit_id ";
    $sql .= "LEFT JOIN units bu ON bu.id = p.base_unit_id ";
    $sql .= "WHERE p.id='{$product_id}'";

    if($client_id !== null){
      $sql .= " AND p.client_id='".$db->escape((int)$client_id)."'";
    }

    $sql .= " LIMIT 1";
    $result = find_by_sql($sql);
    return empty($result) ? null : $result[0];
  }
   /*--------------------------------------------------------------*/
   /* Function for Finding all product name
   /* JOIN with categorie and media database table
   /*--------------------------------------------------------------*/
  function join_product_table($client_id = null){
     global $db;
     $viewer_client_id = current_client_id();

     if($viewer_client_id !== null){
       $client_id = $viewer_client_id;
     }

     $sql  =" SELECT p.id,p.name,p.quantity,p.buy_price,p.sale_price,p.client_id,p.unit_id,p.base_unit_id,p.media_id,p.date,";
     $sql .= "p.no_surat_jalan,p.no_batch,p.grade,p.tebal,p.lebar,p.panjang,p.m3,p.sj_scan,p.pcs_per_crate,";
     $sql .= "(SELECT COALESCE(SUM(quantity),0) FROM stock_movements sm WHERE sm.product_id=p.id AND sm.movement_type='out') AS total_out,";
     $sql .= "(SELECT MAX(created_at) FROM stock_movements sm WHERE sm.product_id=p.id AND sm.movement_type='out') AS last_out_date,";
     $sql .= " c.name AS categorie,m.file_name AS image,u.name AS client_name,un.name AS unit_name,bu.name AS base_unit_name";
     $sql .= " FROM products p";
    $sql  .=" LEFT JOIN categories c ON c.id = p.categorie_id";
    $sql  .=" LEFT JOIN media m ON m.id = p.media_id";
    $sql  .=" LEFT JOIN users u ON u.id = p.client_id";
    $sql  .=" LEFT JOIN units un ON un.id = p.unit_id";
    $sql  .=" LEFT JOIN units bu ON bu.id = p.base_unit_id";

    if($client_id !== null){
      $sql .= " WHERE p.client_id='".$db->escape((int)$client_id)."'";
    }

    $sql  .=" ORDER BY p.id DESC";
    return find_by_sql($sql);

   }
  /*--------------------------------------------------------------*/
  /* Function for Finding all product name
  /* Request coming from ajax.php for auto suggest
  /*--------------------------------------------------------------*/

   function find_product_by_title($product_name, $client_id = null){
     global $db;
     $viewer_client_id = current_client_id();
     $p_name = remove_junk($db->escape($product_name));
     if($viewer_client_id !== null){
       $client_id = $viewer_client_id;
     }

     $sql  = "SELECT p.id,p.name,p.quantity,p.client_id,p.unit_id,p.base_unit_id,u.name AS client_name,un.name AS unit_name,bu.name AS base_unit_name ";
     $sql .= "FROM products p ";
     $sql .= "LEFT JOIN users u ON u.id = p.client_id ";
     $sql .= "LEFT JOIN units un ON un.id = p.unit_id ";
     $sql .= "LEFT JOIN units bu ON bu.id = p.base_unit_id ";
     $sql .= "WHERE p.quantity > 0 AND p.name like '%$p_name%'";

     if($client_id !== null){
       $sql .= " AND p.client_id='".$db->escape((int)$client_id)."'";
     }

     $sql .= " ORDER BY p.name ASC LIMIT 5";
     $result = find_by_sql($sql);
     return $result;
   }

  /*--------------------------------------------------------------*/
  /* Function for Finding all product info by product title
  /* Request coming from ajax.php
  /*--------------------------------------------------------------*/
  function find_all_product_info_by_title($title, $client_id = null){
    global $db;
    $viewer_client_id = current_client_id();

    if($viewer_client_id !== null){
      $client_id = $viewer_client_id;
    }

    $sql  = "SELECT p.*,u.name AS client_name,un.name AS unit_name,bu.name AS base_unit_name FROM products p ";
    $sql .= "LEFT JOIN users u ON u.id = p.client_id ";
    $sql .= "LEFT JOIN units un ON un.id = p.unit_id ";
    $sql .= "LEFT JOIN units bu ON bu.id = p.base_unit_id ";
    $sql .= "WHERE p.name ='{$title}'";

    if($client_id !== null){
      $sql .= " AND p.client_id='".$db->escape((int)$client_id)."'";
    }

    $sql .= " LIMIT 1";
    return find_by_sql($sql);
  }

  /*--------------------------------------------------------------*/
  /* Function for find stock movement history
  /*--------------------------------------------------------------*/
  function find_stock_movements($limit = null, $client_id = null){
    global $db;
    $viewer_client_id = current_client_id();

    if($viewer_client_id !== null){
      $client_id = $viewer_client_id;
    }

    $sql  = "SELECT sm.id,sm.product_id,sm.client_id,sm.movement_type,sm.quantity,sm.unit_id,";
    $sql .= "sm.quantity_before,sm.quantity_after,sm.reference_type,sm.reference_id,";
    $sql .= "sm.note,sm.created_at,p.name AS product_name,u.name AS client_name,";
    $sql .= "actor.name AS created_by_name,un.name AS unit_name ";
    $sql .= "FROM stock_movements sm ";
    $sql .= "LEFT JOIN products p ON p.id = sm.product_id ";
    $sql .= "LEFT JOIN users u ON u.id = sm.client_id ";
    $sql .= "LEFT JOIN units un ON un.id = sm.unit_id ";
    $sql .= "LEFT JOIN users actor ON actor.id = sm.created_by";

    if($client_id !== null){
      $sql .= " WHERE sm.client_id='".$db->escape((int)$client_id)."'";
    }

    $sql .= " ORDER BY sm.created_at DESC, sm.id DESC";

    if($limit !== null){
      $sql .= " LIMIT ".$db->escape((int)$limit);
    }

    return find_by_sql($sql);
  }

  function find_client_inventory_summary($client_id){
    global $db;
    $client_id = (int)$client_id;
    $sql  = "SELECT COUNT(id) AS total_products, COALESCE(SUM(quantity), 0) AS total_stock ";
    $sql .= "FROM products WHERE client_id='".$db->escape($client_id)."'";
    $result = $db->query($sql);
    return $db->fetch_assoc($result);
  }

  function find_client_movement_summary($client_id){
    global $db;
    $client_id = (int)$client_id;
    $sql  = "SELECT ";
    $sql .= "COALESCE(SUM(CASE WHEN movement_type='in' THEN quantity ELSE 0 END), 0) AS total_in, ";
    $sql .= "COALESCE(SUM(CASE WHEN movement_type='out' THEN quantity ELSE 0 END), 0) AS total_out, ";
    $sql .= "COALESCE(SUM(CASE WHEN movement_type='adjustment' THEN quantity ELSE 0 END), 0) AS total_adjustment ";
    $sql .= "FROM stock_movements WHERE client_id='".$db->escape($client_id)."'";
    $result = $db->query($sql);
    return $db->fetch_assoc($result);
  }

  /*--------------------------------------------------------------*/
  /* Product quantity helpers
  /*--------------------------------------------------------------*/
  function change_product_quantity($product_id, $delta){
    global $db;
    ensure_warehouse_schema();
    $product_id = (int)$product_id;
    $delta = (int)$delta;
    if($product_id <= 0 || $delta === 0){ return false; }
    try{
      $db->begin_transaction();
      $product_result = $db->query_or_throw("SELECT * FROM products WHERE id='{$product_id}' LIMIT 1 FOR UPDATE");
      $product = $db->fetch_assoc($product_result);
      if(!$product){ $db->rollback(); return false; }
      // Legacy aggregate mutations must never race with bundle
      // initialization or consume stock already reserved by a request.
      $reserved_result = $db->query_or_throw("SELECT id,quantity FROM pickup_request_items WHERE product_id='{$product_id}' AND status='reserved' ORDER BY id ASC FOR UPDATE");
      $reserved_quantity = 0;
      while($reserved = $db->fetch_assoc($reserved_result)){ $reserved_quantity += (int)$reserved['quantity']; }
      $bundle_result = $db->query_or_throw("SELECT id FROM inventory_bundles WHERE product_id='{$product_id}' ORDER BY id ASC LIMIT 1 FOR UPDATE");
      if($db->fetch_assoc($bundle_result)){ $db->rollback(); return false; }
      $before = (int)$product['quantity'];
      $after = $before + $delta;
      if($after < 0 || ($delta < 0 && abs($delta) > $before - $reserved_quantity)){ $db->rollback(); return false; }
      $db->query_or_throw("UPDATE products SET quantity='{$after}' WHERE id='{$product_id}' LIMIT 1");
      if($db->affected_rows() !== 1){ throw new RuntimeException('Product quantity update conflict.'); }
      $db->commit();
      $product['quantity'] = $after;
      return array('product'=>$product,'before'=>$before,'after'=>$after,'delta'=>$delta);
    } catch(Throwable $e){
      if($db->in_transaction()){ $db->rollback(); }
      return false;
    }
  }

  function set_product_quantity($product_id, $new_quantity){
    global $db;
    ensure_warehouse_schema();
    $product_id = (int)$product_id;
    $new_quantity = (int)$new_quantity;
    if($product_id <= 0 || $new_quantity < 0){ return false; }
    $current = find_by_id('products', $product_id);
    if(!$current){ return false; }
    $delta = $new_quantity - (int)$current['quantity'];
    if($delta === 0){
      return array('product'=>$current,'before'=>$new_quantity,'after'=>$new_quantity,'delta'=>0);
    }
    return change_product_quantity($product_id, $delta);
  }

  /*--------------------------------------------------------------*/
  /* Function for Update product quantity
  /*--------------------------------------------------------------*/
  function update_product_qty($qty,$p_id){
    return change_product_quantity($p_id, (int)$qty * -1);
  }

  function increase_product_qty($qty,$p_id){
    return change_product_quantity($p_id, (int)$qty);
  }

  function record_stock_movement($product_id, $movement_type, $quantity, $quantity_before, $quantity_after, $options = array()){
    global $db;
    $product = find_by_id('products', (int)$product_id);

    if(!$product){
      return false;
    }

    $client_id = isset($options['client_id']) ? (int)$options['client_id'] : (int)$product['client_id'];
    $unit_id = isset($options['unit_id'])
      ? (int)$options['unit_id']
      : (!empty($product['base_unit_id']) ? (int)$product['base_unit_id'] : (isset($product['unit_id']) ? (int)$product['unit_id'] : 0));
    $reference_type = isset($options['reference_type']) ? $db->escape($options['reference_type']) : '';
    $reference_id = isset($options['reference_id']) ? (int)$options['reference_id'] : 0;
    $event_key = isset($options['event_key']) ? trim((string)$options['event_key']) : '';
    if(strlen($event_key) > 120){ $event_key = substr($event_key, 0, 120); }
    $event_key = $db->escape($event_key);
    $note = isset($options['note']) ? $db->escape($options['note']) : '';
    $created_by = isset($options['created_by']) ? (int)$options['created_by'] : 0;
    $created_at = isset($options['created_at']) && !empty($options['created_at'])
      ? $db->escape($options['created_at'])
      : make_date();

    if($created_by === 0){
      $user = current_user();
      $created_by = $user ? (int)$user['id'] : 0;
    }

    $client_value = $client_id > 0 ? "'{$client_id}'" : "NULL";
    $reference_type_value = $reference_type !== '' ? "'{$reference_type}'" : "NULL";
    $reference_id_value = $reference_id > 0 ? "'{$reference_id}'" : "NULL";
    $event_key_value = $event_key !== '' ? "'{$event_key}'" : "NULL";
    $note_value = $note !== '' ? "'{$note}'" : "NULL";
    $created_by_value = $created_by > 0 ? "'{$created_by}'" : "NULL";

    $sql  = "INSERT INTO stock_movements (";
    $sql .= "product_id,client_id,movement_type,quantity,unit_id,quantity_before,quantity_after,";
    $sql .= "reference_type,reference_id,event_key,note,created_by,created_at";
    $sql .= ") VALUES (";
    $unit_value = $unit_id > 0 ? "'{$unit_id}'" : "NULL";
    $sql .= "'".$db->escape((int)$product_id)."',{$client_value},'".$db->escape($movement_type)."',";
    $sql .= "'".$db->escape((int)$quantity)."',{$unit_value},'".$db->escape((int)$quantity_before)."',";
    $sql .= "'".$db->escape((int)$quantity_after)."',{$reference_type_value},{$reference_id_value},{$event_key_value},";
    $sql .= "{$note_value},{$created_by_value},'{$created_at}'";
    $sql .= ")";

    if($db->query($sql)){
      return $db->insert_id();
    }

    return false;
  }

  /*--------------------------------------------------------------*/
  /* Penagihan dan surat jalan untuk sistem penitipan barang
  /*--------------------------------------------------------------*/
  function ensure_consignment_tables(){
    static $done = false;
    global $db;
    if($done){ return true; }
    if($db->in_transaction()){
      throw new RuntimeException('Consignment schema must be initialized before starting a business transaction.');
    }

    $billings_sql  = "CREATE TABLE IF NOT EXISTS billings (";
    $billings_sql .= "id int(11) unsigned NOT NULL AUTO_INCREMENT,";
    $billings_sql .= "invoice_no varchar(50) NOT NULL,";
    $billings_sql .= "client_id int(11) unsigned DEFAULT NULL,";
    $billings_sql .= "product_id int(11) unsigned DEFAULT NULL,";
    $billings_sql .= "reference_type varchar(50) DEFAULT NULL,";
    $billings_sql .= "reference_id int(11) unsigned DEFAULT NULL,";
    $billings_sql .= "description varchar(255) NOT NULL,";
    $billings_sql .= "amount decimal(25,2) NOT NULL DEFAULT '0.00',";
    $billings_sql .= "issue_date date NOT NULL,";
    $billings_sql .= "due_date date NOT NULL,";
    $billings_sql .= "status varchar(20) NOT NULL DEFAULT 'belum_lunas',";
    $billings_sql .= "paid_date date DEFAULT NULL,";
    $billings_sql .= "note text DEFAULT NULL,";
    $billings_sql .= "created_by int(11) unsigned DEFAULT NULL,";
    $billings_sql .= "created_at datetime NOT NULL,";
    $billings_sql .= "PRIMARY KEY (id),";
    $billings_sql .= "UNIQUE KEY invoice_no (invoice_no),";
    $billings_sql .= "KEY client_id (client_id),";
    $billings_sql .= "KEY product_id (product_id),";
    $billings_sql .= "KEY due_date (due_date),";
    $billings_sql .= "KEY status (status)";
    $billings_sql .= ") ENGINE=InnoDB DEFAULT CHARSET=latin1";

    $delivery_sql  = "CREATE TABLE IF NOT EXISTS delivery_orders (";
    $delivery_sql .= "id int(11) unsigned NOT NULL AUTO_INCREMENT,";
    $delivery_sql .= "document_no varchar(50) NOT NULL,";
    $delivery_sql .= "movement_type varchar(10) NOT NULL,";
    $delivery_sql .= "client_id int(11) unsigned DEFAULT NULL,";
    $delivery_sql .= "product_id int(11) unsigned DEFAULT NULL,";
    $delivery_sql .= "quantity int(11) NOT NULL DEFAULT '0',";
    $delivery_sql .= "document_date date NOT NULL,";
    $delivery_sql .= "recipient varchar(100) DEFAULT NULL,";
    $delivery_sql .= "driver_name varchar(100) DEFAULT NULL,";
    $delivery_sql .= "vehicle_no varchar(50) DEFAULT NULL,";
    $delivery_sql .= "reference_type varchar(50) DEFAULT NULL,";
    $delivery_sql .= "reference_id int(11) unsigned DEFAULT NULL,";
    $delivery_sql .= "note text DEFAULT NULL,";
    $delivery_sql .= "created_by int(11) unsigned DEFAULT NULL,";
    $delivery_sql .= "created_at datetime NOT NULL,";
    $delivery_sql .= "PRIMARY KEY (id),";
    $delivery_sql .= "UNIQUE KEY document_no (document_no),";
    $delivery_sql .= "KEY client_id (client_id),";
    $delivery_sql .= "KEY product_id (product_id),";
    $delivery_sql .= "KEY document_date (document_date)";
    $delivery_sql .= ") ENGINE=InnoDB DEFAULT CHARSET=latin1";

    $db->query($billings_sql);
    $db->query($delivery_sql);
    if(function_exists('ensure_warehouse_schema')){ ensure_warehouse_schema(true); }
    $done = true;
    return true;
  }

  function generate_consignment_number($prefix){
    return $prefix . '-' . date('YmdHis') . '-' . strtoupper(randString(4));
  }

  function create_billing($data = array()){
    global $db;
    ensure_consignment_tables();

    $invoice_no = !empty($data['invoice_no'])
      ? $db->escape($data['invoice_no'])
      : $db->escape(generate_consignment_number('INV'));
    $client_id = isset($data['client_id']) ? (int)$data['client_id'] : 0;
    $product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
    $reference_type = isset($data['reference_type']) ? $db->escape($data['reference_type']) : '';
    $reference_id = isset($data['reference_id']) ? (int)$data['reference_id'] : 0;
    $description = !empty($data['description']) ? $db->escape($data['description']) : 'Penagihan penitipan barang';
    $amount = isset($data['amount']) ? (float)$data['amount'] : 0;
    $issue_date = !empty($data['issue_date']) ? $db->escape($data['issue_date']) : date('Y-m-d');
    $due_date = !empty($data['due_date']) ? $db->escape($data['due_date']) : $issue_date;
    $status = !empty($data['status']) ? $db->escape($data['status']) : 'belum_lunas';
    $paid_date = !empty($data['paid_date']) ? "'".$db->escape($data['paid_date'])."'" : "NULL";
    $note = !empty($data['note']) ? "'".$db->escape($data['note'])."'" : "NULL";
    $created_by = isset($data['created_by']) ? (int)$data['created_by'] : 0;
    $created_at = !empty($data['created_at']) ? $db->escape($data['created_at']) : make_date();

    if($created_by === 0){
      $user = current_user();
      $created_by = $user ? (int)$user['id'] : 0;
    }

    $client_value = $client_id > 0 ? "'{$client_id}'" : "NULL";
    $product_value = $product_id > 0 ? "'{$product_id}'" : "NULL";
    $reference_type_value = $reference_type !== '' ? "'{$reference_type}'" : "NULL";
    $reference_id_value = $reference_id > 0 ? "'{$reference_id}'" : "NULL";
    $created_by_value = $created_by > 0 ? "'{$created_by}'" : "NULL";

    $sql  = "INSERT INTO billings (";
    $sql .= "invoice_no,client_id,product_id,reference_type,reference_id,description,amount,";
    $sql .= "issue_date,due_date,status,paid_date,note,created_by,created_at";
    $sql .= ") VALUES (";
    $sql .= "'{$invoice_no}',{$client_value},{$product_value},{$reference_type_value},{$reference_id_value},";
    $sql .= "'{$description}','".$db->escape(number_format($amount, 2, '.', ''))."','{$issue_date}','{$due_date}',";
    $sql .= "'{$status}',{$paid_date},{$note},{$created_by_value},'{$created_at}'";
    $sql .= ")";

    if($db->query($sql)){
      return $db->insert_id();
    }

    return false;
  }

  function create_delivery_order($data = array()){
    global $db;
    ensure_consignment_tables();

    $document_no = !empty($data['document_no'])
      ? $db->escape($data['document_no'])
      : $db->escape(generate_consignment_number('SJ'));
    $movement_type = !empty($data['movement_type']) ? $db->escape($data['movement_type']) : 'out';
    $client_id = isset($data['client_id']) ? (int)$data['client_id'] : 0;
    $product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
    $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 0;
    $document_date = !empty($data['document_date']) ? $db->escape($data['document_date']) : date('Y-m-d');
    $recipient = !empty($data['recipient']) ? "'".$db->escape($data['recipient'])."'" : "NULL";
    $driver_name = !empty($data['driver_name']) ? "'".$db->escape($data['driver_name'])."'" : "NULL";
    $vehicle_no = !empty($data['vehicle_no']) ? "'".$db->escape($data['vehicle_no'])."'" : "NULL";
    $reference_type = isset($data['reference_type']) ? $db->escape($data['reference_type']) : '';
    $reference_id = isset($data['reference_id']) ? (int)$data['reference_id'] : 0;
    $pickup_request_id = isset($data['pickup_request_id']) ? (int)$data['pickup_request_id'] : 0;
    $scheduled_at = !empty($data['scheduled_at']) ? "'".$db->escape($data['scheduled_at'])."'" : "NULL";
    $created_by = isset($data['created_by']) ? (int)$data['created_by'] : 0;
    $created_at = !empty($data['created_at']) ? $db->escape($data['created_at']) : make_date();
    $stock_processed = isset($data['stock_processed']) ? (int)$data['stock_processed'] : (($movement_type === 'out' && $reference_type === 'request_pengambilan') ? 0 : 1);
    $stock_processed_at = $stock_processed === 1 ? "'".$db->escape($created_at)."'" : "NULL";
    $note = !empty($data['note']) ? "'".$db->escape($data['note'])."'" : "NULL";

    if($created_by === 0){
      $user = current_user();
      $created_by = $user ? (int)$user['id'] : 0;
    }

    $client_value = $client_id > 0 ? "'{$client_id}'" : "NULL";
    $product_value = $product_id > 0 ? "'{$product_id}'" : "NULL";
    $reference_type_value = $reference_type !== '' ? "'{$reference_type}'" : "NULL";
    $reference_id_value = $reference_id > 0 ? "'{$reference_id}'" : "NULL";
    $created_by_value = $created_by > 0 ? "'{$created_by}'" : "NULL";

    $sql  = "INSERT INTO delivery_orders (";
    $sql .= "document_no,movement_type,client_id,product_id,quantity,document_date,recipient,";
    $sql .= "driver_name,vehicle_no,reference_type,reference_id,pickup_request_id,scheduled_at,stock_processed,stock_processed_at,note,created_by,created_at";
    $sql .= ") VALUES (";
    $sql .= "'{$document_no}','{$movement_type}',{$client_value},{$product_value},'".$db->escape($quantity)."',";
    $sql .= "'{$document_date}',{$recipient},{$driver_name},{$vehicle_no},{$reference_type_value},";
    $pickup_request_value = $pickup_request_id > 0 ? "'{$pickup_request_id}'" : "NULL";
    $sql .= "{$reference_id_value},{$pickup_request_value},{$scheduled_at},'{$stock_processed}',{$stock_processed_at},{$note},{$created_by_value},'{$created_at}'";
    $sql .= ")";

    if($db->query($sql)){
      return $db->insert_id();
    }

    return false;
  }

  function find_all_billings($client_id = null){
    global $db;
    ensure_consignment_tables();
    $viewer_client_id = current_client_id();

    if($viewer_client_id !== null){
      $client_id = $viewer_client_id;
    }

    $sql  = "SELECT b.*,u.name AS client_name,p.name AS product_name,actor.name AS created_by_name ";
    $sql .= "FROM billings b ";
    $sql .= "LEFT JOIN users u ON u.id = b.client_id ";
    $sql .= "LEFT JOIN products p ON p.id = b.product_id ";
    $sql .= "LEFT JOIN users actor ON actor.id = b.created_by";

    if($client_id !== null){
      $sql .= " WHERE b.client_id='".$db->escape((int)$client_id)."'";
    }

    $sql .= " ORDER BY b.id DESC";
    return find_by_sql($sql);
  }

  function find_billing_details($billing_id, $client_id = null){
    global $db;
    ensure_consignment_tables();
    $billing_id = (int)$billing_id;
    $viewer_client_id = current_client_id();

    if($viewer_client_id !== null){
      $client_id = $viewer_client_id;
    }

    $sql  = "SELECT b.*,u.name AS client_name,u.username AS client_username,p.name AS product_name,";
    $sql .= "actor.name AS created_by_name ";
    $sql .= "FROM billings b ";
    $sql .= "LEFT JOIN users u ON u.id = b.client_id ";
    $sql .= "LEFT JOIN products p ON p.id = b.product_id ";
    $sql .= "LEFT JOIN users actor ON actor.id = b.created_by ";
    $sql .= "WHERE b.id='".$db->escape($billing_id)."'";

    if($client_id !== null){
      $sql .= " AND b.client_id='".$db->escape((int)$client_id)."'";
    }

    $sql .= " LIMIT 1";
    $result = find_by_sql($sql);
    return empty($result) ? null : $result[0];
  }

  function update_billing_status($billing_id, $status, $paid_date = null){
    global $db;
    ensure_consignment_tables();
    $billing_id = (int)$billing_id;
    $status = $db->escape($status);
    $paid_value = $paid_date ? "'".$db->escape($paid_date)."'" : "NULL";

    $sql  = "UPDATE billings SET status='{$status}', paid_date={$paid_value} ";
    $sql .= "WHERE id='".$db->escape($billing_id)."' LIMIT 1";
    return $db->query($sql);
  }

  function find_all_delivery_orders($client_id = null){
    global $db;
    ensure_consignment_tables();
    $viewer_client_id = current_client_id();

    if($viewer_client_id !== null){
      $client_id = $viewer_client_id;
    }

    $sql  = "SELECT d.*,u.name AS client_name,p.name AS product_name,un.name AS unit_name,bu.name AS base_unit_name,actor.name AS created_by_name,r.request_no,r.fulfillment_method,r.delivery_address ";
    $sql .= "FROM delivery_orders d ";
    $sql .= "LEFT JOIN users u ON u.id = d.client_id ";
    $sql .= "LEFT JOIN products p ON p.id = d.product_id ";
    $sql .= "LEFT JOIN units un ON un.id = p.unit_id ";
    $sql .= "LEFT JOIN units bu ON bu.id = p.base_unit_id ";
    $sql .= "LEFT JOIN users actor ON actor.id = d.created_by ";
    $sql .= "LEFT JOIN pickup_requests r ON r.id = d.pickup_request_id";

    if($client_id !== null){
      $sql .= " WHERE d.client_id='".$db->escape((int)$client_id)."'";
    }

    $sql .= " ORDER BY d.document_date DESC, d.id DESC";
    return find_by_sql($sql);
  }

  function find_delivery_order_details($delivery_id, $client_id = null){
    global $db;
    ensure_consignment_tables();
    $delivery_id = (int)$delivery_id;
    $viewer_client_id = current_client_id();

    if($viewer_client_id !== null){
      $client_id = $viewer_client_id;
    }

    $sql  = "SELECT d.*,u.name AS client_name,u.username AS client_username,p.name AS product_name,p.grade,p.no_batch,p.tebal,p.lebar,p.panjang,un.name AS unit_name,bu.name AS base_unit_name,";
    $sql .= "actor.name AS created_by_name,r.request_no,r.fulfillment_method,r.delivery_address ";
    $sql .= "FROM delivery_orders d ";
    $sql .= "LEFT JOIN users u ON u.id = d.client_id ";
    $sql .= "LEFT JOIN products p ON p.id = d.product_id ";
    $sql .= "LEFT JOIN units un ON un.id = p.unit_id ";
    $sql .= "LEFT JOIN units bu ON bu.id = p.base_unit_id ";
    $sql .= "LEFT JOIN users actor ON actor.id = d.created_by ";
    $sql .= "LEFT JOIN pickup_requests r ON r.id = d.pickup_request_id ";
    $sql .= "WHERE d.id='".$db->escape($delivery_id)."'";

    if($client_id !== null){
      $sql .= " AND d.client_id='".$db->escape((int)$client_id)."'";
    }

    $sql .= " LIMIT 1";
    $result = find_by_sql($sql);
    return empty($result) ? null : $result[0];
  }
  /*--------------------------------------------------------------*/
  /* Function for Display Recent product Added
  /*--------------------------------------------------------------*/
 function find_recent_product_added($limit, $client_id = null){
   global $db;
   $viewer_client_id = current_client_id();

   if($viewer_client_id !== null){
     $client_id = $viewer_client_id;
   }

  $sql   = " SELECT p.id,p.name,p.quantity,p.sale_price,p.client_id,p.unit_id,p.media_id,p.date,c.name AS categorie,";
   $sql  .= "m.file_name AS image,u.name AS client_name,un.name AS unit_name FROM products p";
   $sql  .= " LEFT JOIN categories c ON c.id = p.categorie_id";
   $sql  .= " LEFT JOIN media m ON m.id = p.media_id";
   $sql  .= " LEFT JOIN users u ON u.id = p.client_id";
   $sql  .= " LEFT JOIN units un ON un.id = p.unit_id";

   if($client_id !== null){
     $sql .= " WHERE p.client_id='".$db->escape((int)$client_id)."'";
   }

   $sql  .= " ORDER BY p.id DESC LIMIT ".$db->escape((int)$limit);
   return find_by_sql($sql);
 }
 function withdrawal_activity_source_sql($client_id = null){
   $client_id = $client_id !== null ? (int)$client_id : null;
   $legacy_filter = $client_id !== null ? " AND p.client_id='{$client_id}'" : '';
   $bundle_filter = $client_id !== null ? " AND COALESCE(sm.client_id,p.client_id)='{$client_id}'" : '';
   $legacy  = "SELECT s.id AS id,'legacy' AS source_type,s.id AS source_id,s.product_id,s.qty,s.price,DATE(s.date) AS date,CONCAT(DATE(s.date),' 00:00:00') AS activity_at,p.name,p.sale_price,p.buy_price,p.client_id,u.name AS client_name,bu.name AS unit_name,NULL AS delivery_id ";
   $legacy .= "FROM withdrawals s LEFT JOIN products p ON p.id=s.product_id LEFT JOIN users u ON u.id=p.client_id LEFT JOIN units bu ON bu.id=p.base_unit_id WHERE 1=1{$legacy_filter}";
   $bundle  = "SELECT sm.id AS id,'bundle' AS source_type,sm.id AS source_id,sm.product_id,sm.quantity AS qty,0 AS price,DATE(sm.created_at) AS date,sm.created_at AS activity_at,p.name,p.sale_price,p.buy_price,COALESCE(sm.client_id,p.client_id) AS client_id,u.name AS client_name,un.name AS unit_name,sm.reference_id AS delivery_id ";
   $bundle .= "FROM stock_movements sm LEFT JOIN products p ON p.id=sm.product_id LEFT JOIN users u ON u.id=COALESCE(sm.client_id,p.client_id) LEFT JOIN units un ON un.id=sm.unit_id WHERE sm.movement_type='out' AND sm.reference_type='surat_jalan'{$bundle_filter}";
   return $legacy." UNION ALL ".$bundle;
 }

 /*--------------------------------------------------------------*/
 /* Function for Find Highest selling Product
 /*--------------------------------------------------------------*/
 function find_higest_saleing_product($limit, $client_id = null){
   global $db;
   $viewer_client_id = current_client_id();

   if($viewer_client_id !== null){
     $client_id = $viewer_client_id;
   }

   $source = withdrawal_activity_source_sql($client_id);
   $sql  = "SELECT a.name,COUNT(a.product_id) AS totalSold,SUM(a.qty) AS totalQty FROM ({$source}) a";
   $sql .= " GROUP BY a.product_id,a.name ORDER BY SUM(a.qty) DESC LIMIT ".$db->escape((int)$limit);
   return $db->query($sql);
 }
 /*--------------------------------------------------------------*/
 /* Function for find all sales
 /*--------------------------------------------------------------*/
 function find_all_sale($client_id = null){
   global $db;
   $viewer_client_id = current_client_id();

   if($viewer_client_id !== null){
     $client_id = $viewer_client_id;
   }

   $source = withdrawal_activity_source_sql($client_id);
   $sql = "SELECT a.* FROM ({$source}) a ORDER BY a.activity_at DESC,a.source_id DESC";
   return find_by_sql($sql);
 }
 /*--------------------------------------------------------------*/
 /* Function for Display Recent sale
 /*--------------------------------------------------------------*/
function find_recent_sale_added($limit, $client_id = null){
  global $db;
  $viewer_client_id = current_client_id();

  if($viewer_client_id !== null){
    $client_id = $viewer_client_id;
  }

  $source = withdrawal_activity_source_sql($client_id);
  $sql = "SELECT a.* FROM ({$source}) a ORDER BY a.activity_at DESC,a.source_id DESC LIMIT ".$db->escape((int)$limit);
  return find_by_sql($sql);
}
/*--------------------------------------------------------------*/
/* Function for Generate sales report by two dates
/*--------------------------------------------------------------*/
function find_sale_by_dates($start_date,$end_date){
  global $db;
  $start_date  = date("Y-m-d", strtotime($start_date));
  $end_date    = date("Y-m-d", strtotime($end_date));
  $source = withdrawal_activity_source_sql();
  $sql  = "SELECT a.date,a.name,a.unit_name,a.sale_price,a.buy_price,";
  $sql .= "COUNT(a.product_id) AS total_records,SUM(a.qty) AS total_sales,";
  $sql .= "SUM(a.sale_price*a.qty) AS total_saleing_price,SUM(a.buy_price*a.qty) AS total_buying_price ";
  $sql .= "FROM ({$source}) a WHERE a.date BETWEEN '{$start_date}' AND '{$end_date}' ";
  $sql .= "GROUP BY a.date,a.product_id,a.name,a.unit_name,a.sale_price,a.buy_price ORDER BY a.date DESC";
  return $db->query($sql);
}
/*--------------------------------------------------------------*/
/* Function for Generate Daily sales report
/*--------------------------------------------------------------*/
function  dailySales($year,$month){
  global $db;
  $year = (int)$year;
  $month = str_pad((int)$month, 2, '0', STR_PAD_LEFT);
  $source = withdrawal_activity_source_sql();
  $sql  = "SELECT SUM(a.qty) AS qty,DATE_FORMAT(a.date,'%Y-%m-%e') AS date,a.name,a.unit_name,";
  $sql .= "SUM(a.sale_price*a.qty) AS total_saleing_price FROM ({$source}) a ";
  $sql .= "WHERE DATE_FORMAT(a.date,'%Y-%m')='{$year}-{$month}' ";
  $sql .= "GROUP BY DATE_FORMAT(a.date,'%Y-%m-%e'),a.product_id,a.name,a.unit_name";
  return find_by_sql($sql);
}
/*--------------------------------------------------------------*/
/* Function for Generate Monthly sales report
/*--------------------------------------------------------------*/
function  monthlySales($year){
  global $db;
  $year = (int)$year;
  $source = withdrawal_activity_source_sql();
  $sql  = "SELECT SUM(a.qty) AS qty,DATE_FORMAT(a.date,'%Y-%m-%e') AS date,a.name,a.unit_name,";
  $sql .= "SUM(a.sale_price*a.qty) AS total_saleing_price FROM ({$source}) a ";
  $sql .= "WHERE DATE_FORMAT(a.date,'%Y')='{$year}' ";
  $sql .= "GROUP BY DATE_FORMAT(a.date,'%Y-%m-%e'),a.product_id,a.name,a.unit_name ";
  $sql .= "ORDER BY DATE_FORMAT(a.date,'%Y-%m-%e') ASC";
  return find_by_sql($sql);
}



/*--------------------------------------------------------------*/
/* Schema and feature helpers for penitipan barang client/admin
/*--------------------------------------------------------------*/
function column_exists($table, $column){
  global $db;
  $table = $db->escape($table);
  $column = $db->escape($column);
  $result = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
  return ($result && $db->num_rows($result) > 0);
}

function index_exists($table, $index_name){
  global $db;
  $table = $db->escape($table);
  $index_name = $db->escape($index_name);
  $result = $db->query("SHOW INDEX FROM `{$table}` WHERE Key_name='{$index_name}'");
  return ($result && $db->num_rows($result) > 0);
}

function foreign_key_exists($table, $constraint_name){
  global $db;
  $table = $db->escape($table);
  $constraint_name = $db->escape($constraint_name);
  $schema = $db->escape(DB_NAME);
  $result = $db->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA='{$schema}' AND TABLE_NAME='{$table}' AND CONSTRAINT_NAME='{$constraint_name}' AND CONSTRAINT_TYPE='FOREIGN KEY' LIMIT 1");
  return ($result && $db->num_rows($result) > 0);
}

function foreign_key_delete_rule($table, $constraint_name){
  global $db;
  $table = $db->escape($table);
  $constraint_name = $db->escape($constraint_name);
  $schema = $db->escape(DB_NAME);
  $result = $db->query("SELECT DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA='{$schema}' AND TABLE_NAME='{$table}' AND CONSTRAINT_NAME='{$constraint_name}' LIMIT 1");
  $row = $result ? $db->fetch_assoc($result) : null;
  return $row ? strtoupper($row['DELETE_RULE']) : null;
}

function ensure_index_safe($table, $index_name, $definition){
  global $db;
  if(!tableExists($table) || index_exists($table, $index_name)){ return true; }
  return (bool)$db->query_safe("ALTER TABLE `".$db->escape($table)."` ADD ".$definition);
}

/* Best-effort for legacy databases. RESTRICT preserves history; no cascade
 * is installed for request, bundle, delivery, or movement snapshots. */
function ensure_foreign_key_safe($table, $constraint_name, $definition){
  global $db;
  if(!tableExists($table) || foreign_key_exists($table, $constraint_name)){ return true; }
  return (bool)$db->query_safe("ALTER TABLE `".$db->escape($table)."` ADD CONSTRAINT `".$db->escape($constraint_name)."` ".$definition);
}

function migrate_foreign_key_to_restrict($table, $legacy_name, $restrict_name, $column, $referenced_table, $referenced_column='id'){
  global $db;
  foreach(array($table,$legacy_name,$restrict_name,$column,$referenced_table,$referenced_column) as $identifier){
    if(!preg_match('/^[A-Za-z0-9_]+$/', (string)$identifier)){ return false; }
  }
  if(!tableExists($table) || !tableExists($referenced_table)){ return true; }
  if(foreign_key_exists($table, $legacy_name)){
    if(foreign_key_delete_rule($table, $legacy_name) === 'RESTRICT'){ return true; }
    if(foreign_key_exists($table, $restrict_name) && foreign_key_delete_rule($table, $restrict_name) === 'RESTRICT'){
      return (bool)$db->query_safe("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$legacy_name}`");
    }
    return (bool)$db->query_safe("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$legacy_name}`, ADD CONSTRAINT `{$restrict_name}` FOREIGN KEY (`{$column}`) REFERENCES `{$referenced_table}` (`{$referenced_column}`) ON DELETE RESTRICT ON UPDATE RESTRICT");
  }
  if(foreign_key_exists($table, $restrict_name)){
    return foreign_key_delete_rule($table, $restrict_name) === 'RESTRICT';
  }
  return ensure_foreign_key_safe($table, $restrict_name, "FOREIGN KEY (`{$column}`) REFERENCES `{$referenced_table}` (`{$referenced_column}`) ON DELETE RESTRICT ON UPDATE RESTRICT");
}

function ensure_upload_directory($dir){
  if(!is_dir($dir)){
    @mkdir($dir, 0775, true);
  }
  return is_dir($dir) && is_writable($dir);
}

function ensure_warehouse_schema($force = false){
  static $done = false;
  global $db;
  if($done && !$force){ return true; }

  $db->query("CREATE TABLE IF NOT EXISTS units (
    id int(11) unsigned NOT NULL AUTO_INCREMENT,
    name varchar(60) NOT NULL,
    description varchar(255) DEFAULT NULL,
    created_at datetime NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY name (name)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3");

  $default_units = array('unit','dus','krat','lembar','palet');
  foreach($default_units as $unit){
    $safe = $db->escape($unit);
    $db->query("INSERT IGNORE INTO units (name,description,created_at) VALUES ('{$safe}','Satuan default sistem','".make_date()."')");
  }

  if(tableExists('products') && !column_exists('products','unit_id')){
    $db->query("ALTER TABLE products ADD unit_id int(11) unsigned DEFAULT NULL AFTER client_id");
    $db->query("UPDATE products SET unit_id=(SELECT id FROM units WHERE name='unit' LIMIT 1) WHERE unit_id IS NULL");
  }

  if(tableExists('stock_movements') && !column_exists('stock_movements','unit_id')){
    $db->query("ALTER TABLE stock_movements ADD unit_id int(11) unsigned DEFAULT NULL AFTER quantity");
    $db->query("UPDATE stock_movements sm LEFT JOIN products p ON p.id=sm.product_id SET sm.unit_id=p.unit_id WHERE sm.unit_id IS NULL");
  }

  $db->query("CREATE TABLE IF NOT EXISTS product_defects (
    id int(11) unsigned NOT NULL AUTO_INCREMENT,
    product_id int(11) unsigned NOT NULL,
    client_id int(11) unsigned DEFAULT NULL,
    defect_qty int(11) NOT NULL DEFAULT '0',
    note text DEFAULT NULL,
    created_by int(11) unsigned DEFAULT NULL,
    created_at datetime NOT NULL,
    PRIMARY KEY (id),
    KEY product_id (product_id),
    KEY client_id (client_id),
    KEY created_by (created_by)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3");

  $db->query("CREATE TABLE IF NOT EXISTS product_defect_photos (
    id int(11) unsigned NOT NULL AUTO_INCREMENT,
    defect_id int(11) unsigned NOT NULL,
    file_name varchar(255) NOT NULL,
    file_type varchar(100) DEFAULT NULL,
    created_at datetime NOT NULL,
    PRIMARY KEY (id),
    KEY defect_id (defect_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3");

  $db->query("CREATE TABLE IF NOT EXISTS pickup_requests (
    id int(11) unsigned NOT NULL AUTO_INCREMENT,
    request_no varchar(50) NOT NULL,
    client_id int(11) unsigned NOT NULL,
    product_id int(11) unsigned NOT NULL,
    unit_id int(11) unsigned DEFAULT NULL,
    quantity int(11) NOT NULL,
    fulfillment_method varchar(20) NOT NULL DEFAULT 'self_pickup',
    pickup_date date NOT NULL,
    pickup_time time NOT NULL,
    driver_name varchar(100) NOT NULL,
    vehicle_no varchar(50) NOT NULL,
    delivery_address text DEFAULT NULL,
    status varchar(30) NOT NULL DEFAULT 'pending',
    admin_note text DEFAULT NULL,
    processed_by int(11) unsigned DEFAULT NULL,
    processed_at datetime DEFAULT NULL,
    created_at datetime NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY request_no (request_no),
    KEY client_id (client_id),
    KEY product_id (product_id),
    KEY unit_id (unit_id),
    KEY status (status)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3");

  if(tableExists('pickup_requests')){
    if(!column_exists('pickup_requests','fulfillment_method')){
      $db->query_safe("ALTER TABLE pickup_requests ADD fulfillment_method varchar(20) NOT NULL DEFAULT 'self_pickup' AFTER quantity");
    }
    if(!column_exists('pickup_requests','delivery_address')){
      $db->query_safe("ALTER TABLE pickup_requests ADD delivery_address text DEFAULT NULL AFTER vehicle_no");
    }
  }

  if(tableExists('products') && !column_exists('products','base_unit_id')){
    // Nullable by design: historical rows are never guessed or converted.
    $db->query_safe("ALTER TABLE products ADD base_unit_id int(11) unsigned DEFAULT NULL AFTER unit_id");
  }

  $db->query("CREATE TABLE IF NOT EXISTS inventory_bundles (
    id int(11) unsigned NOT NULL AUTO_INCREMENT,
    bundle_no varchar(80) NOT NULL,
    product_id int(11) unsigned NOT NULL,
    client_id int(11) unsigned DEFAULT NULL,
    package_unit_id int(11) unsigned DEFAULT NULL,
    base_unit_id int(11) unsigned NOT NULL,
    quantity int(11) NOT NULL,
    status varchar(20) NOT NULL DEFAULT 'available',
    reserved_request_id int(11) unsigned DEFAULT NULL,
    reserved_at datetime DEFAULT NULL,
    out_delivery_order_id int(11) unsigned DEFAULT NULL,
    out_at datetime DEFAULT NULL,
    created_by int(11) unsigned DEFAULT NULL,
    created_at datetime NOT NULL,
    updated_at datetime NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY bundle_no (bundle_no),
    KEY idx_bundle_product_status (product_id,status),
    KEY idx_bundle_client_status (client_id,status),
    KEY idx_bundle_reserved_request (reserved_request_id),
    KEY idx_bundle_out_delivery (out_delivery_order_id),
    KEY idx_bundle_base_unit (base_unit_id),
    KEY idx_bundle_package_unit (package_unit_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3");

  $db->query("CREATE TABLE IF NOT EXISTS pickup_request_items (
    id int(11) unsigned NOT NULL AUTO_INCREMENT,
    pickup_request_id int(11) unsigned NOT NULL,
    bundle_id int(11) unsigned DEFAULT NULL,
    product_id int(11) unsigned NOT NULL,
    base_unit_id int(11) unsigned DEFAULT NULL,
    package_unit_id int(11) unsigned DEFAULT NULL,
    quantity int(11) NOT NULL,
    bundle_no varchar(80) DEFAULT NULL,
    product_name varchar(255) DEFAULT NULL,
    no_surat_jalan varchar(100) DEFAULT NULL,
    no_batch varchar(100) DEFAULT NULL,
    grade varchar(20) DEFAULT NULL,
    tebal decimal(10,2) DEFAULT NULL,
    lebar decimal(10,2) DEFAULT NULL,
    panjang decimal(10,2) DEFAULT NULL,
    status varchar(20) NOT NULL DEFAULT 'reserved',
    created_at datetime NOT NULL,
    updated_at datetime NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pickup_item_bundle (pickup_request_id,bundle_id),
    KEY idx_pickup_item_request_status (pickup_request_id,status),
    KEY idx_pickup_item_product_status (product_id,status),
    KEY idx_pickup_item_bundle (bundle_id),
    KEY idx_pickup_item_base_unit (base_unit_id),
    KEY idx_pickup_item_package_unit (package_unit_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3");

  $db->query("CREATE TABLE IF NOT EXISTS delivery_order_items (
    id int(11) unsigned NOT NULL AUTO_INCREMENT,
    delivery_order_id int(11) unsigned NOT NULL,
    pickup_request_item_id int(11) unsigned DEFAULT NULL,
    bundle_id int(11) unsigned DEFAULT NULL,
    product_id int(11) unsigned NOT NULL,
    base_unit_id int(11) unsigned DEFAULT NULL,
    package_unit_id int(11) unsigned DEFAULT NULL,
    quantity int(11) NOT NULL,
    bundle_no varchar(80) DEFAULT NULL,
    product_name varchar(255) DEFAULT NULL,
    no_surat_jalan varchar(100) DEFAULT NULL,
    no_batch varchar(100) DEFAULT NULL,
    grade varchar(20) DEFAULT NULL,
    tebal decimal(10,2) DEFAULT NULL,
    lebar decimal(10,2) DEFAULT NULL,
    panjang decimal(10,2) DEFAULT NULL,
    status varchar(20) NOT NULL DEFAULT 'ready',
    processed_at datetime DEFAULT NULL,
    created_at datetime NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_delivery_item_bundle (delivery_order_id,bundle_id),
    UNIQUE KEY uq_delivery_pickup_item (pickup_request_item_id),
    KEY idx_delivery_item_order_status (delivery_order_id,status),
    KEY idx_delivery_item_product (product_id),
    KEY idx_delivery_item_bundle (bundle_id),
    KEY idx_delivery_item_base_unit (base_unit_id),
    KEY idx_delivery_item_package_unit (package_unit_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3");

  // Safe additive migration for installations that created an earlier bundle
  // schema revision. Snapshot columns are not backfilled automatically.
  $item_snapshot_columns = array(
    'package_unit_id' => "ADD package_unit_id int(11) unsigned DEFAULT NULL AFTER base_unit_id",
    'product_name' => "ADD product_name varchar(255) DEFAULT NULL AFTER bundle_no",
    'no_surat_jalan' => "ADD no_surat_jalan varchar(100) DEFAULT NULL AFTER product_name",
    'no_batch' => "ADD no_batch varchar(100) DEFAULT NULL AFTER no_surat_jalan",
    'grade' => "ADD grade varchar(20) DEFAULT NULL AFTER no_batch",
    'tebal' => "ADD tebal decimal(10,2) DEFAULT NULL AFTER grade",
    'lebar' => "ADD lebar decimal(10,2) DEFAULT NULL AFTER tebal",
    'panjang' => "ADD panjang decimal(10,2) DEFAULT NULL AFTER lebar"
  );
  foreach(array('pickup_request_items','delivery_order_items') as $item_table){
    foreach($item_snapshot_columns as $column => $ddl){
      if(!column_exists($item_table, $column)){ $db->query_safe("ALTER TABLE {$item_table} {$ddl}"); }
    }
  }

  if(tableExists('delivery_orders')){
    if(!column_exists('delivery_orders','pickup_request_id')){
      $db->query_safe("ALTER TABLE delivery_orders ADD pickup_request_id int(11) unsigned DEFAULT NULL AFTER reference_id");
    }
    if(!column_exists('delivery_orders','scheduled_at')){
      $db->query_safe("ALTER TABLE delivery_orders ADD scheduled_at datetime DEFAULT NULL AFTER pickup_request_id");
    }
    if(!column_exists('delivery_orders','stock_processed')){
      $db->query_safe("ALTER TABLE delivery_orders ADD stock_processed tinyint(1) NOT NULL DEFAULT '1' AFTER scheduled_at");
    }
    if(!column_exists('delivery_orders','stock_processed_at')){
      $db->query_safe("ALTER TABLE delivery_orders ADD stock_processed_at datetime DEFAULT NULL AFTER stock_processed");
    }

    // Do not delete or rewrite duplicate legacy rows. Install the guard only
    // when the existing data is clean; transaction row locks still protect
    // every new approval on a dirty legacy database.
    if(!index_exists('delivery_orders','uq_delivery_pickup_request')){
      $duplicate_pickups = $db->query("SELECT pickup_request_id FROM delivery_orders WHERE pickup_request_id IS NOT NULL GROUP BY pickup_request_id HAVING COUNT(*) > 1 LIMIT 1");
      if($duplicate_pickups && $db->num_rows($duplicate_pickups) === 0){
        $db->query_safe("ALTER TABLE delivery_orders ADD UNIQUE KEY uq_delivery_pickup_request (pickup_request_id)");
      }
    }
  }

  if(tableExists('stock_movements') && !column_exists('stock_movements','event_key')){
    $db->query_safe("ALTER TABLE stock_movements ADD event_key varchar(120) DEFAULT NULL AFTER reference_id");
  }
  if(tableExists('stock_movements')){
    ensure_index_safe('stock_movements','uq_stock_movement_event','UNIQUE KEY `uq_stock_movement_event` (`event_key`)');
  }
  ensure_index_safe('pickup_request_items','idx_pickup_item_package_unit','KEY `idx_pickup_item_package_unit` (`package_unit_id`)');
  ensure_index_safe('delivery_order_items','idx_delivery_item_package_unit','KEY `idx_delivery_item_package_unit` (`package_unit_id`)');
  ensure_index_safe('inventory_bundles','bundle_no','UNIQUE KEY `bundle_no` (`bundle_no`)');
  ensure_index_safe('pickup_request_items','uq_pickup_item_bundle','UNIQUE KEY `uq_pickup_item_bundle` (`pickup_request_id`,`bundle_id`)');
  ensure_index_safe('delivery_order_items','uq_delivery_item_bundle','UNIQUE KEY `uq_delivery_item_bundle` (`delivery_order_id`,`bundle_id`)');
  ensure_index_safe('delivery_order_items','uq_delivery_pickup_item','UNIQUE KEY `uq_delivery_pickup_item` (`pickup_request_item_id`)');

  // Product, bundle, and item rows form an audit trail. Migrate the early
  // CASCADE revision with one ALTER and a new constraint name; this avoids a
  // window in which no product guard exists and works on MariaDB versions that
  // cannot drop and recreate the same constraint name in one statement.
  $bundle_restrict_fk = 'fk_bundle_product_restrict';
  if(foreign_key_exists('inventory_bundles','fk_bundle_product') && foreign_key_delete_rule('inventory_bundles','fk_bundle_product') !== 'RESTRICT'){
    if(foreign_key_exists('inventory_bundles',$bundle_restrict_fk) && foreign_key_delete_rule('inventory_bundles',$bundle_restrict_fk) === 'RESTRICT'){
      $db->query_safe("ALTER TABLE inventory_bundles DROP FOREIGN KEY fk_bundle_product");
    } else {
      $db->query_safe("ALTER TABLE inventory_bundles DROP FOREIGN KEY fk_bundle_product, ADD CONSTRAINT {$bundle_restrict_fk} FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT ON UPDATE RESTRICT");
    }
  }
  if(!foreign_key_exists('inventory_bundles','fk_bundle_product') && !foreign_key_exists('inventory_bundles',$bundle_restrict_fk)){
    ensure_foreign_key_safe('inventory_bundles','fk_bundle_product','FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
  }
  migrate_foreign_key_to_restrict('stock_movements','FK_stock_movements_product','fk_stock_movements_product_restrict','product_id','products');
  migrate_foreign_key_to_restrict('withdrawals','SK','fk_withdrawals_product_restrict','product_id','products');
  migrate_foreign_key_to_restrict('products','FK_products','fk_products_category_restrict','categorie_id','categories');
  ensure_foreign_key_safe('inventory_bundles','fk_bundle_base_unit','FOREIGN KEY (`base_unit_id`) REFERENCES `units` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
  ensure_foreign_key_safe('inventory_bundles','fk_bundle_package_unit','FOREIGN KEY (`package_unit_id`) REFERENCES `units` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
  ensure_foreign_key_safe('inventory_bundles','fk_bundle_client','FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
  ensure_foreign_key_safe('inventory_bundles','fk_bundle_reserved_request','FOREIGN KEY (`reserved_request_id`) REFERENCES `pickup_requests` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
  ensure_foreign_key_safe('inventory_bundles','fk_bundle_out_delivery','FOREIGN KEY (`out_delivery_order_id`) REFERENCES `delivery_orders` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
  ensure_foreign_key_safe('products','fk_product_base_unit','FOREIGN KEY (`base_unit_id`) REFERENCES `units` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
  ensure_foreign_key_safe('pickup_request_items','fk_pickup_item_request','FOREIGN KEY (`pickup_request_id`) REFERENCES `pickup_requests` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
  ensure_foreign_key_safe('pickup_request_items','fk_pickup_item_bundle','FOREIGN KEY (`bundle_id`) REFERENCES `inventory_bundles` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
  ensure_foreign_key_safe('pickup_request_items','fk_pickup_item_product','FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
  ensure_foreign_key_safe('pickup_request_items','fk_pickup_item_base_unit','FOREIGN KEY (`base_unit_id`) REFERENCES `units` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
  ensure_foreign_key_safe('pickup_request_items','fk_pickup_item_package_unit','FOREIGN KEY (`package_unit_id`) REFERENCES `units` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
  ensure_foreign_key_safe('delivery_orders','fk_delivery_pickup_request','FOREIGN KEY (`pickup_request_id`) REFERENCES `pickup_requests` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
  ensure_foreign_key_safe('delivery_order_items','fk_delivery_item_order','FOREIGN KEY (`delivery_order_id`) REFERENCES `delivery_orders` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
  ensure_foreign_key_safe('delivery_order_items','fk_delivery_item_pickup_item','FOREIGN KEY (`pickup_request_item_id`) REFERENCES `pickup_request_items` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
  ensure_foreign_key_safe('delivery_order_items','fk_delivery_item_bundle','FOREIGN KEY (`bundle_id`) REFERENCES `inventory_bundles` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
  ensure_foreign_key_safe('delivery_order_items','fk_delivery_item_product','FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
  ensure_foreign_key_safe('delivery_order_items','fk_delivery_item_base_unit','FOREIGN KEY (`base_unit_id`) REFERENCES `units` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
  ensure_foreign_key_safe('delivery_order_items','fk_delivery_item_package_unit','FOREIGN KEY (`package_unit_id`) REFERENCES `units` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');

  // Kolom titipan plywood pada products: no surat jalan, no batch, grade, ukuran (T/W/L mm), volume m3
  if(tableExists('products')){
    $plywood_columns = array(
      'no_surat_jalan' => "ADD no_surat_jalan varchar(100) DEFAULT NULL AFTER name",
      'no_batch'       => "ADD no_batch varchar(100) DEFAULT NULL AFTER no_surat_jalan",
      'grade'          => "ADD grade varchar(20) DEFAULT NULL AFTER no_batch",
      'tebal'          => "ADD tebal decimal(10,2) DEFAULT NULL AFTER grade",
      'lebar'          => "ADD lebar decimal(10,2) DEFAULT NULL AFTER tebal",
      'panjang'        => "ADD panjang decimal(10,2) DEFAULT NULL AFTER lebar",
      'm3'             => "ADD m3 decimal(12,4) DEFAULT NULL AFTER panjang",
      'sj_scan'        => "ADD sj_scan varchar(255) DEFAULT NULL AFTER m3",
      'pcs_per_crate'  => "ADD pcs_per_crate int(11) DEFAULT NULL AFTER quantity"
    );
    foreach($plywood_columns as $col => $ddl){
      if(!column_exists('products',$col)){
        $db->query("ALTER TABLE products ".$ddl);
      }
    }
    // quantity = jumlah lembar (PC) isi crate; konversi sekali dari varchar lama ke int
    $qty_col = $db->query("SHOW COLUMNS FROM products LIKE 'quantity'");
    if($qty_col && ($qty_meta = $db->fetch_assoc($qty_col)) && stripos($qty_meta['Type'],'int') === false){
      $db->query("ALTER TABLE products MODIFY quantity int(11) NOT NULL DEFAULT 0");
    }
  }

  // Pengaturan tarif penyimpanan: global (app_settings) + override per-client (users.storage_rate)
  $db->query("CREATE TABLE IF NOT EXISTS app_settings (
    setting_key varchar(60) NOT NULL,
    setting_value varchar(255) DEFAULT NULL,
    PRIMARY KEY (setting_key)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3");
  $db->query("INSERT IGNORE INTO app_settings (setting_key,setting_value) VALUES ('storage_rate_per_crate_month','50000')");

  if(tableExists('users') && !column_exists('users','storage_rate')){
    $db->query("ALTER TABLE users ADD storage_rate decimal(25,2) DEFAULT NULL");
  }

  // Pengumuman (announcements) untuk landing page & dashboard
  $db->query("CREATE TABLE IF NOT EXISTS announcements (
    id int(11) unsigned NOT NULL AUTO_INCREMENT,
    title varchar(255) NOT NULL,
    content text DEFAULT NULL,
    category varchar(50) DEFAULT 'UMUM',
    publish_date date DEFAULT NULL,
    is_active tinyint(1) NOT NULL DEFAULT '1',
    created_by int(11) unsigned DEFAULT NULL,
    created_at datetime NOT NULL,
    PRIMARY KEY (id),
    KEY is_active (is_active),
    KEY publish_date (publish_date)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3");
  $ann_cnt = $db->query("SELECT COUNT(id) AS c FROM announcements");
  $ann_row = $ann_cnt ? $db->fetch_assoc($ann_cnt) : null;
  if($ann_row && (int)$ann_row['c'] === 0){
    $seed_ann = array(
      array('Pendaftaran Klien Baru Gelombang II Dibuka','Pendaftaran calon klien penitipan barang gelombang II resmi dibuka. Informasi syarat dan alur pendaftaran dapat diperoleh di sekretariat.','2026-06-06'),
      array('Rekapitulasi Laporan Mutasi Mei 2026 Selesai','Diberitahukan kepada seluruh pemangku kepentingan bahwa rekapitulasi laporan pergerakan stok bulan Mei telah diaudit dan diterbitkan.','2026-06-05'),
      array('Pemeriksaan Pemeliharaan Inventori Rutin','Mohon kerjasamanya untuk pelaksanaan pemeliharaan dan pengecekan fisik barang titipan rutin di gudang utama.','2026-05-28')
    );
    foreach($seed_ann as $sa){
      $st=$db->escape($sa[0]); $sc=$db->escape($sa[1]); $sd=$db->escape($sa[2]);
      $db->query("INSERT INTO announcements (title,content,category,publish_date,is_active,created_at) VALUES ('{$st}','{$sc}','UMUM','{$sd}','1','".make_date()."')");
    }
  }

  $db->query("CREATE TABLE IF NOT EXISTS role_action_permissions (
    role_level int(11) NOT NULL,
    module_key varchar(40) NOT NULL,
    action_key varchar(30) NOT NULL,
    allowed tinyint(1) NOT NULL DEFAULT '0',
    PRIMARY KEY (role_level, module_key, action_key)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3");

  // Backward compatibility: the old UI exposed stock processing under Surat
  // Jalan. Preserve those grants under the real Request Pengambilan action,
  // then remove the stale permission so custom roles do not silently lose it.
  $db->query_safe("INSERT INTO role_action_permissions (role_level,module_key,action_key,allowed) SELECT role_level,'pickup','process',1 FROM role_action_permissions WHERE module_key='surat_jalan' AND action_key='process' AND allowed='1' ON DUPLICATE KEY UPDATE allowed=GREATEST(allowed,VALUES(allowed))");
  $db->query_safe("DELETE FROM role_action_permissions WHERE module_key='surat_jalan' AND action_key='process'");

  $rap_cnt = $db->query("SELECT COUNT(*) AS c FROM role_action_permissions");
  $rap_row = $rap_cnt ? $db->fetch_assoc($rap_cnt) : null;
  if($rap_row && (int)$rap_row['c'] === 0){
    $seed_permissions = array(
      USER_LEVEL_SPECIAL => array(
        'satuan'      => array('view','create','update','delete'),
        'barang'      => array('view','create','update','delete'),
        'media'       => array('view','create','delete'),
        'transaksi'   => array('view','create','update','delete'),
        'pickup'      => array('view','process'),
        'penagihan'   => array('view','create','update','delete','print','process'),
        'surat_jalan' => array('view','print'),
        'laporan'     => array('view','print')
      ),
      USER_LEVEL_USER => array(
        'barang'      => array('view','create','update'),
        'transaksi'   => array('view','create','update'),
        'pickup'      => array('view'),
        'penagihan'   => array('view'),
        'surat_jalan' => array('view','print'),
        'laporan'     => array('view','print')
      ),
      USER_LEVEL_CLIENT => array(
        'barang_saya' => array('view'),
        'pickup'      => array('view','create'),
        'penagihan'   => array('view','print'),
        'surat_jalan' => array('view','print')
      )
    );
    $modules_for_seed = access_permission_modules();
    foreach($seed_permissions as $role_level => $module_actions){
      foreach($modules_for_seed as $module_key => $module_meta){
        foreach($module_meta['actions'] as $action_key){
          $allowed = (isset($module_actions[$module_key]) && in_array($action_key, $module_actions[$module_key], true)) ? 1 : 0;
          $safe_module = $db->escape($module_key);
          $safe_action = $db->escape($action_key);
          $db->query("INSERT IGNORE INTO role_action_permissions (role_level, module_key, action_key, allowed) VALUES ('{$role_level}','{$safe_module}','{$safe_action}','{$allowed}')");
        }
      }
    }
  }

  ensure_upload_directory(SITE_ROOT.DS.'..'.DS.'uploads'.DS.'defects');
  $done = true;
  return true;
}

/*--------------------------------------------------------------*/
/* Grade kualitas plywood, urut dari terbaik ke terendah
/*--------------------------------------------------------------*/
function product_grades(){
  return array('UT','UT-1','UT-2','RIJEK');
}

function grade_rank($grade){
  $idx = array_search($grade, product_grades(), true);
  return $idx === false ? 999 : (int)$idx;
}

/*--------------------------------------------------------------*/
/* Format satu angka ukuran (mm) ke notasi Indonesia (1.220 / 3,6)
/*--------------------------------------------------------------*/
function format_dimension($value){
  if($value === null || $value === ''){ return null; }
  $formatted = number_format((float)$value, 2, ',', '.');
  // buang nol desimal yang tidak perlu: 1.220,00 -> 1.220 ; 3,60 -> 3,6
  if(strpos($formatted, ',') !== false){
    $formatted = rtrim(rtrim($formatted, '0'), ',');
  }
  return $formatted;
}

/*--------------------------------------------------------------*/
/* Format ukuran plywood gabungan: T x W x L mm
/*--------------------------------------------------------------*/
function format_product_size($product){
  $t = isset($product['tebal']) ? $product['tebal'] : null;
  $w = isset($product['lebar']) ? $product['lebar'] : null;
  $l = isset($product['panjang']) ? $product['panjang'] : null;
  if(($t === null || $t === '') && ($w === null || $w === '') && ($l === null || $l === '')){
    return '-';
  }
  $parts = array(
    format_dimension($t) !== null ? format_dimension($t) : '-',
    format_dimension($w) !== null ? format_dimension($w) : '-',
    format_dimension($l) !== null ? format_dimension($l) : '-'
  );
  return implode(' &times; ', $parts).' mm';
}

/*--------------------------------------------------------------*/
/* Pengaturan aplikasi (key/value) untuk tarif penyimpanan
/*--------------------------------------------------------------*/
function get_setting($key, $default = null){
  global $db;
  ensure_warehouse_schema();
  $key = $db->escape($key);
  $res = $db->query("SELECT setting_value FROM app_settings WHERE setting_key='{$key}' LIMIT 1");
  if($res && $db->num_rows($res) > 0){
    $row = $db->fetch_assoc($res);
    return $row['setting_value'];
  }
  return $default;
}

function set_setting($key, $value){
  global $db;
  ensure_warehouse_schema();
  $key = $db->escape($key);
  $value = $db->escape($value);
  return $db->query("INSERT INTO app_settings (setting_key,setting_value) VALUES ('{$key}','{$value}') ON DUPLICATE KEY UPDATE setting_value='{$value}'");
}

function storage_rate_global(){
  return (float)get_setting('storage_rate_per_crate_month', 50000);
}

/* Tarif berlaku untuk client: override per-client jika ada, jika tidak pakai global */
function client_storage_rate($client_id){
  $client_id = (int)$client_id;
  if($client_id > 0){
    $user = find_by_id('users', $client_id);
    if($user && isset($user['storage_rate']) && $user['storage_rate'] !== null && $user['storage_rate'] !== '' && (float)$user['storage_rate'] > 0){
      return (float)$user['storage_rate'];
    }
  }
  return storage_rate_global();
}

/*--------------------------------------------------------------*/
/* Biaya penyimpanan prorata harian: (tarif/bulan / 30) x hari x crate
/* Minimal dihitung 1 hari.
/*--------------------------------------------------------------*/
function storage_days($masuk_date, $keluar_date = null){
  if(empty($masuk_date)){ return 1; }
  $masuk = strtotime(date('Y-m-d', strtotime($masuk_date)));
  $keluar = $keluar_date ? strtotime(date('Y-m-d', strtotime($keluar_date))) : strtotime(date('Y-m-d'));
  $days = (int)floor(($keluar - $masuk) / 86400);
  return $days < 1 ? 1 : $days;
}

function calculate_storage_fee($masuk_date, $keluar_date, $crates, $client_id){
  $rate = client_storage_rate($client_id);
  $days = storage_days($masuk_date, $keluar_date);
  $crates = (int)$crates < 1 ? 1 : (int)$crates;
  $fee = round(($rate / 30) * $days * $crates);
  return array('rate' => $rate, 'days' => $days, 'crates' => $crates, 'fee' => $fee);
}

/*--------------------------------------------------------------*/
/* Pengumuman (announcements)
/*--------------------------------------------------------------*/
function find_all_announcements($only_active = false, $limit = null){
  global $db;
  ensure_warehouse_schema();
  $sql  = "SELECT a.*, u.name AS created_by_name FROM announcements a ";
  $sql .= "LEFT JOIN users u ON u.id = a.created_by";
  if($only_active){ $sql .= " WHERE a.is_active='1'"; }
  $sql .= " ORDER BY a.publish_date DESC, a.id DESC";
  if($limit !== null){ $sql .= " LIMIT ".(int)$limit; }
  return find_by_sql($sql);
}

function find_announcement_by_id($id){
  ensure_warehouse_schema();
  return find_by_id('announcements', (int)$id);
}

function create_announcement($data = array()){
  global $db;
  ensure_warehouse_schema();
  $raw_title = trim(isset($data['title']) ? $data['title'] : '');
  if($raw_title === ''){ return false; }
  $title = $db->escape($raw_title);
  $content = $db->escape(isset($data['content']) ? trim($data['content']) : '');
  $category = trim(isset($data['category']) ? $data['category'] : '');
  $category = $db->escape($category !== '' ? $category : 'UMUM');
  $publish_date = !empty($data['publish_date']) ? $db->escape($data['publish_date']) : date('Y-m-d');
  $is_active = (isset($data['is_active']) && (int)$data['is_active'] === 1) ? 1 : 0;
  $user = current_user();
  $created_by = $user ? (int)$user['id'] : 0;
  $created_by_value = $created_by > 0 ? "'{$created_by}'" : "NULL";
  $sql  = "INSERT INTO announcements (title,content,category,publish_date,is_active,created_by,created_at) VALUES (";
  $sql .= "'{$title}','{$content}','{$category}','{$publish_date}','{$is_active}',{$created_by_value},'".make_date()."')";
  return $db->query($sql) ? $db->insert_id() : false;
}

function update_announcement($id, $data = array()){
  global $db;
  ensure_warehouse_schema();
  $id = (int)$id;
  $raw_title = trim(isset($data['title']) ? $data['title'] : '');
  if($id <= 0 || $raw_title === ''){ return false; }
  $title = $db->escape($raw_title);
  $content = $db->escape(isset($data['content']) ? trim($data['content']) : '');
  $category = trim(isset($data['category']) ? $data['category'] : '');
  $category = $db->escape($category !== '' ? $category : 'UMUM');
  $publish_date = !empty($data['publish_date']) ? $db->escape($data['publish_date']) : date('Y-m-d');
  $is_active = (isset($data['is_active']) && (int)$data['is_active'] === 1) ? 1 : 0;
  $sql  = "UPDATE announcements SET title='{$title}', content='{$content}', category='{$category}', ";
  $sql .= "publish_date='{$publish_date}', is_active='{$is_active}' WHERE id='{$id}' LIMIT 1";
  return $db->query($sql);
}

/*--------------------------------------------------------------*/
/* Konfigurasi Landing Page (disimpan di app_settings key/value)
/*--------------------------------------------------------------*/
function landing_setting_defaults(){
  return array(
    'landing_hero_badge'     => 'Portal Sistem Informasi v1.0.0',
    'landing_hero_title'     => 'Membangun Sistem Unggul dalam Pengelolaan Penitipan',
    'landing_hero_subtitle'  => 'Selamat datang di Sistem Informasi Manajemen Terpusat Sistem Penitipan Barang. Solusi digital modern untuk mengelola data barang, pencatatan mutasi, tagihan klien, dan pelaporan operasional secara real-time.',
    'landing_footer_address' => 'Jl. Sadewa Saraswati No. 32, Lantai 3, Sleman, D.I. Yogyakarta',
    'landing_footer_email'   => 'info@penitipanbarang.com',
    'landing_footer_phone'   => '150 770',
    'landing_footer_hotline' => '150 990'
  );
}

function landing_setting($key){
  $d = landing_setting_defaults();
  return get_setting($key, isset($d[$key]) ? $d[$key] : '');
}

/*--------------------------------------------------------------*/
/* Logo aplikasi (branding) -> uploads/branding, disimpan di app_settings
/*--------------------------------------------------------------*/
function save_app_logo($field = 'app_logo_file'){
  if(empty($_FILES[$field]) || !isset($_FILES[$field]['name']) || $_FILES[$field]['name'] === '' || $_FILES[$field]['error'] !== UPLOAD_ERR_OK){
    return '';
  }
  $dir = SITE_ROOT.DS.'..'.DS.'uploads'.DS.'branding';
  ensure_upload_directory($dir);
  $allowed = array('jpg','jpeg','png','gif','svg','webp');
  $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
  if(!in_array($ext, $allowed)){ return ''; }
  $tmp = $_FILES[$field]['tmp_name'];
  if($ext !== 'svg' && !@getimagesize($tmp)){ return ''; }
  $safe_name = 'logo_'.date('YmdHis').'_'.randString(4).'.'.$ext;
  if(move_uploaded_file($tmp, $dir.DS.$safe_name)){
    return $safe_name;
  }
  return '';
}

/* URL logo aktif; fallback ke avatar bawaan jika belum diunggah */
function app_logo_url(){
  $logo = get_setting('app_logo', '');
  if($logo !== '' && file_exists(SITE_ROOT.DS.'..'.DS.'uploads'.DS.'branding'.DS.$logo)){
    return htmlspecialchars('uploads/branding/'.$logo);
  }
  return htmlspecialchars('https://ui-avatars.com/api/?name=PB&background=10b981&color=ffffff&bold=true');
}

function find_all_units(){
  ensure_warehouse_schema();
  return find_by_sql("SELECT * FROM units ORDER BY name ASC");
}

function find_unit_by_id($id){
  ensure_warehouse_schema();
  return find_by_id('units',(int)$id);
}

function create_unit($name, $description=''){
  global $db;
  ensure_warehouse_schema();
  $name = remove_junk($db->escape($name));
  $description = remove_junk($db->escape($description));
  if($name === ''){ return false; }
  $sql = "INSERT INTO units (name,description,created_at) VALUES ('{$name}','{$description}','".make_date()."')";
  return $db->query($sql) ? $db->insert_id() : false;
}

function update_unit($id, $name, $description=''){
  global $db;
  ensure_warehouse_schema();
  $id = (int)$id;
  $clean_name = remove_junk(trim((string)$name));
  $current = find_unit_by_id($id);
  if(!$current || ($current['name'] !== $clean_name && unit_is_used($id))){ return false; }
  $name = $db->escape($clean_name);
  $description = remove_junk($db->escape($description));
  if($id <= 0 || $name === ''){ return false; }
  $sql = "UPDATE units SET name='{$name}', description='{$description}' WHERE id='{$id}' LIMIT 1";
  return $db->query($sql);
}

function unit_is_used($id){
  global $db;
  ensure_warehouse_schema();
  $id = (int)$id;
  if($id <= 0){ return false; }
  $checks = array(
    "SELECT id FROM products WHERE unit_id='{$id}' OR base_unit_id='{$id}' LIMIT 1",
    "SELECT id FROM inventory_bundles WHERE package_unit_id='{$id}' OR base_unit_id='{$id}' LIMIT 1",
    "SELECT id FROM pickup_request_items WHERE package_unit_id='{$id}' OR base_unit_id='{$id}' LIMIT 1",
    "SELECT id FROM delivery_order_items WHERE package_unit_id='{$id}' OR base_unit_id='{$id}' LIMIT 1",
    "SELECT id FROM pickup_requests WHERE unit_id='{$id}' LIMIT 1",
    "SELECT id FROM stock_movements WHERE unit_id='{$id}' LIMIT 1"
  );
  foreach($checks as $sql){
    $result = $db->query_safe($sql);
    if($result && $db->num_rows($result) > 0){ return true; }
  }
  return false;
}

function user_has_inventory_ownership_history($user_id){
  global $db;
  ensure_consignment_tables();
  $user_id = (int)$user_id;
  if($user_id <= 0){ return false; }
  $checks = array(
    "SELECT id FROM products WHERE client_id='{$user_id}' LIMIT 1",
    "SELECT id FROM inventory_bundles WHERE client_id='{$user_id}' LIMIT 1",
    "SELECT id FROM pickup_requests WHERE client_id='{$user_id}' LIMIT 1",
    "SELECT id FROM delivery_orders WHERE client_id='{$user_id}' LIMIT 1",
    "SELECT id FROM billings WHERE client_id='{$user_id}' LIMIT 1",
    "SELECT id FROM stock_movements WHERE client_id='{$user_id}' LIMIT 1",
    "SELECT id FROM product_defects WHERE client_id='{$user_id}' LIMIT 1"
  );
  foreach($checks as $sql){
    $result = $db->query_safe($sql);
    if($result && $db->num_rows($result) > 0){ return true; }
  }
  return false;
}

function delete_unit_safe($id){
  ensure_warehouse_schema();
  if(unit_is_used($id)){ return false; }
  return delete_by_id('units',(int)$id);
}

function find_product_defect_summary($product_id){
  global $db;
  ensure_warehouse_schema();
  $product_id = (int)$product_id;
  $sql = "SELECT COALESCE(SUM(defect_qty),0) AS total_defect, COUNT(id) AS total_report FROM product_defects WHERE product_id='{$product_id}'";
  $result = $db->query($sql);
  return $db->fetch_assoc($result);
}

function record_product_defect($product_id, $client_id, $defect_qty, $note){
  global $db;
  ensure_warehouse_schema();
  $product_id = (int)$product_id;
  $client_id = (int)$client_id;
  $defect_qty = (int)$defect_qty;
  $note = $db->escape($note);
  $user = current_user();
  $created_by = $user ? (int)$user['id'] : 0;
  $client_value = $client_id > 0 ? "'{$client_id}'" : "NULL";
  $created_by_value = $created_by > 0 ? "'{$created_by}'" : "NULL";
  if($product_id <= 0 || $defect_qty <= 0){ return false; }
  $sql = "INSERT INTO product_defects (product_id,client_id,defect_qty,note,created_by,created_at) VALUES ('{$product_id}',{$client_value},'{$defect_qty}','{$note}',{$created_by_value},'".make_date()."')";
  return $db->query($sql) ? $db->insert_id() : false;
}

function save_defect_photos($defect_id, $field='defect_photos'){
  global $db;
  ensure_warehouse_schema();
  $saved = 0;
  $defect_id = (int)$defect_id;
  if($defect_id <= 0 || empty($_FILES[$field]) || !isset($_FILES[$field]['name']) || !is_array($_FILES[$field]['name'])){
    return $saved;
  }
  $dir = SITE_ROOT.DS.'..'.DS.'uploads'.DS.'defects';
  ensure_upload_directory($dir);
  $allowed = array('jpg','jpeg','png','gif');
  foreach($_FILES[$field]['name'] as $idx => $name){
    if(empty($name) || $_FILES[$field]['error'][$idx] !== UPLOAD_ERR_OK){ continue; }
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if(!in_array($ext, $allowed)){ continue; }
    $tmp = $_FILES[$field]['tmp_name'][$idx];
    if(!@getimagesize($tmp)){ continue; }
    $safe_name = 'defect_'.$defect_id.'_'.date('YmdHis').'_'.randString(5).'.'.$ext;
    if(move_uploaded_file($tmp, $dir.DS.$safe_name)){
      $mime = $db->escape($_FILES[$field]['type'][$idx]);
      $db->query("INSERT INTO product_defect_photos (defect_id,file_name,file_type,created_at) VALUES ('{$defect_id}','".$db->escape($safe_name)."','{$mime}','".make_date()."')");
      $saved++;
    }
  }
  return $saved;
}

/*--------------------------------------------------------------*/
/* Simpan scan/foto surat jalan (jpg/png/pdf) -> uploads/surat_jalan
/*--------------------------------------------------------------*/
function save_sj_scan($field = 'sj_scan_file'){
  if(empty($_FILES[$field]) || !isset($_FILES[$field]['name']) || $_FILES[$field]['name'] === '' || $_FILES[$field]['error'] !== UPLOAD_ERR_OK){
    return '';
  }
  $dir = SITE_ROOT.DS.'..'.DS.'uploads'.DS.'surat_jalan';
  ensure_upload_directory($dir);
  $allowed = array('jpg','jpeg','png','pdf');
  $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
  if(!in_array($ext, $allowed)){ return ''; }
  $safe_name = 'sj_'.date('YmdHis').'_'.randString(5).'.'.$ext;
  if(move_uploaded_file($_FILES[$field]['tmp_name'], $dir.DS.$safe_name)){
    return $safe_name;
  }
  return '';
}

function find_product_defects($product_id, $client_id = null){
  global $db;
  ensure_warehouse_schema();
  $viewer_client_id = current_client_id();
  if($viewer_client_id !== null){ $client_id = $viewer_client_id; }
  $product_id = (int)$product_id;
  $sql  = "SELECT d.*,p.name AS product_name,u.name AS client_name,actor.name AS created_by_name ";
  $sql .= "FROM product_defects d ";
  $sql .= "LEFT JOIN products p ON p.id=d.product_id ";
  $sql .= "LEFT JOIN users u ON u.id=d.client_id ";
  $sql .= "LEFT JOIN users actor ON actor.id=d.created_by ";
  $sql .= "WHERE d.product_id='{$product_id}'";
  if($client_id !== null){ $sql .= " AND d.client_id='".$db->escape((int)$client_id)."'"; }
  $sql .= " ORDER BY d.created_at DESC";
  return find_by_sql($sql);
}

function find_defect_photos($defect_id){
  global $db;
  ensure_warehouse_schema();
  $defect_id = (int)$defect_id;
  return find_by_sql("SELECT * FROM product_defect_photos WHERE defect_id='{$defect_id}' ORDER BY id ASC");
}

function find_latest_product_defect_photo($product_id){
  global $db;
  ensure_warehouse_schema();
  $product_id = (int)$product_id;
  $sql  = "SELECT pfp.* FROM product_defect_photos pfp ";
  $sql .= "INNER JOIN product_defects pd ON pd.id = pfp.defect_id ";
  $sql .= "WHERE pd.product_id='{$product_id}' ";
  $sql .= "ORDER BY pfp.id DESC LIMIT 1";
  $result = find_by_sql($sql);
  return empty($result) ? null : $result[0];
}

function find_product_defect_photos_by_product($product_id){
  global $db;
  ensure_warehouse_schema();
  $product_id = (int)$product_id;
  $sql  = "SELECT pfp.* FROM product_defect_photos pfp ";
  $sql .= "INNER JOIN product_defects pd ON pd.id = pfp.defect_id ";
  $sql .= "WHERE pd.product_id='{$product_id}' ";
  $sql .= "ORDER BY pfp.id DESC";
  return find_by_sql($sql);
}

/*--------------------------------------------------------------*/
/* Physical bundle inventory (crate/pallet -> immutable base qty)
/*--------------------------------------------------------------*/
function warehouse_actor_id(){
  $user = current_user();
  return $user ? (int)$user['id'] : 0;
}

function normalize_positive_integer_list($values){
  if(!is_array($values)){ $values = array($values); }
  $normalized = array();
  foreach($values as $value){
    $value = (int)$value;
    if($value <= 0 || $value > 2147483647){ return false; }
    $normalized[] = $value;
  }
  return empty($normalized) ? false : $normalized;
}

function normalize_unique_ids($values){
  $values = normalize_positive_integer_list($values);
  if($values === false){ return false; }
  $values = array_values(array_unique($values));
  sort($values, SORT_NUMERIC);
  return $values;
}

function product_has_bundle_details($product_id){
  global $db;
  ensure_warehouse_schema();
  $product_id = (int)$product_id;
  if($product_id <= 0){ return false; }
  $result = $db->query("SELECT COUNT(*) AS total FROM inventory_bundles WHERE product_id='{$product_id}'");
  $row = $db->fetch_assoc($result);
  return $row && (int)$row['total'] > 0;
}

function lock_product_owner_for_metadata_update($product_id, $requested_client_id, $page_had_bundles){
  global $db;
  $product_id = (int)$product_id;
  $requested_client_id = (int)$requested_client_id;
  $product_result = $db->query_or_throw("SELECT id,client_id FROM products WHERE id='{$product_id}' LIMIT 1 FOR UPDATE");
  $locked_product = $db->fetch_assoc($product_result);
  if(!$locked_product){ return false; }
  $bundle_result = $db->query_or_throw("SELECT id FROM inventory_bundles WHERE product_id='{$product_id}' LIMIT 1 FOR UPDATE");
  $live_has_bundles = (bool)$db->fetch_assoc($bundle_result);
  $locked_client_id = !empty($locked_product['client_id']) ? (int)$locked_product['client_id'] : 0;
  if($live_has_bundles){
    // A stale edit tab must never overwrite ownership after bundle
    // initialization committed in another session.
    if(!$page_had_bundles && $requested_client_id !== $locked_client_id){ return false; }
    return array('client_id'=>$locked_client_id,'has_bundle_details'=>true);
  }
  if($requested_client_id > 0){
    $owner_result = $db->query_or_throw("SELECT id FROM users WHERE id='{$requested_client_id}' AND user_level='".(int)USER_LEVEL_CLIENT."' AND status='1' LIMIT 1 FOR UPDATE");
    if(!$db->fetch_assoc($owner_result)){ return false; }
  }
  return array('client_id'=>$requested_client_id,'has_bundle_details'=>false);
}

function find_product_inventory_bundles($product_id, $statuses = null, $client_id = null){
  global $db;
  ensure_warehouse_schema();
  $product_id = (int)$product_id;
  $viewer_client_id = current_client_id();
  if($viewer_client_id !== null){ $client_id = $viewer_client_id; }
  if($product_id <= 0){ return array(); }
  $sql  = "SELECT b.*,p.name AS product_name,pu.name AS package_unit_name,bu.name AS base_unit_name ";
  $sql .= "FROM inventory_bundles b ";
  $sql .= "INNER JOIN products p ON p.id=b.product_id ";
  $sql .= "LEFT JOIN units pu ON pu.id=b.package_unit_id ";
  $sql .= "LEFT JOIN units bu ON bu.id=b.base_unit_id ";
  $sql .= "WHERE b.product_id='{$product_id}'";
  if($client_id !== null){ $sql .= " AND b.client_id='".$db->escape((int)$client_id)."'"; }
  // UI compatibility: true means include all statuses.
  if($statuses !== null && $statuses !== true){
    if(!is_array($statuses)){ $statuses = array($statuses); }
    $safe_statuses = array();
    foreach($statuses as $status){
      $status = trim((string)$status);
      if($status !== ''){ $safe_statuses[] = "'".$db->escape($status)."'"; }
    }
    if(!empty($safe_statuses)){ $sql .= " AND b.status IN (".implode(',', $safe_statuses).")"; }
  }
  $sql .= " ORDER BY b.id ASC";
  return find_by_sql($sql);
}

function find_available_inventory_bundles($client_id, $product_id = null){
  global $db;
  ensure_warehouse_schema();
  $viewer_client_id = current_client_id();
  if($viewer_client_id !== null){ $client_id = $viewer_client_id; }
  $client_id = (int)$client_id;
  if($client_id <= 0){ return array(); }
  $sql  = "SELECT b.*,b.id AS bundle_id,b.quantity AS base_quantity,p.name AS product_name,p.quantity AS product_quantity,p.no_surat_jalan,p.no_batch,p.grade,p.tebal,p.lebar,p.panjang,";
  $sql .= "pu.name AS package_unit_name,bu.name AS base_unit_name ";
  $sql .= "FROM inventory_bundles b INNER JOIN products p ON p.id=b.product_id ";
  $sql .= "LEFT JOIN units pu ON pu.id=b.package_unit_id LEFT JOIN units bu ON bu.id=b.base_unit_id ";
  $sql .= "WHERE b.client_id='{$client_id}' AND p.client_id='{$client_id}' AND b.status='available' AND b.reserved_request_id IS NULL";
  if($product_id !== null){ $sql .= " AND b.product_id='".$db->escape((int)$product_id)."'"; }
  $sql .= " ORDER BY p.name ASC,b.id ASC";
  return find_by_sql($sql);
}

function find_available_bundles_for_client($client_id, $product_id = null){
  return find_available_inventory_bundles($client_id, $product_id);
}

function find_product_bundle_summary($product_id){
  global $db;
  ensure_warehouse_schema();
  $product_id = (int)$product_id;
  $result = $db->query("SELECT COUNT(*) AS total_count,COALESCE(SUM(quantity),0) AS total_quantity,MAX(package_unit_id) AS package_unit_id,MAX(base_unit_id) AS base_unit_id,".
    "COALESCE(SUM(CASE WHEN status='available' THEN 1 ELSE 0 END),0) AS available_count,".
    "COALESCE(SUM(CASE WHEN status='available' THEN quantity ELSE 0 END),0) AS available_quantity,".
    "COALESCE(SUM(CASE WHEN status='reserved' THEN 1 ELSE 0 END),0) AS reserved_count,".
    "COALESCE(SUM(CASE WHEN status='reserved' THEN quantity ELSE 0 END),0) AS reserved_quantity,".
    "COALESCE(SUM(CASE WHEN status='out' THEN 1 ELSE 0 END),0) AS out_count,".
    "COALESCE(SUM(CASE WHEN status='out' THEN quantity ELSE 0 END),0) AS out_quantity ".
    "FROM inventory_bundles WHERE product_id='{$product_id}'");
  $row = $db->fetch_assoc($result);
  if(!$row){
    $row = array('total_count'=>0,'total_quantity'=>0,'package_unit_id'=>null,'base_unit_id'=>null,'available_count'=>0,'available_quantity'=>0,'reserved_count'=>0,'reserved_quantity'=>0,'out_count'=>0,'out_quantity'=>0);
  }
  $row['active_quantity'] = (int)$row['available_quantity'] + (int)$row['reserved_quantity'];
  $row['has_details'] = (int)$row['total_count'] > 0;
  $row['bundle_count'] = (int)$row['total_count'];
  $row['current_bundle_count'] = (int)$row['available_count'] + (int)$row['reserved_count'];
  return $row;
}

function generate_inventory_bundle_number($product_id, $sequence = 1){
  return 'BND-'.(int)$product_id.'-'.date('YmdHis').'-'.str_pad((int)$sequence, 3, '0', STR_PAD_LEFT).'-'.strtoupper(randString(3));
}

function insert_inventory_bundles_locked($product, $quantities, $package_unit_id, $base_unit_id, $options = array()){
  global $db;
  $product_id = (int)$product['id'];
  $package_unit_id = (int)$package_unit_id;
  $base_unit_id = (int)$base_unit_id;
  $quantities = normalize_positive_integer_list($quantities);
  if($product_id <= 0 || (int)$product['client_id'] <= 0 || $base_unit_id <= 0 || $quantities === false){ return false; }
  $owner_id = (int)$product['client_id'];
  $owner_result = $db->query_or_throw("SELECT id FROM users WHERE id='{$owner_id}' AND user_level='".(int)USER_LEVEL_CLIENT."' AND status='1' LIMIT 1 FOR UPDATE");
  if(!$db->fetch_assoc($owner_result)){ return false; }
  $base_unit_result = $db->query_or_throw("SELECT id FROM units WHERE id='{$base_unit_id}' LIMIT 1");
  if(!$db->fetch_assoc($base_unit_result)){ return false; }
  if($package_unit_id > 0){
    $package_result = $db->query_or_throw("SELECT id FROM units WHERE id='{$package_unit_id}' LIMIT 1");
    if(!$db->fetch_assoc($package_result)){ return false; }
  }
  if(!empty($product['base_unit_id']) && (int)$product['base_unit_id'] !== $base_unit_id){ return false; }
  if(empty($product['base_unit_id'])){
    $db->query_or_throw("UPDATE products SET base_unit_id='{$base_unit_id}' WHERE id='{$product_id}' AND base_unit_id IS NULL LIMIT 1");
  }
  $legacy_reservation = $db->query_or_throw("SELECT id FROM pickup_request_items WHERE product_id='{$product_id}' AND bundle_id IS NULL AND status='reserved' LIMIT 1 FOR UPDATE");
  if($db->fetch_assoc($legacy_reservation)){ return false; }
  $active_result = $db->query_or_throw("SELECT COALESCE(SUM(quantity),0) AS qty FROM inventory_bundles WHERE product_id='{$product_id}' AND status IN ('available','reserved')");
  $active_row = $db->fetch_assoc($active_result);
  $active_before = $active_row ? (int)$active_row['qty'] : 0;
  $new_total = array_sum($quantities);
  $product_quantity = (int)$product['quantity'];
  if(!empty($options['adjust_product_stock'])){
    $db->query_or_throw("UPDATE products SET quantity=quantity+'{$new_total}' WHERE id='{$product_id}' LIMIT 1");
    $product_quantity += $new_total;
  }
  // Core invariant: product aggregate equals all non-out physical bundles.
  if($active_before + $new_total !== $product_quantity){ return false; }
  $client_id = isset($options['client_id']) ? (int)$options['client_id'] : (int)$product['client_id'];
  if($client_id !== (int)$product['client_id']){ return false; }
  $actor_id = isset($options['created_by']) ? (int)$options['created_by'] : warehouse_actor_id();
  $bundle_nos = isset($options['bundle_nos']) && is_array($options['bundle_nos']) ? array_values($options['bundle_nos']) : array();
  $now = !empty($options['created_at']) ? $db->escape($options['created_at']) : make_date();
  $ids = array();
  foreach($quantities as $index => $quantity){
    $bundle_no = isset($bundle_nos[$index]) && trim((string)$bundle_nos[$index]) !== ''
      ? trim((string)$bundle_nos[$index])
      : generate_inventory_bundle_number($product_id, $index + 1);
    if(strlen($bundle_no) > 80){ return false; }
    $bundle_no = $db->escape($bundle_no);
    $client_value = $client_id > 0 ? "'{$client_id}'" : 'NULL';
    $package_value = $package_unit_id > 0 ? "'{$package_unit_id}'" : 'NULL';
    $actor_value = $actor_id > 0 ? "'{$actor_id}'" : 'NULL';
    $db->query_or_throw("INSERT INTO inventory_bundles (bundle_no,product_id,client_id,package_unit_id,base_unit_id,quantity,status,reserved_request_id,reserved_at,out_delivery_order_id,out_at,created_by,created_at,updated_at) VALUES ('{$bundle_no}','{$product_id}',{$client_value},{$package_value},'{$base_unit_id}','".(int)$quantity."','available',NULL,NULL,NULL,NULL,{$actor_value},'{$now}','{$now}')");
    $ids[] = $db->insert_id();
  }
  return $ids;
}

function create_inventory_bundles($product_id, $quantities, $package_unit_id, $base_unit_id, $options = array()){
  global $db;
  ensure_warehouse_schema();
  $product_id = (int)$product_id;
  $owns_transaction = !$db->in_transaction();
  try{
    if($owns_transaction){ $db->begin_transaction(); }
    $result = $db->query_or_throw("SELECT * FROM products WHERE id='{$product_id}' LIMIT 1 FOR UPDATE");
    $product = $db->fetch_assoc($result);
    if(!$product){ throw new RuntimeException('Product not found while creating bundles.'); }
    $ids = insert_inventory_bundles_locked($product, $quantities, $package_unit_id, $base_unit_id, $options);
    if($ids === false){ throw new RuntimeException('Invalid or inconsistent bundle details.'); }
    if($owns_transaction){ $db->commit(); }
    return $ids;
  } catch(Throwable $e){
    if($owns_transaction && $db->in_transaction()){ $db->rollback(); }
    if(!$owns_transaction){ throw $e; }
    return false;
  }
}

function create_inbound_delivery_order_items($delivery_id, $product_id, $bundle_ids){
  global $db;
  ensure_warehouse_schema();
  $delivery_id = (int)$delivery_id;
  $product_id = (int)$product_id;
  $bundle_ids = normalize_unique_ids($bundle_ids);
  if($delivery_id <= 0 || $product_id <= 0 || $bundle_ids === false){ return false; }
  $owns_transaction = !$db->in_transaction();
  try{
    if($owns_transaction){ $db->begin_transaction(); }
    $order_result = $db->query_or_throw("SELECT * FROM delivery_orders WHERE id='{$delivery_id}' LIMIT 1 FOR UPDATE");
    $order = $db->fetch_assoc($order_result);
    $product_result = $db->query_or_throw("SELECT * FROM products WHERE id='{$product_id}' LIMIT 1 FOR UPDATE");
    $product = $db->fetch_assoc($product_result);
    if(!$order || !$product || $order['movement_type'] !== 'in' || (int)$order['product_id'] !== $product_id || (int)$order['stock_processed'] !== 1){ throw new RuntimeException('Invalid inbound delivery header.'); }
    $id_list = implode(',', $bundle_ids);
    $bundle_result = $db->query_or_throw("SELECT * FROM inventory_bundles WHERE id IN ({$id_list}) AND product_id='{$product_id}' ORDER BY id ASC FOR UPDATE");
    $bundles = array();
    $total = 0;
    while($bundle = $db->fetch_assoc($bundle_result)){
      if($bundle['status'] !== 'available' || !empty($bundle['reserved_request_id'])){ throw new RuntimeException('Inbound bundle is not available.'); }
      $bundles[] = $bundle;
      $total += (int)$bundle['quantity'];
    }
    if(count($bundles) !== count($bundle_ids) || $total !== (int)$order['quantity'] || $total !== (int)$product['quantity']){ throw new RuntimeException('Inbound bundle total does not match aggregate stock.'); }
    $snapshot = array();
    foreach(array('name','no_surat_jalan','no_batch','grade','tebal','lebar','panjang') as $field){
      $snapshot[$field] = isset($product[$field]) && $product[$field] !== null && $product[$field] !== '' ? "'".$db->escape($product[$field])."'" : 'NULL';
    }
    $now = make_date();
    foreach($bundles as $bundle){
      $package_value = !empty($bundle['package_unit_id']) ? "'".(int)$bundle['package_unit_id']."'" : 'NULL';
      $base_value = !empty($bundle['base_unit_id']) ? "'".(int)$bundle['base_unit_id']."'" : 'NULL';
      $bundle_no_value = !empty($bundle['bundle_no']) ? "'".$db->escape($bundle['bundle_no'])."'" : 'NULL';
      $db->query_or_throw("INSERT INTO delivery_order_items (delivery_order_id,pickup_request_item_id,bundle_id,product_id,base_unit_id,package_unit_id,quantity,bundle_no,product_name,no_surat_jalan,no_batch,grade,tebal,lebar,panjang,status,processed_at,created_at) VALUES ('{$delivery_id}',NULL,'".(int)$bundle['id']."','{$product_id}',{$base_value},{$package_value},'".(int)$bundle['quantity']."',{$bundle_no_value},{$snapshot['name']},{$snapshot['no_surat_jalan']},{$snapshot['no_batch']},{$snapshot['grade']},{$snapshot['tebal']},{$snapshot['lebar']},{$snapshot['panjang']},'received','{$now}','{$now}')");
    }
    if($owns_transaction){ $db->commit(); }
    return true;
  } catch(Throwable $e){
    if($owns_transaction && $db->in_transaction()){ $db->rollback(); }
    if(!$owns_transaction){ throw $e; }
    return false;
  }
}

function delete_inventory_bundles_for_product($product_id){
  global $db;
  ensure_warehouse_schema();
  $product_id = (int)$product_id;
  if($product_id <= 0){ return false; }
  try{
    $db->begin_transaction();
    $product_result = $db->query_or_throw("SELECT id FROM products WHERE id='{$product_id}' LIMIT 1 FOR UPDATE");
    if(!$db->fetch_assoc($product_result)){ $db->rollback(); return true; }
    $bundles_result = $db->query_or_throw("SELECT * FROM inventory_bundles WHERE product_id='{$product_id}' ORDER BY id ASC FOR UPDATE");
    $has_rows = false;
    while($bundle = $db->fetch_assoc($bundles_result)){
      $has_rows = true;
      if($bundle['status'] !== 'available' || !empty($bundle['reserved_request_id']) || !empty($bundle['out_delivery_order_id']) || !empty($bundle['out_at'])){ $db->rollback(); return false; }
    }
    if($has_rows){ $db->query_or_throw("DELETE FROM inventory_bundles WHERE product_id='{$product_id}'"); }
    $db->commit();
    return true;
  } catch(Throwable $e){
    if($db->in_transaction()){ $db->rollback(); }
    return false;
  }
}

function initialize_historical_inventory_bundles($product_id, $quantities, $package_unit_id, $base_unit_id, $options = array()){
  global $db;
  ensure_warehouse_schema();
  $product_id = (int)$product_id;
  $quantities = normalize_positive_integer_list($quantities);
  if($quantities === false){ return false; }
  try{
    $db->begin_transaction();
    $result = $db->query_or_throw("SELECT * FROM products WHERE id='{$product_id}' LIMIT 1 FOR UPDATE");
    $product = $db->fetch_assoc($result);
    if(!$product || (int)$product['client_id'] <= 0 || array_sum($quantities) !== (int)$product['quantity']){ $db->rollback(); return false; }
    $existing = $db->query_or_throw("SELECT id FROM inventory_bundles WHERE product_id='{$product_id}' LIMIT 1 FOR UPDATE");
    if($db->fetch_assoc($existing)){ $db->rollback(); return false; }
    $db->query_or_throw("UPDATE products SET unit_id='".(int)$package_unit_id."',base_unit_id='".(int)$base_unit_id."',pcs_per_crate=NULL WHERE id='{$product_id}' LIMIT 1");
    $product['unit_id'] = (int)$package_unit_id;
    $product['base_unit_id'] = (int)$base_unit_id;
    $ids = insert_inventory_bundles_locked($product, $quantities, $package_unit_id, $base_unit_id, $options);
    if($ids === false){ $db->rollback(); return false; }
    $db->commit();
    return $ids;
  } catch(Throwable $e){
    if($db->in_transaction()){ $db->rollback(); }
    return false;
  }
}

function find_pickup_request_items($request_id, $client_id = null){
  global $db;
  ensure_warehouse_schema();
  $request_id = (int)$request_id;
  $viewer_client_id = current_client_id();
  if($viewer_client_id !== null){ $client_id = $viewer_client_id; }
  $sql  = "SELECT i.*,i.quantity AS base_quantity,COALESCE(i.product_name,p.name) AS display_product_name,";
  $sql .= "COALESCE(i.no_surat_jalan,p.no_surat_jalan) AS display_no_surat_jalan,COALESCE(i.no_batch,p.no_batch) AS display_no_batch,";
  $sql .= "COALESCE(i.grade,p.grade) AS display_grade,COALESCE(i.tebal,p.tebal) AS display_tebal,COALESCE(i.lebar,p.lebar) AS display_lebar,COALESCE(i.panjang,p.panjang) AS display_panjang,";
  $sql .= "pu.name AS package_unit_name,bu.name AS base_unit_name,b.status AS bundle_status ";
  $sql .= "FROM pickup_request_items i INNER JOIN pickup_requests r ON r.id=i.pickup_request_id ";
  $sql .= "LEFT JOIN products p ON p.id=i.product_id LEFT JOIN inventory_bundles b ON b.id=i.bundle_id LEFT JOIN units pu ON pu.id=i.package_unit_id LEFT JOIN units bu ON bu.id=i.base_unit_id ";
  $sql .= "WHERE i.pickup_request_id='{$request_id}'";
  if($client_id !== null){ $sql .= " AND r.client_id='".$db->escape((int)$client_id)."'"; }
  $sql .= " ORDER BY i.id ASC";
  return find_by_sql($sql);
}

function find_delivery_order_items($delivery_id, $client_id = null){
  global $db;
  ensure_warehouse_schema();
  $delivery_id = (int)$delivery_id;
  $viewer_client_id = current_client_id();
  if($viewer_client_id !== null){ $client_id = $viewer_client_id; }
  $sql  = "SELECT i.*,i.quantity AS base_quantity,COALESCE(i.product_name,p.name) AS display_product_name,";
  $sql .= "COALESCE(i.no_surat_jalan,p.no_surat_jalan) AS display_no_surat_jalan,COALESCE(i.no_batch,p.no_batch) AS display_no_batch,";
  $sql .= "COALESCE(i.grade,p.grade) AS display_grade,COALESCE(i.tebal,p.tebal) AS display_tebal,COALESCE(i.lebar,p.lebar) AS display_lebar,COALESCE(i.panjang,p.panjang) AS display_panjang,";
  $sql .= "pu.name AS package_unit_name,bu.name AS base_unit_name ";
  $sql .= "FROM delivery_order_items i INNER JOIN delivery_orders d ON d.id=i.delivery_order_id ";
  $sql .= "LEFT JOIN products p ON p.id=i.product_id LEFT JOIN inventory_bundles b ON b.id=i.bundle_id LEFT JOIN units pu ON pu.id=i.package_unit_id LEFT JOIN units bu ON bu.id=i.base_unit_id ";
  $sql .= "WHERE i.delivery_order_id='{$delivery_id}'";
  if($client_id !== null){ $sql .= " AND d.client_id='".$db->escape((int)$client_id)."'"; }
  $sql .= " ORDER BY i.id ASC";
  return find_by_sql($sql);
}

function pickup_status_label($status){
  if($status === 'approved'){ return 'Disetujui'; }
  if($status === 'rejected'){ return 'Ditolak'; }
  if($status === 'auto_rejected'){ return 'Ditolak Otomatis'; }
  if($status === 'cancelled'){ return 'Dibatalkan'; }
  if($status === 'completed'){ return 'Selesai'; }
  return 'Menunggu';
}

function pickup_status_class($status){
  if($status === 'approved'){ return 'success'; }
  if($status === 'rejected' || $status === 'auto_rejected' || $status === 'cancelled'){ return 'danger'; }
  if($status === 'completed'){ return 'primary'; }
  return 'warning';
}

function normalize_pickup_fulfillment_method($method){
  $method = trim((string)$method);
  return in_array($method, array('self_pickup','delivery'), true) ? $method : null;
}

function pickup_fulfillment_label($method){
  return normalize_pickup_fulfillment_method($method) === 'delivery' ? 'Dikirim' : 'Diambil Sendiri';
}

function find_pickup_requests($client_id = null){
  global $db;
  ensure_consignment_tables();
  ensure_warehouse_schema();
  $viewer_client_id = current_client_id();
  if($viewer_client_id !== null){ $client_id = $viewer_client_id; }
  $sql  = "SELECT r.*,p.name AS product_name,p.quantity AS current_stock,u.name AS client_name,un.name AS unit_name,bu.name AS base_unit_name,";
  $sql .= "d.id AS delivery_id,d.document_no,d.stock_processed ";
  $sql .= "FROM pickup_requests r ";
  $sql .= "LEFT JOIN products p ON p.id=r.product_id ";
  $sql .= "LEFT JOIN users u ON u.id=r.client_id ";
  $sql .= "LEFT JOIN units un ON un.id=r.unit_id ";
  $sql .= "LEFT JOIN units bu ON bu.id=p.base_unit_id ";
  $sql .= "LEFT JOIN delivery_orders d ON d.pickup_request_id=r.id";
  if($client_id !== null){ $sql .= " WHERE r.client_id='".$db->escape((int)$client_id)."'"; }
  $sql .= " ORDER BY r.created_at DESC, r.id DESC";
  return find_by_sql($sql);
}

function find_pickup_request_details($id, $client_id = null){
  global $db;
  ensure_consignment_tables();
  ensure_warehouse_schema();
  $id = (int)$id;
  $viewer_client_id = current_client_id();
  if($viewer_client_id !== null){ $client_id = $viewer_client_id; }
  $sql  = "SELECT r.*,p.name AS product_name,p.quantity AS current_stock,p.client_id AS product_client_id,u.name AS client_name,un.name AS unit_name,bu.name AS base_unit_name,";
  $sql .= "d.id AS delivery_id,d.document_no,d.stock_processed ";
  $sql .= "FROM pickup_requests r ";
  $sql .= "LEFT JOIN products p ON p.id=r.product_id ";
  $sql .= "LEFT JOIN users u ON u.id=r.client_id ";
  $sql .= "LEFT JOIN units un ON un.id=r.unit_id ";
  $sql .= "LEFT JOIN units bu ON bu.id=p.base_unit_id ";
  $sql .= "LEFT JOIN delivery_orders d ON d.pickup_request_id=r.id ";
  $sql .= "WHERE r.id='{$id}'";
  if($client_id !== null){ $sql .= " AND r.client_id='".$db->escape((int)$client_id)."'"; }
  $sql .= " LIMIT 1";
  $result = find_by_sql($sql);
  return empty($result) ? null : $result[0];
}

function valid_pickup_date_time($pickup_date, $pickup_time){
  if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$pickup_date)){ return false; }
  $parts = explode('-', $pickup_date);
  if(count($parts) !== 3 || !checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])){ return false; }
  return (bool)preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', (string)$pickup_time);
}

function insert_pickup_request_header_locked($data, $product_id, $unit_id, $quantity, $status = 'pending', $admin_note = null){
  global $db;
  $client_id = (int)$data['client_id'];
  $pickup_date = $db->escape($data['pickup_date']);
  $pickup_time = $db->escape($data['pickup_time']);
  $driver_name = $db->escape(trim((string)$data['driver_name']));
  $vehicle_no = $db->escape(trim((string)$data['vehicle_no']));
  $fulfillment_method = normalize_pickup_fulfillment_method(isset($data['fulfillment_method']) ? $data['fulfillment_method'] : 'self_pickup');
  if($fulfillment_method === null){ throw new InvalidArgumentException('Invalid pickup fulfillment method.'); }
  $delivery_address = isset($data['delivery_address']) ? trim((string)$data['delivery_address']) : '';
  $delivery_address_value = $delivery_address !== '' ? "'".$db->escape($delivery_address)."'" : 'NULL';
  $request_no = $db->escape(!empty($data['request_no']) ? $data['request_no'] : generate_consignment_number('REQ'));
  $unit_value = (int)$unit_id > 0 ? "'".(int)$unit_id."'" : 'NULL';
  $note_value = $admin_note !== null && trim((string)$admin_note) !== '' ? "'".$db->escape($admin_note)."'" : 'NULL';
  $now = make_date();
  $db->query_or_throw("INSERT INTO pickup_requests (request_no,client_id,product_id,unit_id,quantity,fulfillment_method,pickup_date,pickup_time,driver_name,vehicle_no,delivery_address,status,admin_note,created_at) VALUES ('{$request_no}','{$client_id}','".(int)$product_id."',{$unit_value},'".(int)$quantity."','{$fulfillment_method}','{$pickup_date}','{$pickup_time}','{$driver_name}','{$vehicle_no}',{$delivery_address_value},'".$db->escape($status)."',{$note_value},'{$now}')");
  return $db->insert_id();
}

function create_multi_bundle_pickup_request($data = array(), $bundle_ids = array()){
  global $db;
  ensure_consignment_tables();
  ensure_warehouse_schema();
  $client_id = isset($data['client_id']) ? (int)$data['client_id'] : 0;
  if(empty($bundle_ids) && isset($data['bundle_ids'])){ $bundle_ids = $data['bundle_ids']; }
  $bundle_ids = normalize_unique_ids($bundle_ids);
  $pickup_date = !empty($data['pickup_date']) ? $data['pickup_date'] : date('Y-m-d');
  $pickup_time = !empty($data['pickup_time']) ? $data['pickup_time'] : '00:00';
  $driver_name = isset($data['driver_name']) ? trim((string)$data['driver_name']) : '';
  $vehicle_no = isset($data['vehicle_no']) ? trim((string)$data['vehicle_no']) : '';
  $fulfillment_method = normalize_pickup_fulfillment_method(isset($data['fulfillment_method']) ? $data['fulfillment_method'] : 'self_pickup');
  $delivery_address = isset($data['delivery_address']) ? trim((string)$data['delivery_address']) : '';
  $self_pickup_invalid = $fulfillment_method === 'self_pickup' && ($driver_name === '' || $vehicle_no === '');
  $delivery_invalid = $fulfillment_method === 'delivery' && $delivery_address === '';
  if($client_id <= 0 || $bundle_ids === false || $fulfillment_method === null || $self_pickup_invalid || $delivery_invalid || !valid_pickup_date_time($pickup_date, $pickup_time)){ return false; }
  if($fulfillment_method === 'delivery'){
    $driver_name = '';
    $vehicle_no = '';
  } else {
    $delivery_address = '';
  }
  $data['client_id'] = $client_id;
  $data['pickup_date'] = $pickup_date;
  $data['pickup_time'] = $pickup_time;
  $data['fulfillment_method'] = $fulfillment_method;
  $data['delivery_address'] = $delivery_address;
  $data['driver_name'] = $driver_name;
  $data['vehicle_no'] = $vehicle_no;
  $id_list = implode(',', $bundle_ids);
  // Establish deterministic product -> bundle lock order before mutating.
  $seed_result = $db->query("SELECT DISTINCT product_id FROM inventory_bundles WHERE id IN ({$id_list}) ORDER BY product_id ASC");
  $product_ids = array();
  while($seed = $db->fetch_assoc($seed_result)){ $product_ids[] = (int)$seed['product_id']; }
  if(empty($product_ids)){ return false; }
  try{
    $db->begin_transaction();
    $products = array();
    foreach($product_ids as $product_id){
      $product_result = $db->query_or_throw("SELECT * FROM products WHERE id='{$product_id}' LIMIT 1 FOR UPDATE");
      $product = $db->fetch_assoc($product_result);
      if(!$product || (int)$product['client_id'] !== $client_id){ $db->rollback(); return false; }
      $products[$product_id] = $product;
    }
    $bundle_result = $db->query_or_throw("SELECT * FROM inventory_bundles WHERE id IN ({$id_list}) ORDER BY id ASC FOR UPDATE");
    $bundles = array();
    while($row = $db->fetch_assoc($bundle_result)){ $bundles[] = $row; }
    if(count($bundles) !== count($bundle_ids)){ $db->rollback(); return false; }
    $total_quantity = 0;
    $request_base_units = array();
    foreach($bundles as $bundle){
      $bundle_product_id = (int)$bundle['product_id'];
      if(!isset($products[$bundle_product_id]) || (int)$bundle['client_id'] !== $client_id || $bundle['status'] !== 'available' || !empty($bundle['reserved_request_id']) || (int)$bundle['quantity'] <= 0 || (int)$bundle['base_unit_id'] <= 0){ $db->rollback(); return false; }
      if(!empty($products[$bundle_product_id]['base_unit_id']) && (int)$products[$bundle_product_id]['base_unit_id'] !== (int)$bundle['base_unit_id']){ $db->rollback(); return false; }
      $total_quantity += (int)$bundle['quantity'];
      $request_base_units[(int)$bundle['base_unit_id']] = (int)$bundle['base_unit_id'];
      if($total_quantity > 2147483647){ $db->rollback(); return false; }
    }
    foreach($products as $product_id => $product){
      $active_result = $db->query_or_throw("SELECT COALESCE(SUM(quantity),0) AS qty FROM inventory_bundles WHERE product_id='{$product_id}' AND status IN ('available','reserved')");
      $active = $db->fetch_assoc($active_result);
      if(!$active || (int)$active['qty'] !== (int)$product['quantity']){ $db->rollback(); return false; }
    }
    $first_bundle = $bundles[0];
    $first_product_id = (int)$first_bundle['product_id'];
    // Header quantity is a compatibility checksum; detail rows are
    // authoritative. Never label a mixed pcs/lembar total with the first unit.
    $header_base_unit_id = count($request_base_units) === 1 ? (int)$first_bundle['base_unit_id'] : 0;
    $request_id = insert_pickup_request_header_locked($data, $first_product_id, $header_base_unit_id, $total_quantity, 'pending', null);
    $now = make_date();
    foreach($bundles as $bundle){
      $product_id = (int)$bundle['product_id'];
      $product = $products[$product_id];
      $bundle_no = $db->escape($bundle['bundle_no']);
      $package_value = !empty($bundle['package_unit_id']) ? "'".(int)$bundle['package_unit_id']."'" : 'NULL';
      $snapshot = array();
      foreach(array('name','no_surat_jalan','no_batch','grade','tebal','lebar','panjang') as $field){
        $snapshot[$field] = isset($product[$field]) && $product[$field] !== null && $product[$field] !== '' ? "'".$db->escape($product[$field])."'" : 'NULL';
      }
      $db->query_or_throw("INSERT INTO pickup_request_items (pickup_request_id,bundle_id,product_id,base_unit_id,package_unit_id,quantity,bundle_no,product_name,no_surat_jalan,no_batch,grade,tebal,lebar,panjang,status,created_at,updated_at) VALUES ('{$request_id}','".(int)$bundle['id']."','{$product_id}','".(int)$bundle['base_unit_id']."',{$package_value},'".(int)$bundle['quantity']."','{$bundle_no}',{$snapshot['name']},{$snapshot['no_surat_jalan']},{$snapshot['no_batch']},{$snapshot['grade']},{$snapshot['tebal']},{$snapshot['lebar']},{$snapshot['panjang']},'reserved','{$now}','{$now}')");
      $db->query_or_throw("UPDATE inventory_bundles SET status='reserved',reserved_request_id='{$request_id}',reserved_at='{$now}',updated_at='{$now}' WHERE id='".(int)$bundle['id']."' AND status='available' AND reserved_request_id IS NULL LIMIT 1");
      if($db->affected_rows() !== 1){ throw new RuntimeException('Bundle reservation conflict.'); }
    }
    $db->commit();
    return $request_id;
  } catch(Throwable $e){
    if($db->in_transaction()){ $db->rollback(); }
    return false;
  }
}

function create_pickup_request($data = array()){
  global $db;
  if(!empty($data['bundle_ids'])){ return create_multi_bundle_pickup_request($data); }
  ensure_warehouse_schema();
  $client_id = isset($data['client_id']) ? (int)$data['client_id'] : 0;
  $product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
  $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 0;
  $data['pickup_date'] = !empty($data['pickup_date']) ? $data['pickup_date'] : date('Y-m-d');
  $data['pickup_time'] = !empty($data['pickup_time']) ? $data['pickup_time'] : '00:00';
  $data['driver_name'] = isset($data['driver_name']) ? trim((string)$data['driver_name']) : '';
  $data['vehicle_no'] = isset($data['vehicle_no']) ? trim((string)$data['vehicle_no']) : '';
  $data['fulfillment_method'] = normalize_pickup_fulfillment_method(isset($data['fulfillment_method']) ? $data['fulfillment_method'] : 'self_pickup');
  $data['delivery_address'] = isset($data['delivery_address']) ? trim((string)$data['delivery_address']) : '';
  $data['client_id'] = $client_id;
  $self_pickup_invalid = $data['fulfillment_method'] === 'self_pickup' && ($data['driver_name'] === '' || $data['vehicle_no'] === '');
  $delivery_invalid = $data['fulfillment_method'] === 'delivery' && $data['delivery_address'] === '';
  if($client_id <= 0 || $product_id <= 0 || $quantity <= 0 || $data['fulfillment_method'] === null || $self_pickup_invalid || $delivery_invalid || !valid_pickup_date_time($data['pickup_date'], $data['pickup_time'])){ return false; }
  if($data['fulfillment_method'] === 'delivery'){
    $data['driver_name'] = '';
    $data['vehicle_no'] = '';
  } else {
    $data['delivery_address'] = '';
  }
  try{
    $db->begin_transaction();
    $product_result = $db->query_or_throw("SELECT * FROM products WHERE id='{$product_id}' LIMIT 1 FOR UPDATE");
    $product = $db->fetch_assoc($product_result);
    if(!$product || (int)$product['client_id'] !== $client_id){ $db->rollback(); return false; }
    $bundle_check = $db->query_or_throw("SELECT id FROM inventory_bundles WHERE product_id='{$product_id}' LIMIT 1");
    // Bundled products must use exact bundle_ids; never silently interpret a
    // package count as base-unit quantity.
    if($db->fetch_assoc($bundle_check)){ $db->rollback(); return false; }
    $reserved_result = $db->query_or_throw("SELECT COALESCE(SUM(quantity),0) AS qty FROM pickup_request_items WHERE product_id='{$product_id}' AND status='reserved'");
    $reserved_row = $db->fetch_assoc($reserved_result);
    $available = (int)$product['quantity'] - ($reserved_row ? (int)$reserved_row['qty'] : 0);
    // Aggregate legacy requests are only safe when an administrator has
    // explicitly identified the base unit. Never reuse a package unit as a
    // guessed pcs/lembar unit.
    if(empty($product['base_unit_id'])){ $db->rollback(); return false; }
    $unit_id = (int)$product['base_unit_id'];
    if($quantity > $available){
      $request_id = insert_pickup_request_header_locked($data, $product_id, $unit_id, $quantity, 'auto_rejected', 'Jumlah request melebihi stok yang belum direservasi. Stok tersedia: '.max(0, $available).'.');
      $db->commit();
      return $request_id;
    }
    $request_id = insert_pickup_request_header_locked($data, $product_id, $unit_id, $quantity, 'pending', null);
    $now = make_date();
    $package_value = !empty($product['unit_id']) ? "'".(int)$product['unit_id']."'" : 'NULL';
    $snapshot = array();
    foreach(array('name','no_surat_jalan','no_batch','grade','tebal','lebar','panjang') as $field){
      $snapshot[$field] = isset($product[$field]) && $product[$field] !== null && $product[$field] !== '' ? "'".$db->escape($product[$field])."'" : 'NULL';
    }
    $db->query_or_throw("INSERT INTO pickup_request_items (pickup_request_id,bundle_id,product_id,base_unit_id,package_unit_id,quantity,bundle_no,product_name,no_surat_jalan,no_batch,grade,tebal,lebar,panjang,status,created_at,updated_at) VALUES ('{$request_id}',NULL,'{$product_id}',".($unit_id > 0 ? "'{$unit_id}'" : 'NULL').",{$package_value},'{$quantity}',NULL,{$snapshot['name']},{$snapshot['no_surat_jalan']},{$snapshot['no_batch']},{$snapshot['grade']},{$snapshot['tebal']},{$snapshot['lebar']},{$snapshot['panjang']},'reserved','{$now}','{$now}')");
    $db->commit();
    return $request_id;
  } catch(Throwable $e){
    if($db->in_transaction()){ $db->rollback(); }
    return false;
  }
}

function load_or_create_request_items_locked($request, $legacy_product = null){
  global $db;
  $request_id = (int)$request['id'];
  // Keep one global lock order: request header (locked by caller), products,
  // request items, then physical bundles. The seed read is safe because every
  // state-changing request workflow locks the request header first.
  $product_ids = array();
  $seed_result = $db->query_or_throw("SELECT DISTINCT product_id FROM pickup_request_items WHERE pickup_request_id='{$request_id}' ORDER BY product_id ASC");
  while($seed = $db->fetch_assoc($seed_result)){ $product_ids[(int)$seed['product_id']] = (int)$seed['product_id']; }
  if(empty($product_ids)){ $product_ids[(int)$request['product_id']] = (int)$request['product_id']; }
  sort($product_ids, SORT_NUMERIC);
  $products = array();
  foreach($product_ids as $product_id){
    if($legacy_product && (int)$legacy_product['id'] === $product_id){
      $product = $legacy_product;
    } else {
      $product_result = $db->query_or_throw("SELECT * FROM products WHERE id='{$product_id}' LIMIT 1 FOR UPDATE");
      $product = $db->fetch_assoc($product_result);
    }
    if(!$product || (int)$product['client_id'] !== (int)$request['client_id']){ return false; }
    $products[$product_id] = $product;
  }
  $items_result = $db->query_or_throw("SELECT * FROM pickup_request_items WHERE pickup_request_id='{$request_id}' ORDER BY id ASC FOR UPDATE");
  $items = array();
  while($item = $db->fetch_assoc($items_result)){
    if(!isset($products[(int)$item['product_id']])){ return false; }
    $items[] = $item;
  }
  if(empty($items)){
    $product = $products[(int)$request['product_id']];
    $bundle_result = $db->query_or_throw("SELECT id FROM inventory_bundles WHERE product_id='".(int)$product['id']."' LIMIT 1");
    if($db->fetch_assoc($bundle_result)){ return false; }
    $reserved_result = $db->query_or_throw("SELECT COALESCE(SUM(quantity),0) AS qty FROM pickup_request_items WHERE product_id='".(int)$product['id']."' AND status='reserved'");
    $reserved = $db->fetch_assoc($reserved_result);
    if((int)$request['quantity'] > (int)$product['quantity'] - ($reserved ? (int)$reserved['qty'] : 0)){ return false; }
    if(empty($product['base_unit_id']) || empty($request['unit_id']) || (int)$request['unit_id'] !== (int)$product['base_unit_id']){ return false; }
    $base_unit_id = (int)$product['base_unit_id'];
    $now = make_date();
    $package_value = !empty($product['unit_id']) ? "'".(int)$product['unit_id']."'" : 'NULL';
    $snapshot = array();
    foreach(array('name','no_surat_jalan','no_batch','grade','tebal','lebar','panjang') as $field){
      $snapshot[$field] = isset($product[$field]) && $product[$field] !== null && $product[$field] !== '' ? "'".$db->escape($product[$field])."'" : 'NULL';
    }
    $db->query_or_throw("INSERT INTO pickup_request_items (pickup_request_id,bundle_id,product_id,base_unit_id,package_unit_id,quantity,bundle_no,product_name,no_surat_jalan,no_batch,grade,tebal,lebar,panjang,status,created_at,updated_at) VALUES ('{$request_id}',NULL,'".(int)$product['id']."',".($base_unit_id > 0 ? "'{$base_unit_id}'" : 'NULL').",{$package_value},'".(int)$request['quantity']."',NULL,{$snapshot['name']},{$snapshot['no_surat_jalan']},{$snapshot['no_batch']},{$snapshot['grade']},{$snapshot['tebal']},{$snapshot['lebar']},{$snapshot['panjang']},'reserved','{$now}','{$now}')");
    $item_id = $db->insert_id();
    $items[] = array('id'=>$item_id,'pickup_request_id'=>$request_id,'bundle_id'=>null,'product_id'=>(int)$product['id'],'base_unit_id'=>$base_unit_id,'package_unit_id'=>(int)$product['unit_id'],'quantity'=>(int)$request['quantity'],'bundle_no'=>null,'product_name'=>$product['name'],'no_surat_jalan'=>$product['no_surat_jalan'],'no_batch'=>$product['no_batch'],'grade'=>$product['grade'],'tebal'=>$product['tebal'],'lebar'=>$product['lebar'],'panjang'=>$product['panjang'],'status'=>'reserved');
  }
  $sum = 0;
  foreach($items as $item){
    $product_id = (int)$item['product_id'];
    if($item['status'] !== 'reserved' || !isset($products[$product_id]) || (int)$item['quantity'] <= 0){ return false; }
    $sum += (int)$item['quantity'];
    if(!empty($item['bundle_id'])){
      $bundle_result = $db->query_or_throw("SELECT * FROM inventory_bundles WHERE id='".(int)$item['bundle_id']."' LIMIT 1 FOR UPDATE");
      $bundle = $db->fetch_assoc($bundle_result);
      if(!$bundle || $bundle['status'] !== 'reserved' || (int)$bundle['reserved_request_id'] !== $request_id || (int)$bundle['quantity'] !== (int)$item['quantity']){ return false; }
    } else {
      $product = $products[$product_id];
      if(empty($product['base_unit_id']) || empty($request['unit_id']) || (int)$request['unit_id'] !== (int)$product['base_unit_id'] || (int)$item['base_unit_id'] !== (int)$product['base_unit_id']){ return false; }
    }
  }
  foreach($products as $product_id => $product){
    $bundle_check = $db->query_or_throw("SELECT id FROM inventory_bundles WHERE product_id='{$product_id}' LIMIT 1");
    if($db->fetch_assoc($bundle_check)){
      $active_result = $db->query_or_throw("SELECT COALESCE(SUM(quantity),0) AS qty FROM inventory_bundles WHERE product_id='{$product_id}' AND status IN ('available','reserved')");
      $active = $db->fetch_assoc($active_result);
      if(!$active || (int)$active['qty'] !== (int)$product['quantity']){ return false; }
    }
  }
  return $sum === (int)$request['quantity'] ? $items : false;
}

function approve_pickup_request($id){
  global $db;
  ensure_consignment_tables();
  ensure_warehouse_schema();
  $id = (int)$id;
  if($id <= 0){ return false; }
  try{
    $db->begin_transaction();
    $request_result = $db->query_or_throw("SELECT * FROM pickup_requests WHERE id='{$id}' LIMIT 1 FOR UPDATE");
    $request = $db->fetch_assoc($request_result);
    if(!$request || !in_array($request['status'], array('pending','approved'), true)){ $db->rollback(); return false; }
    $orders_result = $db->query_or_throw("SELECT * FROM delivery_orders WHERE pickup_request_id='{$id}' ORDER BY id ASC FOR UPDATE");
    $orders = array();
    while($order_row = $db->fetch_assoc($orders_result)){ $orders[] = $order_row; }
    if(count($orders) > 1){ $db->rollback(); return false; }
    $items = load_or_create_request_items_locked($request);
    if($items === false){
      if($request['status'] === 'pending'){
        $now = make_date();
        // Validation can fail precisely because a reservation drifted. Release
        // every bundle still owned by this request and terminate the request
        // instead of rolling it back into a permanently pending state.
        release_pickup_reservations_locked($id, 'released', false);
        $actor = warehouse_actor_id();
        $actor_value = $actor > 0 ? "'{$actor}'" : 'NULL';
        $db->query_or_throw("UPDATE pickup_requests SET status='auto_rejected',admin_note='Reservasi tidak lagi valid atau stok tidak mencukupi saat persetujuan.',processed_by={$actor_value},processed_at='{$now}' WHERE id='{$id}' AND status='pending' LIMIT 1");
        if($db->affected_rows() !== 1){ throw new RuntimeException('Pickup auto rejection conflict.'); }
        $db->commit();
      } else { $db->rollback(); }
      return false;
    }
    $now = make_date();
    $delivery_id = 0;
    if(!empty($orders)){
      $delivery_id = (int)$orders[0]['id'];
      if((int)$orders[0]['stock_processed'] === 1 && $request['status'] !== 'completed'){ $db->rollback(); return false; }
    } else {
      $client_result = $db->query_or_throw("SELECT name FROM users WHERE id='".(int)$request['client_id']."' LIMIT 1");
      $client = $db->fetch_assoc($client_result);
      $recipient = $db->escape($client ? $client['name'] : '');
      $document_no = $db->escape(generate_consignment_number('SJ'));
      $created_by = warehouse_actor_id();
      $created_by_value = $created_by > 0 ? "'{$created_by}'" : 'NULL';
      $scheduled = $db->escape($request['pickup_date'].' '.$request['pickup_time']);
      $driver = $db->escape($request['driver_name']);
      $vehicle = $db->escape($request['vehicle_no']);
      $fulfillment_note = normalize_pickup_fulfillment_method(isset($request['fulfillment_method']) ? $request['fulfillment_method'] : 'self_pickup') === 'delivery'
        ? 'Surat jalan pengiriman barang dari request pelanggan.'
        : 'Surat jalan pengambilan barang oleh pelanggan.';
      $db->query_or_throw("INSERT INTO delivery_orders (document_no,movement_type,client_id,product_id,quantity,document_date,recipient,driver_name,vehicle_no,reference_type,reference_id,pickup_request_id,scheduled_at,stock_processed,stock_processed_at,note,created_by,created_at) VALUES ('{$document_no}','out','".(int)$request['client_id']."','".(int)$request['product_id']."','".(int)$request['quantity']."','".date('Y-m-d')."','{$recipient}','{$driver}','{$vehicle}','request_pengambilan','{$id}','{$id}','{$scheduled}','0',NULL,'".$db->escape($fulfillment_note)."',{$created_by_value},'{$now}')");
      $delivery_id = $db->insert_id();
    }
    $detail_check = $db->query_or_throw("SELECT COUNT(*) AS total FROM delivery_order_items WHERE delivery_order_id='{$delivery_id}'");
    $detail_row = $db->fetch_assoc($detail_check);
    if(!$detail_row || (int)$detail_row['total'] === 0){
      foreach($items as $item){
        $pickup_item_value = !empty($item['id']) ? "'".(int)$item['id']."'" : 'NULL';
        $bundle_value = !empty($item['bundle_id']) ? "'".(int)$item['bundle_id']."'" : 'NULL';
        $base_value = !empty($item['base_unit_id']) ? "'".(int)$item['base_unit_id']."'" : 'NULL';
        $package_value = !empty($item['package_unit_id']) ? "'".(int)$item['package_unit_id']."'" : 'NULL';
        $bundle_no_value = !empty($item['bundle_no']) ? "'".$db->escape($item['bundle_no'])."'" : 'NULL';
        $snapshot = array();
        foreach(array('product_name','no_surat_jalan','no_batch','grade','tebal','lebar','panjang') as $field){
          $snapshot[$field] = isset($item[$field]) && $item[$field] !== null && $item[$field] !== '' ? "'".$db->escape($item[$field])."'" : 'NULL';
        }
        $db->query_or_throw("INSERT INTO delivery_order_items (delivery_order_id,pickup_request_item_id,bundle_id,product_id,base_unit_id,package_unit_id,quantity,bundle_no,product_name,no_surat_jalan,no_batch,grade,tebal,lebar,panjang,status,processed_at,created_at) VALUES ('{$delivery_id}',{$pickup_item_value},{$bundle_value},'".(int)$item['product_id']."',{$base_value},{$package_value},'".(int)$item['quantity']."',{$bundle_no_value},{$snapshot['product_name']},{$snapshot['no_surat_jalan']},{$snapshot['no_batch']},{$snapshot['grade']},{$snapshot['tebal']},{$snapshot['lebar']},{$snapshot['panjang']},'ready',NULL,'{$now}')");
      }
    } elseif((int)$detail_row['total'] !== count($items)){
      $db->rollback(); return false;
    }
    if($request['status'] === 'pending'){
      $actor = warehouse_actor_id();
      $actor_value = $actor > 0 ? "'{$actor}'" : 'NULL';
      $db->query_or_throw("UPDATE pickup_requests SET status='approved',processed_by={$actor_value},processed_at='{$now}',admin_note=NULL WHERE id='{$id}' AND status='pending' LIMIT 1");
      if($db->affected_rows() !== 1){ throw new RuntimeException('Pickup approval conflict.'); }
    }
    $db->commit();
    return true;
  } catch(Throwable $e){
    if($db->in_transaction()){ $db->rollback(); }
    return false;
  }
}

function release_pickup_reservations_locked($request_id, $item_status, $strict = true){
  global $db;
  $request_id = (int)$request_id;
  $now = make_date();
  $product_ids = array();
  $seed_result = $db->query_or_throw("SELECT DISTINCT product_id FROM pickup_request_items WHERE pickup_request_id='{$request_id}' ORDER BY product_id ASC");
  while($seed = $db->fetch_assoc($seed_result)){ $product_ids[(int)$seed['product_id']] = (int)$seed['product_id']; }
  sort($product_ids, SORT_NUMERIC);
  foreach($product_ids as $product_id){ $db->query_or_throw("SELECT id FROM products WHERE id='{$product_id}' LIMIT 1 FOR UPDATE"); }
  $items_result = $db->query_or_throw("SELECT * FROM pickup_request_items WHERE pickup_request_id='{$request_id}' ORDER BY product_id,id ASC FOR UPDATE");
  $items = array();
  while($item = $db->fetch_assoc($items_result)){ $items[] = $item; }
  foreach($items as $item){
    if($item['status'] !== 'reserved'){ continue; }
    if(!empty($item['bundle_id'])){
      $db->query_or_throw("UPDATE inventory_bundles SET status='available',reserved_request_id=NULL,reserved_at=NULL,updated_at='{$now}' WHERE id='".(int)$item['bundle_id']."' AND status='reserved' AND reserved_request_id='{$request_id}' LIMIT 1");
      if($db->affected_rows() !== 1 && $strict){ throw new RuntimeException('Bundle reservation release conflict.'); }
    }
  }
  $db->query_or_throw("UPDATE pickup_request_items SET status='".$db->escape($item_status)."',updated_at='{$now}' WHERE pickup_request_id='{$request_id}' AND status='reserved'");
  return true;
}

function reject_pickup_request($id, $reason, $auto=false){
  global $db;
  ensure_warehouse_schema();
  $id = (int)$id;
  $reason = trim((string)$reason);
  if($id <= 0 || $reason === ''){ return false; }
  try{
    $db->begin_transaction();
    $request_result = $db->query_or_throw("SELECT * FROM pickup_requests WHERE id='{$id}' LIMIT 1 FOR UPDATE");
    $request = $db->fetch_assoc($request_result);
    $allowed_statuses = $auto ? array('pending') : array('pending','approved');
    if(!$request || !in_array($request['status'], $allowed_statuses, true)){ $db->rollback(); return false; }
    $original_status = $request['status'];
    $orders_result = $db->query_or_throw("SELECT * FROM delivery_orders WHERE pickup_request_id='{$id}' ORDER BY id ASC FOR UPDATE");
    $delivery_ids = array();
    while($order = $db->fetch_assoc($orders_result)){
      if((int)$order['stock_processed'] === 1){ $db->rollback(); return false; }
      $delivery_ids[] = (int)$order['id'];
    }
    // Recovery for older non-transactional approvals: an approved request may
    // have zero or duplicate unprocessed DO headers. Rejecting is the only safe
    // exit; every unprocessed header/detail is removed below in this transaction.
    // Rejection is also the recovery path for a partially committed/legacy
    // request. Release only bundles still owned by this request and terminate
    // stale item rows without touching a bundle now owned by another request.
    release_pickup_reservations_locked($id, 'released', false);
    if(!empty($delivery_ids)){
      $delivery_list = implode(',', $delivery_ids);
      $db->query_or_throw("DELETE FROM delivery_order_items WHERE delivery_order_id IN ({$delivery_list})");
      $db->query_or_throw("DELETE FROM delivery_orders WHERE id IN ({$delivery_list}) AND stock_processed='0'");
      if($db->affected_rows() !== count($delivery_ids)){ throw new RuntimeException('Pickup delivery cancellation conflict.'); }
    }
    $status = $auto ? 'auto_rejected' : 'rejected';
    $actor = warehouse_actor_id();
    $actor_value = $actor > 0 ? "'{$actor}'" : 'NULL';
    $now = make_date();
    $db->query_or_throw("UPDATE pickup_requests SET status='{$status}',admin_note='".$db->escape($reason)."',processed_by={$actor_value},processed_at='{$now}' WHERE id='{$id}' AND status='".$db->escape($original_status)."' LIMIT 1");
    if($db->affected_rows() !== 1){ throw new RuntimeException('Pickup rejection conflict.'); }
    $db->commit();
    return true;
  } catch(Throwable $e){
    if($db->in_transaction()){ $db->rollback(); }
    return false;
  }
}

function cancel_pickup_request($id, $client_id = null, $reason = 'Request dibatalkan.'){
  global $db;
  ensure_consignment_tables();
  ensure_warehouse_schema();
  $id = (int)$id;
  if($id <= 0){ return false; }
  try{
    $db->begin_transaction();
    $request_result = $db->query_or_throw("SELECT * FROM pickup_requests WHERE id='{$id}' LIMIT 1 FOR UPDATE");
    $request = $db->fetch_assoc($request_result);
    if(!$request || $request['status'] !== 'pending' || ($client_id !== null && (int)$request['client_id'] !== (int)$client_id)){ $db->rollback(); return false; }
    $orders_result = $db->query_or_throw("SELECT * FROM delivery_orders WHERE pickup_request_id='{$id}' ORDER BY id ASC FOR UPDATE");
    $delivery_ids = array();
    while($order = $db->fetch_assoc($orders_result)){ $delivery_ids[] = (int)$order['id']; }
    if(!empty($delivery_ids)){ $db->rollback(); return false; }
    release_pickup_reservations_locked($id, 'cancelled');
    $now = make_date();
    $db->query_or_throw("UPDATE pickup_requests SET status='cancelled',admin_note='".$db->escape(trim((string)$reason))."',processed_at='{$now}' WHERE id='{$id}' AND status='pending' LIMIT 1");
    if($db->affected_rows() !== 1){ throw new RuntimeException('Pickup cancellation conflict.'); }
    $db->commit();
    return true;
  } catch(Throwable $e){
    if($db->in_transaction()){ $db->rollback(); }
    return false;
  }
}

function process_delivery_order_stock($delivery_id, $transport_data = array()){
  global $db;
  ensure_consignment_tables();
  ensure_warehouse_schema();
  $delivery_id = (int)$delivery_id;
  if($delivery_id <= 0){ return false; }
  $seed_result = $db->query("SELECT pickup_request_id FROM delivery_orders WHERE id='{$delivery_id}' LIMIT 1");
  $seed = $db->fetch_assoc($seed_result);
  if(!$seed){ return false; }
  $request_id = !empty($seed['pickup_request_id']) ? (int)$seed['pickup_request_id'] : 0;
  try{
    $db->begin_transaction();
    $request = null;
    if($request_id > 0){
      $request_result = $db->query_or_throw("SELECT * FROM pickup_requests WHERE id='{$request_id}' LIMIT 1 FOR UPDATE");
      $request = $db->fetch_assoc($request_result);
      if(!$request){ $db->rollback(); return false; }
    }
    $order_result = $db->query_or_throw("SELECT * FROM delivery_orders WHERE id='{$delivery_id}' LIMIT 1 FOR UPDATE");
    $order = $db->fetch_assoc($order_result);
    if(!$order || (int)$order['pickup_request_id'] !== $request_id){ $db->rollback(); return false; }
    if((int)$order['stock_processed'] === 1){
      if($request && $request['status'] === 'approved'){
        $db->query_or_throw("UPDATE pickup_requests SET status='completed' WHERE id='{$request_id}' AND status='approved' LIMIT 1");
      }
      $db->commit();
      return true;
    }
    if($order['movement_type'] !== 'out'){ $db->commit(); return true; }
    if($request){
      if($request['status'] !== 'approved'){ $db->rollback(); return false; }
      $fulfillment_method = normalize_pickup_fulfillment_method(isset($request['fulfillment_method']) ? $request['fulfillment_method'] : 'self_pickup');
      if($fulfillment_method === null){ $db->rollback(); return false; }
      if($fulfillment_method === 'delivery'){
        $driver_name = isset($transport_data['driver_name']) ? trim((string)$transport_data['driver_name']) : trim((string)$request['driver_name']);
        $vehicle_no = isset($transport_data['vehicle_no']) ? trim((string)$transport_data['vehicle_no']) : trim((string)$request['vehicle_no']);
        if($driver_name === '' || $vehicle_no === '' || trim((string)$request['delivery_address']) === ''){ $db->rollback(); return false; }
        $driver_value = $db->escape($driver_name);
        $vehicle_value = $db->escape($vehicle_no);
        $db->query_or_throw("UPDATE pickup_requests SET driver_name='{$driver_value}',vehicle_no='{$vehicle_value}' WHERE id='{$request_id}' LIMIT 1");
        $db->query_or_throw("UPDATE delivery_orders SET driver_name='{$driver_value}',vehicle_no='{$vehicle_value}' WHERE id='{$delivery_id}' AND stock_processed='0' LIMIT 1");
        $request['driver_name'] = $driver_name;
        $request['vehicle_no'] = $vehicle_no;
        $order['driver_name'] = $driver_name;
        $order['vehicle_no'] = $vehicle_no;
      }
      $duplicate_result = $db->query_or_throw("SELECT COUNT(*) AS total FROM delivery_orders WHERE pickup_request_id='{$request_id}'");
      $duplicate = $db->fetch_assoc($duplicate_result);
      if(!$duplicate || (int)$duplicate['total'] !== 1){ $db->rollback(); return false; }
    }
    $items_result = $db->query_or_throw("SELECT * FROM delivery_order_items WHERE delivery_order_id='{$delivery_id}' ORDER BY product_id,id ASC FOR UPDATE");
    $items = array();
    while($item = $db->fetch_assoc($items_result)){ $items[] = $item; }
    $legacy_delivery_fallback = empty($items);
    if($legacy_delivery_fallback){
      // Legacy non-bundle DO compatibility. New pickup approvals always have details.
      $items[] = array('id'=>0,'pickup_request_item_id'=>null,'bundle_id'=>null,'product_id'=>(int)$order['product_id'],'base_unit_id'=>null,'quantity'=>(int)$order['quantity'],'status'=>'ready');
    }
    $totals = array();
    $product_ids = array();
    foreach($items as $item){
      $product_id = (int)$item['product_id'];
      $quantity = (int)$item['quantity'];
      if($product_id <= 0 || $quantity <= 0 || !in_array($item['status'], array('ready','reserved'), true)){ $db->rollback(); return false; }
      if(!isset($totals[$product_id])){ $totals[$product_id] = 0; }
      $totals[$product_id] += $quantity;
      $product_ids[$product_id] = $product_id;
    }
    sort($product_ids, SORT_NUMERIC);
    $products = array();
    foreach($product_ids as $product_id){
      $product_result = $db->query_or_throw("SELECT * FROM products WHERE id='{$product_id}' LIMIT 1 FOR UPDATE");
      $product = $db->fetch_assoc($product_result);
      if(!$product || (int)$product['quantity'] < (int)$totals[$product_id] || (!empty($order['client_id']) && (int)$product['client_id'] !== (int)$order['client_id'])){ $db->rollback(); return false; }
      $products[$product_id] = $product;
    }
    // Older approved requests can have a DO header but no request/DO item
    // rows. Materialize one auditable legacy detail lazily, but only while the
    // product has never been converted to physical bundles.
    if($legacy_delivery_fallback && $request){
      $legacy_product_id = (int)$order['product_id'];
      $legacy_product = isset($products[$legacy_product_id]) ? $products[$legacy_product_id] : null;
      // Never guess whether an old header quantity meant packages or base
      // units. Only a product with an explicit matching base unit is safe to
      // process; ambiguous historical approvals must be cancelled/audited.
      if(!$legacy_product || empty($legacy_product['base_unit_id']) || (int)$request['unit_id'] !== (int)$legacy_product['base_unit_id'] || product_has_bundle_details($legacy_product_id) || (int)$request['quantity'] !== (int)$order['quantity']){ $db->rollback(); return false; }
      $legacy_request_items = load_or_create_request_items_locked($request, $legacy_product);
      if(!is_array($legacy_request_items) || count($legacy_request_items) !== 1 || !empty($legacy_request_items[0]['bundle_id']) || (int)$legacy_request_items[0]['quantity'] !== (int)$order['quantity']){ $db->rollback(); return false; }
      $legacy_item = $legacy_request_items[0];
      $base_value = !empty($legacy_item['base_unit_id']) ? "'".(int)$legacy_item['base_unit_id']."'" : 'NULL';
      $package_value = !empty($legacy_item['package_unit_id']) ? "'".(int)$legacy_item['package_unit_id']."'" : 'NULL';
      $snapshot = array();
      foreach(array('product_name','no_surat_jalan','no_batch','grade','tebal','lebar','panjang') as $field){
        $snapshot[$field] = isset($legacy_item[$field]) && $legacy_item[$field] !== null && $legacy_item[$field] !== '' ? "'".$db->escape($legacy_item[$field])."'" : 'NULL';
      }
      $now = make_date();
      $db->query_or_throw("INSERT INTO delivery_order_items (delivery_order_id,pickup_request_item_id,bundle_id,product_id,base_unit_id,package_unit_id,quantity,bundle_no,product_name,no_surat_jalan,no_batch,grade,tebal,lebar,panjang,status,processed_at,created_at) VALUES ('{$delivery_id}','".(int)$legacy_item['id']."',NULL,'{$legacy_product_id}',{$base_value},{$package_value},'".(int)$legacy_item['quantity']."',NULL,{$snapshot['product_name']},{$snapshot['no_surat_jalan']},{$snapshot['no_batch']},{$snapshot['grade']},{$snapshot['tebal']},{$snapshot['lebar']},{$snapshot['panjang']},'ready',NULL,'{$now}')");
      $legacy_item['id'] = $db->insert_id();
      $legacy_item['pickup_request_item_id'] = (int)$legacy_request_items[0]['id'];
      $legacy_item['status'] = 'ready';
      $items = array($legacy_item);
    }
    foreach($items as $item){
      $product_id = (int)$item['product_id'];
      if($request_id > 0){
        if(empty($item['pickup_request_item_id'])){ $db->rollback(); return false; }
        $request_item_result = $db->query_or_throw("SELECT * FROM pickup_request_items WHERE id='".(int)$item['pickup_request_item_id']."' LIMIT 1 FOR UPDATE");
        $request_item = $db->fetch_assoc($request_item_result);
        if(!$request_item || (int)$request_item['pickup_request_id'] !== $request_id || $request_item['status'] !== 'reserved' || (int)$request_item['product_id'] !== $product_id || (int)$request_item['quantity'] !== (int)$item['quantity'] || (int)$request_item['bundle_id'] !== (int)$item['bundle_id'] || (int)$request_item['base_unit_id'] !== (int)$item['base_unit_id']){ $db->rollback(); return false; }
      }
      $has_bundles_result = $db->query_or_throw("SELECT id FROM inventory_bundles WHERE product_id='{$product_id}' LIMIT 1");
      $has_bundles = (bool)$db->fetch_assoc($has_bundles_result);
      if($has_bundles && empty($item['bundle_id'])){ $db->rollback(); return false; }
      if($request_id > 0 && empty($item['bundle_id'])){
        $product = $products[$product_id];
        if(empty($product['base_unit_id']) || empty($request['unit_id']) || (int)$request['unit_id'] !== (int)$product['base_unit_id'] || (int)$item['base_unit_id'] !== (int)$product['base_unit_id']){ $db->rollback(); return false; }
      }
      if(!empty($item['bundle_id'])){
        $bundle_result = $db->query_or_throw("SELECT * FROM inventory_bundles WHERE id='".(int)$item['bundle_id']."' LIMIT 1 FOR UPDATE");
        $bundle = $db->fetch_assoc($bundle_result);
        if(!$bundle || $bundle['status'] !== 'reserved' || (int)$bundle['product_id'] !== $product_id || (int)$bundle['quantity'] !== (int)$item['quantity'] || (int)$bundle['base_unit_id'] !== (int)$item['base_unit_id'] || (!empty($products[$product_id]['base_unit_id']) && (int)$products[$product_id]['base_unit_id'] !== (int)$bundle['base_unit_id']) || ($request_id > 0 && (int)$bundle['reserved_request_id'] !== $request_id)){ $db->rollback(); return false; }
      }
    }
    $now = make_date();
    foreach($products as $product_id => $product){
      $quantity = (int)$totals[$product_id];
      $before = (int)$product['quantity'];
      $after = $before - $quantity;
      if(product_has_bundle_details($product_id)){
        $active_result = $db->query_or_throw("SELECT COALESCE(SUM(quantity),0) AS qty FROM inventory_bundles WHERE product_id='{$product_id}' AND status IN ('available','reserved')");
        $active = $db->fetch_assoc($active_result);
        if(!$active || (int)$active['qty'] !== $before){ $db->rollback(); return false; }
      }
      $db->query_or_throw("UPDATE products SET quantity=quantity-'{$quantity}' WHERE id='{$product_id}' AND quantity>='{$quantity}' LIMIT 1");
      if($db->affected_rows() !== 1){ throw new RuntimeException('Insufficient or concurrently changed stock.'); }
      $base_unit_id = !empty($product['base_unit_id']) ? (int)$product['base_unit_id'] : (int)$product['unit_id'];
      $unit_value = $base_unit_id > 0 ? "'{$base_unit_id}'" : 'NULL';
      $client_value = !empty($product['client_id']) ? "'".(int)$product['client_id']."'" : 'NULL';
      $actor = warehouse_actor_id();
      $actor_value = $actor > 0 ? "'{$actor}'" : 'NULL';
      $event_key = $db->escape('delivery-order:'.$delivery_id.':product:'.$product_id);
      $db->query_or_throw("INSERT INTO stock_movements (product_id,client_id,movement_type,quantity,unit_id,quantity_before,quantity_after,reference_type,reference_id,event_key,note,created_by,created_at) VALUES ('{$product_id}',{$client_value},'out','{$quantity}',{$unit_value},'{$before}','{$after}','surat_jalan','{$delivery_id}','{$event_key}','Stok keluar saat serah terima surat jalan',{$actor_value},'{$now}')");
    }
    foreach($items as $item){
      if(!empty($item['bundle_id'])){
        $db->query_or_throw("UPDATE inventory_bundles SET status='out',reserved_request_id=NULL,reserved_at=NULL,out_delivery_order_id='{$delivery_id}',out_at='{$now}',updated_at='{$now}' WHERE id='".(int)$item['bundle_id']."' AND status='reserved' LIMIT 1");
        if($db->affected_rows() !== 1){ throw new RuntimeException('Bundle fulfilment conflict.'); }
      }
    }
    if($request_id > 0){
      $db->query_or_throw("UPDATE pickup_request_items SET status='out',updated_at='{$now}' WHERE pickup_request_id='{$request_id}' AND status='reserved'");
      if($db->affected_rows() !== count($items)){ throw new RuntimeException('Pickup item completion conflict.'); }
    }
    $db->query_or_throw("UPDATE delivery_order_items SET status='out',processed_at='{$now}' WHERE delivery_order_id='{$delivery_id}' AND status='ready'");
    if(!empty($items) && (int)$items[0]['id'] > 0 && $db->affected_rows() !== count($items)){ throw new RuntimeException('Delivery item completion conflict.'); }
    $db->query_or_throw("UPDATE delivery_orders SET stock_processed='1',stock_processed_at='{$now}' WHERE id='{$delivery_id}' AND stock_processed='0' LIMIT 1");
    if($db->affected_rows() !== 1){ throw new RuntimeException('Delivery order was already processed.'); }
    if($request_id > 0){
      $db->query_or_throw("UPDATE pickup_requests SET status='completed' WHERE id='{$request_id}' AND status='approved' LIMIT 1");
      if($db->affected_rows() !== 1){ throw new RuntimeException('Pickup completion conflict.'); }
    }
    foreach($products as $product_id => $product){
      if(product_has_bundle_details($product_id)){
        $check_result = $db->query_or_throw("SELECT p.quantity,COALESCE(SUM(CASE WHEN b.status IN ('available','reserved') THEN b.quantity ELSE 0 END),0) AS active_qty FROM products p LEFT JOIN inventory_bundles b ON b.product_id=p.id WHERE p.id='{$product_id}' GROUP BY p.id,p.quantity");
        $check = $db->fetch_assoc($check_result);
        if(!$check || (int)$check['quantity'] !== (int)$check['active_qty']){ throw new RuntimeException('Bundle/product stock invariant failed.'); }
      }
    }
    $db->commit();
    return true;
  } catch(Throwable $e){
    if($db->in_transaction()){ $db->rollback(); }
    return false;
  }
}

function process_pickup_request_stock($request_id, $transport_data = array()){
  global $db;
  ensure_consignment_tables();
  ensure_warehouse_schema();
  $request_id = (int)$request_id;
  $result = $db->query("SELECT id FROM delivery_orders WHERE pickup_request_id='{$request_id}' ORDER BY id ASC");
  $ids = array();
  while($row = $db->fetch_assoc($result)){ $ids[] = (int)$row['id']; }
  if(count($ids) !== 1){ return false; }
  return process_delivery_order_stock($ids[0], $transport_data);
}

function fulfill_delivery_order($delivery_id){
  return process_delivery_order_stock($delivery_id);
}

ensure_warehouse_schema();

?>
