<?php
require 'database.php';

date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

if (!is_numeric($month) || !is_numeric($year)) {
    echo json_encode(['success' => false, 'message' => 'Invalid month or year parameter.']);
    exit;
}

$events = [];

$scheduleQuery = "
    SELECT
        s.schedule_date AS event_date,
        e.custom_equip_id,
        'Scheduled Maintenance' AS type,
        s.pms_status AS status
    FROM maintenance_sched_tbl s
    JOIN equip_tbl e ON s.equipment_id = e.equipment_id
    WHERE MONTH(s.schedule_date) = ? AND YEAR(s.schedule_date) = ?
";

$reportQuery = "
    SELECT
        r.report_date AS event_date,
        e.custom_equip_id,
        'Reported Maintenance' AS type,
        r.report_status AS status
    FROM report_tbl r
    JOIN equip_tbl e ON r.equipment_id = e.equipment_id
    WHERE r.report_type = 'Maintenance'
    AND MONTH(r.report_date) = ? AND YEAR(r.report_date) = ?
";

$combinedQuery = "$scheduleQuery UNION ALL $reportQuery ORDER BY event_date ASC";

$stmt = $connection->prepare($combinedQuery);
$stmt->bind_param("ssss", $month, $year, $month, $year);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $date = $row['event_date'];
        if (!isset($events[$date])) {
            $events[$date] = [];
        }
        $events[$date][] = $row;
    }
    echo json_encode(['success' => true, 'data' => $events]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch events.']);
}

$stmt->close();
$connection->close();
?>