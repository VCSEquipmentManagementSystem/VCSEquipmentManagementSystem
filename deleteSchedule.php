<?php
require('./database.php');

header('Content-Type: application/json');

try {
    // Get and decode JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!isset($data['archive_ids']) || empty($data['archive_ids'])) {
        throw new Exception('No items selected for deletion');
    }

    // Start transaction
    $connection->begin_transaction();

    // Prepare the delete statement
    $archiveIds = implode(',', array_map('intval', $data['archive_ids']));
    $deleteQuery = "DELETE FROM archived_schedule_tbl WHERE archive_id IN ($archiveIds)";
    
    if ($connection->query($deleteQuery)) {
        $deletedCount = $connection->affected_rows;
        $connection->commit();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Items deleted successfully',
            'count' => $deletedCount
        ]);
    } else {
        throw new Exception('Failed to delete items: ' . $connection->error);
    }

} catch (Exception $e) {
    $connection->rollback();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$connection->close();