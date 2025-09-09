<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); 
ob_start(); 
require('./database.php');

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $response = [
        'status' => 'error',
        'messages' => []
    ];
    
    // Validate input
    if (!isset($data['equipment_ids']) || empty($data['equipment_ids'])) {
        $response['messages'][] = 'No equipment IDs provided';
        echo json_encode($response);
        exit;
    }
    
    $equipmentIds = $data['equipment_ids'];
    $successCount = 0;
    $errorCount = 0;
    
    // Process each equipment ID
    foreach ($equipmentIds as $equipmentId) {
        // Sanitize the ID
        $equipmentId = mysqli_real_escape_string($connection, $equipmentId);
        
        // Start transaction for atomicity
        mysqli_begin_transaction($connection);
        
        try {
            $query = "SELECT * FROM archivedequipment_tbl WHERE equipment_id = '$equipmentId'";
            $result = mysqli_query($connection, $query);
            
            if (!$result || mysqli_num_rows($result) === 0) {
                $errorCount++;
                $response['messages'][] = "Equipment ID: $equipmentId not found in archive";
                mysqli_rollback($connection); 
                continue;
            }
            
            $archivedData = mysqli_fetch_assoc($result);
            
            // Verify foreign key constraints
            $constraints = [
                'brand_id' => [
                    'table' => 'brand_tbl',
                    'column' => 'brand_id'
                ],
                'operator_id' => [
                    'table' => 'employee_tbl',
                    'column' => 'employee_id'  
                ],
                'equip_type_id' => [
                    'table' => 'equip_type_tbl',
                    'column' => 'equip_type_id'
                ],
                'assigned_proj_id' => [
                    'table' => 'proj_sched_tbl',
                    'column' => 'project_id'  
                ]
            ];
            
            // Check foreign key constraints
            foreach ($constraints as $field => $reference) {
                if (isset($archivedData[$field])) {
                    $checkQuery = "SELECT {$reference['column']} FROM {$reference['table']} 
                                 WHERE {$reference['column']} = " . 
                                (is_numeric($archivedData[$field]) ? 
                                $archivedData[$field] : 
                                "'" . mysqli_real_escape_string($connection, $archivedData[$field]) . "'");
                    
                    $checkResult = mysqli_query($connection, $checkQuery);
                    
                    if (!$checkResult || mysqli_num_rows($checkResult) === 0) {
                        $archivedData[$field] = null;
                    }
                }
            }

            // Build INSERT query
            $insertQuery = "INSERT INTO equip_tbl SET ";
            
            $fields = [];
            foreach ($archivedData as $key => $value) {
                if ($key != 'archived_date') {
                    if ($key == 'equip_status' && $value == 'Active') {
                        $fields[] = "`$key` = 'Idle'";
                    } else if (array_key_exists($key, $constraints) && $value === null) {
                        $fields[] = "`$key` = NULL";
                    } else if ($key == 'date_added') {
                        // Preserve original date_added
                        $fields[] = "`$key` = '" . mysqli_real_escape_string($connection, $value) . "'";
                    } else {
                        $fields[] = "`$key` = " . ($value === null ? "NULL" : 
                                  "'" . mysqli_real_escape_string($connection, $value) . "'");
                    }
                }
            }

            // Build INSERT query
            $insertQuery = "INSERT INTO equip_tbl SET ";
            
            $fields = [];
            foreach ($archivedData as $key => $value) {
                if ($key != 'archived_date' && $key != 'archived_id') { // Exclude archived_id
                    if ($key == 'equip_status' && $value == 'Active') {
                        $fields[] = "`$key` = 'Idle'";
                    } else if (in_array($key, array_keys($constraints)) && $value === null) {
                        $fields[] = "`$key` = NULL";
                    } else if ($key == 'date_added') {
                        $fields[] = "`$key` = '" . mysqli_real_escape_string($connection, $value) . "'";
                    } else {
                        $fields[] = "`$key` = " . ($value === null ? "NULL" : 
                                  "'" . mysqli_real_escape_string($connection, $value) . "'");
                    }
                }
            }
            
            $insertQuery .= implode(", ", $fields);
            
            $insertResult = mysqli_query($connection, $insertQuery);
            
            if (!$insertResult) {
                $errorCount++;
                $response['messages'][] = "Failed to restore equipment ID: $equipmentId. Error: " . mysqli_error($connection);
                mysqli_rollback($connection); 
                continue;
            }
            
            // Delete from archived table
            $deleteQuery = "DELETE FROM archivedequipment_tbl WHERE equipment_id = '$equipmentId'";
            $deleteResult = mysqli_query($connection, $deleteQuery);
            
            if (!$deleteResult) {
                $errorCount++;
                $response['messages'][] = "Failed to remove from archive equipment ID: $equipmentId. Error: " . mysqli_error($connection);
                mysqli_rollback($connection); 
                continue;
            }
            
            mysqli_commit($connection); 
            $successCount++;
        } catch (Exception $e) {
            mysqli_rollback($connection);
            $errorCount++;
            $response['messages'][] = "Error processing equipment ID: $equipmentId. " . $e->getMessage();
        }
    }

    // Reset AUTO_INCREMENT if archivedequipment_tbl is empty
    $checkEmpty = mysqli_query($connection, "SELECT COUNT(*) as cnt FROM archivedequipment_tbl");
    $row = mysqli_fetch_assoc($checkEmpty);
    if ($row && $row['cnt'] == 0) {
        mysqli_query($connection, "ALTER TABLE archivedequipment_tbl AUTO_INCREMENT = 1");
    }
    
    if ($successCount > 0 && $errorCount === 0) {
        $response['status'] = 'success';
        $response['message'] = "$successCount equipment item(s) restored successfully";
    } elseif ($successCount > 0 && $errorCount > 0) {
        $response['status'] = 'partial';
        $response['message'] = "$successCount equipment item(s) restored successfully, $errorCount failed";
    } else {
        $response['message'] = "Failed to restore any equipment items";
    }
    
    ob_end_clean();
    echo json_encode($response);
    
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'messages' => ['Unexpected error: ' . $e->getMessage()]
    ]);
}

mysqli_close($connection);
?>
