<?php
// getPurchaseRequestItems.php
// This file will fetch purchase request items based on a purchase_id

// Adjust this path to correctly point to your database connection file
require('./database.php');

header('Content-Type: application/json'); // Tell the browser to expect JSON

if (isset($_GET['purchase_id']) && is_numeric($_GET['purchase_id'])) {
    $purchaseId = $_GET['purchase_id'];

    // Assuming your purchase_request_items_tbl has columns like purchase_request_id, qty, unit, item_description, remarks
    $query = "SELECT qty, unit, item_description, remarks FROM purchase_request_items_tbl WHERE purchase_request_id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $purchaseId); // 'i' for integer type
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }

    echo json_encode($items); // Encode the results as JSON

    mysqli_stmt_close($stmt);
    mysqli_close($connection); // Close connection when done
} else {
    // Return an empty array if no valid purchase_id is provided
    echo json_encode([]);
}

?>