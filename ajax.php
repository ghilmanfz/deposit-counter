<?php
  require_once('includes/load.php');
  if (!$session->isUserLoggedIn(true)) { redirect('index.php', false);}
?>

<?php
 // Auto suggetion
    $html = '';
   if(isset($_POST['product_name']) && strlen($_POST['product_name']))
   {
     $products = find_product_by_title($_POST['product_name']);
     if($products){
      foreach ($products as $product):
        $html .= "<li class=\"list-group-item\"";
        $html .= " data-product-id=\"".(int)$product['id']."\" data-product-title=\"".remove_junk($product['name'])."\">";
        $html .= remove_junk($product['name']);
        $html .= !empty($product['client_name']) ? ' - '.remove_junk($product['client_name']) : ' - Internal';
        $html .= ' (Stock: '.(int)$product['quantity'].')';
        $html .= "</li>";
      endforeach;
      } else {

        $html .= '<li class="list-group-item">';
        $html .= 'Not found';
        $html .= "</li>";

      }

      echo json_encode($html);
   }
 ?>
 <?php
 // find all product
  if((isset($_POST['p_id']) && (int)$_POST['p_id'] > 0) || (isset($_POST['p_name']) && strlen($_POST['p_name'])))
  {
    $results = array();
    if(isset($_POST['p_id']) && (int)$_POST['p_id'] > 0){
      $product = find_product_details((int)$_POST['p_id']);
      if($product){
        $results[] = $product;
      }
    } else {
      $product_title = remove_junk($db->escape($_POST['p_name']));
      $results = find_all_product_info_by_title($product_title);
    }

    if($results){
        foreach ($results as $result) {

          $html .= "<tr>";

          $html .= "<td id=\"s_name\">".remove_junk($result['name']);
          $html .= !empty($result['client_name']) ? "<br><small class=\"text-muted\">Client: ".remove_junk($result['client_name'])."</small>" : "<br><small class=\"text-muted\">Internal stock</small>";
          $html .= "<br><small class=\"text-muted\">Available stock: ".(int)$result['quantity']."</small></td>";
          $html .= "<input type=\"hidden\" name=\"s_id\" value=\"{$result['id']}\">";
          $html  .= "<td>";
          $html  .= "<input type=\"text\" class=\"form-control\" name=\"price\" value=\"{$result['sale_price']}\">";
          $html  .= "</td>";
          $html .= "<td id=\"s_qty\">";
          $html .= "<input type=\"number\" min=\"1\" max=\"".(int)$result['quantity']."\" class=\"form-control\" name=\"quantity\" value=\"1\">";
          $html  .= "</td>";
          $html  .= "<td>";
          $html  .= "<input type=\"text\" class=\"form-control\" name=\"total\" value=\"{$result['sale_price']}\">";
          $html  .= "</td>";
          $html  .= "<td>";
          $html  .= "<input type=\"date\" class=\"form-control datePicker\" name=\"date\" data-date data-date-format=\"yyyy-mm-dd\">";
          $html  .= "</td>";
          $html  .= "<td>";
          $html  .= "<button type=\"submit\" name=\"add_sale\" class=\"btn btn-primary\">Add sale</button>";
          $html  .= "</td>";
          $html  .= "</tr>";

        }
    } else {
        $html ='<tr><td>product name not resgister in database</td></tr>';
    }

    echo json_encode($html);
  }
 ?>
