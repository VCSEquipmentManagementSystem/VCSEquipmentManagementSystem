<?php
require('./database.php');

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($_GET['action']) && $_GET['action'] === 'getCount') {
    $countQuery = "SELECT COUNT(*) as count 
                   FROM (
                       SELECT equipment_id FROM equip_tbl 
                       WHERE equip_status = 'Archived'
                       UNION ALL
                       SELECT equipment_id FROM archivedequipment_tbl
                   ) as combined_archives";
    
    $result = mysqli_query($connection, $countQuery);
    $count = 0;
    
    if ($row = mysqli_fetch_assoc($result)) {
        $count = $row['count'];
    }
    
    echo json_encode(['count' => $count]);
    exit;
}

if (!isset($data['equipment_id']) || !isset($data['status'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields'
    ]);
    exit;
}

$equipment_id = $data['equipment_id'];
$status = $data['status'];
// Set deployment status based on status
$deployment_status = ($status === 'Deployed') ? 'Deployed' : 'Undeployed';

try {
    $query = "UPDATE equip_tbl 
              SET equip_status = ?, 
                  deployment_status = ?
              WHERE custom_equip_id = ?";
    
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "sss", $status, $deployment_status, $equipment_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Equipment status updated successfully'
        ]);
    } else {
        throw new Exception(mysqli_error($connection));
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

mysqli_close($connection);
?>
