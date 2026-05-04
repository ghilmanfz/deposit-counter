<?php
  $page_title = 'Add Sale';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
   page_require_level(3);
  $msg = $session->msg();
?>
<?php

  if(isset($_POST['add_sale'])){
    $req_fields = array('s_id','quantity','price','total', 'date' );
    validate_fields($req_fields);
        if(empty($errors)){
          $p_id      = $db->escape((int)$_POST['s_id']);
          $s_qty     = $db->escape((int)$_POST['quantity']);
          $s_total   = $db->escape($_POST['total']);
          $date      = $db->escape($_POST['date']);
          $transaction_date = date("Y-m-d", strtotime($date));
          $movement_date = $transaction_date.' '.date('H:i:s');
          $product = find_product_details($p_id);

          if(!$product){
            $session->msg('d',' Product not found.');
            redirect('add_sale.php', false);
          }

          if((int)$s_qty <= 0){
            $session->msg('d',' Quantity must be greater than zero.');
            redirect('add_sale.php', false);
          }

          if((int)$s_qty > (int)$product['quantity']){
            $session->msg('d',' Quantity exceeds available stock.');
            redirect('add_sale.php', false);
          }

          $sql  = "INSERT INTO sales (";
          $sql .= " product_id,qty,price,date";
          $sql .= ") VALUES (";
          $sql .= "'{$p_id}','{$s_qty}','{$s_total}','{$transaction_date}'";
          $sql .= ")";

                if($db->query($sql)){
                  $sale_id = $db->insert_id();
                  $stock_change = update_product_qty($s_qty,$p_id);

                  if(!$stock_change){
                    delete_by_id('sales', $sale_id);
                    $session->msg('d',' Failed to update stock.');
                    redirect('add_sale.php', false);
                  }

                  $movement_id = record_stock_movement($p_id, 'out', (int)$s_qty, $stock_change['before'], $stock_change['after'], array(
                    'client_id' => (int)$product['client_id'],
                    'reference_type' => 'sale',
                    'reference_id' => $sale_id,
                    'note' => 'Stock released from warehouse',
                    'created_at' => $movement_date
                  ));

                  if(!$movement_id){
                    increase_product_qty($s_qty, $p_id);
                    delete_by_id('sales', $sale_id);
                    $session->msg('d',' Failed to save stock history.');
                    redirect('add_sale.php', false);
                  }

                  $session->msg('s',"Sale added. ");
                  redirect('add_sale.php', false);
                } else {
                  $session->msg('d',' Sorry failed to add!');
                  redirect('add_sale.php', false);
                }
        } else {
           $session->msg("d", $errors);
           redirect('add_sale.php',false);
        }
  }

?>
<?php include_once('layouts/header.php'); ?>
<div class="row">
  <div class="col-md-6">
    <?php echo display_msg($msg); ?>
    <form method="post" action="ajax.php" autocomplete="off" id="sug-form">
        <div class="form-group">
          <div class="input-group">
            <span class="input-group-btn">
              <button type="submit" class="btn btn-primary">Find It</button>
            </span>
            <input type="text" id="sug_input" class="form-control" name="title"  placeholder="Search for product name">
            <input type="hidden" id="selected_product_id" name="selected_product_id" value="">
         </div>
         <div id="result" class="list-group"></div>
        </div>
    </form>
  </div>
</div>
<div class="row">

  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading clearfix">
        <strong>
          <span class="glyphicon glyphicon-th"></span>
            <span>Stock Out</span>
       </strong>
      </div>
      <div class="panel-body">
        <form method="post" action="add_sale.php">
         <table class="table table-bordered">
           <thead>
            <th> Item </th>
            <th> Price </th>
            <th> Qty </th>
            <th> Total </th>
            <th> Date</th>
            <th> Action</th>
           </thead>
             <tbody  id="product_info"> </tbody>
         </table>
       </form>
      </div>
    </div>
  </div>

</div>

<?php include_once('layouts/footer.php'); ?>
