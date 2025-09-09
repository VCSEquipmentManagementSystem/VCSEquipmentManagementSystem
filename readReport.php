<?php
include_once('database.php');

$reportData = []; //holds the combine data
$equipmentData = [];
$operatorData = [];
$chiefMechanicData = [];
$inventoryData = [];
$projectData = [];
$profileData = [];

if (isset($_SESSION['userID'])) {
  $currentUserID = $_SESSION['userID'];
  $profileQuery = "SELECT name FROM user_tbl WHERE user_id = '$currentUserID'";
  $profileResult = mysqli_query($connection, $profileQuery);
  if ($profileResult && mysqli_num_rows($profileResult) > 0) {
    $profileData = mysqli_fetch_assoc($profileResult);
    $profileData['first_name'] = $profileData['name'];
    $profileData['last_name'] = '';
  }
}

// Main query to fetch all report data
$query = "
    SELECT
        r.report_id,
        r.report_type,
        r.report_status AS status,
        r.report_date AS date,
        e.custom_equip_id,
        CONCAT(emp_op.first_name, ' ', emp_op.last_name) AS operator_name,
        CONCAT(emp_rep.first_name, ' ', emp_rep.last_name) AS reported_by,
        CONCAT(emp_insp.first_name, ' ', emp_insp.last_name) AS inspected_by,
        CONCAT(emp_repby.first_name, ' ', emp_repby.last_name) AS repaired_by,
        r.problem_encountered,
        r.final_diagnosis,
        r.details_of_work_done,
        r.remarks_report AS remarks,
        CONCAT(emp_con.first_name, ' ', emp_con.last_name) AS conducted_by_name,
        r.date_started,
        r.date_completed,
        r.time_started,
        r.time_completed
    FROM
        report_tbl r
    LEFT JOIN
        equip_tbl e ON r.equipment_id = e.equipment_id
    LEFT JOIN
        user_tbl u_op ON r.operator_id = u_op.user_id
    LEFT JOIN
        employee_tbl emp_op ON u_op.employee_id = emp_op.employee_id
    LEFT JOIN
        user_tbl u_rep ON r.report_by = u_rep.user_id
    LEFT JOIN
        employee_tbl emp_rep ON u_rep.employee_id = emp_rep.employee_id
    LEFT JOIN
        user_tbl u_insp ON r.inspected_by = u_insp.user_id
    LEFT JOIN
        employee_tbl emp_insp ON u_insp.employee_id = emp_insp.employee_id
    LEFT JOIN
        user_tbl u_repby ON r.repaired_by = u_repby.user_id
    LEFT JOIN
        employee_tbl emp_repby ON u_repby.employee_id = emp_repby.employee_id
    LEFT JOIN
        user_tbl u_con ON r.conducted_by = u_con.user_id
    LEFT JOIN
        employee_tbl emp_con ON u_con.employee_id = emp_con.employee_id
    ORDER BY
        r.report_date DESC, r.report_id DESC
";

$result = mysqli_query($connection, $query);
$reportData = [];
if ($result) {
  while ($row = mysqli_fetch_assoc($result)) {
    $reportData[] = $row;
  }
} else {
  echo "Error fetching reports: " . mysqli_error($connection);
}

// Function to get part requests for a specific report
function getPartsByReportId($connection, $reportId)
{
  $parts = [];
  $reportId = mysqli_real_escape_string($connection, $reportId);
  $query = "SELECT quantity, unit, item_description, remarks FROM report_part_tbl WHERE report_id = '$reportId'";
  $result = mysqli_query($connection, $query);
  if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
      $parts[] = $row;
    }
  }
  return $parts;
}

// Sort the combined reportData by date (most recent first)
usort($reportData, function ($a, $b) {
  return strtotime($b['date']) - strtotime($a['date']);
});


// Fetch equipment data for dropdowns
$equipmentQuery = "SELECT equipment_id, custom_equip_id FROM equip_tbl";
$equipmentResult = mysqli_query($connection, $equipmentQuery);
if ($equipmentResult) {
  while ($row = mysqli_fetch_assoc($equipmentResult)) {
    $equipmentData[] = $row;
  }
}

