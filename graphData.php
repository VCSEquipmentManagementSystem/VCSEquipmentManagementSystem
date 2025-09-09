<?php
include('./database.php');

$labels = [];
$counts = [];
// fetch reports
$query_reports = "SELECT report_type, COUNT(*) as count FROM report_tbl GROUP BY report_type";
$result_reports = mysqli_query($connection, $query_reports);

if ($result_reports) {
    while ($row = mysqli_fetch_assoc($result_reports)) {
        if (in_array($row['report_type'], ['Maintenance', 'Repair', 'Breakdown'])) {
            $labels[] = $row['report_type'];
            $counts[] = (int)$row['count'];
        }
    }
    mysqli_free_result($result_reports);
} else {
    error_log("Error in report_tbl query: " . mysqli_error($connection));
}

// fetch usage
$query_usage = "SELECT COUNT(*) as usage_count FROM equip_usage_tbl";
$result_usage = mysqli_query($connection, $query_usage);

if ($result_usage) {
    $row_usage = mysqli_fetch_assoc($result_usage);
    $usage_count = (int)$row_usage['usage_count'];

    // Add "Equipment Usage" to the labels and counts
    $labels[] = 'Equipment Usage Report';
    $counts[] = $usage_count;

    mysqli_free_result($result_usage);
} else {
    error_log("Error in equip_usage_tbl query: " . mysqli_error($connection));
}
echo json_encode(['labels' => $labels, 'counts' => $counts]);
mysqli_close($connection);
