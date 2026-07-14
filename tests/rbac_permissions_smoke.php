<?php
require_once __DIR__ . '/../includes/load.php';

function assert_same_value($expected, $actual, $message){
  if($expected !== $actual){
    echo "FAIL: {$message}\n";
    echo "Expected: ";
    var_export($expected);
    echo "\nActual: ";
    var_export($actual);
    echo "\n";
    exit(1);
  }
}

function assert_truthy_value($actual, $message){
  if(!$actual){
    echo "FAIL: {$message}\n";
    var_export($actual);
    echo "\n";
    exit(1);
  }
}

ensure_warehouse_schema(true);

$actions = permission_actions();
assert_truthy_value(isset($actions['view']), 'permission_actions exposes view');
assert_truthy_value(isset($actions['create']), 'permission_actions exposes create');
assert_truthy_value(isset($actions['update']), 'permission_actions exposes update');
assert_truthy_value(isset($actions['delete']), 'permission_actions exposes delete');
assert_truthy_value(isset($actions['print']), 'permission_actions exposes print');
assert_truthy_value(isset($actions['process']), 'permission_actions exposes process');

$modules = access_permission_modules();
assert_truthy_value(isset($modules['barang']), 'access_permission_modules exposes barang');
assert_truthy_value(in_array('create', $modules['barang']['actions'], true), 'barang supports create');
assert_truthy_value(isset($modules['penagihan']), 'access_permission_modules exposes penagihan');
assert_truthy_value(in_array('process', $modules['penagihan']['actions'], true), 'penagihan supports process');
assert_truthy_value(isset($modules['pickup']), 'access_permission_modules exposes pickup');
assert_truthy_value(in_array('process', $modules['pickup']['actions'], true), 'pickup owns stock processing permission');
assert_same_value(false, in_array('process', $modules['surat_jalan']['actions'], true), 'surat jalan does not expose an unused process permission');

assert_same_value(true, role_can_action('barang', 'delete', USER_LEVEL_ADMIN), 'admin can delete barang');
assert_same_value(false, role_can_action('user_mgmt', 'view', USER_LEVEL_USER), 'staff cannot access admin-only user management');
assert_same_value(false, role_can_action('unknown_module', 'view', USER_LEVEL_USER), 'unknown module is denied');

$test_level = 9876;
set_role_action_permission($test_level, 'barang', 'view', 1);
set_role_action_permission($test_level, 'barang', 'create', 0);
assert_same_value(true, role_can_action('barang', 'view', $test_level), 'explicit view permission is allowed');
assert_same_value(false, role_can_action('barang', 'create', $test_level), 'explicit create denial is denied');

$db->query("DELETE FROM role_action_permissions WHERE role_level='{$test_level}'");

echo "RBAC permission smoke tests passed\n";
