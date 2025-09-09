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
    // This script now expects an array of IDs for bulk approval
    $idsToApprove = isset($input['ids']) && is_array($input['ids']) ? $input['ids'] : [];

    if (empty($idsToApprove)) {
        $response['message'] = 'Missing IDs or action in the request.'; // Consistent error message
        echo json_encode($response);
        exit();
    }

    // Sanitize IDs and convert to comma-separated string for the SQL IN clause
    $sanitizedIds = array_map('intval', $idsToApprove); // Ensure all IDs are integers
    $idsString = implode(',', $sanitizedIds);

    // Start a database transaction for atomicity
    mysqli_begin_transaction($connection);

    try {
        // Update the status of selected purchase requests to 'approved'
        // Add a check to ensure they are not already 'archived' or 'approved' if desired
        $query = "UPDATE purchase_requests_tbl SET status = 'approved' WHERE id IN ($idsString) AND status != 'archived'";
        $sql = mysqli_query($connection, $query);

        if (!$sql) {
            throw new Exception("Database query failed: " . mysqli_error($connection));
        }

        $affectedRows = mysqli_affected_rows($connection);

        if ($affectedRows > 0) {
            mysqli_commit($connection); // Commit the transaction
            $response['success'] = true;
            $response['message'] = "$affectedRows purchase request(s) approved successfully.";
        } else {
            mysqli_rollback($connection); // Rollback if no rows were affected
            $response['message'] = "No purchase requests were approved. They might not exist, are already approved, or are archived.";
        }
    } catch (Exception $e) {
        mysqli_rollback($connection); // Rollback transaction on any error
        $response['message'] = 'Error approving purchase requests: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method. Only POST requests are allowed.';
}

echo json_encode($response);
exit();
