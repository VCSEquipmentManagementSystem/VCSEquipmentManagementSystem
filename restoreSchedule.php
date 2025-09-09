<?php
require('./database.php');

header('Content-Type: application/json');

try {
    // Get and decode JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!isset($data['archive_ids']) || empty($data['archive_ids'])) {
        throw new Exception('No items selected for restoration');
    }

    // Start transaction
    $connection->begin_transaction();

    $successCount = 0;
    $errors = [];

    // Prepare the select statement to get archived records
    $archiveIds = implode(',', array_map('intval', $data['archive_ids']));
    $selectQuery = "SELECT * FROM archived_schedule_tbl WHERE archive_id IN ($archiveIds)";
    $result = $connection->query($selectQuery);

    if (!$result) {
        throw new Exception("Failed to fetch archived records: " . $connection->error);
    }

    while ($row = $result->fetch_assoc()) {
        try {
            // First, insert into maintenance_sched_tbl
            $insertMaintenanceQuery = "INSERT INTO maintenance_sched_tbl 
                (equipment_id, schedule_date, pms_status, pms_remarks) 
                VALUES (?, ?, ?, ?)";
            
            $maintenanceStmt = $connection->prepare($insertMaintenanceQuery);
            if (!$maintenanceStmt) {
                throw new Exception("Failed to prepare maintenance statement: " . $connection->error);
            }

            $maintenanceStmt->bind_param("isss", 
                $row['equipment_id'],
                $row['schedule_date'],
                $row['pms_status'],
                $row['pms_remarks']
            );

            if (!$maintenanceStmt->execute()) {
                throw new Exception("Failed to execute maintenance insert: " . $maintenanceStmt->error);
            }

            $newMaintenanceId = $connection->insert_id;

            // Only try to insert report if we have the required data
            if (!empty($row['report_type']) && !empty($row['report_date'])) {
                $insertReportQuery = "INSERT INTO report_tbl 
                    (equipment_id, report_type, report_date, report_status, report_description, date_started) 
                    VALUES (?, ?, ?, 'Not yet started', ?, ?)";
                
                $reportStmt = $connection->prepare($insertReportQuery);
                if (!$reportStmt) {
                    throw new Exception("Failed to prepare report statement: " . $connection->error);
                }

                $reportStmt->bind_param("issss", 
                    $row['equipment_id'],
                    $row['report_type'],
                    $row['report_date'],
                    $row['pms_remarks'],
                    $row['schedule_date']
                );

                if (!$reportStmt->execute()) {
                    throw new Exception("Failed to execute report insert: " . $reportStmt->error);
                }
                $reportStmt->close();
            }

            // Delete from archived_schedule_tbl
            $deleteQuery = "DELETE FROM archived_schedule_tbl WHERE archive_id = ?";
            $deleteStmt = $connection->prepare($deleteQuery);
            if (!$deleteStmt) {
                throw new Exception("Failed to prepare delete statement: " . $connection->error);
            }

            $deleteStmt->bind_param("i", $row['archive_id']);
            
            if (!$deleteStmt->execute()) {
                throw new Exception("Failed to delete archive: " . $deleteStmt->error);
            }
            
            $deleteStmt->close();
            $successCount++;

        } catch (Exception $rowError) {
            $errors[] = "Error processing archive_id {$row['archive_id']}: " . $rowError->getMessage();
        } finally {
            if (isset($maintenanceStmt)) {
                $maintenanceStmt->close();
            }
        }
    }

    if (empty($errors)) {
        $connection->commit();
        echo json_encode([
            'status' => 'success',
            'message' => 'Successfully restored ' . $successCount . ' item(s)',
            'count' => $successCount
        ]);
    } else {
        throw new Exception(implode("\n", $errors));
    }

} catch (Exception $e) {
    $connection->rollback();
    error_log("Restore Schedule Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($connection)) {
        $connection->close();
    }
}