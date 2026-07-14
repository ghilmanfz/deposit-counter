<?php
require_once __DIR__ . '/../includes/load.php';

function bundle_smoke_assert($condition, $message){
  if(!$condition){
    throw new RuntimeException($message);
  }
}

function bundle_smoke_row($sql){
  global $db;
  $result = $db->query_or_throw($sql);
  return $db->fetch_assoc($result);
}

function bundle_smoke_cleanup($marker){
  global $db;
  $marker = $db->escape($marker);
  $products = array();
  $product_result = $db->query_safe("SELECT id FROM products WHERE name LIKE '{$marker}%'");
  if($product_result){
    while($row = $db->fetch_assoc($product_result)){ $products[] = (int)$row['id']; }
  }
  if(empty($products)){ return; }

  $product_list = implode(',', $products);
  $requests = array();
  $request_result = $db->query_safe("SELECT id AS pickup_request_id FROM pickup_requests WHERE product_id IN ({$product_list}) UNION SELECT DISTINCT pickup_request_id FROM pickup_request_items WHERE product_id IN ({$product_list})");
  if($request_result){
    while($row = $db->fetch_assoc($request_result)){ $requests[] = (int)$row['pickup_request_id']; }
  }

  $request_list = !empty($requests) ? implode(',', array_unique($requests)) : '';
  $db->query_safe("DELETE FROM delivery_order_items WHERE product_id IN ({$product_list})");
  $db->query_safe("DELETE FROM pickup_request_items WHERE product_id IN ({$product_list})");
  // Processed bundles point to outgoing DOs, so details and physical bundle
  // rows must be removed before delivery/request headers under RESTRICT FKs.
  $db->query_safe("DELETE FROM inventory_bundles WHERE product_id IN ({$product_list})");
  $db->query_safe("DELETE FROM delivery_orders WHERE product_id IN ({$product_list})");
  if($request_list !== ''){ $db->query_safe("DELETE FROM pickup_requests WHERE id IN ({$request_list})"); }
  $db->query_safe("DELETE FROM stock_movements WHERE product_id IN ({$product_list})");
  $db->query_safe("DELETE FROM products WHERE id IN ({$product_list})");

  $left_products = $db->query_safe("SELECT id FROM products WHERE id IN ({$product_list}) LIMIT 1");
  $left_requests = $request_list !== '' ? $db->query_safe("SELECT id FROM pickup_requests WHERE id IN ({$request_list}) LIMIT 1") : false;
  if(($left_products && $db->num_rows($left_products) > 0) || ($left_requests && $db->num_rows($left_requests) > 0)){
    throw new RuntimeException('Smoke fixture cleanup left product or request rows behind.');
  }
}

ensure_warehouse_schema(true);

$marker = '__BUNDLE_SMOKE_'.date('YmdHis').'_'.strtoupper(randString(4)).'__';
$failure = null;

