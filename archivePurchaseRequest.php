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

    // Check if 'id' is present and is a valid integer
    $idToArchive = isset($input['id']) ? (int)$input['id'] : 0;

    if ($idToArchive > 0) {
        // Start a database transaction for atomicity
        mysqli_begin_transaction($connection);

        try {
            // 1. Fetch the current status AND pr_number of the purchase request
            $selectQuery = "SELECT status, pr_number FROM purchase_requests_tbl WHERE id = ?";
            $stmtSelect = mysqli_prepare($connection, $selectQuery);
            if (!$stmtSelect) {
                throw new Exception("Failed to prepare select statement: " . mysqli_error($connection));
            }
            mysqli_stmt_bind_param($stmtSelect, 'i', $idToArchive);
            mysqli_stmt_execute($stmtSelect);
            $result = mysqli_stmt_get_result($stmtSelect);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmtSelect);

            if (!$row) {
                // If no row is found, the ID doesn't exist
                throw new Exception("Purchase request with ID $idToArchive not found.");
            }

            $currentStatus = $row['status'];
            $prNumber = $row['pr_number']; // Get the pr_number

            // Prevent archiving if it's already archived (optional, but good practice)
            if ($currentStatus === 'archived') {
                // Use prNumber in this message as well for consistency
                throw new Exception("Purchase request $prNumber is already archived.");
            }

            // 2. Update the status to 'archived' and store the current status in 'previous_status'
            $updateQuery = "UPDATE purchase_requests_tbl SET status = 'archived', previous_status = ? WHERE id = ?";
            $stmtUpdate = mysqli_prepare($connection, $updateQuery);
            if (!$stmtUpdate) {
                throw new Exception("Failed to prepare update statement: " . mysqli_error($connection));
            }
            mysqli_stmt_bind_param($stmtUpdate, 'si', $currentStatus, $idToArchive); // 's' for string, 'i' for integer

            if (mysqli_stmt_execute($stmtUpdate)) {
                $affectedRows = mysqli_stmt_affected_rows($stmtUpdate);
                if ($affectedRows > 0) {
                    mysqli_commit($connection); // Commit the transaction
                    $response['success'] = true;
                    // Use prNumber in the success message
                    $response['message'] = "Purchase request $prNumber archived successfully. Previous status: '$currentStatus'.";
                } else {
                    // No rows affected, might mean ID exists but status was already 'archived'
                    // (though we added a check for this above) or some other issue.
                    throw new Exception("No changes made. Purchase request $prNumber might not exist or its status is already 'archived'.");
                }
            } else {
                throw new Exception("Database execution failed: " . mysqli_stmt_error($stmtUpdate));
            }
            mysqli_stmt_close($stmtUpdate);

        } catch (Exception $e) {
            mysqli_rollback($connection); // Rollback transaction on any error
            $response['message'] = 'Archiving failed: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid Purchase Request ID received.';
    }
} else {
    $response['message'] = 'Invalid request method. Only POST requests are allowed.';
}

echo json_encode($response);
exit();
?>