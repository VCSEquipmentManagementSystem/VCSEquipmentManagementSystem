<?php
// readSchedule.php
require './database.php';

// Set the current month and year
$currentMonth = date('m');
$currentYear = date('Y');

// Fetch total count of maintenance for the month
$countQuery = "SELECT COUNT(*) AS total_maintenance FROM report_tbl WHERE report_type = 'Maintenance' AND MONTH(report_date) = ? AND YEAR(report_date) = ?";
$stmt = $connection->prepare($countQuery);
$stmt->bind_param("ss", $currentMonth, $currentYear);
$stmt->execute();
$result = $stmt->get_result();
$totalMaintenanceCount = 0;
if ($result) {
    $row = $result->fetch_assoc();
    $totalMaintenanceCount = $row['total_maintenance'];
}
$stmt->close();

// Fetch maintenance dates for the calendar, with source differentiation
$maintenanceDatesQuery = "
    SELECT 
        schedule_date AS maintenance_date,
        'schedule' as source,
        pms_status as status,
        e.custom_equip_id
    FROM maintenance_sched_tbl m
    JOIN equip_tbl e ON m.equipment_id = e.equipment_id
    WHERE MONTH(schedule_date) = ? AND YEAR(schedule_date) = ?
    
    UNION
    
    SELECT 
        report_date AS maintenance_date,
        'report' as source,
        report_status as status,
        e.custom_equip_id
    FROM report_tbl r
    JOIN equip_tbl e ON r.equipment_id = e.equipment_id
    WHERE report_type = 'Maintenance'
    AND MONTH(report_date) = ?
    AND YEAR(report_date) = ?
    ORDER BY maintenance_date ASC
";

$stmtDates = $connection->prepare($maintenanceDatesQuery);
$stmtDates->bind_param("ssss", $currentMonth, $currentYear, $currentMonth, $currentYear);
$stmtDates->execute();
$resultDates = $stmtDates->get_result();

$maintenanceDates = array();
while ($dateRow = $resultDates->fetch_assoc()) {
    $maintenanceDates[] = array(
        'date' => date('Y-m-d', strtotime($dateRow['maintenance_date'])),
        'source' => $dateRow['source'],
        'status' => $dateRow['status'],
        'custom_equip_id' => $dateRow['custom_equip_id']
    );
}
$stmtDates->close();

// Existing code for fetching schedules to display in the table
$schedules = [];
$schedulesQuery = "
    SELECT r.report_id, r.equipment_id, r.report_date AS schedule_date, r.report_status AS pms_status, e.custom_equip_id
    FROM report_tbl r
    JOIN equip_tbl e ON r.equipment_id = e.equipment_id
    WHERE MONTH(r.report_date) = ? AND YEAR(r.report_date) = ?
    AND r.report_type = 'Maintenance'
    ORDER BY r.report_date ASC";

$stmtSchedules = $connection->prepare($schedulesQuery);
$stmtSchedules->bind_param("ss", $currentMonth, $currentYear);
$stmtSchedules->execute();
$resultSchedules = $stmtSchedules->get_result();

if ($resultSchedules) {
    while ($row = $resultSchedules->fetch_assoc()) {
        $schedules[] = $row;
    }
}
$stmtSchedules->close();

$connection->close();
?>