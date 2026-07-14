<?php
require_once(LIB_PATH_INC.DS."config.php");

class MySqli_DB {

    private $con;
    public $query_id;
    private $transaction_active = false;
    private $last_error = '';

    function __construct() {
      $this->db_connect();
    }

/*--------------------------------------------------------------*/
/* Function for Open database connection
/*--------------------------------------------------------------*/
public function db_connect()
{
  $this->con = mysqli_connect(DB_HOST,DB_USER,DB_PASS);
  if(!$this->con)
         {
           die(" Database connection failed:". mysqli_connect_error());
         } else {
           $select_db = $this->con->select_db(DB_NAME);
             if(!$select_db)
             {
               die("Failed to Select Database". mysqli_connect_error());
             }
         }
}
/*--------------------------------------------------------------*/
/* Function for Close database connection
/*--------------------------------------------------------------*/

public function db_disconnect()
{
  if(isset($this->con))
  {
    mysqli_close($this->con);
    unset($this->con);
  }
}
/*--------------------------------------------------------------*/
/* Function for mysqli query
/*--------------------------------------------------------------*/
public function query($sql)
   {
      if (trim((string)$sql) !== '') {
          $this->query_id = $this->con->query($sql);
      }
      if (!$this->query_id) {
        $this->last_error = $this->con->error;
        // A hard die inside a transaction makes rollback impossible.  Keep
        // legacy behaviour outside transactions, but let transactional
        // callers catch the failure and rollback every related mutation.
        if($this->transaction_active){
          throw new RuntimeException($this->last_error !== '' ? $this->last_error : 'Database query failed.');
        }
        // only for Develope mode
        die("Error on this Query :<pre> " . $sql ."</pre>");
      }
       // For production mode
        //  die("Error on Query");

       return $this->query_id;

   }

/*--------------------------------------------------------------*/
/* Transaction helpers used by stock/reservation workflows.
/*--------------------------------------------------------------*/
public function begin_transaction()
{
  if($this->transaction_active){
    throw new RuntimeException('Nested database transactions are not supported.');
  }
  if(!$this->con->begin_transaction()){
    $this->last_error = $this->con->error;
    throw new RuntimeException($this->last_error !== '' ? $this->last_error : 'Unable to start database transaction.');
  }
  $this->transaction_active = true;
  return true;
}

public function commit()
{
  if(!$this->transaction_active){ return true; }
  if(!$this->con->commit()){
    $this->last_error = $this->con->error;
    throw new RuntimeException($this->last_error !== '' ? $this->last_error : 'Unable to commit database transaction.');
  }
  $this->transaction_active = false;
  return true;
}

public function rollback()
{
  if(!$this->transaction_active){ return true; }
  $result = $this->con->rollback();
  $this->transaction_active = false;
  if(!$result){ $this->last_error = $this->con->error; }
  return $result;
}

public function in_transaction()
{
  return $this->transaction_active;
}

/* Query variants for new code. query_or_throw is suitable for business
 * transactions; query_safe is intentionally limited to optional/idempotent
 * schema guards where an unsupported FK/index must not take the site down. */
public function query_or_throw($sql)
{
  $result = $this->con->query($sql);
  $this->query_id = $result;
  if(!$result){
    $this->last_error = $this->con->error;
    throw new RuntimeException($this->last_error !== '' ? $this->last_error : 'Database query failed.');
  }
  return $result;
}

public function query_safe($sql)
{
  try{
    $result = $this->con->query($sql);
    $this->query_id = $result;
    if(!$result){ $this->last_error = $this->con->error; }
    return $result;
  } catch(Throwable $e){
    $this->query_id = false;
    $this->last_error = $e->getMessage();
    return false;
  }
}

public function last_error()
{
  return $this->last_error;
}

/*--------------------------------------------------------------*/
/* Function for Query Helper
/*--------------------------------------------------------------*/
public function fetch_array($statement)
{
  return mysqli_fetch_array($statement);
}
public function fetch_object($statement)
{
  return mysqli_fetch_object($statement);
}
public function fetch_assoc($statement)
{
  return mysqli_fetch_assoc($statement);
}
public function num_rows($statement)
{
  return mysqli_num_rows($statement);
}
public function insert_id()
{
  return mysqli_insert_id($this->con);
}
public function affected_rows()
{
  return mysqli_affected_rows($this->con);
}
/*--------------------------------------------------------------*/
 /* Function for Remove escapes special
 /* characters in a string for use in an SQL statement
 /*--------------------------------------------------------------*/
 public function escape($str){
   return $this->con->real_escape_string($str);
 }
/*--------------------------------------------------------------*/
/* Function for while loop
/*--------------------------------------------------------------*/
public function while_loop($loop){
 global $db;
   $results = array();
   while ($result = $this->fetch_array($loop)) {
      $results[] = $result;
   }
 return $results;
}

}

$db = new MySqli_DB();

?>
