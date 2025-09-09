<?php
// readInventory.php
require('./database.php');

$sql = "
  SELECT
    si.spare_id,
    si.part_id,
    p.part_name,
    p.part_number,
    b.brand_name,
    si.stock_quantity,
    si.equipment_id,
    e.custom_equip_id,
    si.supplier_id,
    s.supplier_comp_name AS supplier_name,
    si.unit_price,
    si.last_update
FROM spareparts_inventory_tbl AS si
JOIN inventory_parts_tbl AS p ON si.part_id = p.part_id
LEFT JOIN brand_tbl AS b ON p.brand_id = b.brand_id
LEFT JOIN supplier_tbl AS s ON si.supplier_id = s.supplier_id
LEFT JOIN equip_tbl AS e ON si.equipment_id = e.equipment_id
ORDER BY si.last_update DESC";

// $sql = "
//  SELECT CONCAT(part_name, ' ', part_id) AS concatenated
//  FROM inventory_parts_tbl";

$queryEquipment = "SELECT equipment_id, custom_equip_id FROM equip_tbl";
$sqlEquipment = mysqli_query($connection, $queryEquipment);

$querySupplier = "SELECT supplier_comp_name FROM supplier_tbl";
$sqlSupplier = mysqli_query($connection, $querySupplier);

$queryBrand = "SELECT * FROM brand_tbl";
$sqlBrand = mysqli_query($connection, $queryBrand);

$res = mysqli_query($connection, $sql);
if (!$res) {
  die("Read failed: " . mysqli_error($connection));
}

$inventoryRows = mysqli_fetch_all($res, MYSQLI_ASSOC);
