<?php
require('./database.php');
header('Content-Type: application/json');

$sql = "SELECT 
    a.*,
    e.custom_equip_id 
FROM archived_schedule_tbl a
JOIN equip_tbl e ON a.equipment_id = e.equipment_id 
ORDER BY a.archive_date DESC";

$result = $connection->query($sql);
$archivedSchedules = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $archivedSchedules[] = $row;
    }
}

echo json_encode($archivedSchedules);
$connection->close();