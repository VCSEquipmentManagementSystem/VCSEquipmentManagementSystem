<?php
require('../database.php');

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['part_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No part ID provided'
    ]);
    exit;
}

$part_id = intval($data['part_id']);

mysqli_begin_transaction($connection);

try {
    // Get the archived item details
    $getItemQuery = "SELECT * FROM archived_inventory_tbl WHERE part_id = ?";
    $stmt = mysqli_prepare($connection, $getItemQuery);
    mysqli_stmt_bind_param($stmt, "i", $part_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to get archived item details");
    }
    
    $result = mysqli_stmt_get_result($stmt);
    if ($item = mysqli_fetch_assoc($result)) {
        // Restore to spareparts_inventory_tbl
        $restoreQuery = "UPDATE spareparts_inventory_tbl 
                        SET stock_quantity = ? 
                        WHERE part_id = ?";
        
        $restoreStmt = mysqli_prepare($connection, $restoreQuery);
        mysqli_stmt_bind_param($restoreStmt, "ii", $item['stock_quantity'], $part_id);
        
        if (!mysqli_stmt_execute($restoreStmt)) {
            throw new Exception("Failed to restore item");
        }

        // Delete from archived_inventory_tbl
        $deleteQuery = "DELETE FROM archived_inventory_tbl WHERE part_id = ?";
        $deleteStmt = mysqli_prepare($connection, $deleteQuery);
        mysqli_stmt_bind_param($deleteStmt, "i", $part_id);
        
        if (!mysqli_stmt_execute($deleteStmt)) {
            throw new Exception("Failed to remove from archive");
        }

        mysqli_commit($connection);
        echo json_encode([
            'success' => true,
            'message' => 'Item restored successfully'
        ]);
    } else {
        throw new Exception("Archived item not found");
    }
    
} catch (Exception $e) {
    mysqli_rollback($connection);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    mysqli_close($connection);
}
?>
