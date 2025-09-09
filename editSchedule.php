<?php
require('./database.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    // Get equipment ID and validate it
    $equipment_id = isset($_POST['equipment_id']) ? intval($_POST['equipment_id']) : 0;
    
    if ($equipment_id <= 0) {
        sendResponse('error', 'Invalid equipment ID', $isAjax);
        exit;
    }
    
    error_log("POST data: " . print_r($_POST, true));
    
    // Get current equipment data
    $getCurrentDataQuery = "SELECT custom_equip_id, equip_type_id FROM equip_tbl WHERE equipment_id = $equipment_id";
    $currentDataResult = mysqli_query($connection, $getCurrentDataQuery);
    
    if (!$currentDataResult || mysqli_num_rows($currentDataResult) == 0) {
        sendResponse('error', 'Equipment not found', $isAjax);
        exit;
    }
    
    $currentData = mysqli_fetch_assoc($currentDataResult);
    $currentCustomEquipId = $currentData['custom_equip_id'];
    $currentEquipTypeId = $currentData['equip_type_id'];
    
    // Collect and sanitize form data
    $equipmentType = isset($_POST['equipmentType']) ? mysqli_real_escape_string($connection, $_POST['equipmentType']) : '';
    $model = isset($_POST['model']) ? mysqli_real_escape_string($connection, $_POST['model']) : '';
    $year = isset($_POST['year']) ? mysqli_real_escape_string($connection, $_POST['year']) : '';
    $serial = isset($_POST['serial']) ? mysqli_real_escape_string($connection, $_POST['serial']) : '';
    $capacity = isset($_POST['capacity']) ? mysqli_real_escape_string($connection, $_POST['capacity']) : '';
    $bodyId = isset($_POST['bodyId']) ? mysqli_real_escape_string($connection, $_POST['bodyId']) : '';
    $operatorId = isset($_POST['operator']) ? (int)$_POST['operator'] : null;
    $remarks = isset($_POST['remarks']) ? mysqli_real_escape_string($connection, $_POST['remarks']) : '';
    $lastOperatingHours = isset($_POST['lastOperatingHours']) ? mysqli_real_escape_string($connection, $_POST['lastOperatingHours']) : '';
    $maintenanceInterval = isset($_POST['maintenanceInterval']) ? mysqli_real_escape_string($connection, $_POST['maintenanceInterval']) : '';
    $transmissionType = isset($_POST['transmissionType']) ? mysqli_real_escape_string($connection, $_POST['transmissionType']) : '';
    $engineType = isset($_POST['engineType']) ? mysqli_real_escape_string($connection, $_POST['engineType']) : '';
    $fuelType = isset($_POST['fuelType']) ? mysqli_real_escape_string($connection, $_POST['fuelType']) : '';
    $lastPMS = isset($_POST['lastPMS']) ? mysqli_real_escape_string($connection, $_POST['lastPMS']) : '';
    
    $fuelTankCapacity = isset($_POST['fuelTankCapacity']) ? mysqli_real_escape_string($connection, $_POST['fuelTankCapacity']) : '';
    if ($fuelTankCapacity !== '') {
        // Remove any "Liters" (case-insensitive), trim, then append " Liters"
        $fuelTankCapacity = trim(preg_replace('/\s*liters?$/i', '', $fuelTankCapacity)) . ' Liters';
    }
    
    // Check if the fuel_tank_capacity column exists
    $checkColumnQuery = "SHOW COLUMNS FROM equip_tbl LIKE 'fuel_tank_capacity'";
    $columnResult = mysqli_query($connection, $checkColumnQuery);
    
    // If the column doesn't exist, add it
    if (mysqli_num_rows($columnResult) == 0) {
        $addColumnQuery = "ALTER TABLE equip_tbl ADD COLUMN fuel_tank_capacity VARCHAR(50) AFTER fuel_type";
        mysqli_query($connection, $addColumnQuery);
    }
    
    // Get the equipment type ID from the equipment type name
    $equip_type_id = null;
    if (!empty($equipmentType)) {
        if (is_numeric($equipmentType)) {
            $equip_type_id = intval($equipmentType);
        } else {
            $typeQuery = "SELECT equip_type_id FROM equip_type_tbl WHERE equip_type_name = '$equipmentType'";
            $typeResult = mysqli_query($connection, $typeQuery);
            
            if ($typeResult && mysqli_num_rows($typeResult) > 0) {
                $typeRow = mysqli_fetch_assoc($typeResult);
                $equip_type_id = $typeRow['equip_type_id'];
            } else {
                $insertTypeQuery = "INSERT INTO equip_type_tbl (equip_type_name) VALUES ('$equipmentType')";
                if (mysqli_query($connection, $insertTypeQuery)) {
                    $equip_type_id = mysqli_insert_id($connection);
                } else {
                    sendResponse('error', 'Failed to create equipment type: ' . mysqli_error($connection), $isAjax);
                    exit;
                }
            }
        }
    }
    
    // Generate new custom_equip_id if equipment type changed
    $newCustomEquipId = $currentCustomEquipId;
    if (!empty($equip_type_id) && $equip_type_id != $currentEquipTypeId) {
        // Get the new prefix code
        $prefixQuery = "SELECT prefix_code FROM equip_type_tbl WHERE equip_type_id = $equip_type_id";
        $prefixResult = mysqli_query($connection, $prefixQuery);
        
        if ($prefixResult && mysqli_num_rows($prefixResult) > 0) {
            $prefixRow = mysqli_fetch_assoc($prefixResult);
            $newPrefix = $prefixRow['prefix_code'];
            
            // Extract the number part from current custom_equip_id
            $currentNumber = preg_replace('/^[A-Z]+/', '', $currentCustomEquipId);
            
            // Generate new custom_equip_id with new prefix but same number
            $newCustomEquipId = $newPrefix . $currentNumber;
            
            // Check if this new custom_equip_id already exists
            $checkDuplicateQuery = "SELECT equipment_id FROM equip_tbl WHERE custom_equip_id = '$newCustomEquipId' AND equipment_id != $equipment_id";
            $duplicateResult = mysqli_query($connection, $checkDuplicateQuery);
            
            if ($duplicateResult && mysqli_num_rows($duplicateResult) > 0) {
                // If duplicate exists, find the next available number for this prefix
                $findNextNumberQuery = "SELECT custom_equip_id FROM equip_tbl WHERE custom_equip_id LIKE '$newPrefix%' ORDER BY CAST(SUBSTRING(custom_equip_id, " . (strlen($newPrefix) + 1) . ") AS UNSIGNED) DESC LIMIT 1";
                $nextNumberResult = mysqli_query($connection, $findNextNumberQuery);
                
                if ($nextNumberResult && mysqli_num_rows($nextNumberResult) > 0) {
                    $lastRow = mysqli_fetch_assoc($nextNumberResult);
                    $lastNumber = preg_replace('/^[A-Z]+/', '', $lastRow['custom_equip_id']);
                    $nextNumber = str_pad((int)$lastNumber + 1, strlen($currentNumber), '0', STR_PAD_LEFT);
                    $newCustomEquipId = $newPrefix . $nextNumber;
                } else {
                    $newCustomEquipId = $newPrefix . '01';
                }
            }
        }
    }
    
    // Start transaction to handle foreign key constraint
    mysqli_autocommit($connection, false);
    
    try {
        // If custom_equip_id is changing, update related tables first
        if ($newCustomEquipId != $currentCustomEquipId) {
            // Update spareparts_inventory_tbl first
            $updateSparepartsQuery = "UPDATE spareparts_inventory_tbl SET custom_equip_id = '$newCustomEquipId' WHERE custom_equip_id = '$currentCustomEquipId'";
            if (!mysqli_query($connection, $updateSparepartsQuery)) {
                throw new Exception("Failed to update spareparts inventory: " . mysqli_error($connection));
            }
            
            // Check for other tables that might reference custom_equip_id
            // Add similar updates for other tables if they exist
            // Example:
            // $updateOtherTableQuery = "UPDATE other_table SET custom_equip_id = '$newCustomEquipId' WHERE custom_equip_id = '$currentCustomEquipId'";
            // if (!mysqli_query($connection, $updateOtherTableQuery)) {
            //     throw new Exception("Failed to update other table: " . mysqli_error($connection));
            // }
        }
        
        // Handle operator validation
        $updateFields = [];
        
        if ($operatorId !== null) {
            // Improved operator validation
            $operatorCheck = mysqli_prepare($connection,
                "SELECT e.employee_id 
                 FROM employee_tbl e
                 INNER JOIN position_tbl p ON e.position_id = p.position_id
                 WHERE e.employee_id = ? 
                 AND e.emp_status = 'Active'
                 AND p.position_name LIKE '%operator%'"
            );
            
            if ($operatorCheck) {
                mysqli_stmt_bind_param($operatorCheck, 'i', $operatorId);
                mysqli_stmt_execute($operatorCheck);
                mysqli_stmt_store_result($operatorCheck);
                
                if (mysqli_stmt_num_rows($operatorCheck) > 0) {
                    $updateFields[] = "operator_id = " . $operatorId;
                } else {
                    $updateFields[] = "operator_id = NULL";
                }
                mysqli_stmt_close($operatorCheck);
            }
        } else {
            $updateFields[] = "operator_id = NULL";
        }
        
        // Build the update query
        if (!empty($equip_type_id)) {
            $updateFields[] = "equip_type_id = '$equip_type_id'";
        }
        
        // Update custom_equip_id
        $updateFields[] = "custom_equip_id = '$newCustomEquipId'";
        
        if (!empty($model)) {
            $updateFields[] = "model = '$model'";
        }
        if (!empty($year)) {
            $updateFields[] = "equip_year = '$year'";
        }
        
        $updateFields[] = "engine_serial_num = '$serial'";
        $updateFields[] = "capacity = '$capacity'";
        $updateFields[] = "body_id = '$bodyId'";
        $updateFields[] = "equip_remarks = '$remarks'";
        $updateFields[] = "fuel_tank_capacity = '$fuelTankCapacity'";
        
        if (!empty($lastOperatingHours)) {
            $updateFields[] = "last_operating_hours = '$lastOperatingHours'";
        }
        
        if (!empty($maintenanceInterval)) {
            $updateFields[] = "maintenance_interval = '$maintenanceInterval'";
        }
        
        if (!empty($transmissionType)) {
            $updateFields[] = "transmission_type = '$transmissionType'";
        }
        
        if (!empty($engineType)) {
            $updateFields[] = "engine_type = '$engineType'";
        }
        
        if (!empty($fuelType)) {
            $updateFields[] = "fuel_type = '$fuelType'";
        }
        
        // Handle the date field properly
        if (!empty($lastPMS)) {
            $date = DateTime::createFromFormat('Y-m-d', $lastPMS);
            if ($date && $date->format('Y-m-d') === $lastPMS) {
                $updateFields[] = "last_pms_date = '$lastPMS'";
            } else {
                throw new Exception('Invalid date format for Last PMS Date');
            }
        } else {
            $updateFields[] = "last_pms_date = NULL";
        }
        
        if (empty($updateFields)) {
            throw new Exception('No fields to update');
        }
        
        // Update the main equipment table
        $updateQuery = "UPDATE equip_tbl SET " . implode(", ", $updateFields) . " WHERE equipment_id = $equipment_id";
        
        error_log("Update query: " . $updateQuery);
        
        if (!mysqli_query($connection, $updateQuery)) {
            throw new Exception('Failed to update equipment: ' . mysqli_error($connection));
        }
        
        // Commit the transaction
        mysqli_commit($connection);
        
        $message = 'Equipment updated successfully';
        if ($newCustomEquipId != $currentCustomEquipId) {
            $message .= " (Equipment ID changed from $currentCustomEquipId to $newCustomEquipId)";
        }
        sendResponse('success', $message, $isAjax);
        
    } catch (Exception $e) {
        // Rollback the transaction on error
        mysqli_rollback($connection);
        sendResponse('error', $e->getMessage(), $isAjax);
    }
    
    // Restore autocommit
    mysqli_autocommit($connection, true);
    exit;
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $equipment_id = intval($_GET['id']);
    
    if ($equipment_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid equipment ID']);
        exit;
    }
    
    try {
        $checkColumnQuery = "SHOW COLUMNS FROM equip_tbl LIKE 'fuel_tank_capacity'";
        $columnResult = mysqli_query($connection, $checkColumnQuery);
        
        if (mysqli_num_rows($columnResult) == 0) {
            $addColumnQuery = "ALTER TABLE equip_tbl ADD COLUMN fuel_tank_capacity VARCHAR(50) AFTER fuel_type";
            mysqli_query($connection, $addColumnQuery);
        }
        
        $query = "SELECT e.*, et.equip_type_name, et.prefix_code
                  FROM equip_tbl e
                  LEFT JOIN equip_type_tbl et ON e.equip_type_id = et.equip_type_id
                  WHERE e.equipment_id = $equipment_id";
        
        $result = mysqli_query($connection, $query);
        
        if (!$result) {
            throw new Exception(mysqli_error($connection));
        }
        
        if (mysqli_num_rows($result) > 0) {
            $equipment = mysqli_fetch_assoc($result);
            
            if (!isset($equipment['engine_serial_num'])) {
                $equipment['engine_serial_num'] = '';
            }
            
            if (!isset($equipment['fuel_tank_capacity'])) {
                $equipment['fuel_tank_capacity'] = '';
            }
            
                        if (isset($equipment['last_pms_date']) && $equipment['last_pms_date']) {
                try {
                    $date = new DateTime($equipment['last_pms_date']);
                    $equipment['last_pms_date'] = $date->format('Y-m-d');
                } catch (Exception $e) {
                    $equipment['last_pms_date'] = '';
                }
            }
            
            error_log("Equipment data being sent: " . json_encode($equipment));
            
            echo json_encode(['status' => 'success', 'data' => $equipment]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Equipment not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
    exit;
}

function sendResponse($status, $message, $isAjax = true) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => $status, 'message' => $message]);
    } else {
        header('Location: EquipmentProfiling.php?status=' . $status . '&message=' . urlencode($message));
    }
}
?>
