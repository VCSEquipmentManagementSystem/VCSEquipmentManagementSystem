<?php
require('./database.php'); // Include your database connection

header('Content-Type: application/json'); // Set header to indicate JSON response

$response = [
    'success' => false,
    'message' => 'An unknown error occurred.',
    'archivedRequests' => []
];

try {
    // Query to select purchase requests with status 'archived', INCLUDING pr_number
    $queryArchivedRequests = "SELECT id, pr_number, purpose, requested_by, date_prepared FROM purchase_requests_tbl WHERE status = 'archived' ORDER BY date_prepared DESC";
    $sqlArchivedRequests = mysqli_query($connection, $queryArchivedRequests);

    if (!$sqlArchivedRequests) {
        throw new Exception("Database query failed: " . mysqli_error($connection));
    }

    $archivedRequests = [];
    if (mysqli_num_rows($sqlArchivedRequests) > 0) {
        while ($row = mysqli_fetch_assoc($sqlArchivedRequests)) {
            $archivedRequests[] = [
                'id' => htmlspecialchars($row['id']), // Keep 'id' for internal use (checkbox value, backend operations)
                'pr_number' => htmlspecialchars($row['pr_number']), // ADDED: pr_number for display
                'purpose' => htmlspecialchars($row['purpose']),
                'requested_by' => htmlspecialchars($row['requested_by']),
                'date_prepared' => htmlspecialchars($row['date_prepared'])
            ];
        }
    }

    $response['success'] = true;
    $response['message'] = 'Archived requests fetched successfully.';
    $response['archivedRequests'] = $archivedRequests;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit();
?>