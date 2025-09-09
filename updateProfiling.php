<?php
require('./database.php');

// Process form submission for updating equipment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    // Get equipment_id and validate
    $equipment_id = isset($_POST["equipment_id"]) ? $_POST["equipment_id"] : null;
    
    if (!$equipment_id) {
        header('Location: EquipmentProfiling.php?error=Equipment ID is required');
        exit;
    }
    
    $equipment_type = $_POST['equipmentType'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $serial = $_POST['serial'];
    $capacity = $_POST['capacity'];
    $body_id = $_POST['bodyId'];
    $operator_name = isset($_POST["operator"]) ? $_POST["operator"] : null;
    $remarks = isset($_POST["remarks"]) ? $_POST["remarks"] : '';
    
    $fuel_tank_capacity = isset($_POST['fuelTankCapacity']) ? $_POST['fuelTankCapacity'] : '';
    $last_operating_hours = isset($_POST['lastOperatingHours']) ? $_POST['lastOperatingHours'] : '';
    $maintenance_interval = isset($_POST['maintenanceInterval']) ? $_POST['maintenanceInterval'] : '';
    $transmission_type = isset($_POST['transmissionType']) ? $_POST['transmissionType'] : '';
    $engine_type = isset($_POST['engineType']) ? $_POST['engineType'] : '';
    $fuel_type = isset($_POST['fuelType']) ? $_POST['fuelType'] : '';
    $last_pms = isset($_POST['lastPMS']) ? $_POST['lastPMS'] : '';
    
    // Get equipment type ID from the type name using prepared statement
    $type_query = "SELECT equip_type_id FROM equip_type_tbl WHERE equip_type_name = ?";
    $stmt = mysqli_prepare($connection, $type_query);
    mysqli_stmt_bind_param($stmt, "s", $equipment_type);
    mysqli_stmt_execute($stmt);
    $type_result = mysqli_stmt_get_result($stmt);
    
    if ($type_result && mysqli_num_rows($type_result) > 0) {
        $type_row = mysqli_fetch_assoc($type_result);
        $equip_type_id = $type_row['equip_type_id'];
        
        $update_query = "UPDATE equip_tbl SET 
            equip_type_id = ?,
            model = ?,
            equip_year = ?,
            engine_serial_num = ?,
            capacity = ?,
            body_id = ?,
            operator_id = ?,
            equip_remarks = ?,
            fuel_tank_capacity = ?,
            last_operating_hours = ?,
            maintenance_interval = ?,
            transmission_type = ?,
            engine_type = ?,
            fuel_type = ?,
            last_pms_date = ?,
            updated_at = NOW()
        WHERE equipment_id = ?";
        
        $update_stmt = mysqli_prepare($connection, $update_query);
        mysqli_stmt_bind_param($update_stmt, "issssssssssssssi", 
            $equip_type_id,
            $model,
            $year,
            $serial,
            $capacity,
            $body_id,
            $operator_name,
            $remarks,
            $fuel_tank_capacity,
            $last_operating_hours,
            $maintenance_interval,
            $transmission_type,
            $engine_type,
            $fuel_type,
            $last_pms,
            $equipment_id
        );
        
        $update_result = mysqli_stmt_execute($update_stmt);
        
        if ($update_result) {
            header('Location: EquipmentProfiling.php?success=Equipment updated successfully');
            exit;
        } else {
            header('Location: EquipmentProfiling.php?error=' . urlencode('Error updating equipment: ' . mysqli_error($connection)));
            exit;
        }
    } else {
        // If equipment type doesn't exist, create a new one
        $insert_type_query = "INSERT INTO equip_type_tbl (equip_type_name) VALUES (?)";
        $insert_stmt = mysqli_prepare($connection, $insert_type_query);
        mysqli_stmt_bind_param($insert_stmt, "s", $equipment_type);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            $equip_type_id = mysqli_insert_id($connection);
            
            $update_query = "UPDATE equip_tbl SET 
                equip_type_id = ?,
                model = ?,
                equip_year = ?,
                engine_serial_num = ?,
                capacity = ?,
                body_id = ?,
                operator_id = ?,
                equip_remarks = ?,
                fuel_tank_capacity = ?,
                last_operating_hours = ?,
                maintenance_interval = ?,
                transmission_type = ?,
                engine_type = ?,
                fuel_type = ?,
                last_pms_date = ?,
                updated_at = NOW()
            WHERE equipment_id = ?";
            
            $update_stmt = mysqli_prepare($connection, $update_query);
            mysqli_stmt_bind_param($update_stmt, "issssssssssssssi", 
                $equip_type_id,
                $model,
                $year,
                $serial,
                $capacity,
                $body_id,
                $operator_name,
                $remarks,
                $fuel_tank_capacity,
                $last_operating_hours,
                $maintenance_interval,
                $transmission_type,
                $engine_type,
                $fuel_type,
                $last_pms,
                $equipment_id
            );
            
            $update_result = mysqli_stmt_execute($update_stmt);
            
            if ($update_result) {
                header('Location: EquipmentProfiling.php?success=Equipment updated successfully with new equipment type');
                exit;
            } else {
                header('Location: EquipmentProfiling.php?error=' . urlencode('Error updating equipment: ' . mysqli_error($connection)));
                exit;
            }
        } else {
            header('Location: EquipmentProfiling.php?error=' . urlencode('Error creating equipment type: ' . mysqli_error($connection)));
            exit;
        }
    }
}

header('Location: EquipmentProfiling.php');
exit;
?>
