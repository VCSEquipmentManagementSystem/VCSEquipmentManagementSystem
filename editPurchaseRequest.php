<?php
require('./database.php');

// Start a transaction for atomicity
mysqli_begin_transaction($connection);

try {
    if (isset($_POST['editPurchaseRequest'])) {
        // --- 1. Get main purchase request details ---
        $purchaseRequestId = $_POST['purchase_request_id'];
        $dateNeeded = $_POST['date_needed'];
        $location = $_POST['location'];
        $purpose = $_POST['purpose'];

        // --- 2. Update purchase_requests_tbl (main table) ---
        $queryMain = "UPDATE purchase_requests_tbl
                      SET date_needed = ?, location = ?, purpose = ?
                      WHERE id = ?";
        $stmtMain = mysqli_prepare($connection, $queryMain);
        mysqli_stmt_bind_param($stmtMain, "sssi", $dateNeeded, $location, $purpose, $purchaseRequestId);
        mysqli_stmt_execute($stmtMain);

        // --- 3. Handle purchase_request_items_tbl (items table) ---

        $submittedItemIds = $_POST['item_id'] ?? [];
        $submittedQtys = $_POST['qty'] ?? [];
        $submittedUnits = $_POST['unit'] ?? [];
        $submittedDescriptions = $_POST['item_description'] ?? [];
        $submittedRemarks = $_POST['remarks'] ?? [];

        // Fetch existing item IDs for this purchase request
        $existingItemIds = [];
        $queryExistingItems = "SELECT id FROM purchase_request_items_tbl WHERE purchase_request_id = ?";
        $stmtExistingItems = mysqli_prepare($connection, $queryExistingItems);
        mysqli_stmt_bind_param($stmtExistingItems, "i", $purchaseRequestId);
        mysqli_stmt_execute($stmtExistingItems);
        $resultExistingItems = mysqli_stmt_get_result($stmtExistingItems);
        while ($row = mysqli_fetch_assoc($resultExistingItems)) {
            $existingItemIds[] = $row['id'];
        }

        // --- Determine items to delete ---
        $itemsToDelete = array_diff($existingItemIds, $submittedItemIds);
        if (!empty($itemsToDelete)) {
            $placeholders = implode(',', array_fill(0, count($itemsToDelete), '?'));
            $queryDelete = "DELETE FROM purchase_request_items_tbl WHERE id IN ($placeholders) AND purchase_request_id = ?";
            $stmtDelete = mysqli_prepare($connection, $queryDelete);
            
            $itemsForBind = $itemsToDelete;
            $itemsForBind[] = $purchaseRequestId; // Add purchaseRequestId to the array
            $types = str_repeat('i', count($itemsForBind)); // 'i' for each item ID + 'i' for purchase_request_id
            mysqli_stmt_bind_param($stmtDelete, $types, ...$itemsForBind); // Unpack the modified array
            
            mysqli_stmt_execute($stmtDelete);
        }

        // --- Loop through submitted items for update/insert ---
        for ($i = 0; $i < count($submittedQtys); $i++) {
            $itemId = $submittedItemIds[$i]; // Corrected: removed backslash
            $qty = $submittedQtys[$i]; // Corrected: removed backslash
            $unit = $submittedUnits[$i]; // Corrected: removed backslash
            $description = $submittedDescriptions[$i]; // Corrected: removed backslash
            $remarks = $submittedRemarks[$i]; // Corrected: removed backslash

            if (!empty($itemId)) {
                // Item has an ID, so it's an existing item to be updated
                $queryItem = "UPDATE purchase_request_items_tbl
                              SET qty = ?, unit = ?, item_description = ?, remarks = ?
                              WHERE id = ? AND purchase_request_id = ?";
                $stmtItem = mysqli_prepare($connection, $queryItem);
                mysqli_stmt_bind_param($stmtItem, "isssii", $qty, $unit, $description, $remarks, $itemId, $purchaseRequestId);
                mysqli_stmt_execute($stmtItem);
            } else {
                // Item has no ID, so it's a new item to be inserted
                $queryItem = "INSERT INTO purchase_request_items_tbl
                              (purchase_request_id, qty, unit, item_description, remarks)
                              VALUES (?, ?, ?, ?, ?)";
                $stmtItem = mysqli_prepare($connection, $queryItem);
                mysqli_stmt_bind_param($stmtItem, "iisss", $purchaseRequestId, $qty, $unit, $description, $remarks);
                mysqli_stmt_execute($stmtItem);
            }
        }

        // If all operations successful, commit the transaction
        mysqli_commit($connection);
        echo '<script>alert("Purchase Request updated successfully."); window.location.href = "/EMS/PurchaseRequest.php";</script>';

    } else {
        // If accessed directly without POST data
        header("Location: /EMS/PurchaseRequest.php");
        exit();
    }
} catch (Exception $e) {
    // An error occurred, rollback the transaction
    mysqli_rollback($connection);
    error_log("Error updating purchase request: " . $e->getMessage()); // Log the error for debugging
    echo '<script>alert("Error: Failed to update purchase request. Please try again."); window.location.href = "/EMS/PurchaseRequest.php";</script>';
}

// Close prepared statements and connection (optional, as script termination does this)
if (isset($stmtMain)) mysqli_stmt_close($stmtMain);
if (isset($stmtExistingItems)) mysqli_stmt_close($stmtExistingItems);
if (isset($stmtDelete)) mysqli_stmt_close($stmtDelete);
if (isset($stmtItem)) mysqli_stmt_close($stmtItem); // This might be the last one opened in the loop
mysqli_close($connection);

?>