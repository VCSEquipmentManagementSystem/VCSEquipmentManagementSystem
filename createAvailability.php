<?php
require('./database.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipment_id = $_POST['equipment_id'] ?? '';
    $start_date = $_POST['startDate'] ?? '';
    $end_date = $_POST['endDate'] ?? '';
    $project_id = $_POST['project_id'] ?? null; 
    
    // Validate required fields
    if (!$equipment_id || !$start_date || !$end_date || !$project_id) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields including project selection']);
        exit;
    }
    
    // Check if equipment is already deployed in this date range
    $check_query = "SELECT * FROM equip_tbl 
                    WHERE custom_equip_id = ? 
                    AND deployment_status = 'deployed'
                    AND NOT (deployment_end_date < ? OR deployment_start_date > ?)";
    
    $stmt = mysqli_prepare($connection, $check_query);
    mysqli_stmt_bind_param($stmt, "sss", $equipment_id, $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Equipment already deployed during this period']);
        exit;
    }
    
    // Update equipment deployment status AND assign to project
    $update_query = "UPDATE equip_tbl 
                     SET deployment_status = 'deployed',
                         deployment_start_date = ?,
                         deployment_end_date = ?,
                         assigned_proj_id = ?
                     WHERE custom_equip_id = ?";
    
    $stmt = mysqli_prepare($connection, $update_query);
    mysqli_stmt_bind_param($stmt, "ssss", $start_date, $end_date, $project_id, $equipment_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($connection)]);
    }
}
?>
