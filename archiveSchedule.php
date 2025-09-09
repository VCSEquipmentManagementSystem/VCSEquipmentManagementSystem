<?php
// archiveSchedule.php
require ('./database.php');

header('Content-Type: application/json'); // Set header to indicate JSON response

if ($connection->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $connection->connect_error]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['report_id'])) {
    $report_id = $_POST['report_id'];
    // maintenance_id might be empty string if not applicable for a specific report
    $maintenance_id = isset($_POST['maintenance_id']) ? $_POST['maintenance_id'] : null;
    $custom_equip_id = isset($_POST['custom_equip_id']) ? $_POST['custom_equip_id'] : 'N/A'; // Get custom_equip_id from client

    // Start a transaction for atomicity
    $connection->begin_transaction();

    try {
        // 1. Fetch data from report_tbl
        $stmt_fetch_report = $connection->prepare("SELECT equipment_id, report_type, report_date, report_status FROM report_tbl WHERE report_id = ?");
        $stmt_fetch_report->bind_param("i", $report_id);
        $stmt_fetch_report->execute();
        $result_report = $stmt_fetch_report->get_result();
        $report_data = $result_report->fetch_assoc();
        $stmt_fetch_report->close();

        if (!$report_data) {
            throw new Exception("Report not found.");
        }

        // 2. Fetch data from maintenance_sched_tbl if maintenance_id is provided
        $maintenance_data = [];
        if ($maintenance_id !== null && !empty($maintenance_id)) {
            $stmt_fetch_maintenance = $connection->prepare("SELECT maintenance_id, equipment_id, schedule_date, pms_status, pms_remarks FROM maintenance_sched_tbl WHERE maintenance_id = ?");
            $stmt_fetch_maintenance->bind_param("i", $maintenance_id);
            $stmt_fetch_maintenance->execute();
            $result_maintenance = $stmt_fetch_maintenance->get_result();
            $maintenance_data = $result_maintenance->fetch_assoc();
            $stmt_fetch_maintenance->close();
            if (!$maintenance_data) {
                 // Even if maintenance_id is passed, if no data found, treat it as not linked or invalid
                 $maintenance_id = null;
            }
        }

        // Determine actual values for insertion, prioritizing maintenance_data if available
        $insert_maintenance_id = ($maintenance_id !== null && !empty($maintenance_data['maintenance_id'])) ? $maintenance_data['maintenance_id'] : null;
        $insert_equipment_id = $report_data['equipment_id'];
        $insert_schedule_date = ($maintenance_id !== null && !empty($maintenance_data['schedule_date'])) ? $maintenance_data['schedule_date'] : $report_data['report_date'];
        $insert_pms_status = ($maintenance_id !== null && !empty($maintenance_data['pms_status'])) ? $maintenance_data['pms_status'] : $report_data['report_status'];
        $insert_pms_remarks = ($maintenance_id !== null && !empty($maintenance_data['pms_remarks'])) ? $maintenance_data['pms_remarks'] : null;
        $insert_report_id = $report_id;
        $insert_report_type = $report_data['report_type'];
        $insert_report_date = $report_data['report_date'];
        $insert_report_status = $report_data['report_status'];


        // 3. Insert into archived_schedule_tbl
        // Adjust bind_param types if any field is guaranteed to be NULL for some rows
        $stmt_archive = $connection->prepare("INSERT INTO archived_schedule_tbl (maintenance_id, equipment_id, schedule_date, pms_status, pms_remarks, report_id, report_type, report_date, report_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        // Use "i" for integers, "s" for strings, "d" for doubles, "b" for blobs
        // If maintenance_id or pms_remarks can truly be NULL, use `issssssss` and bind `null` directly, or set their initial value to DB NULL type.
        // For simplicity, assuming they might be strings for now, adjust types as per your DB schema.
        $stmt_archive->bind_param(
            "iisssssss", // Assuming maintenance_id and equipment_id are integers
            $insert_maintenance_id,
            $insert_equipment_id,
            $insert_schedule_date,
            $insert_pms_status,
            $insert_pms_remarks,
            $insert_report_id,
            $insert_report_type,
            $insert_report_date,
            $insert_report_status
        );
        $stmt_archive->execute();
        $stmt_archive->close();

        // 4. Delete from report_tbl
        $stmt_delete_report = $connection->prepare("DELETE FROM report_tbl WHERE report_id = ?");
        $stmt_delete_report->bind_param("i", $report_id);
        $stmt_delete_report->execute();
        $stmt_delete_report->close();

        // 5. Delete from maintenance_sched_tbl if maintenance_id was found and used
        if ($insert_maintenance_id !== null) { // Only delete if a valid maintenance_id was inserted
            $stmt_delete_maintenance = $connection->prepare("DELETE FROM maintenance_sched_tbl WHERE maintenance_id = ?");
            $stmt_delete_maintenance->bind_param("i", $insert_maintenance_id);
            $stmt_delete_maintenance->execute();
            $stmt_delete_maintenance->close();
        }

        $connection->commit();
        echo json_encode(["status" => "success", "message" => "Maintenance schedule archived successfully!", "custom_equip_id" => $custom_equip_id]);

    } catch (Exception $e) {
        $connection->rollback();
        echo json_encode(["status" => "error", "message" => "Error archiving schedule: " . $e->getMessage()]);
    }

    $connection->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request. Missing report_id or not a POST request."]);
}
?>