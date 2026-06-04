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
    $sql    = "SELECT COUNT(id) AS total FROM ".$db->escape($table);
    $result = $db->query($sql);
     return($db->fetch_assoc($result));
  }
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

    $sql  = "SELECT p.*,c.name AS categorie,m.file_name AS image,u.name AS client_name,un.name AS unit_name ";
    $sql .= "FROM products p ";
    $sql .= "LEFT JOIN categories c ON c.id = p.categorie_id ";
    $sql .= "LEFT JOIN media m ON m.id = p.media_id ";
    $sql .= "LEFT JOIN users u ON u.id = p.client_id ";
    $sql .= "LEFT JOIN units un ON un.id = p.unit_id ";
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

     $sql  =" SELECT p.id,p.name,p.quantity,p.buy_price,p.sale_price,p.client_id,p.unit_id,p.media_id,p.date,";
     $sql .= "(SELECT COALESCE(SUM(quantity),0) FROM stock_movements sm WHERE sm.product_id=p.id AND sm.movement_type='out') AS total_out,";
     $sql .= "(SELECT MAX(created_at) FROM stock_movements sm WHERE sm.product_id=p.id AND sm.movement_type='out') AS last_out_date,";
     $sql .= " c.name AS categorie,m.file_name AS image,u.name AS client_name,un.name AS unit_name";
     $sql .= " FROM products p";
    $sql  .=" LEFT JOIN categories c ON c.id = p.categorie_id";
    $sql  .=" LEFT JOIN media m ON m.id = p.media_id";
    $sql  .=" LEFT JOIN users u ON u.id = p.client_id";
    $sql  .=" LEFT JOIN units un ON un.id = p.unit_id";

    if($client_id !== null){
      $sql .= " WHERE p.client_id='".$db->escape((int)$client_id)."'";
    }

    $sql  .=" ORDER BY p.id ASC";
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

     $sql  = "SELECT p.id,p.name,p.quantity,p.client_id,p.unit_id,u.name AS client_name,un.name AS unit_name ";
     $sql .= "FROM products p ";
     $sql .= "LEFT JOIN users u ON u.id = p.client_id ";
     $sql .= "LEFT JOIN units un ON un.id = p.unit_id ";
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

    $sql  = "SELECT p.*,u.name AS client_name,un.name AS unit_name FROM products p ";
    $sql .= "LEFT JOIN users u ON u.id = p.client_id ";
    $sql .= "LEFT JOIN units un ON un.id = p.unit_id ";
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
    $product_id = (int)$product_id;
    $delta = (int)$delta;
    $product = find_by_id('products', $product_id);

    if(!$product){
      return false;
    }

    $before = (int)$product['quantity'];
    $after = $before + $delta;

    if($after < 0){
      return false;
    }

    $sql = "UPDATE products SET quantity='".$db->escape($after)."' WHERE id='".$db->escape($product_id)."' LIMIT 1";
    $result = $db->query($sql);

    if(!$result){
      return false;
    }

    $product['quantity'] = $after;

    return array(
      'product' => $product,
      'before' => $before,
      'after' => $after,
      'delta' => $delta
    );
  }

  function set_product_quantity($product_id, $new_quantity){
    global $db;
    $product_id = (int)$product_id;
    $new_quantity = (int)$new_quantity;
    $product = find_by_id('products', $product_id);

    if(!$product || $new_quantity < 0){
      return false;
    }

    $before = (int)$product['quantity'];
    $sql = "UPDATE products SET quantity='".$db->escape($new_quantity)."' WHERE id='".$db->escape($product_id)."' LIMIT 1";
    $result = $db->query($sql);

    if(!$result){
      return false;
    }

    $product['quantity'] = $new_quantity;

    return array(
      'product' => $product,
      'before' => $before,
      'after' => $new_quantity,
      'delta' => $new_quantity - $before
    );
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
    $unit_id = isset($options['unit_id']) ? (int)$options['unit_id'] : (isset($product['unit_id']) ? (int)$product['unit_id'] : 0);
    $reference_type = isset($options['reference_type']) ? $db->escape($options['reference_type']) : '';
    $reference_id = isset($options['reference_id']) ? (int)$options['reference_id'] : 0;
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
    $note_value = $note !== '' ? "'{$note}'" : "NULL";
    $created_by_value = $created_by > 0 ? "'{$created_by}'" : "NULL";

    $sql  = "INSERT INTO stock_movements (";
    $sql .= "product_id,client_id,movement_type,quantity,unit_id,quantity_before,quantity_after,";
    $sql .= "reference_type,reference_id,note,created_by,created_at";
    $sql .= ") VALUES (";
    $unit_value = $unit_id > 0 ? "'{$unit_id}'" : "NULL";
    $sql .= "'".$db->escape((int)$product_id)."',{$client_value},'".$db->escape($movement_type)."',";
    $sql .= "'".$db->escape((int)$quantity)."',{$unit_value},'".$db->escape((int)$quantity_before)."',";
    $sql .= "'".$db->escape((int)$quantity_after)."',{$reference_type_value},{$reference_id_value},";
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
    global $db;

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

    $sql .= " ORDER BY b.due_date ASC, b.id DESC";
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

    $sql  = "SELECT d.*,u.name AS client_name,p.name AS product_name,un.name AS unit_name,actor.name AS created_by_name ";
    $sql .= "FROM delivery_orders d ";
    $sql .= "LEFT JOIN users u ON u.id = d.client_id ";
    $sql .= "LEFT JOIN products p ON p.id = d.product_id ";
    $sql .= "LEFT JOIN units un ON un.id = p.unit_id ";
    $sql .= "LEFT JOIN users actor ON actor.id = d.created_by";

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

    $sql  = "SELECT d.*,u.name AS client_name,u.username AS client_username,p.name AS product_name,un.name AS unit_name,";
    $sql .= "actor.name AS created_by_name ";
    $sql .= "FROM delivery_orders d ";
    $sql .= "LEFT JOIN users u ON u.id = d.client_id ";
    $sql .= "LEFT JOIN products p ON p.id = d.product_id ";
    $sql .= "LEFT JOIN units un ON un.id = p.unit_id ";
    $sql .= "LEFT JOIN users actor ON actor.id = d.created_by ";
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
 /*--------------------------------------------------------------*/
 /* Function for Find Highest selling Product
 /*--------------------------------------------------------------*/
 function find_higest_saleing_product($limit, $client_id = null){
   global $db;
   $viewer_client_id = current_client_id();

   if($viewer_client_id !== null){
     $client_id = $viewer_client_id;
   }

   $sql  = "SELECT p.name, COUNT(s.product_id) AS totalSold, SUM(s.qty) AS totalQty";
   $sql .= " FROM withdrawals s";
   $sql .= " LEFT JOIN products p ON p.id = s.product_id ";

   if($client_id !== null){
     $sql .= " WHERE p.client_id='".$db->escape((int)$client_id)."'";
   }

   $sql .= " GROUP BY s.product_id";
   $sql .= " ORDER BY SUM(s.qty) DESC LIMIT ".$db->escape((int)$limit);
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

   $sql  = "SELECT s.id,s.product_id,s.qty,s.price,s.date,p.name,u.name AS client_name,un.name AS unit_name";
   $sql .= " FROM withdrawals s";
   $sql .= " LEFT JOIN products p ON s.product_id = p.id";
   $sql .= " LEFT JOIN users u ON u.id = p.client_id";
   $sql .= " LEFT JOIN units un ON un.id = p.unit_id";

   if($client_id !== null){
     $sql .= " WHERE p.client_id='".$db->escape((int)$client_id)."'";
   }

   $sql .= " ORDER BY s.date DESC, s.id DESC";
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

  $sql  = "SELECT s.id,s.product_id,s.qty,s.price,s.date,p.name,u.name AS client_name,un.name AS unit_name";
  $sql .= " FROM withdrawals s";
  $sql .= " LEFT JOIN products p ON s.product_id = p.id";
  $sql .= " LEFT JOIN users u ON u.id = p.client_id";
  $sql .= " LEFT JOIN units un ON un.id = p.unit_id";

  if($client_id !== null){
    $sql .= " WHERE p.client_id='".$db->escape((int)$client_id)."'";
  }

  $sql .= " ORDER BY s.date DESC, s.id DESC LIMIT ".$db->escape((int)$limit);
  return find_by_sql($sql);
}
/*--------------------------------------------------------------*/
/* Function for Generate sales report by two dates
/*--------------------------------------------------------------*/
function find_sale_by_dates($start_date,$end_date){
  global $db;
  $start_date  = date("Y-m-d", strtotime($start_date));
  $end_date    = date("Y-m-d", strtotime($end_date));
  $sql  = "SELECT DATE(s.date) AS date, p.name,p.sale_price,p.buy_price,";
  $sql .= "COUNT(s.product_id) AS total_records,";
  $sql .= "SUM(s.qty) AS total_sales,";
  $sql .= "SUM(p.sale_price * s.qty) AS total_saleing_price,";
  $sql .= "SUM(p.buy_price * s.qty) AS total_buying_price ";
  $sql .= "FROM withdrawals s ";
  $sql .= "LEFT JOIN products p ON s.product_id = p.id";
  $sql .= " WHERE s.date BETWEEN '{$start_date}' AND '{$end_date}'";
  $sql .= " GROUP BY DATE(s.date),p.name,p.sale_price,p.buy_price";
  $sql .= " ORDER BY DATE(s.date) DESC";
  return $db->query($sql);
}
/*--------------------------------------------------------------*/
/* Function for Generate Daily sales report
/*--------------------------------------------------------------*/
function  dailySales($year,$month){
  global $db;
  $sql  = "SELECT SUM(s.qty) AS qty,";
  $sql .= " DATE_FORMAT(s.date, '%Y-%m-%e') AS date,p.name,";
  $sql .= "SUM(p.sale_price * s.qty) AS total_saleing_price";
  $sql .= " FROM withdrawals s";
  $sql .= " LEFT JOIN products p ON s.product_id = p.id";
  $sql .= " WHERE DATE_FORMAT(s.date, '%Y-%m' ) = '{$year}-{$month}'";
  $sql .= " GROUP BY DATE_FORMAT( s.date,  '%Y-%m-%e' ),s.product_id,p.name";
  return find_by_sql($sql);
}
/*--------------------------------------------------------------*/
/* Function for Generate Monthly sales report
/*--------------------------------------------------------------*/
function  monthlySales($year){
  global $db;
  $sql  = "SELECT SUM(s.qty) AS qty,";
  $sql .= " DATE_FORMAT(s.date, '%Y-%m-%e') AS date,p.name,";
  $sql .= "SUM(p.sale_price * s.qty) AS total_saleing_price";
  $sql .= " FROM withdrawals s";
  $sql .= " LEFT JOIN products p ON s.product_id = p.id";
  $sql .= " WHERE DATE_FORMAT(s.date, '%Y' ) = '{$year}'";
  $sql .= " GROUP BY DATE_FORMAT( s.date,  '%Y-%m-%e' ),s.product_id,p.name";
  $sql .= " ORDER BY DATE_FORMAT( s.date,  '%Y-%m-%e' ) ASC";
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
    pickup_date date NOT NULL,
    pickup_time time NOT NULL,
    driver_name varchar(100) NOT NULL,
    vehicle_no varchar(50) NOT NULL,
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

  if(tableExists('delivery_orders')){
    if(!column_exists('delivery_orders','pickup_request_id')){
      $db->query("ALTER TABLE delivery_orders ADD pickup_request_id int(11) unsigned DEFAULT NULL AFTER reference_id");
    }
    if(!column_exists('delivery_orders','scheduled_at')){
      $db->query("ALTER TABLE delivery_orders ADD scheduled_at datetime DEFAULT NULL AFTER pickup_request_id");
    }
    if(!column_exists('delivery_orders','stock_processed')){
      $db->query("ALTER TABLE delivery_orders ADD stock_processed tinyint(1) NOT NULL DEFAULT '1' AFTER scheduled_at");
    }
    if(!column_exists('delivery_orders','stock_processed_at')){
      $db->query("ALTER TABLE delivery_orders ADD stock_processed_at datetime DEFAULT NULL AFTER stock_processed");
    }
  }

  ensure_upload_directory(SITE_ROOT.DS.'..'.DS.'uploads'.DS.'defects');
  $done = true;
  return true;
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
  $name = remove_junk($db->escape($name));
  $description = remove_junk($db->escape($description));
  if($id <= 0 || $name === ''){ return false; }
  $sql = "UPDATE units SET name='{$name}', description='{$description}' WHERE id='{$id}' LIMIT 1";
  return $db->query($sql);
}

function unit_is_used($id){
  global $db;
  ensure_warehouse_schema();
  $id = (int)$id;
  $result = $db->query("SELECT id FROM products WHERE unit_id='{$id}' LIMIT 1");
  return ($result && $db->num_rows($result) > 0);
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

function pickup_status_label($status){
  if($status === 'approved'){ return 'Disetujui'; }
  if($status === 'rejected'){ return 'Ditolak'; }
  if($status === 'auto_rejected'){ return 'Ditolak Otomatis'; }
  if($status === 'completed'){ return 'Selesai'; }
  return 'Menunggu';
}

function pickup_status_class($status){
  if($status === 'approved'){ return 'success'; }
  if($status === 'rejected' || $status === 'auto_rejected'){ return 'danger'; }
  if($status === 'completed'){ return 'primary'; }
  return 'warning';
}

function find_pickup_requests($client_id = null){
  global $db;
  ensure_consignment_tables();
  ensure_warehouse_schema();
  $viewer_client_id = current_client_id();
  if($viewer_client_id !== null){ $client_id = $viewer_client_id; }
  $sql  = "SELECT r.*,p.name AS product_name,p.quantity AS current_stock,u.name AS client_name,un.name AS unit_name,";
  $sql .= "d.id AS delivery_id,d.document_no,d.stock_processed ";
  $sql .= "FROM pickup_requests r ";
  $sql .= "LEFT JOIN products p ON p.id=r.product_id ";
  $sql .= "LEFT JOIN users u ON u.id=r.client_id ";
  $sql .= "LEFT JOIN units un ON un.id=r.unit_id ";
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
  $sql  = "SELECT r.*,p.name AS product_name,p.quantity AS current_stock,p.client_id AS product_client_id,u.name AS client_name,un.name AS unit_name,";
  $sql .= "d.id AS delivery_id,d.document_no,d.stock_processed ";
  $sql .= "FROM pickup_requests r ";
  $sql .= "LEFT JOIN products p ON p.id=r.product_id ";
  $sql .= "LEFT JOIN users u ON u.id=r.client_id ";
  $sql .= "LEFT JOIN units un ON un.id=r.unit_id ";
  $sql .= "LEFT JOIN delivery_orders d ON d.pickup_request_id=r.id ";
  $sql .= "WHERE r.id='{$id}'";
  if($client_id !== null){ $sql .= " AND r.client_id='".$db->escape((int)$client_id)."'"; }
  $sql .= " LIMIT 1";
  $result = find_by_sql($sql);
  return empty($result) ? null : $result[0];
}

function create_pickup_request($data = array()){
  global $db;
  ensure_warehouse_schema();
  $client_id = isset($data['client_id']) ? (int)$data['client_id'] : 0;
  $product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
  $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 0;
  $pickup_date = !empty($data['pickup_date']) ? $db->escape($data['pickup_date']) : date('Y-m-d');
  $pickup_time = !empty($data['pickup_time']) ? $db->escape($data['pickup_time']) : '00:00';
  $driver_name = !empty($data['driver_name']) ? $db->escape($data['driver_name']) : '';
  $vehicle_no = !empty($data['vehicle_no']) ? $db->escape($data['vehicle_no']) : '';
  $product = find_product_details($product_id, $client_id);
  if(!$product || $client_id <= 0 || $quantity <= 0 || $driver_name === '' || $vehicle_no === ''){
    return false;
  }
  $unit_id = !empty($product['unit_id']) ? (int)$product['unit_id'] : 0;
  $status = 'pending';
  $admin_note = null;
  if($quantity > (int)$product['quantity']){
    $status = 'auto_rejected';
    $admin_note = 'Jumlah request melebihi stok tersedia. Stok tersedia: '.(int)$product['quantity'].'.';
  }
  $request_no = $db->escape(generate_consignment_number('REQ'));
  $note_value = $admin_note !== null ? "'".$db->escape($admin_note)."'" : "NULL";
  $unit_value = $unit_id > 0 ? "'{$unit_id}'" : "NULL";
  $sql  = "INSERT INTO pickup_requests (request_no,client_id,product_id,unit_id,quantity,pickup_date,pickup_time,driver_name,vehicle_no,status,admin_note,created_at) VALUES (";
  $sql .= "'{$request_no}','{$client_id}','{$product_id}',{$unit_value},'{$quantity}','{$pickup_date}','{$pickup_time}','{$driver_name}','{$vehicle_no}','{$status}',{$note_value},'".make_date()."')";
  return $db->query($sql) ? $db->insert_id() : false;
}

function approve_pickup_request($id){
  global $db;
  ensure_warehouse_schema();
  $request = find_pickup_request_details($id);
  if(!$request || $request['status'] !== 'pending'){ return false; }
  if((int)$request['quantity'] > (int)$request['current_stock']){
    return reject_pickup_request($id, 'Jumlah request melebihi stok tersedia saat diproses admin.', true);
  }
  $user = current_user();
  $processed_by = $user ? (int)$user['id'] : 0;
  $processed_by_value = $processed_by > 0 ? "'{$processed_by}'" : "NULL";
  $now = make_date();
  $sql = "UPDATE pickup_requests SET status='approved', processed_by={$processed_by_value}, processed_at='{$now}', admin_note=NULL WHERE id='".(int)$id."' LIMIT 1";
  if(!$db->query($sql)){ return false; }
  $existing = find_by_sql("SELECT id FROM delivery_orders WHERE pickup_request_id='".(int)$id."' LIMIT 1");
  if(!empty($existing)){ return true; }
  $delivery_id = create_delivery_order(array(
    'movement_type' => 'out',
    'client_id' => (int)$request['client_id'],
    'product_id' => (int)$request['product_id'],
    'quantity' => (int)$request['quantity'],
    'document_date' => date('Y-m-d'),
    'recipient' => $request['client_name'],
    'driver_name' => $request['driver_name'],
    'vehicle_no' => $request['vehicle_no'],
    'reference_type' => 'request_pengambilan',
    'reference_id' => (int)$id,
    'pickup_request_id' => (int)$id,
    'scheduled_at' => $request['pickup_date'].' '.$request['pickup_time'],
    'stock_processed' => 0,
    'note' => 'Surat jalan dari request pengambilan barang.'
  ));
  return $delivery_id ? true : false;
}

function reject_pickup_request($id, $reason, $auto=false){
  global $db;
  ensure_warehouse_schema();
  $request = find_pickup_request_details($id);
  if(!$request || $request['status'] !== 'pending'){ return false; }
  $reason = $db->escape($reason);
  if(trim($reason) === ''){ return false; }
  $user = current_user();
  $processed_by = $user ? (int)$user['id'] : 0;
  $processed_by_value = $processed_by > 0 ? "'{$processed_by}'" : "NULL";
  $status = $auto ? 'auto_rejected' : 'rejected';
  $now = make_date();
  $sql = "UPDATE pickup_requests SET status='{$status}', admin_note='{$reason}', processed_by={$processed_by_value}, processed_at='{$now}' WHERE id='".(int)$id."' LIMIT 1";
  return $db->query($sql);
}

function process_delivery_order_stock($delivery_id){
  global $db;
  ensure_warehouse_schema();
  $order = find_delivery_order_details($delivery_id);
  if(!$order){ return false; }
  if($order['movement_type'] !== 'out' || (int)$order['stock_processed'] === 1){ return true; }
  $product = find_product_details((int)$order['product_id'], (int)$order['client_id']);
  if(!$product || (int)$order['quantity'] <= 0 || (int)$order['quantity'] > (int)$product['quantity']){ return false; }
  $stock_change = update_product_qty((int)$order['quantity'], (int)$order['product_id']);
  if(!$stock_change){ return false; }
  $movement_id = record_stock_movement((int)$order['product_id'], 'out', (int)$order['quantity'], $stock_change['before'], $stock_change['after'], array(
    'client_id' => (int)$order['client_id'],
    'reference_type' => 'surat_jalan',
    'reference_id' => (int)$order['id'],
    'note' => 'Stok keluar saat surat jalan dicetak/diproses'
  ));
  if(!$movement_id){
    increase_product_qty((int)$order['quantity'], (int)$order['product_id']);
    return false;
  }
  $now = make_date();
  $db->query("UPDATE delivery_orders SET stock_processed='1', stock_processed_at='{$now}' WHERE id='".(int)$order['id']."' LIMIT 1");
  if(!empty($order['pickup_request_id'])){
    $db->query("UPDATE pickup_requests SET status='completed' WHERE id='".(int)$order['pickup_request_id']."' AND status='approved' LIMIT 1");
  }
  return true;
}

ensure_warehouse_schema();

?>
