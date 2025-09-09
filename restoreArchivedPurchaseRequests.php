<?php
require('./database.php'); // Include your database connection

header('Content-Type: application/json'); // Set header to indicate JSON response

$response = [
    'success' => false,
    'message' => 'An unknown error occurred.'
];

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decode the JSON input from the frontend
    $input = json_decode(file_get_contents('php://input'), true);

    // Check if 'ids' array is present and not empty
    if (isset($input['ids']) && is_array($input['ids']) && !empty($input['ids'])) {
        $idsToRestore = $input['ids'];

        // Sanitize each ID to ensure it's an integer
        $sanitizedIds = [];
        foreach ($idsToRestore as $id) {
            $sanitizedIds[] = (int)$id; // Cast to integer for security
        }

        if (empty($sanitizedIds)) {
            $response['message'] = 'No valid IDs provided for restoration.';
            echo json_encode($response);
            exit();
        }

        // Convert array of IDs to a comma-separated string for the SQL IN clause
        $idsString = implode(',', $sanitizedIds);

        // Start a database transaction for atomicity
        // This ensures either all updates succeed or all fail, preventing inconsistent data
        mysqli_begin_transaction($connection);

        $restoredCount = 0;
        $failedCount = 0;
        $messages = [];

        try {
            // Fetch the current status and previous status for the selected IDs
            // We need previous_status to restore, and current status should be 'archived'
            $selectQuery = "SELECT id, status, previous_status FROM purchase_requests_tbl WHERE id IN ($idsString)";
            $sqlSelect = mysqli_query($connection, $selectQuery);

            if (!$sqlSelect) {
                throw new Exception("Database query failed: " . mysqli_error($connection));
            }

            $itemsToUpdate = [];
            while ($row = mysqli_fetch_assoc($sqlSelect)) {
                $itemsToUpdate[] = $row;
            }

            if (empty($itemsToUpdate)) {
                throw new Exception("No archived purchase requests found with the provided IDs.");
            }

            foreach ($itemsToUpdate as $item) {
                $prId = $item['id'];
                $currentStatus = $item['status'];
                $previousStatus = $item['previous_status'];

                // Only restore if the current status is 'archived'
                if ($currentStatus === 'archived') {
                    // Determine the status to restore to
                    // If previous_status is NULL or empty, default to 'pending' or your default active status
                    $restoreToStatus = !empty($previousStatus) ? $previousStatus : 'pending'; // Adjust default if needed

                    // Prepare the SQL query to update the status and clear previous_status
                    $updateQuery = "UPDATE purchase_requests_tbl SET status = ?, previous_status = NULL WHERE id = ?";
                    $stmtUpdate = mysqli_prepare($connection, $updateQuery);

                    if ($stmtUpdate) {
                        mysqli_stmt_bind_param($stmtUpdate, 'si', $restoreToStatus, $prId); // 's' for string status, 'i' for ID

                        if (mysqli_stmt_execute($stmtUpdate)) {
                            if (mysqli_stmt_affected_rows($stmtUpdate) > 0) {
                                $restoredCount++;
                                $messages[] = "Purchase request ID $prId restored to '$restoreToStatus'.";
                            } else {
                                $failedCount++;
                                $messages[] = "Failed to restore purchase request ID $prId (no rows affected).";
                            }
                        } else {
                            $failedCount++;
                            $messages[] = "Database execution failed for ID $prId: " . mysqli_stmt_error($stmtUpdate);
                        }
                        mysqli_stmt_close($stmtUpdate);
                    } else {
                        $failedCount++;
                        $messages[] = "Statement preparation failed for ID $prId: " . mysqli_error($connection);
                    }
                } else {
                    $failedCount++;
                    $messages[] = "Purchase request ID $prId is not archived and cannot be restored.";
                }
            }

            // If any items were successfully restored, commit the transaction
            if ($restoredCount > 0) {
                mysqli_commit($connection);
                $response['success'] = true;
                $response['message'] = "$restoredCount purchase request(s) restored successfully. " . ($failedCount > 0 ? "$failedCount failed." : "");
            } else {
                // If no items were restored, rollback the transaction
                mysqli_rollback($connection);
                $response['message'] = "No purchase requests were restored. " . implode(" ", $messages);
            }

        } catch (Exception $e) {
            mysqli_rollback($connection); // Rollback transaction on any error
            $response['message'] = 'Restoration failed: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid or empty IDs array received.';
    }
} else {
    $response['message'] = 'Invalid request method. Only POST requests are allowed.';
}

echo json_encode($response);
exit();
?>