// Fetch operator data for dropdowns (from user_tbl where role is 'Equipment Operator')
$operatorQuery = "SELECT user_id, name FROM user_tbl WHERE role = 'Equipment Operator' AND employee_id IS NOT NULL";
$operatorResult = mysqli_query($connection, $operatorQuery);
if ($operatorResult) {
  while ($row = mysqli_fetch_assoc($operatorResult)) {
    $operatorData[] = $row;
  }
}

// Fetch Chief Mechanic data for dropdowns (from user_tbl where role is 'Chief Mechanic')
$chiefMechanicQuery = "SELECT user_id, name FROM user_tbl WHERE role = 'Chief Mechanic' AND employee_id IS NOT NULL";
$chiefMechanicResult = mysqli_query($connection, $chiefMechanicQuery);
if ($chiefMechanicResult) {
  while ($row = mysqli_fetch_assoc($chiefMechanicResult)) {
    $chiefMechanicData[] = $row;
  }
}

// Fetch all personnel with an employee_id for the "Conducted by" dropdown (INT field)
$allPersonnelForConductedByQuery = "SELECT user_id, name FROM user_tbl WHERE employee_id IS NOT NULL ORDER BY name ASC";
$allPersonnelForConductedByResult = mysqli_query($connection, $allPersonnelForConductedByQuery);
$allPersonnelForConductedByData = [];
if ($allPersonnelForConductedByResult) {
  while ($row = mysqli_fetch_assoc($allPersonnelForConductedByResult)) {
    $allPersonnelForConductedByData[] = $row;
  }
}


// Fetch inventory data for dropdowns (part names)
$inventoryQuery = "SELECT part_id, part_name FROM inventory_parts_tbl";
$inventoryResult = mysqli_query($connection, $inventoryQuery);
if ($inventoryResult) {
  while ($row = mysqli_fetch_assoc($inventoryResult)) {
    $inventoryData[] = $row;
  }
}

// Fetch project data for dropdowns
$projectQuery = "SELECT project_id, project_name FROM proj_sched_tbl";
$projectResult = mysqli_query($connection, $projectQuery);
if ($projectResult) {
  while ($row = mysqli_fetch_assoc($projectResult)) {
    $projectData[] = $row;
  }
}

// total reports
$currentMonth = date('Y-m');

$totalMaintenanceReportsQuery = "SELECT COUNT(report_id) AS count FROM report_tbl WHERE DATE_FORMAT(report_date, '%Y-%m') = '$currentMonth'";
$totalMaintenanceReportsResult = mysqli_query($connection, $totalMaintenanceReportsQuery);
$totalMaintenanceReportsRow = mysqli_fetch_assoc($totalMaintenanceReportsResult);
$totalMaintenanceReports = $totalMaintenanceReportsRow['count'] ?? 0;

$totalUsageReportsQuery = "SELECT COUNT(usage_id) AS count FROM equip_usage_tbl WHERE DATE_FORMAT(log_date, '%Y-%m') = '$currentMonth'";
$totalUsageReportsResult = mysqli_query($connection, $totalUsageReportsQuery);
$totalUsageReportsRow = mysqli_fetch_assoc($totalUsageReportsResult);
$totalUsageReports = $totalUsageReportsRow['count'] ?? 0;

$totalReportsCount = $totalMaintenanceReports + $totalUsageReports;

// pending review
$pendingReviewQuery = "SELECT COUNT(*) as total FROM report_tbl WHERE report_status = 'Open' AND MONTH(report_date) = MONTH(CURRENT_DATE()) AND YEAR(report_date) = YEAR(CURRENT_DATE())";
$pendingReviewResult = mysqli_query($connection, $pendingReviewQuery);
$pendingReviewCount = mysqli_fetch_assoc($pendingReviewResult)['total'];

// IN progress
// Fetch ongoing reports for the current month/year
$ongoingReportQuery = "SELECT COUNT(*) as total FROM report_tbl WHERE report_status = 'In Progress' AND MONTH(report_date) = MONTH(CURRENT_DATE()) AND YEAR(report_date) = YEAR(CURRENT_DATE())";
$ongoingReportResult = mysqli_query($connection, $ongoingReportQuery);
$ongoingReportCount = mysqli_fetch_assoc($ongoingReportResult)['total'];
