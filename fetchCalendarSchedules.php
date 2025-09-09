<?php
// fetchCalendarSchedules.php
require 'database.php';

header('Content-Type: application/json');

$date = $_GET['date'] ?? '';

if (empty($date)) {
    echo json_encode(['success' => false, 'message' => 'Date parameter is missing.']);
    exit;
}

// Combine maintenance schedule and reported maintenance for the given date
$schedules = [];
$query = "
    SELECT
        e.custom_equip_id,
        'Maintenance' AS report_type,
        s.pms_status AS status
    FROM maintenance_sched_tbl s
    JOIN equip_tbl e ON s.equipment_id = e.equipment_id
    WHERE s.schedule_date = ?
    UNION ALL
    SELECT
        e.custom_equip_id,
        r.report_type,
        r.report_status AS status
    FROM report_tbl r
    JOIN equip_tbl e ON r.equipment_id = e.equipment_id
    WHERE r.report_date = ? AND r.report_type = 'Maintenance'
";

$stmt = $connection->prepare($query);
$stmt->bind_param("ss", $date, $date);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $schedules]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch schedules.']);
}

$stmt->close();
$connection->close();
?>