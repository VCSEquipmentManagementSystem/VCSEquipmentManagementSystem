<?php
require('./database.php'); // Keep user's existing database include
if ($connection->connect_error) { // Use $connection as per user's file
    die("Connection failed: " . $connection->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["create"])) {
    $equipment_id = $_POST['equipment_id'];
    $schedule_date = $_POST['schedule_date'];
    $pms_status_from_form = $_POST['pms_status']; // The status selected by the user in the form

    // The report_status will always be the status selected by the user from the form.
    // This allows setting 'Completed' for future dates if desired, overcoming the previous 'Not yet started' default.
    $report_status_for_db = $pms_status_from_form;

    // Insert into maintenance_sched_tbl
    // The pms_status in maintenance_sched_tbl will be set to the determined report_status for consistency
    $stmt_maintenance = $connection->prepare("INSERT INTO maintenance_sched_tbl (equipment_id, schedule_date, pms_status) VALUES (?, ?, ?)");
    $stmt_maintenance->bind_param("iss", $equipment_id, $schedule_date, $report_status_for_db);

    if ($stmt_maintenance->execute()) {
        // Insert into report_tbl with report_type 'Maintenance' and the determined status
        $report_type = 'Maintenance';
        $report_date = $schedule_date; // Use the schedule date as report date

        $stmt_report = $connection->prepare("INSERT INTO report_tbl (equipment_id, report_type, report_date, report_status) VALUES (?, ?, ?, ?)");
        $stmt_report->bind_param("isss", $equipment_id, $report_type, $report_date, $report_status_for_db);

        if ($stmt_report->execute()) {
            echo "<script>alert('New maintenance schedule and report added successfully!'); window.location.href='MaintenanceScheduling.php';</script>";
        } else {
            echo "<script>alert('Error adding report: " . $stmt_report->error . "'); window.location.href='MaintenanceScheduling.php';</script>";
        }
        $stmt_report->close();
    } else {
        echo "<script>alert('Error adding maintenance schedule: " . $stmt_maintenance->error . "'); window.location.href='MaintenanceScheduling.php';</script>";
    }
    $stmt_maintenance->close();
}

$connection->close();
