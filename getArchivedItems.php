<?php
require('../database.php');

// Query to get all archived items with complete information
$archivedQuery = "SELECT ai.*, ip.part_name, ip.part_number, b.brand_name as brand, 
                         ai.custom_equip_id
                 FROM archived_inventory_tbl ai
                 LEFT JOIN inventory_parts_tbl ip ON ai.part_id = ip.part_id
                 LEFT JOIN brand_tbl b ON ip.brand_id = b.brand_id";

$result = mysqli_query($connection, $archivedQuery); // Fixed variable name

if (!$result) {
    die('Query failed: ' . mysqli_error($connection));
}

$items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $items[] = [
        'part_id' => $row['part_id'],
        'part_name' => $row['part_name'],
        'part_number' => $row['part_number'],
        'brand_name' => $row['brand'],  
        'stock_quantity' => $row['stock_quantity'],
        'custom_equip_id' => $row['custom_equip_id'],  
        'archive_date' => $row['archive_date']
    ];
}

echo json_encode([
    'success' => true,
    'items' => $items
]);

mysqli_close($connection);
?>