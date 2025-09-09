<?php
require('./database.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    error_log("Received data: " . print_r($data, true));

    if (!isset($data['equipment_ids']) || !is_array($data['equipment_ids']) || empty($data['equipment_ids'])) {
        echo json_encode(['status' => 'error', 'messages' => ['No equipment IDs provided']]);
        exit;
    }

    $equipmentIds = $data['equipment_ids'];
} else {
    echo json_encode(['status' => 'error', 'messages' => ['Invalid request method']]);
    exit;
}

$success = true;
$messages = [];
$archivedCount = 0;

mysqli_begin_transaction($connection);

try {
    foreach ($equipmentIds as $equipmentId) {
        $equipmentId = mysqli_real_escape_string($connection, $equipmentId);

        // Get equipment data including date_added
        $sql = "SELECT * FROM equip_tbl WHERE " .
            (is_numeric($equipmentId) ? "equipment_id = '$equipmentId'" : "custom_equip_id = '$equipmentId'");

        $result = mysqli_query($connection, $sql);

        if (!$result) {
            $error = mysqli_error($connection);
            error_log("SELECT query failed: $error - $sql");
            throw new Exception("Failed to fetch equipment data for ID: $equipmentId. MySQL error: $error");
        }

        if (mysqli_num_rows($result) > 0) {
            $equipmentData = mysqli_fetch_assoc($result);

            // Build INSERT query, EXCLUDING equipment_id (primary key)
            $insertSql = "INSERT INTO archivedequipment_tbl SET ";

            $fields = [];
            foreach ($equipmentData as $key => $value) {
                if ($key != 'equipment_id') {
                    $fields[] = "`$key` = '" . mysqli_real_escape_string($connection, $value) . "'";
                }
            }

            // Add archived_date
            $fields[] = "archived_date = NOW()";

            $insertSql .= implode(", ", $fields);

            $insertResult = mysqli_query($connection, $insertSql);

            if (!$insertResult) {
                $error = mysqli_error($connection);
                error_log("INSERT query failed: $error - $insertSql");
                throw new Exception("Failed to archive equipment data for ID: $equipmentId. MySQL error: $error");
            }

            // Delete related inventory parts first (to avoid foreign key constraint error)
            $deletePartsSql = "DELETE FROM inventory_parts_tbl WHERE equipment_id = '{$equipmentData['equipment_id']}'";
            $deletePartsResult = mysqli_query($connection, $deletePartsSql);
            if (!$deletePartsResult) {
                $error = mysqli_error($connection);
                error_log("DELETE parts query failed: $error - $deletePartsSql");
                throw new Exception("Failed to delete inventory parts for equipment ID: {$equipmentData['equipment_id']}. MySQL error: $error");
            }

            // Delete from main table
            $deleteSql = "DELETE FROM equip_tbl WHERE equipment_id = '{$equipmentData['equipment_id']}'";
            $deleteResult = mysqli_query($connection, $deleteSql);

            if (!$deleteResult) {
                $error = mysqli_error($connection);
                error_log("DELETE query failed: $error - $deleteSql");
                throw new Exception("Failed to delete equipment data for ID: $equipmentId. MySQL error: $error");
            }

            $archivedCount++;
            $messages[] = "Equipment ID $equipmentId archived successfully";
        } else {
            // Instead of error, just warn and continue
            $messages[] = "Equipment ID $equipmentId not found or already archived";
            // Do not set $success = false here
            continue;
        }
    }

    // Reset AUTO_INCREMENT if equip_tbl is empty
    $checkEmpty = mysqli_query($connection, "SELECT COUNT(*) as cnt FROM equip_tbl");
    $row = mysqli_fetch_assoc($checkEmpty);
    if ($row && $row['cnt'] == 0) {
        mysqli_query($connection, "ALTER TABLE equip_tbl AUTO_INCREMENT = 1");
    }

    mysqli_commit($connection);

    echo json_encode([
        'status' => ($archivedCount > 0 && $success) ? 'success' : ($archivedCount > 0 ? 'partial' : 'error'),
        'message' => $archivedCount . " equipment item(s) archived successfully",
        'messages' => $messages
    ]);
} catch (Exception $e) {
    mysqli_rollback($connection);
    error_log("Transaction failed: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Error archiving equipment: ' . $e->getMessage(),
        'messages' => [$e->getMessage()]
    ]);
}

mysqli_close($connection);
?>