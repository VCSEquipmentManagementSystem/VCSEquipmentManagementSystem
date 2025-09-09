<?php
require_once('database.php');

header('Content-Type: application/json');

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'No equipment ID provided']);
    exit;
}

// Get equipment ID and validate
$equipment_id = trim($_GET['id']);

// Use prepared statement to prevent SQL injection
$query = "SELECT 
    e.*,
    et.equip_type_name,
    p.project_location as location,
    p.project_name,
    p.start_date as deployment_start_date,
    p.end_date as deployment_end_date
FROM equip_tbl e
LEFT JOIN equip_type_tbl et ON e.equip_type_id = et.equip_type_id
LEFT JOIN proj_sched_tbl p ON e.assigned_proj_id = p.project_id
WHERE e.custom_equip_id = ?";

$stmt = mysqli_prepare($connection, $query);

if (!$stmt) {
    echo json_encode(['error' => 'Database prepare failed: ' . mysqli_error($connection)]);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $equipment_id);

if (!mysqli_stmt_execute($stmt)) {
    echo json_encode(['error' => 'Database query failed: ' . mysqli_error($connection)]);
    mysqli_stmt_close($stmt);
    exit;
}

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $equipment = mysqli_fetch_assoc($result);

    // Apply the new logic to determine deployment_status from equip_status
    $equipStatus = strtolower($equipment['equip_status'] ?? '');
    
    if ($equipStatus === 'active') {
        $equipment['deployment_status'] = 'Deployed';
    } elseif ($equipStatus === 'idle') {
        $equipment['deployment_status'] = 'Available';
    } else {
        $equipment['deployment_status'] = 'Undeployed';
    }
    
    // The Condition is simply the equip_status from the database
    $equipment['Condition'] = $equipment['equip_status'] ?? 'Unknown';
    
    // Format the location to include both location and project name if available
    if (!empty($equipment['location']) && !empty($equipment['project_name'])) {
        $equipment['formatted_location'] = $equipment['location'] . ' (' . $equipment['project_name'] . ')';
    } else if (!empty($equipment['location'])) {
        $equipment['formatted_location'] = $equipment['location'];
    } else if (!empty($equipment['project_name'])) {
        $equipment['formatted_location'] = $equipment['project_name'];
    } else {
        $equipment['formatted_location'] = 'Not Assigned';
    }
    
    // Format dates if they exist
    if (!empty($equipment['deployment_start_date'])) {
        $equipment['deployment_start_date'] = date('M d, Y', strtotime($equipment['deployment_start_date']));
    }
    if (!empty($equipment['deployment_end_date'])) {
        $equipment['deployment_end_date'] = date('M d, Y', strtotime($equipment['deployment_end_date']));
    }
    
    echo json_encode($equipment);
} else {
    echo json_encode(['error' => 'Equipment not found']);
}

mysqli_stmt_close($stmt);
mysqli_close($connection);
?>