try {
  $client = bundle_smoke_row("SELECT id FROM users WHERE user_level='".USER_LEVEL_CLIENT."' AND status='1' ORDER BY id ASC LIMIT 1");
  $category = bundle_smoke_row("SELECT id FROM categories ORDER BY id ASC LIMIT 1");
  $package_unit = bundle_smoke_row("SELECT id FROM units WHERE name='krat' LIMIT 1");
  $base_unit = bundle_smoke_row("SELECT id FROM units WHERE name='lembar' LIMIT 1");
  $other_base_unit = bundle_smoke_row("SELECT id FROM units WHERE name='unit' LIMIT 1");
  bundle_smoke_assert($client && $category && $package_unit && $base_unit && $other_base_unit, 'Fixture master data is unavailable.');

  $client_id = (int)$client['id'];
  $category_id = (int)$category['id'];
  $package_unit_id = (int)$package_unit['id'];
  $base_unit_id = (int)$base_unit['id'];
  $other_base_unit_id = (int)$other_base_unit['id'];
  $now = make_date();

  ensure_consignment_tables();
  $internal_name = $db->escape($marker.'INTERNAL_NO_EXIT');
  $db->query_or_throw("INSERT INTO products (name,no_surat_jalan,quantity,pcs_per_crate,buy_price,sale_price,categorie_id,client_id,unit_id,base_unit_id,media_id,date) VALUES ('{$internal_name}',NULL,'10',NULL,'0','0','{$category_id}',NULL,'{$package_unit_id}','{$base_unit_id}','0','{$now}')");
  $internal_product_id = $db->insert_id();
  bundle_smoke_assert(create_inventory_bundles($internal_product_id, array(10), $package_unit_id, $base_unit_id, array('client_id'=>0)) === false, 'Internal stock was converted to bundles without an available pickup path.');
  $internal_bundle_count = bundle_smoke_row("SELECT COUNT(*) AS total FROM inventory_bundles WHERE product_id='{$internal_product_id}'");
  bundle_smoke_assert((int)$internal_bundle_count['total'] === 0, 'Rejected internal bundle conversion left physical rows behind.');
  bundle_smoke_assert(unit_is_used($base_unit_id) === true, 'Base-unit usage guard missed a product reference.');

  $receipt_product_id = 0;
  try{
    $db->begin_transaction();
    $receipt_name = $db->escape($marker.'RECEIPT');
    $db->query_or_throw("INSERT INTO products (name,no_surat_jalan,quantity,pcs_per_crate,buy_price,sale_price,categorie_id,client_id,unit_id,base_unit_id,media_id,date) VALUES ('{$receipt_name}','SMOKE-SJ-RECEIPT','30',NULL,'0','0','{$category_id}','{$client_id}','{$package_unit_id}','{$base_unit_id}','0','{$now}')");
    $receipt_product_id = $db->insert_id();
    $receipt_movement_id = record_stock_movement($receipt_product_id, 'in', 30, 0, 30, array('client_id'=>$client_id,'reference_type'=>'product','reference_id'=>$receipt_product_id,'event_key'=>'smoke-receipt:'.$receipt_product_id,'unit_id'=>$base_unit_id));
    $receipt_delivery_id = create_delivery_order(array('movement_type'=>'in','client_id'=>$client_id,'product_id'=>$receipt_product_id,'quantity'=>30,'document_date'=>date('Y-m-d'),'reference_type'=>'barang_masuk','reference_id'=>$receipt_product_id));
    $receipt_bundle_ids = create_inventory_bundles($receipt_product_id, array(9,21), $package_unit_id, $base_unit_id, array('client_id'=>$client_id));
    bundle_smoke_assert($receipt_movement_id && $receipt_delivery_id && is_array($receipt_bundle_ids), 'Atomic receipt components were not created.');
    bundle_smoke_assert(create_inbound_delivery_order_items($receipt_delivery_id, $receipt_product_id, $receipt_bundle_ids) === true, 'Inbound delivery bundle details were not created.');
    $db->commit();
  } catch(Throwable $receipt_error){
    if($db->in_transaction()){ $db->rollback(); }
    throw $receipt_error;
  }
  $receipt_check = bundle_smoke_row("SELECT COUNT(*) AS total,SUM(quantity) AS quantity FROM delivery_order_items WHERE delivery_order_id='".(int)$receipt_delivery_id."' AND status='received'");
  bundle_smoke_assert((int)$receipt_check['total'] === 2 && (int)$receipt_check['quantity'] === 30, 'Inbound Surat Jalan did not preserve every physical bundle quantity.');

  $failed_receipt_name = $marker.'FAILED_RECEIPT';
  try{
    $db->begin_transaction();
    $db->query_or_throw("INSERT INTO products (name,no_surat_jalan,quantity,pcs_per_crate,buy_price,sale_price,categorie_id,client_id,unit_id,base_unit_id,media_id,date) VALUES ('".$db->escape($failed_receipt_name)."','SMOKE-SJ-FAILED','10',NULL,'0','0','{$category_id}','{$client_id}','{$package_unit_id}','{$base_unit_id}','0','{$now}')");
    $failed_product_id = $db->insert_id();
    // Deliberate mismatch: bundle sum 9 cannot represent aggregate stock 10.
    create_inventory_bundles($failed_product_id, array(9), $package_unit_id, $base_unit_id, array('client_id'=>$client_id));
    $db->commit();
    throw new RuntimeException('An inconsistent receipt was unexpectedly committed.');
  } catch(Throwable $expected_receipt_failure){
    if($db->in_transaction()){ $db->rollback(); }
  }
  $failed_receipt_check = bundle_smoke_row("SELECT COUNT(*) AS total FROM products WHERE name='".$db->escape($failed_receipt_name)."'");
  bundle_smoke_assert((int)$failed_receipt_check['total'] === 0, 'Failed receipt left a partial product behind.');

  $ambiguous_pending_name = $marker.'AMBIGUOUS_PENDING';
  $db->query_or_throw("INSERT INTO products (name,no_surat_jalan,quantity,pcs_per_crate,buy_price,sale_price,categorie_id,client_id,unit_id,base_unit_id,media_id,date) VALUES ('".$db->escape($ambiguous_pending_name)."','SMOKE-SJ-AMB-P','30',NULL,'0','0','{$category_id}','{$client_id}','{$package_unit_id}',NULL,'0','{$now}')");
  $ambiguous_pending_product = $db->insert_id();
  $ambiguous_pending_no = $db->escape('REQ-AMB-P-'.strtoupper(randString(8)));
  $db->query_or_throw("INSERT INTO pickup_requests (request_no,client_id,product_id,unit_id,quantity,pickup_date,pickup_time,driver_name,vehicle_no,status,admin_note,created_at) VALUES ('{$ambiguous_pending_no}','{$client_id}','{$ambiguous_pending_product}','{$package_unit_id}','5','".date('Y-m-d')."','09:00:00','Legacy Driver','B 1 LEG','pending',NULL,'{$now}')");
  $ambiguous_pending_request = $db->insert_id();
  bundle_smoke_assert(approve_pickup_request($ambiguous_pending_request) === false, 'Ambiguous package-unit legacy request was approved.');
  $ambiguous_pending_state = bundle_smoke_row("SELECT status FROM pickup_requests WHERE id='{$ambiguous_pending_request}'");
  $ambiguous_pending_stock = bundle_smoke_row("SELECT quantity FROM products WHERE id='{$ambiguous_pending_product}'");
  bundle_smoke_assert($ambiguous_pending_state['status'] === 'auto_rejected' && (int)$ambiguous_pending_stock['quantity'] === 30, 'Ambiguous pending request changed stock or remained actionable.');

  $ambiguous_approved_name = $marker.'AMBIGUOUS_APPROVED';
  $db->query_or_throw("INSERT INTO products (name,no_surat_jalan,quantity,pcs_per_crate,buy_price,sale_price,categorie_id,client_id,unit_id,base_unit_id,media_id,date) VALUES ('".$db->escape($ambiguous_approved_name)."','SMOKE-SJ-AMB-A','30',NULL,'0','0','{$category_id}','{$client_id}','{$package_unit_id}',NULL,'0','{$now}')");
  $ambiguous_approved_product = $db->insert_id();
  $ambiguous_approved_no = $db->escape('REQ-AMB-A-'.strtoupper(randString(8)));
  $db->query_or_throw("INSERT INTO pickup_requests (request_no,client_id,product_id,unit_id,quantity,pickup_date,pickup_time,driver_name,vehicle_no,status,admin_note,created_at) VALUES ('{$ambiguous_approved_no}','{$client_id}','{$ambiguous_approved_product}','{$package_unit_id}','5','".date('Y-m-d')."','09:00:00','Legacy Driver','B 2 LEG','approved',NULL,'{$now}')");
  $ambiguous_approved_request = $db->insert_id();
  $db->query_or_throw("INSERT INTO pickup_request_items (pickup_request_id,bundle_id,product_id,base_unit_id,package_unit_id,quantity,bundle_no,product_name,no_surat_jalan,status,created_at,updated_at) VALUES ('{$ambiguous_approved_request}',NULL,'{$ambiguous_approved_product}','{$package_unit_id}','{$package_unit_id}','5',NULL,'".$db->escape($ambiguous_approved_name)."','SMOKE-SJ-AMB-A','reserved','{$now}','{$now}')");
  $ambiguous_pickup_item = $db->insert_id();
  $ambiguous_delivery = create_delivery_order(array('movement_type'=>'out','client_id'=>$client_id,'product_id'=>$ambiguous_approved_product,'quantity'=>5,'document_date'=>date('Y-m-d'),'reference_type'=>'request_pengambilan','reference_id'=>$ambiguous_approved_request,'pickup_request_id'=>$ambiguous_approved_request,'stock_processed'=>0));
  bundle_smoke_assert((int)$ambiguous_delivery > 0, 'Ambiguous approved delivery fixture failed.');
  $db->query_or_throw("INSERT INTO delivery_order_items (delivery_order_id,pickup_request_item_id,bundle_id,product_id,base_unit_id,package_unit_id,quantity,bundle_no,product_name,no_surat_jalan,status,processed_at,created_at) VALUES ('".(int)$ambiguous_delivery."','{$ambiguous_pickup_item}',NULL,'{$ambiguous_approved_product}','{$package_unit_id}','{$package_unit_id}','5',NULL,'".$db->escape($ambiguous_approved_name)."','SMOKE-SJ-AMB-A','ready',NULL,'{$now}')");
  bundle_smoke_assert(process_delivery_order_stock($ambiguous_delivery) === false, 'Already-materialized ambiguous legacy request changed stock.');
  $ambiguous_approved_stock = bundle_smoke_row("SELECT quantity FROM products WHERE id='{$ambiguous_approved_product}'");
  bundle_smoke_assert((int)$ambiguous_approved_stock['quantity'] === 30, 'Ambiguous approved request deducted a package count as base units.');
  bundle_smoke_assert(reject_pickup_request($ambiguous_approved_request, 'Satuan data lama ambigu') === true, 'Ambiguous approval could not be safely cancelled.');

  $product_ids = array();
  foreach(array(
    array('name'=>$marker.'A','sj'=>'SMOKE-SJ-A','quantity'=>25,'base_unit_id'=>$base_unit_id),
    array('name'=>$marker.'B','sj'=>'SMOKE-SJ-B','quantity'=>20,'base_unit_id'=>$base_unit_id),
    array('name'=>$marker.'C','sj'=>'SMOKE-SJ-C','quantity'=>13,'base_unit_id'=>$other_base_unit_id)
  ) as $fixture){
    $fixture_base_unit_id = (int)$fixture['base_unit_id'];
    $db->query_or_throw("INSERT INTO products (name,no_surat_jalan,quantity,pcs_per_crate,buy_price,sale_price,categorie_id,client_id,unit_id,base_unit_id,media_id,date) VALUES ('".$db->escape($fixture['name'])."','".$db->escape($fixture['sj'])."','".(int)$fixture['quantity']."',NULL,'0','0','{$category_id}','{$client_id}','{$package_unit_id}','{$fixture_base_unit_id}','0','{$now}')");
    $product_ids[] = $db->insert_id();
  }

  $bundles_a = create_inventory_bundles($product_ids[0], array(10,15), $package_unit_id, $base_unit_id, array('client_id'=>$client_id));
  $bundles_b = create_inventory_bundles($product_ids[1], array(7,13), $package_unit_id, $base_unit_id, array('client_id'=>$client_id));
  $bundles_c = create_inventory_bundles($product_ids[2], array(5,8), $package_unit_id, $other_base_unit_id, array('client_id'=>$client_id));
  bundle_smoke_assert(is_array($bundles_a) && count($bundles_a) === 2, 'Product A bundles were not created.');
  bundle_smoke_assert(is_array($bundles_b) && count($bundles_b) === 2, 'Product B bundles were not created.');
  bundle_smoke_assert(is_array($bundles_c) && count($bundles_c) === 2, 'Product C bundles were not created.');

  $db->begin_transaction();
  $stale_owner_update = lock_product_owner_for_metadata_update($product_ids[0], 0, false);
  $db->rollback();
  bundle_smoke_assert($stale_owner_update === false, 'A stale pre-bundle edit could overwrite ownership after bundle initialization.');
  $db->begin_transaction();
  $locked_owner_update = lock_product_owner_for_metadata_update($product_ids[0], $client_id, true);
  $db->rollback();
  bundle_smoke_assert(is_array($locked_owner_update) && (int)$locked_owner_update['client_id'] === $client_id, 'Bundle-backed metadata edit did not preserve its locked owner.');

  $request_data = array(
    'client_id'=>$client_id,
    'pickup_date'=>date('Y-m-d', strtotime('+1 day')),
    'pickup_time'=>'18:30',
    'driver_name'=>'Smoke Driver',
    'vehicle_no'=>'B 1234 TEST'
  );
  $request_id = create_multi_bundle_pickup_request($request_data, array($bundles_a[0], $bundles_b[1], $bundles_c[1]));
  bundle_smoke_assert((int)$request_id > 0, 'Multi-product pickup request was not created.');

  $reserved = bundle_smoke_row("SELECT COUNT(*) AS total,SUM(quantity) AS quantity FROM inventory_bundles WHERE reserved_request_id='".(int)$request_id."' AND status='reserved'");
  $mixed_header = bundle_smoke_row("SELECT unit_id FROM pickup_requests WHERE id='".(int)$request_id."'");
  bundle_smoke_assert((int)$reserved['total'] === 3 && (int)$reserved['quantity'] === 31, 'Exact bundles were not reserved.');
  bundle_smoke_assert(empty($mixed_header['unit_id']), 'Mixed base-unit request incorrectly exposed one header unit.');
  bundle_smoke_assert(create_multi_bundle_pickup_request($request_data, array($bundles_a[0])) === false, 'A reserved bundle was selected twice.');

  bundle_smoke_assert(approve_pickup_request($request_id) === true, 'Pickup approval failed.');
  bundle_smoke_assert(approve_pickup_request($request_id) === true, 'Repeated approval was not idempotent.');
  $delivery = bundle_smoke_row("SELECT id,stock_processed FROM delivery_orders WHERE pickup_request_id='".(int)$request_id."'");
  bundle_smoke_assert($delivery && (int)$delivery['stock_processed'] === 0, 'Approval did not create one unprocessed delivery order.');
  $delivery_count = bundle_smoke_row("SELECT COUNT(*) AS total FROM delivery_orders WHERE pickup_request_id='".(int)$request_id."'");
  $delivery_items = bundle_smoke_row("SELECT COUNT(*) AS total FROM delivery_order_items WHERE delivery_order_id='".(int)$delivery['id']."'");
  bundle_smoke_assert((int)$delivery_count['total'] === 1 && (int)$delivery_items['total'] === 3, 'Approval duplicated or lost delivery details.');

  $before_a = bundle_smoke_row("SELECT quantity FROM products WHERE id='".(int)$product_ids[0]."'");
  $before_b = bundle_smoke_row("SELECT quantity FROM products WHERE id='".(int)$product_ids[1]."'");
  $before_c = bundle_smoke_row("SELECT quantity FROM products WHERE id='".(int)$product_ids[2]."'");
  $activity_count_before = count_by_client_id('withdrawals', $client_id);
  bundle_smoke_assert((int)$before_a['quantity'] === 25 && (int)$before_b['quantity'] === 20 && (int)$before_c['quantity'] === 13, 'Stock changed before Proses Pengambilan.');

  bundle_smoke_assert(process_pickup_request_stock($request_id) === true, 'Pickup stock processing failed.');
  $after_a = bundle_smoke_row("SELECT quantity FROM products WHERE id='".(int)$product_ids[0]."'");
  $after_b = bundle_smoke_row("SELECT quantity FROM products WHERE id='".(int)$product_ids[1]."'");
  $after_c = bundle_smoke_row("SELECT quantity FROM products WHERE id='".(int)$product_ids[2]."'");
  bundle_smoke_assert((int)$after_a['quantity'] === 15 && (int)$after_b['quantity'] === 7 && (int)$after_c['quantity'] === 5, 'Grouped per-product stock deduction is incorrect.');

  bundle_smoke_assert(process_pickup_request_stock($request_id) === true, 'Repeated stock processing was not idempotent.');
  $repeat_a = bundle_smoke_row("SELECT quantity FROM products WHERE id='".(int)$product_ids[0]."'");
  $repeat_b = bundle_smoke_row("SELECT quantity FROM products WHERE id='".(int)$product_ids[1]."'");
  $repeat_c = bundle_smoke_row("SELECT quantity FROM products WHERE id='".(int)$product_ids[2]."'");
  $movement_count = bundle_smoke_row("SELECT COUNT(*) AS total FROM stock_movements WHERE reference_type='surat_jalan' AND reference_id='".(int)$delivery['id']."'");
  $movement_units = bundle_smoke_row("SELECT COUNT(DISTINCT unit_id) AS total FROM stock_movements WHERE reference_type='surat_jalan' AND reference_id='".(int)$delivery['id']."'");
  bundle_smoke_assert((int)$repeat_a['quantity'] === 15 && (int)$repeat_b['quantity'] === 7 && (int)$repeat_c['quantity'] === 5 && (int)$movement_count['total'] === 3, 'Repeated processing deducted or recorded stock twice.');
  bundle_smoke_assert((int)$movement_units['total'] === 2, 'Per-product stock movements lost their distinct base units.');

  $activity_count_after = count_by_client_id('withdrawals', $client_id);
  bundle_smoke_assert((int)$activity_count_after['total'] - (int)$activity_count_before['total'] === 3, 'Dashboard withdrawal count did not include every processed product line exactly once.');
  $activities = find_all_sale($client_id);
  $delivery_activity_count = 0;
  $delivery_activity_quantity = 0;
  foreach($activities as $activity){
    if(isset($activity['source_type']) && $activity['source_type'] === 'bundle' && (int)$activity['delivery_id'] === (int)$delivery['id']){
      $delivery_activity_count++;
      $delivery_activity_quantity += (int)$activity['qty'];
    }
  }
  bundle_smoke_assert($delivery_activity_count === 3 && $delivery_activity_quantity === 31, 'Processed bundle pickup was missing or duplicated in the withdrawal activity list.');
  $period_result = find_sale_by_dates(date('Y-m-d'), date('Y-m-d'));
  $period_quantity = 0;
  foreach($period_result as $period_row){
    if(isset($period_row['name']) && strpos($period_row['name'], $marker) === 0){ $period_quantity += (int)$period_row['total_sales']; }
  }
  bundle_smoke_assert($period_quantity === 31, 'Processed bundle pickup was missing from the period report.');

  $cancel_request = create_multi_bundle_pickup_request($request_data, array($bundles_a[1], $bundles_b[0]));
  bundle_smoke_assert((int)$cancel_request > 0, 'Cancellation fixture request failed.');
  bundle_smoke_assert(cancel_pickup_request($cancel_request, $client_id + 999) === false, 'Another client could cancel the request.');
  bundle_smoke_assert(cancel_pickup_request($cancel_request, $client_id) === true, 'Pending cancellation failed.');
  $released = bundle_smoke_row("SELECT COUNT(*) AS total FROM inventory_bundles WHERE id IN (".(int)$bundles_a[1].",".(int)$bundles_b[0].") AND status='available' AND reserved_request_id IS NULL");
  bundle_smoke_assert((int)$released['total'] === 2, 'Cancellation did not release every bundle.');

  $reject_request = create_multi_bundle_pickup_request($request_data, array($bundles_a[1]));
  bundle_smoke_assert((int)$reject_request > 0 && reject_pickup_request($reject_request, 'Smoke rejection') === true, 'Rejection flow failed.');
  $rejected_bundle = bundle_smoke_row("SELECT status,reserved_request_id FROM inventory_bundles WHERE id='".(int)$bundles_a[1]."'");
  bundle_smoke_assert($rejected_bundle['status'] === 'available' && empty($rejected_bundle['reserved_request_id']), 'Rejection did not release the bundle.');

  $approved_cancel = create_multi_bundle_pickup_request($request_data, array($bundles_a[1]));
  bundle_smoke_assert((int)$approved_cancel > 0 && approve_pickup_request($approved_cancel) === true, 'Approved-cancellation fixture failed.');
  $approved_delivery = bundle_smoke_row("SELECT id,stock_processed FROM delivery_orders WHERE pickup_request_id='".(int)$approved_cancel."'");
  bundle_smoke_assert($approved_delivery && (int)$approved_delivery['stock_processed'] === 0, 'Approved request did not create an unprocessed delivery order.');
  bundle_smoke_assert(reject_pickup_request($approved_cancel, 'Approval dibatalkan sebelum proses') === true, 'Approved request could not be cancelled before stock processing.');
  $approved_cancel_state = bundle_smoke_row("SELECT status FROM pickup_requests WHERE id='".(int)$approved_cancel."'");
  $approved_cancel_order = bundle_smoke_row("SELECT COUNT(*) AS total FROM delivery_orders WHERE pickup_request_id='".(int)$approved_cancel."'");
  $approved_cancel_bundle = bundle_smoke_row("SELECT status,reserved_request_id FROM inventory_bundles WHERE id='".(int)$bundles_a[1]."'");
  $approved_cancel_stock = bundle_smoke_row("SELECT quantity FROM products WHERE id='".(int)$product_ids[0]."'");
  bundle_smoke_assert($approved_cancel_state['status'] === 'rejected' && (int)$approved_cancel_order['total'] === 0, 'Approved cancellation left a request or delivery order in a stuck state.');
  bundle_smoke_assert($approved_cancel_bundle['status'] === 'available' && empty($approved_cancel_bundle['reserved_request_id']) && (int)$approved_cancel_stock['quantity'] === 15, 'Approved cancellation changed stock or failed to release its bundle.');

  // Recover a legacy/partially committed approval whose DO header was never
  // created. Admin must still be able to reject it and release the bundle.
  $approved_without_order = create_multi_bundle_pickup_request($request_data, array($bundles_a[1]));
  bundle_smoke_assert((int)$approved_without_order > 0, 'Zero-DO recovery fixture failed.');
  $db->query_or_throw("UPDATE pickup_requests SET status='approved' WHERE id='".(int)$approved_without_order."' AND status='pending' LIMIT 1");
  bundle_smoke_assert($db->affected_rows() === 1, 'Zero-DO recovery fixture could not enter the approved state.');
  bundle_smoke_assert(reject_pickup_request($approved_without_order, 'Approval lama tanpa surat jalan dibatalkan') === true, 'Approved request without a delivery order became stuck.');
  $zero_do_state = bundle_smoke_row("SELECT status FROM pickup_requests WHERE id='".(int)$approved_without_order."'");
  $zero_do_bundle = bundle_smoke_row("SELECT status,reserved_request_id FROM inventory_bundles WHERE id='".(int)$bundles_a[1]."'");
  $zero_do_stock = bundle_smoke_row("SELECT quantity FROM products WHERE id='".(int)$product_ids[0]."'");
  bundle_smoke_assert($zero_do_state['status'] === 'rejected' && $zero_do_bundle['status'] === 'available' && empty($zero_do_bundle['reserved_request_id']) && (int)$zero_do_stock['quantity'] === 15, 'Zero-DO recovery changed stock or failed to release its bundle.');

  $approved_drift = create_multi_bundle_pickup_request($request_data, array($bundles_a[1]));
  bundle_smoke_assert((int)$approved_drift > 0 && approve_pickup_request($approved_drift) === true, 'Approved-drift recovery fixture failed.');
  $db->query_or_throw("UPDATE inventory_bundles SET status='available',reserved_request_id=NULL,reserved_at=NULL WHERE id='".(int)$bundles_a[1]."'");
  bundle_smoke_assert(reject_pickup_request($approved_drift, 'Reservasi approval lama tidak konsisten') === true, 'Approved request with reservation drift became stuck.');
  $approved_drift_state = bundle_smoke_row("SELECT status FROM pickup_requests WHERE id='".(int)$approved_drift."'");
  $approved_drift_item = bundle_smoke_row("SELECT status FROM pickup_request_items WHERE pickup_request_id='".(int)$approved_drift."'");
  $approved_drift_order = bundle_smoke_row("SELECT COUNT(*) AS total FROM delivery_orders WHERE pickup_request_id='".(int)$approved_drift."'");
  $approved_drift_stock = bundle_smoke_row("SELECT quantity FROM products WHERE id='".(int)$product_ids[0]."'");
  bundle_smoke_assert($approved_drift_state['status'] === 'rejected' && $approved_drift_item['status'] === 'released' && (int)$approved_drift_order['total'] === 0 && (int)$approved_drift_stock['quantity'] === 15, 'Approved-drift recovery changed stock or left stale reservation data.');

  $drift_request = create_multi_bundle_pickup_request($request_data, array($bundles_b[0]));
  bundle_smoke_assert((int)$drift_request > 0, 'Reservation-drift fixture failed.');
  $db->query_or_throw("UPDATE inventory_bundles SET status='available',reserved_request_id=NULL,reserved_at=NULL WHERE id='".(int)$bundles_b[0]."'");
  bundle_smoke_assert(approve_pickup_request($drift_request) === false, 'Invalid reservation drift was approved.');
  $drift_state = bundle_smoke_row("SELECT status,processed_at FROM pickup_requests WHERE id='".(int)$drift_request."'");
  $drift_item = bundle_smoke_row("SELECT status FROM pickup_request_items WHERE pickup_request_id='".(int)$drift_request."'");
  bundle_smoke_assert($drift_state['status'] === 'auto_rejected' && !empty($drift_state['processed_at']) && $drift_item['status'] === 'released', 'Reservation drift remained pending or kept its item reserved.');
} catch (Throwable $e) {
  $failure = $e;
} finally {
  bundle_smoke_cleanup($marker);
}

if($failure){
  fwrite(STDERR, "FAIL: ".$failure->getMessage().PHP_EOL);
  exit(1);
}

echo "Bundle pickup smoke tests passed".PHP_EOL;
