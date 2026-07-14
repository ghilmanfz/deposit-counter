<?php
 $errors = array();

 /*--------------------------------------------------------------*/
 /* Function for Remove escapes special
 /* characters in a string for use in an SQL statement
 /*--------------------------------------------------------------*/
function real_escape($str){
  global $con;
  $escape = mysqli_real_escape_string($con,$str);
  return $escape;
}
/*--------------------------------------------------------------*/
/* Function for Remove html characters
/*--------------------------------------------------------------*/
function remove_junk($str){
  $str = nl2br($str);
  $str = htmlspecialchars(strip_tags($str, ENT_QUOTES));
  return $str;
}
/*--------------------------------------------------------------*/
/* Function for Uppercase first character
/*--------------------------------------------------------------*/
function first_character($str){
  $val = str_replace('-'," ",$str);
  $val = ucfirst($val);
  return $val;
}
/*--------------------------------------------------------------*/
/* Function for Checking input fields not empty
/*--------------------------------------------------------------*/
function validate_fields($var){
  global $errors;
  foreach ($var as $field) {
    $val = remove_junk($_POST[$field]);
    if(isset($val) && $val==''){
      $errors = $field ." tidak boleh kosong.";
      return $errors;
    }
  }
}
/*--------------------------------------------------------------*/
/* Function for Display Session Message
   Ex echo displayt_msg($message);
/*--------------------------------------------------------------*/
function display_msg($msg =''){
   $output = array();
   if(!empty($msg)) {
      foreach ($msg as $key => $value) {
         $output  = "<div class=\"alert alert-{$key}\">";
         $output .= "<a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a>";
         $output .= remove_junk(first_character($value));
         $output .= "</div>";
      }
      return $output;
   } else {
     return "" ;
   }
}
/*--------------------------------------------------------------*/
/* Function for redirect
/*--------------------------------------------------------------*/
function redirect($url, $permanent = false)
{
    if (headers_sent() === false)
    {
      header('Location: ' . $url, true, ($permanent === true) ? 301 : 302);
    }

    exit();
}
/*--------------------------------------------------------------*/
/* Function for find out total saleing price, buying price and profit
/*--------------------------------------------------------------*/
function total_price($totals){
   $sum = 0;
   $sub = 0;
   foreach($totals as $total ){
     $sum += $total['total_saleing_price'];
     $sub += $total['total_buying_price'];
     $profit = $sum - $sub;
   }
   return array($sum,$profit);
}
/*--------------------------------------------------------------*/
/* Function for Readable date time
/*--------------------------------------------------------------*/
function read_date($str){
     if($str)
      return date('d/m/Y H:i:s', strtotime($str));
     else
      return null;
  }
/*--------------------------------------------------------------*/
/* Format angka rupiah
/*--------------------------------------------------------------*/
function format_rupiah($value){
  return 'Rp ' . number_format((float)$value, 0, ',', '.');
}
/*--------------------------------------------------------------*/
/* Label status tagihan
/*--------------------------------------------------------------*/
function billing_status_label($status){
  $status = strtolower((string)$status);

  if($status === 'lunas'){
    return 'Lunas';
  }

  if($status === 'jatuh_tempo'){
    return 'Jatuh Tempo';
  }

  return 'Belum Lunas';
}
/*--------------------------------------------------------------*/
/* Label jenis mutasi surat jalan
/*--------------------------------------------------------------*/
function delivery_movement_label($type){
  return $type === 'in' ? 'Barang Masuk' : 'Barang Keluar';
}
/*--------------------------------------------------------------*/
/* Function for  Readable Make date time
/*--------------------------------------------------------------*/
function make_date(){
  return date("Y-m-d H:i:s");
}
/*--------------------------------------------------------------*/
/* Function for  Readable date time
/*--------------------------------------------------------------*/
function count_id(){
  static $count = 1;
  return $count++;
}
/*--------------------------------------------------------------*/
/* Function for Creting random string
/*--------------------------------------------------------------*/
function randString($length = 5)
{
  $str='';
  $cha = "0123456789abcdefghijklmnopqrstuvwxyz";

  for($x=0; $x<$length; $x++)
   $str .= $cha[mt_rand(0,strlen($cha) - 1)];
  return $str;
}

/*--------------------------------------------------------------*/
/* CSRF protection for warehouse state-changing forms
/*--------------------------------------------------------------*/
function warehouse_csrf_token()
{
  if(empty($_SESSION['warehouse_csrf_token'])){
    try {
      if(!function_exists('random_bytes')){
        throw new Exception('Secure random generator is unavailable.');
      }
      $_SESSION['warehouse_csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
      $_SESSION['warehouse_csrf_token'] = hash('sha256', uniqid((string)mt_rand(), true));
    }
  }

  return (string)$_SESSION['warehouse_csrf_token'];
}

function warehouse_csrf_field()
{
  return '<input type="hidden" name="csrf_token" value="'.htmlspecialchars(warehouse_csrf_token(), ENT_QUOTES, 'UTF-8').'">';
}

function warehouse_csrf_is_valid($token)
{
  if(empty($_SESSION['warehouse_csrf_token']) || !is_string($token)){
    return false;
  }

  return hash_equals((string)$_SESSION['warehouse_csrf_token'], $token);
}


?>
