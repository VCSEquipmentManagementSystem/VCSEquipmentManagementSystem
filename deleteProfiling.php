<?php
require('./database.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!isset($data['equipment_ids']) || !is_array($data['equipment_ids'])) {
        echo json_encode(['status' => 'error', 'message' => 'No equipment IDs provided']);
        exit;
    }
    
    $equipmentIds = $data['equipment_ids'];
    $success = true;
    $messages = [];
    $deletedCount = 0;
    
    mysqli_begin_transaction($connection);
    
    try {
        foreach ($equipmentIds as $equipmentId) {
            $equipmentId = mysqli_real_escape_string($connection, $equipmentId);
            
            // Delete the record from the archived table
            $deleteSql = "DELETE FROM archivedEquipment_tbl WHERE equipment_id = '$equipmentId'";
            $deleteResult = mysqli_query($connection, $deleteSql);
            
            if ($deleteResult) {
                $deletedCount++;
                $messages[] = "Equipment ID $equipmentId deleted successfully";
            } else {
                $messages[] = "Failed to delete equipment ID $equipmentId: " . mysqli_error($connection);
                $success = false;
            }
        }
        
        // If everything went well, commit the transaction
        mysqli_commit($connection);
        
        echo json_encode([
            'status' => $success ? 'success' : 'partial',
            'message' => $deletedCount . " equipment item(s) deleted successfully",
            'messages' => $messages
        ]);
        
    } catch (Exception $e) {
        // If there was an error, roll back the transaction
        mysqli_rollback($connection);
        
        echo json_encode([
            'status' => 'error',
            'message' => 'Error deleting equipment: ' . $e->getMessage(),
            'messages' => $messages
        ]);
    }
    
} else {
    // Not a POST request
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

mysqli_close($connection);
?>
