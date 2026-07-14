<?php
  require_once('includes/load.php');
  if (!$session->isUserLoggedIn(true)) { redirect('index.php', false);}
?>

<?php
 // Saran otomatis barang titipan
    $html = '';
   if(isset($_POST['product_name']) && strlen($_POST['product_name']))
   {
     $products = find_product_by_title($_POST['product_name']);
     if($products){
      foreach ($products as $product):
        $bundle_managed = function_exists('product_has_bundle_details') && product_has_bundle_details((int)$product['id']);
        $stock_unit = !empty($product['base_unit_name']) ? $product['base_unit_name'] : $product['unit_name'];
        $html .= "<li class=\"list-group-item\"";
        $html .= " data-product-id=\"".(int)$product['id']."\" data-product-title=\"".remove_junk($product['name'])."\">";
        $html .= remove_junk($product['name']);
        $html .= !empty($product['client_name']) ? ' - Pelanggan: '.remove_junk($product['client_name']) : ' - Internal';
        $html .= ' (Stok: '.(int)$product['quantity'].' '.(!empty($stock_unit) ? remove_junk($stock_unit) : '').')';
        if($bundle_managed){ $html .= ' - gunakan Request Pengambilan'; }
        $html .= "</li>";
      endforeach;
      } else {

        $html .= '<li class="list-group-item">';
        $html .= 'Barang tidak ditemukan';
        $html .= "</li>";

      }

      echo json_encode($html);
   }
 ?>
 <?php
 // Ambil detail barang titipan
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
          if(function_exists('product_has_bundle_details') && product_has_bundle_details((int)$result['id'])){
            $html .= '<tr><td colspan="8" class="alert alert-warning">';
            $html .= '<strong>'.remove_junk($result['name']).'</strong> dikelola per bundle. ';
            $html .= 'Gunakan <a href="pickup_requests.php">Request Pengambilan</a> agar bundle dipilih utuh dan stok tetap konsisten.';
            $html .= '</td></tr>';
            continue;
          }
          $pcs_per_crate = isset($result['pcs_per_crate']) ? (int)$result['pcs_per_crate'] : 0;
          $stok_krat = $pcs_per_crate > 0 ? intdiv((int)$result['quantity'], $pcs_per_crate) : 0;
          $default_qty = $pcs_per_crate > 0 ? $pcs_per_crate : 1;
          $unit_disp = !empty($result['unit_name']) ? remove_junk($result['unit_name']) : 'satuan';
          $base_unit_disp = !empty($result['base_unit_name']) ? remove_junk($result['base_unit_name']) : 'unit dasar';

          $html .= "<tr>";

          $html .= "<td id=\"s_name\">".remove_junk($result['name']);
          $html .= !empty($result['client_name']) ? "<br><small class=\"text-muted\">Pelanggan: ".remove_junk($result['client_name'])."</small>" : "<br><small class=\"text-muted\">Stok internal</small>";
          $html .= "<br><small class=\"text-muted\">Stok tersedia: ".(int)$result['quantity']." ".$base_unit_disp.($pcs_per_crate > 0 ? " (".$stok_krat." ".$unit_disp." utuh)" : "")."</small>";
          $html .= "<input type=\"hidden\" name=\"s_id\" value=\"{$result['id']}\">";
          $html .= "<input type=\"hidden\" name=\"price\" value=\"0.00\">";
          $html .= "<input type=\"hidden\" name=\"total\" value=\"0.00\"></td>";
          $html .= "<td id=\"s_qty\">";
          $html .= "<input type=\"number\" min=\"1\" step=\"".$default_qty."\" max=\"".(int)$result['quantity']."\" class=\"form-control\" name=\"quantity\" value=\"".$default_qty."\">";
          $html .= $pcs_per_crate > 0 ? "<small class=\"text-muted\">1 ".$unit_disp." = ".$pcs_per_crate." lembar</small>" : "";
          $html  .= "</td>";
          $html  .= "<td>";
          $html  .= "<input type=\"date\" class=\"form-control datePicker\" name=\"date\" data-date data-date-format=\"yyyy-mm-dd\" value=\"".date('Y-m-d')."\">";
          $html  .= "</td>";
          $storage = calculate_storage_fee($result['date'], date('Y-m-d'), 1, (int)$result['client_id']);
          $html  .= "<td>";
          $html  .= "<input type=\"number\" min=\"0\" step=\"1000\" class=\"form-control\" name=\"billing_amount\" value=\"".(int)$storage['fee']."\" readonly>";
          $html  .= "<small class=\"text-muted\">Otomatis: ".$storage['days']." hari &times; ".format_rupiah($storage['rate'])."/".$unit_disp."/bln (per ".$unit_disp.", dihitung saat simpan)</small>";
          $html  .= "</td>";
          $html  .= "<td>";
          $html  .= "<input type=\"date\" class=\"form-control\" name=\"due_date\" value=\"".date('Y-m-d', strtotime('+7 days'))."\">";
          $html  .= "</td>";
          $html  .= "<td>";
          $html  .= "<input type=\"text\" class=\"form-control\" name=\"driver_name\" placeholder=\"Nama Supir\" value=\"-\">";
          $html  .= "</td>";
          $html  .= "<td>";
          $html  .= "<input type=\"text\" class=\"form-control\" name=\"vehicle_no\" placeholder=\"Pelat Kendaraan\" value=\"-\">";
          $html  .= "</td>";
          $html  .= "<td>";
          $html  .= "<button type=\"submit\" name=\"add_sale\" class=\"btn btn-primary\">Simpan Pengambilan</button>";
          $html  .= "</td>";
          $html  .= "</tr>";

        }
    } else {
        $html ='<tr><td>Barang titipan tidak terdaftar di database</td></tr>';
    }

    echo json_encode($html);
  }
 ?>
