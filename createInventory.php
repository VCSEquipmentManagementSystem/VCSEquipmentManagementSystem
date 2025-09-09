<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require('./database.php');

function respondWithError($msg)
{
    $msg = urlencode($msg);
    header("Location: Inventory.php?error={$msg}");
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['add'])) {
        throw new Exception('Invalid request');
    }

    // 1) Sanitize & pull inputs
    $part_name      = trim($_POST['PartNameInput']    ?? '');
    $part_number    = trim($_POST['PartNumInput']     ?? '');
    $brand_name     = trim($_POST['BrandInput']       ?? '');
    $stock_quantity = filter_var($_POST['StockInput'] ?? null, FILTER_VALIDATE_INT);
    $custom_equip   = trim($_POST['EquipmentIdInput'] ?? '');
    $supplier_name  = trim($_POST['SupplierIdInput']  ?? '');
    $unit_price     = filter_var($_POST['UnitPriceInput'] ?? null, FILTER_VALIDATE_FLOAT);
    $last_update_in = trim($_POST['LastUpdateInput']  ?? '');

    // 2) Validate
    if ($part_name === '' || $part_number === '' || $custom_equip === '' || $supplier_name === '') {
        throw new Exception('All fields are required.');
    }
    if ($stock_quantity === false || $stock_quantity < 0) {
        throw new Exception('Stock must be a non-negative integer.');
    }
    if ($unit_price === false || $unit_price < 0) {
        throw new Exception('Unit price must be a non-negative number.');
    }

    // parse optional datetime-local
    $last_update = null;
    if ($last_update_in !== '') {
        $dt = date_create_from_format('Y-m-d\TH:i', $last_update_in);
        if (!$dt) throw new Exception('Bad Last Update format');
        $last_update = $dt->format('Y-m-d H:i:s');
    }

    mysqli_begin_transaction($connection);

    // BRAND lookup/insert → $brand_id
    $brand_id = null;
    if ($brand_name !== '') {
        $sql = "SELECT brand_id FROM brand_tbl WHERE LOWER(TRIM(brand_name)) = LOWER(TRIM(?))";
        $st  = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($st, 's', $brand_name);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);

        if ($row = mysqli_fetch_assoc($res)) {
            $brand_id = $row['brand_id'];
        } else {
            $sql = "INSERT INTO brand_tbl (brand_name) VALUES (?)";
            $st  = mysqli_prepare($connection, $sql);
            mysqli_stmt_bind_param($st, 's', $brand_name);
            mysqli_stmt_execute($st);
            $brand_id = mysqli_insert_id($connection);
        }
    }

    // PART insert → $part_id (prevent duplicate part_number for same equipment)
    $sql = "SELECT ip.part_id FROM inventory_parts_tbl ip
            JOIN spareparts_inventory_tbl si ON ip.part_id = si.part_id
            JOIN equip_tbl eq ON si.equipment_id = eq.equipment_id
            WHERE ip.part_number = ? AND eq.custom_equip_id = ?";
    $st = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($st, 'ss', $part_number, $custom_equip);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    if (mysqli_fetch_assoc($res)) {
        throw new Exception("Part number already exists for this equipment.");
    }

    $sql = "INSERT INTO inventory_parts_tbl (part_name, brand_id, part_number)
            VALUES (?, ?, ?)";
    $st  = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($st, 'sis', $part_name, $brand_id, $part_number);
    if (!mysqli_stmt_execute($st)) {
        throw new Exception("Insert part failed: " . mysqli_stmt_error($st));
    }
    $part_id = mysqli_insert_id($connection);

    // EQUIPMENT lookup by custom_equip_id → equipment_id
    $sql = "SELECT equipment_id FROM equip_tbl WHERE custom_equip_id = ?";
    $st  = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($st, 's', $custom_equip);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    if (! $row = mysqli_fetch_assoc($res)) {
        throw new Exception("Equipment “{$custom_equip}” not found");
    }
    $equipment_id = intval($row['equipment_id']);

    // SUPPLIER lookup by company name → supplier_id
    $sql = "SELECT supplier_id FROM supplier_tbl WHERE supplier_comp_name = ?";
    $st  = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($st, 's', $supplier_name);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    if (! $row = mysqli_fetch_assoc($res)) {
        throw new Exception("Supplier “{$supplier_name}” not found");
    }
    $supplier_id = intval($row['supplier_id']);

    // FINAL INSERT into spareparts_inventory_tbl
    $fields       = ['part_id', 'equipment_id', 'stock_quantity', 'supplier_id', 'unit_price'];
    $placeholders = '?,?,?,?,?';
    $types        = 'iiiid';
    $values       = [
        $part_id,
        $equipment_id,
        $stock_quantity,
        $supplier_id,
        $unit_price
    ];

    if ($last_update !== null) {
        $fields[]      = 'last_update';
        $placeholders .= ',?';
        $types        .= 's';
        $values[]      = $last_update;
    }

    $sql = sprintf(
        "INSERT INTO spareparts_inventory_tbl (%s) VALUES (%s)",
        implode(',', $fields),
        $placeholders
    );
    $st  = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($st, $types, ...$values);
    if (!mysqli_stmt_execute($st)) {
        throw new Exception("Insert inventory failed: " . mysqli_stmt_error($st));
    }

    mysqli_commit($connection);
    echo "<script>
            alert('Successfully Added');
            window.location.href='Inventory.php';
          </script>";
    exit;
} catch (Exception $e) {
    if (isset($connection) && mysqli_ping($connection)) {
        mysqli_rollback($connection);
    }
    respondWithError($e->getMessage());
} finally {
    if (isset($connection)) {
        mysqli_close($connection);
    }
}
