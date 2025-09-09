<?php
// fetchInventory.php
// This script fetches inventory data and defines $rows for use in Inventory.php

error_reporting(E_ALL);
ini_set('display_errors', 1);
require('./database.php');  // establishes $connection (mysqli)

// initialize rows array to avoid undefined variable
$rows = [];

// 1) Build & execute the JOIN query
$sql = "
  SELECT
    i.spare_id,
    i.part_id,
    p.part_name,
    b.brand_name,
    p.part_number,
    i.equipment_id,
    i.stock_quantity,
    i.supplier_id,
    i.unit_price,
    i.last_update
  FROM spareparts_inventory_tbl AS i
  JOIN inventory_parts_tbl    AS p ON i.part_id   = p.part_id
  LEFT JOIN brand_tbl         AS b ON p.brand_id   = b.brand_id
  ORDER BY i.last_update DESC
";

if ($result = mysqli_query($connection, $sql)) {
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_free_result($result);
} else {
    error_log("Inventory fetch failed: " . mysqli_error($connection));
    // keep $rows as empty array, display logic will handle no-data case
}

mysqli_close($connection);
