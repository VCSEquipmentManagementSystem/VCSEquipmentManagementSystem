<?php
require('./database.php');

header('Content-Type: application/json');

// Fetch all archived equipment
$sql = "SELECT a.*, e.equip_type_name 
        FROM archivedEquipment_tbl a
        LEFT JOIN equip_type_tbl e ON a.equip_type_id = e.equip_type_id
        ORDER BY a.custom_equip_id ASC";

$result = mysqli_query($connection, $sql);

if (!$result) {
    echo json_encode(['status' => 'error', 'message' => 'Error fetching archived equipment: ' . mysqli_error($connection)]);
    exit;
}

$archivedEquipment = [];
while ($row = mysqli_fetch_assoc($result)) {
    $archivedEquipment[] = $row;
}

echo json_encode($archivedEquipment);

mysqli_close($connection);
?>
