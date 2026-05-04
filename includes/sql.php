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

    $sql  = "SELECT p.*,c.name AS categorie,m.file_name AS image,u.name AS client_name ";
    $sql .= "FROM products p ";
    $sql .= "LEFT JOIN categories c ON c.id = p.categorie_id ";
    $sql .= "LEFT JOIN media m ON m.id = p.media_id ";
    $sql .= "LEFT JOIN users u ON u.id = p.client_id ";
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

     $sql  =" SELECT p.id,p.name,p.quantity,p.buy_price,p.sale_price,p.client_id,p.media_id,p.date,c.name";
    $sql  .=" AS categorie,m.file_name AS image,u.name AS client_name";
    $sql  .=" FROM products p";
    $sql  .=" LEFT JOIN categories c ON c.id = p.categorie_id";
    $sql  .=" LEFT JOIN media m ON m.id = p.media_id";
    $sql  .=" LEFT JOIN users u ON u.id = p.client_id";

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

     $sql  = "SELECT p.id,p.name,p.quantity,p.client_id,u.name AS client_name ";
     $sql .= "FROM products p ";
     $sql .= "LEFT JOIN users u ON u.id = p.client_id ";
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

    $sql  = "SELECT p.*,u.name AS client_name FROM products p ";
    $sql .= "LEFT JOIN users u ON u.id = p.client_id ";
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

    $sql  = "SELECT sm.id,sm.product_id,sm.client_id,sm.movement_type,sm.quantity,";
    $sql .= "sm.quantity_before,sm.quantity_after,sm.reference_type,sm.reference_id,";
    $sql .= "sm.note,sm.created_at,p.name AS product_name,u.name AS client_name,";
    $sql .= "actor.name AS created_by_name ";
    $sql .= "FROM stock_movements sm ";
    $sql .= "LEFT JOIN products p ON p.id = sm.product_id ";
    $sql .= "LEFT JOIN users u ON u.id = sm.client_id ";
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
    $sql .= "product_id,client_id,movement_type,quantity,quantity_before,quantity_after,";
    $sql .= "reference_type,reference_id,note,created_by,created_at";
    $sql .= ") VALUES (";
    $sql .= "'".$db->escape((int)$product_id)."',{$client_value},'".$db->escape($movement_type)."',";
    $sql .= "'".$db->escape((int)$quantity)."','".$db->escape((int)$quantity_before)."',";
    $sql .= "'".$db->escape((int)$quantity_after)."',{$reference_type_value},{$reference_id_value},";
    $sql .= "{$note_value},{$created_by_value},'{$created_at}'";
    $sql .= ")";

    if($db->query($sql)){
      return $db->insert_id();
    }

    return false;
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

  $sql   = " SELECT p.id,p.name,p.quantity,p.sale_price,p.client_id,p.media_id,p.date,c.name AS categorie,";
   $sql  .= "m.file_name AS image,u.name AS client_name FROM products p";
   $sql  .= " LEFT JOIN categories c ON c.id = p.categorie_id";
   $sql  .= " LEFT JOIN media m ON m.id = p.media_id";
   $sql  .= " LEFT JOIN users u ON u.id = p.client_id";

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
   $sql .= " FROM sales s";
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

   $sql  = "SELECT s.id,s.product_id,s.qty,s.price,s.date,p.name,u.name AS client_name";
   $sql .= " FROM sales s";
   $sql .= " LEFT JOIN products p ON s.product_id = p.id";
   $sql .= " LEFT JOIN users u ON u.id = p.client_id";

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

  $sql  = "SELECT s.id,s.product_id,s.qty,s.price,s.date,p.name,u.name AS client_name";
  $sql .= " FROM sales s";
  $sql .= " LEFT JOIN products p ON s.product_id = p.id";
  $sql .= " LEFT JOIN users u ON u.id = p.client_id";

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
  $sql  = "SELECT s.date, p.name,p.sale_price,p.buy_price,";
  $sql .= "COUNT(s.product_id) AS total_records,";
  $sql .= "SUM(s.qty) AS total_sales,";
  $sql .= "SUM(p.sale_price * s.qty) AS total_saleing_price,";
  $sql .= "SUM(p.buy_price * s.qty) AS total_buying_price ";
  $sql .= "FROM sales s ";
  $sql .= "LEFT JOIN products p ON s.product_id = p.id";
  $sql .= " WHERE s.date BETWEEN '{$start_date}' AND '{$end_date}'";
  $sql .= " GROUP BY DATE(s.date),p.name";
  $sql .= " ORDER BY DATE(s.date) DESC";
  return $db->query($sql);
}
/*--------------------------------------------------------------*/
/* Function for Generate Daily sales report
/*--------------------------------------------------------------*/
function  dailySales($year,$month){
  global $db;
  $sql  = "SELECT s.qty,";
  $sql .= " DATE_FORMAT(s.date, '%Y-%m-%e') AS date,p.name,";
  $sql .= "SUM(p.sale_price * s.qty) AS total_saleing_price";
  $sql .= " FROM sales s";
  $sql .= " LEFT JOIN products p ON s.product_id = p.id";
  $sql .= " WHERE DATE_FORMAT(s.date, '%Y-%m' ) = '{$year}-{$month}'";
  $sql .= " GROUP BY DATE_FORMAT( s.date,  '%e' ),s.product_id";
  return find_by_sql($sql);
}
/*--------------------------------------------------------------*/
/* Function for Generate Monthly sales report
/*--------------------------------------------------------------*/
function  monthlySales($year){
  global $db;
  $sql  = "SELECT s.qty,";
  $sql .= " DATE_FORMAT(s.date, '%Y-%m-%e') AS date,p.name,";
  $sql .= "SUM(p.sale_price * s.qty) AS total_saleing_price";
  $sql .= " FROM sales s";
  $sql .= " LEFT JOIN products p ON s.product_id = p.id";
  $sql .= " WHERE DATE_FORMAT(s.date, '%Y' ) = '{$year}'";
  $sql .= " GROUP BY DATE_FORMAT( s.date,  '%c' ),s.product_id";
  $sql .= " ORDER BY date_format(s.date, '%c' ) ASC";
  return find_by_sql($sql);
}

?>
