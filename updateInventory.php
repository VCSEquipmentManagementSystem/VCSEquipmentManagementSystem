<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);
require './database.php';

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new Exception('Invalid request method');
  }

  // 1) Pull & cast
  $part_id      = intval($_POST['part_id'] ?? 0);
  $name         = trim($_POST['part_name'] ?? '');
  $number       = trim($_POST['part_number'] ?? '');
  $brand_name   = trim($_POST['brand_name'] ?? '');
  $equipment_id = trim($_POST['equipment_id'] ?? '');
  $stock_qty    = intval($_POST['stock_quantity'] ?? -1);
  $supplier_name  = trim($_POST['supplier_name'] ?? '');
  $unit_price   = floatval($_POST['unit_price'] ?? -1);
  $last_in      = trim($_POST['last_update'] ?? '');


  // 2) Validation
  if ($part_id <= 0) {
    throw new Exception('Invalid part ID');
  }
  if ($name === '' || $number === '') {
    throw new Exception('Part name & number are required');
  }
  if ($stock_qty < 0) {
    throw new Exception('Stock quantity is required');
  }
  if ($unit_price < 0) {
    throw new Exception('Unit price must be ≥ 0');
  }
  if ($brand_name === '') {
    throw new Exception('Brand name is required');
  }
  if ($supplier_name === '') {
    throw new Exception('Supplier name is required');
  }
  if ($equipment_id === '') {
    throw new Exception('Equipment ID is required');
  }

  // Get brand_id from brand_tbl
  $brand_id = 0;
  $brand_id_query = mysqli_prepare($connection, "SELECT brand_id FROM brand_tbl WHERE brand_name = ?");
  if ($brand_id_query) {
    mysqli_stmt_bind_param($brand_id_query, 's', $brand_name);
    mysqli_stmt_execute($brand_id_query);
    $result = mysqli_stmt_get_result($brand_id_query);
    $brand_row = mysqli_fetch_assoc($result);
    $brand_id = $brand_row['brand_id'] ?? 0;
    mysqli_stmt_close($brand_id_query);
  }

  if ($brand_id === 0) {
    throw new Exception('Invalid Brand Name. Brand does not exist in the database.');
  }

  // Get supplier_id from supplier_tbl
  $supplier_id = 0;
  $supplier_id_query = mysqli_prepare($connection, "SELECT supplier_id FROM supplier_tbl WHERE supplier_comp_name = ?");
  if ($supplier_id_query) {
    mysqli_stmt_bind_param($supplier_id_query, 's', $supplier_name);
    mysqli_stmt_execute($supplier_id_query);
    $result = mysqli_stmt_get_result($supplier_id_query);
    $supplier_row = mysqli_fetch_assoc($result);
    $supplier_id = $supplier_row['supplier_id'] ?? 0;
    mysqli_stmt_close($supplier_id_query);
  }

  if ($supplier_id === 0) {
    throw new Exception('Invalid Supplier Name. Supplier does not exist in the database.');
  }

  // Get equipment_id from equip_tbl
  $equipment_numeric_id = 0;
  $equipment_id_query = mysqli_prepare($connection, "SELECT equipment_id FROM equip_tbl WHERE custom_equip_id = ?");
  if ($equipment_id_query) {
    mysqli_stmt_bind_param($equipment_id_query, 's', $equipment_id);
    mysqli_stmt_execute($equipment_id_query);
    $result = mysqli_stmt_get_result($equipment_id_query);
    $equipment_row = mysqli_fetch_assoc($result);
    $equipment_numeric_id = $equipment_row['equipment_id'] ?? 0;
    mysqli_stmt_close($equipment_id_query);
  }

  if ($equipment_numeric_id === 0) {
    throw new Exception('Invalid Equipment ID. Equipment does not exist in the database.');
  }

  // parse last_update
  $dt = date_create_from_format('Y-m-d\TH:i', $last_in);
  if (! $dt) throw new Exception('Bad last_update format');
  $last_update = $dt->format('Y-m-d H:i:s');

  mysqli_begin_transaction($connection);
  $upd1 = mysqli_prepare(
    $connection,
    "UPDATE inventory_parts_tbl
       SET part_name=?, part_number=?, brand_id=?
     WHERE part_id=?"
  );
  if (!$upd1) {
    throw new Exception('Failed to prepare inventory_parts_tbl update statement: ' . mysqli_error($connection));
  }
  mysqli_stmt_bind_param($upd1, 'ssii', $name, $number, $brand_id, $part_id);
  if (!mysqli_stmt_execute($upd1)) {
    throw new Exception('Part update failed: ' . mysqli_stmt_error($upd1));
  }
  mysqli_stmt_close($upd1);

  $upd2 = mysqli_prepare(
    $connection,
    "UPDATE spareparts_inventory_tbl
       SET equipment_id=?, stock_quantity=?, supplier_id=?, unit_price=?, last_update=?
     WHERE part_id=?"
  );
  if (!$upd2) {
    throw new Exception('Failed to prepare spareparts_inventory_tbl update statement: ' . mysqli_error($connection));
  }
  mysqli_stmt_bind_param(
    $upd2,
    'iiidsi',
    $equipment_numeric_id,
    $stock_qty,
    $supplier_id,
    $unit_price,
    $last_update,
    $part_id
  );
  if (!mysqli_stmt_execute($upd2)) {
    throw new Exception('Inventory update failed: ' . mysqli_stmt_error($upd2));
  }
  mysqli_stmt_close($upd2);

  mysqli_commit($connection);

  header('Location: Inventory.php?status=success');
  exit();
} catch (Exception $e) {
  if (isset($connection) && mysqli_ping($connection)) {
    mysqli_rollback($connection);
  }

  echo json_encode([
    'success' => false,
    'message' => $e->getMessage()
  ]);
} finally {
  if (isset($connection)) {
    mysqli_close($connection);
  }
}
