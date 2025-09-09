<?php
require ('./database.php');

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["report_id"]) && isset($_POST["new_status"])) {
    $report_id = $_POST['report_id'];
    $new_status = $_POST['new_status'];

    // Update report_tbl
    $stmt_report = $connection->prepare("UPDATE report_tbl SET report_status = ? WHERE report_id = ?");
    $stmt_report->bind_param("si", $new_status, $report_id);

    if ($stmt_report->execute()) {
        // Optionally, update maintenance_sched_tbl as well if a matching entry exists
        // You'll need to fetch the equipment_id and schedule_date from report_tbl first to find the corresponding maintenance_sched_tbl entry
        $stmt_fetch_details = $connection->prepare("SELECT equipment_id, report_date FROM report_tbl WHERE report_id = ?");
        $stmt_fetch_details->bind_param("i", $report_id);
        $stmt_fetch_details->execute();
        $result_details = $stmt_fetch_details->get_result();
        $details = $result_details->fetch_assoc();
        $stmt_fetch_details->close();

        if ($details) {
            $equipment_id = $details['equipment_id'];
            $schedule_date = $details['report_date'];

            $stmt_maintenance = $connection->prepare("UPDATE maintenance_sched_tbl SET pms_status = ? WHERE equipment_id = ? AND schedule_date = ?");
            $stmt_maintenance->bind_param("sis", $new_status, $equipment_id, $schedule_date);
            $stmt_maintenance->execute();
            $stmt_maintenance->close();
        }

        echo "success";
    } else {
        echo "Error: " . $stmt_report->error;
    }
    $stmt_report->close();
} else {
    echo "Invalid request.";
}

$connection->close();
?>