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
        $idsToDelete = $input['ids'];

        // Sanitize each ID to ensure it's an integer
        $sanitizedIds = [];
        foreach ($idsToDelete as $id) {
            $sanitizedIds[] = (int)$id; // Cast to integer for security
        }

        if (empty($sanitizedIds)) {
            $response['message'] = 'No valid IDs provided for deletion.';
            echo json_encode($response);
            exit();
        }

        // Convert array of IDs to a comma-separated string for the SQL IN clause
        $idsString = implode(',', $sanitizedIds);

        // Start a database transaction for atomicity
        // This ensures either both deletions succeed or both fail for a given PR
        mysqli_begin_transaction($connection);

        $deletedCount = 0;
        $failedCount = 0;
        $messages = [];

        try {
            // 1. Delete associated items from purchase_request_items_tbl first
            $deleteItemsQuery = "DELETE FROM purchase_request_items_tbl WHERE purchase_request_id IN ($idsString)";
            $sqlDeleteItems = mysqli_query($connection, $deleteItemsQuery);

            if (!$sqlDeleteItems) {
                throw new Exception("Failed to delete associated items: " . mysqli_error($connection));
            }
            // Note: mysqli_affected_rows for DELETE returns number of rows deleted.
            // We don't necessarily need to check it here, as a PR might have no items.
            // The important part is that the query ran without error.

            // 2. Delete the main purchase requests from purchase_requests_tbl
            // Only delete if their status is 'archived' as a safeguard
            $deletePrQuery = "DELETE FROM purchase_requests_tbl WHERE id IN ($idsString) AND status = 'archived'";
            $sqlDeletePr = mysqli_query($connection, $deletePrQuery);

            if (!$sqlDeletePr) {
                throw new Exception("Failed to delete purchase requests: " . mysqli_error($connection));
            }

            $affectedPrRows = mysqli_affected_rows($connection);

            if ($affectedPrRows > 0) {
                mysqli_commit($connection); // Commit the transaction if main PRs were deleted
                $deletedCount = $affectedPrRows;
                $response['success'] = true;
                $response['message'] = "$deletedCount purchase request(s) and their items permanently deleted.";
            } else {
                // If no PRs were deleted (e.g., not found or not archived), rollback
                mysqli_rollback($connection);
                $response['message'] = "No archived purchase requests were found with the provided IDs, or they were not in 'archived' status. No items were deleted.";
            }

        } catch (Exception $e) {
            mysqli_rollback($connection); // Rollback transaction on any error
            $response['message'] = 'Deletion failed: ' . $e->getMessage();
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