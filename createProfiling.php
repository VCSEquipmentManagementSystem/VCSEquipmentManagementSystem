<?php
include './database.php';

// Handle equipment type creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['equipmentTypeName'])) {
    $response = array();

    try {
        $equipmentTypeName = trim($_POST['equipmentTypeName']);

        if (empty($equipmentTypeName)) {
            throw new Exception('Equipment name cannot be empty');
        }

        // Generate prefix code
        $words = explode(' ', strtoupper($equipmentTypeName));
        $prefix = '';
        foreach ($words as $word) {
            $prefix .= substr($word, 0, 1);
        }
        $prefix = substr($prefix, 0, 3);

        // Start transaction
        mysqli_begin_transaction($connection);

        try {
            // Check for duplicate equipment type name
            $checkName = "SELECT COUNT(*) as count FROM equip_type_tbl WHERE equip_type_name = ?";
            $stmt = mysqli_prepare($connection, $checkName);
            mysqli_stmt_bind_param($stmt, 's', $equipmentTypeName);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);

            if ($row['count'] > 0) {
                throw new Exception('Equipment type already exists');
            }

            // Ensure unique prefix
            $checkPrefix = "SELECT COUNT(*) as count FROM equip_type_tbl WHERE prefix_code = ?";
            $stmt = mysqli_prepare($connection, $checkPrefix);
            mysqli_stmt_bind_param($stmt, 's', $prefix);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);

            if ($row['count'] > 0) {
                $i = 1;
                do {
                    $newPrefix = $prefix . $i;
                    mysqli_stmt_bind_param($stmt, 's', $newPrefix);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $row = mysqli_fetch_assoc($result);
                    if ($row['count'] == 0) {
                        $prefix = $newPrefix;
                        break;
                    }
                    $i++;
                } while (true);
            }

            // Insert new equipment type
            $insertQuery = "INSERT INTO equip_type_tbl (equip_type_name, prefix_code) VALUES (?, ?)";
            $stmt = mysqli_prepare($connection, $insertQuery);

            if (!$stmt) {
                throw new Exception('Database error: ' . mysqli_error($connection));
            }

            mysqli_stmt_bind_param($stmt, 'ss', $equipmentTypeName, $prefix);

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to add equipment type: ' . mysqli_error($connection));
            }

            $newId = mysqli_insert_id($connection);

            // Commit transaction
            mysqli_commit($connection);

            $response = array(
                'status' => 'success',
                'message' => 'Equipment type added successfully',
                'equipment_type_id' => $newId,
                'prefix_code' => $prefix
            );
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($connection);
            throw $e;
        }
    } catch (Exception $e) {
        $response = array(
            'status' => 'error',
            'message' => $e->getMessage()
        );
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if (isset($_POST['create'])) {
    // 1) Collect form data with proper type handling
    $CategoryId = $_POST['equipmentType'] ?? '';
    $Model = $_POST['model'] ?? '';
    $EngineType = $_POST['engineType'] ?? '';
    $FuelType = $_POST['fuelType'] ?? '';
    $TransmissionType = $_POST['transmissionType'] ?? '';
    $Capacity = $_POST['capacity'] ?? '';
    $capacityValue = $_POST['capacityValue'] ?? '';
    $capacityUnit = $_POST['capacityUnit'] ?? '';
    $MaintenanceInterval = $_POST['maintenanceInterval'] ?? '';
    $Bodyid = !empty($_POST['bodyId']) ? $_POST['bodyId'] : null;
    $LastPmsDate = !empty($_POST['lastPMS']) ? date('Y-m-d', strtotime($_POST['lastPMS'])) : null;
    $Remarks = $_POST['remarks'] ?? '';
    $operator_id = !empty($_POST['operator']) ? $_POST['operator'] : null;
    $EngineSerialNum = $_POST['serial'] ?? '';
    $EquipStatus = 'Idle';
    $DeploymentStatus = 'Undeployed';

    $FuelTankCapacity = $_POST['fuelTankCapacity'] ?? '';
    if ($FuelTankCapacity !== '') {
        // Remove any existing "Liters" (case-insensitive), trim, then append " Liters"
        $FuelTankCapacity = trim(preg_replace('/\s*liters?$/i', '', $FuelTankCapacity)) . ' Liters';
    }

    // Handle operating hours
    $LastOperatingHours = null;
    if (!empty($_POST['lastOperatingHours'])) {
        $hours = trim($_POST['lastOperatingHours']);
        if (is_numeric($hours) && floor($hours) == $hours) {
            $LastOperatingHours = number_format($hours, 0, '.', '');
        } else {
            $LastOperatingHours = number_format((float)$hours, 2, '.', '');
        }
    }

    // Validate year
    $Year = $_POST['year'] ?? '';
    if (
        !preg_match('/^\d{4}$/', $Year) ||
        $Year < 1900 ||
        $Year > date('Y')
    ) {
        echo "<script>
            alert('Please enter a valid year between 1900 and " . date('Y') . "');
            history.back();
        </script>";
        exit;
    }

    // 2) Lookup equip_type_id and prefix_code using ID
    $getTypeQ = "SELECT equip_type_id, prefix_code FROM equip_type_tbl WHERE equip_type_id = ?";

    $stmt = mysqli_prepare($connection, $getTypeQ);
    if (!$stmt) {
        echo "<script>alert('Database error: " . addslashes(mysqli_error($connection)) . "'); history.back();</script>";
        exit;
    }

    mysqli_stmt_bind_param($stmt, 'i', $CategoryId);
    $execResult = mysqli_stmt_execute($stmt);

    if (!$execResult) {
        echo "<script>alert('Database error: " . addslashes(mysqli_stmt_error($stmt)) . "'); history.back();</script>";
        exit;
    }

    mysqli_stmt_store_result($stmt);
    $rowCount = mysqli_stmt_num_rows($stmt);

    if ($rowCount == 0) {
        mysqli_stmt_close($stmt);
        echo "<script>alert('Invalid equipment type.'); history.back();</script>";
        exit;
    }

    if (empty($capacityValue) || !is_numeric($capacityValue) || $capacityValue <= 0) {
        echo "<script>
            alert('Please enter a valid capacity value');
            history.back();
        </script>";
        exit;
    }

    if (empty($capacityUnit)) {
        echo "<script>
            alert('Please select a capacity unit');
            history.back();
        </script>";
        exit;
    }

    $formattedCapacity = number_format((float)$capacityValue, 2, '.', '') . ' ' . $capacityUnit;

    mysqli_stmt_bind_result($stmt, $CategoryId, $PrefixCode);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // 3) Generate custom_equip_id
    // Get the highest sequence number for this prefix
    $seqQuery = "SELECT MAX(CAST(SUBSTRING_INDEX(custom_equip_id, '-', -1) AS UNSIGNED)) as max_seq
                 FROM equip_tbl
                 WHERE custom_equip_id LIKE ?";

    $stmt = mysqli_prepare($connection, $seqQuery);
    if (!$stmt) {
        echo "<script>alert('Database error: " . addslashes(mysqli_error($connection)) . "'); history.back();</script>";
        exit;
    }

    $prefixPattern = $PrefixCode . '-%';
    mysqli_stmt_bind_param($stmt, 's', $prefixPattern);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $maxSeq);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // If no existing equipment with this prefix, start at 1
    $nextSeq = ($maxSeq ?? 0) + 1;

    // Format the custom_equip_id: PREFIX-001
    $customEquipId = $PrefixCode . '-' . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);

    // 4) Plate number handling
    $Plateno = !empty($_POST['plateNo']) ? $_POST['plateNo'] : 'N/A';

    // 5) Get operator ID with improved validation
    $Operatorid = null;
    if (!empty($_POST['operator'])) {
        $Operatorid = (int)$_POST['operator'];
        $getOpQ = "SELECT employee_id
                   FROM employee_tbl
                   WHERE employee_id = ?
                   AND emp_status = 'Active'";
        $stmt = mysqli_prepare($connection, $getOpQ);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $Operatorid);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) === 0) {
                $Operatorid = null;
            }
            mysqli_stmt_close($stmt);
        }
    }

    // 6) Check if fuel_tank_capacity column exists
    $checkColumnQuery = "SHOW COLUMNS FROM equip_tbl LIKE 'fuel_tank_capacity'";
    $columnResult = mysqli_query($connection, $checkColumnQuery);

    // If the column doesn't exist, add it
    if (mysqli_num_rows($columnResult) == 0) {
        $addColumnQuery = "ALTER TABLE equip_tbl ADD COLUMN fuel_tank_capacity VARCHAR(50) AFTER fuel_type";
        mysqli_query($connection, $addColumnQuery);
    }

    // 7) Validate operator exists and has operator role (enhanced validation)
    if ($operator_id !== null) {
        $operatorCheck = mysqli_prepare(
            $connection,
            "SELECT employee_id
             FROM employee_tbl
             WHERE employee_id = ?
             AND emp_status = 'Active'
             AND EXISTS (
                 SELECT 1 FROM position_tbl p 
                 WHERE p.position_id = employee_tbl.position_id
                 AND p.position_name LIKE '%operator%'
             )"
        );

        if ($operatorCheck) {
            mysqli_stmt_bind_param($operatorCheck, 'i', $operator_id);
            mysqli_stmt_execute($operatorCheck);
            mysqli_stmt_store_result($operatorCheck);
            if (mysqli_stmt_num_rows($operatorCheck) === 0) {
                $operator_id = null;
            }
            mysqli_stmt_close($operatorCheck);
        }
    }

    // 8) Prepare data for insertion with proper escaping
    $safeCustomEquipId = mysqli_real_escape_string($connection, $customEquipId);
    $safeEquipStatus = mysqli_real_escape_string($connection, $EquipStatus);
    $safeDeploymentStatus = mysqli_real_escape_string($connection, $DeploymentStatus);
    $safePlateno = mysqli_real_escape_string($connection, $Plateno);
    $safeModel = mysqli_real_escape_string($connection, $Model);
    $safeImages = mysqli_real_escape_string($connection, $Images);
    $safeEngineType = mysqli_real_escape_string($connection, $EngineType);
    $safeEngineSerialNum = mysqli_real_escape_string($connection, $EngineSerialNum);
    $safeFuelType = mysqli_real_escape_string($connection, $FuelType);
    $safeTransmissionType = mysqli_real_escape_string($connection, $TransmissionType);
    $safeCapacity = mysqli_real_escape_string($connection, $formattedCapacity);
    $safeMaintenanceInterval = mysqli_real_escape_string($connection, $MaintenanceInterval);
    $safeRemarks = mysqli_real_escape_string($connection, $Remarks);
    $safeFuelTankCapacity = mysqli_real_escape_string($connection, $FuelTankCapacity);

    // Format the last PMS date properly
    $lastPmsDateSql = "NULL";
    if (!empty($LastPmsDate)) {
        $lastPmsDateSql = "'" . mysqli_real_escape_string($connection, $LastPmsDate) . "'";
    }

    // Format nullable integer fields
    $brandIdSql = "NULL";
    $operatorIdSql = "NULL";
    if ($Operatorid !== null) {
        $operatorIdSql = intval($Operatorid);
    }
    $assignedProjIdSql = "NULL";

    // 9) Insert equipment into equip_tbl
    $insertSql = "INSERT INTO equip_tbl (
        custom_equip_id, equip_type_id, equip_status, deployment_status, plate_num,
        model, equip_year, brand_id, operator_id, images, engine_type,
        engine_serial_num, fuel_type, transmission_type, last_operating_hours,
        capacity, maintenance_interval, body_id, last_pms_date, equip_remarks, 
        assigned_proj_id, fuel_tank_capacity
    ) VALUES (
        '$safeCustomEquipId', 
        '$CategoryId', 
        '$safeEquipStatus', 
        '$safeDeploymentStatus', 
        '$safePlateno',
        '$safeModel', 
        " . ($Year !== null ? "'$Year'" : "NULL") . ", 
        $brandIdSql, 
        $operatorIdSql, 
        '$safeImages', 
        '$safeEngineType',
        '$safeEngineSerialNum', 
        '$safeFuelType', 
        '$safeTransmissionType', 
        " . ($LastOperatingHours !== null ? "'$LastOperatingHours'" : "NULL") . ",
        '$safeCapacity',
        '$safeMaintenanceInterval', 
        " . ($Bodyid !== null ? "'$Bodyid'" : "NULL") . ", 
        " . ($LastPmsDate !== null ? "'$LastPmsDate'" : "NULL") . ", 
        '$safeRemarks',
        $assignedProjIdSql, 
        '$safeFuelTankCapacity'
    )";

    $result = mysqli_query($connection, $insertSql);

    if ($result) {
        $equipment_id = mysqli_insert_id($connection);

        // 10) Optional: Auto-assign to project if specified
        if (!empty($_POST['assign_to_project'])) {
            $project_id = (int)$_POST['assign_to_project'];

            // Verify project exists
            $projectCheck = mysqli_prepare($connection, "SELECT project_id FROM proj_sched_tbl WHERE project_id = ?");
            if ($projectCheck) {
                mysqli_stmt_bind_param($projectCheck, 'i', $project_id);
                mysqli_stmt_execute($projectCheck);
                mysqli_stmt_store_result($projectCheck);

                if (mysqli_stmt_num_rows($projectCheck) > 0) {
                    // Insert into proj_eqp_assign_tbl
                    $assignEquipStmt = mysqli_prepare(
                        $connection,
                        "INSERT INTO proj_eqp_assign_tbl (project_id, equipment_id) VALUES (?, ?)"
                    );
                    if ($assignEquipStmt) {
                        mysqli_stmt_bind_param($assignEquipStmt, 'ii', $project_id, $equipment_id);
                        if (mysqli_stmt_execute($assignEquipStmt)) {
                            // Update equipment status to deployed
                            $updateStatusStmt = mysqli_prepare(
                                $connection,
                                "UPDATE equip_tbl SET deployment_status = 'Deployed', assigned_proj_id = ?, equip_status = 'Operational' WHERE equipment_id = ?"
                            );
                            if ($updateStatusStmt) {
                                mysqli_stmt_bind_param($updateStatusStmt, 'ii', $project_id, $equipment_id);
                                mysqli_stmt_execute($updateStatusStmt);
                                mysqli_stmt_close($updateStatusStmt);
                            }
                        }
                        mysqli_stmt_close($assignEquipStmt);
                    }
                }
                mysqli_stmt_close($projectCheck);
            }
        }

        echo "<script>alert('Equipment successfully added with ID: $customEquipId'); window.location.href='./EquipmentProfiling.php';</script>";
    } else {
        $errorCode = mysqli_errno($connection);
        $errorMessage = mysqli_error($connection);

        if ($errorCode == 1062) {
            echo "<script>alert('This equipment ID already exists. Please try again.'); history.back();</script>";
        } else {
            echo "<script>alert('Database error (" . $errorCode . "): " . addslashes($errorMessage) . "\\n\\nSQL: " . addslashes($insertSql) . "'); history.back();</script>";
        }
    }
}

mysqli_close($connection);
