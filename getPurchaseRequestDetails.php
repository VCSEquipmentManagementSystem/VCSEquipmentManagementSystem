<?php
// getPurchaseRequestDetails.php
// This file fetches all details (main request and its items) for a given purchase_id

// Adjust this path to correctly point to your database connection file
require('./database.php');

header('Content-Type: application/json'); // Tell the browser to expect JSON

$response = [
    'success' => false,
    'message' => 'Invalid request.',
    'data' => null
];

if (isset($_GET['purchase_id']) && is_numeric($_GET['purchase_id'])) {
    $purchaseId = $_GET['purchase_id'];

    // 1. Fetch main purchase request details
    $queryMain = "SELECT id, date_prepared, requested_by, date_needed, location, purpose, status FROM purchase_requests_tbl WHERE id = ?";
    $stmtMain = mysqli_prepare($connection, $queryMain);
    mysqli_stmt_bind_param($stmtMain, "i", $purchaseId);
    mysqli_stmt_execute($stmtMain);
    $resultMain = mysqli_stmt_get_result($stmtMain);
    $mainDetails = mysqli_fetch_assoc($resultMain);
    mysqli_stmt_close($stmtMain);

    if ($mainDetails) {
        // 2. Fetch associated purchase request items
        $queryItems = "SELECT id AS item_id, qty, unit, item_description, remarks FROM purchase_request_items_tbl WHERE purchase_request_id = ?";
        $stmtItems = mysqli_prepare($connection, $queryItems);
        mysqli_stmt_bind_param($stmtItems, "i", $purchaseId);
        mysqli_stmt_execute($stmtItems);
        $resultItems = mysqli_stmt_get_result($stmtItems);

        $items = [];
        while ($row = mysqli_fetch_assoc($resultItems)) {
            $items[] = $row;
        }
        mysqli_stmt_close($stmtItems);

        // Combine main details and items into a single response object
        $response['success'] = true;
        $response['message'] = 'Purchase request details fetched successfully.';
        $response['data'] = [
            'main' => $mainDetails,
            'items' => $items
        ];
    } else {
        $response['message'] = 'Purchase request not found.';
    }
} else {
    $response['message'] = 'No valid purchase_id provided.';
}

echo json_encode($response);

mysqli_close($connection); // Close connection
?>