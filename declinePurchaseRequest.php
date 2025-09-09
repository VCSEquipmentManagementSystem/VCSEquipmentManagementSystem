<?php
require('./database.php'); // Include your database connection

header('Content-Type: application/json'); // Set header to indicate JSON response

$response = [
    'success' => false,
    'message' => 'An unknown error occurred.'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $idsToDecline = isset($input['ids']) && is_array($input['ids']) ? $input['ids'] : [];

    if (empty($idsToDecline)) {
        $response['message'] = 'No purchase requests selected for decline.';
        echo json_encode($response);
        exit();
    }

    // Sanitize IDs and convert to comma-separated string for IN clause
    $sanitizedIds = array_map('intval', $idsToDecline);
    $idsString = implode(',', $sanitizedIds);

    // Start a transaction for atomicity
    mysqli_begin_transaction($connection);

    try {
        // Update the status of selected purchase requests to 'declined'
        $query = "UPDATE purchase_requests_tbl SET status = 'declined' WHERE id IN ($idsString)";
        $sql = mysqli_query($connection, $query);

        if (!$sql) {
            throw new Exception("Database query failed: " . mysqli_error($connection));
        }

        $affectedRows = mysqli_affected_rows($connection);

        if ($affectedRows > 0) {
            mysqli_commit($connection); // Commit transaction
            $response['success'] = true;
            $response['message'] = "$affectedRows purchase request(s) declined successfully.";
        } else {
            mysqli_rollback($connection); // Rollback if no rows were affected
            $response['message'] = "No purchase requests were declined. They might not exist or were already declined.";
        }

    } catch (Exception $e) {
        mysqli_rollback($connection); // Rollback on error
        $response['message'] = 'Error declining purchase requests: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
exit();
?>