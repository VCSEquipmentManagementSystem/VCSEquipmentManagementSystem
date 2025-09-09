<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);
require './database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['selected_archive_ids'])) {
        throw new Exception('Invalid request or missing selected IDs.');
    }

    $selectedArchiveIds = $_POST['selected_archive_ids'];

    if (!is_array($selectedArchiveIds) || empty($selectedArchiveIds)) {
        throw new Exception('No items selected for permanent deletion.');
    }

    mysqli_begin_transaction($connection);

    foreach ($selectedArchiveIds as $archive_id) {
        $archive_id = intval($archive_id);
        if ($archive_id <= 0) {
            continue; // Skip invalid IDs
        }

        // Delete from archived_inventory_tbl
        $delete_archive_sql = "DELETE FROM archived_inventory_tbl WHERE archive_id = ?";
        $delete_archive_stmt = mysqli_prepare($connection, $delete_archive_sql);
        if (!$delete_archive_stmt) {
            throw new Exception('Failed to prepare delete statement: ' . mysqli_error($connection));
        }
        mysqli_stmt_bind_param($delete_archive_stmt, 'i', $archive_id);
        if (!mysqli_stmt_execute($delete_archive_stmt)) {
            throw new Exception('Failed to delete from archive: ' . mysqli_stmt_error($delete_archive_stmt));
        }
        mysqli_stmt_close($delete_archive_stmt);
    }

    mysqli_commit($connection);
    echo json_encode(['success' => true, 'message' => 'Selected items permanently deleted.']);
} catch (Exception $e) {
    if (isset($connection) && mysqli_ping($connection)) {
        mysqli_rollback($connection);
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($connection)) {
        mysqli_close($connection);
    }
}
