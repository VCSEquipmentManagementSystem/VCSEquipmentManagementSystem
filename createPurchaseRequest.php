<?php
require('./database.php');

if (isset($_POST['createPurchaseRequest'])) {
    // Get form inputs
    $datePrepared = date("Y-m-d");
    // Ensure 'user_name' is safely retrieved from session or a default
    $requestedBy = $_SESSION['user_name'] ?? 'Ivan Baltar';
    $dateNeeded = $_POST['date_needed'];
    $location = $_POST['location'];
    $purpose = $_POST['purpose'];

    // Start a database transaction for atomicity
    mysqli_begin_transaction($connection);

    try {
        // 1. Insert to purchase_requests_tbl (main) without pr_number first
        // The 'id' column will auto-increment.
        $queryMain = "INSERT INTO purchase_requests_tbl (date_prepared, requested_by, date_needed, location, purpose, status)
                      VALUES (?, ?, ?, ?, ?, 'pending approval')";
        $stmtMain = mysqli_prepare($connection, $queryMain);
        if (!$stmtMain) {
            throw new Exception("Failed to prepare main insert statement: " . mysqli_error($connection));
        }
        mysqli_stmt_bind_param($stmtMain, "sssss", $datePrepared, $requestedBy, $dateNeeded, $location, $purpose);

        if (!mysqli_stmt_execute($stmtMain)) {
            throw new Exception("Failed to insert main purchase request: " . mysqli_stmt_error($stmtMain));
        }
        mysqli_stmt_close($stmtMain);

        // 2. Get the last inserted ID
        $purchaseRequestId = mysqli_insert_id($connection);

        if ($purchaseRequestId === 0) {
            throw new Exception("Failed to retrieve last inserted ID.");
        }

        // 3. Generate the pr_number (e.g., PR-1, PR-2)
        $prNumber = "PR-" . $purchaseRequestId;

        // 4. Update the newly inserted row with the generated pr_number
        $queryUpdatePrNumber = "UPDATE purchase_requests_tbl SET pr_number = ? WHERE id = ?";
        $stmtUpdatePrNumber = mysqli_prepare($connection, $queryUpdatePrNumber);
        if (!$stmtUpdatePrNumber) {
            throw new Exception("Failed to prepare pr_number update statement: " . mysqli_error($connection));
        }
        mysqli_stmt_bind_param($stmtUpdatePrNumber, "si", $prNumber, $purchaseRequestId);

        if (!mysqli_stmt_execute($stmtUpdatePrNumber)) {
            throw new Exception("Failed to update pr_number: " . mysqli_stmt_error($stmtUpdatePrNumber));
        }
        mysqli_stmt_close($stmtUpdatePrNumber);

        // 5. Insert associated items
        $qtyArr = $_POST['qty'];
        $unitArr = $_POST['unit'];
        $descArr = $_POST['item_description'];
        $remarksArr = $_POST['remarks'];

        $queryItem = "INSERT INTO purchase_request_items_tbl (purchase_request_id, qty, unit, item_description, remarks)
                      VALUES (?, ?, ?, ?, ?)";
        $stmtItem = mysqli_prepare($connection, $queryItem);
        if (!$stmtItem) {
            throw new Exception("Failed to prepare item insert statement: " . mysqli_error($connection));
        }

        for ($i = 0; $i < count($qtyArr); $i++) {
            $qty = $qtyArr[$i];
            $unit = $unitArr[$i];
            $desc = $descArr[$i];
            $remarks = $remarksArr[$i];

            mysqli_stmt_bind_param($stmtItem, "iisss", $purchaseRequestId, $qty, $unit, $desc, $remarks);
            if (!mysqli_stmt_execute($stmtItem)) {
                throw new Exception("Failed to insert item " . ($i + 1) . ": " . mysqli_stmt_error($stmtItem));
            }
        }
        mysqli_stmt_close($stmtItem);

        // If all operations successful, commit the transaction
        mysqli_commit($connection);
        echo '<script>alert("Purchase Request created successfully with ID: ' . $prNumber . '");</script>';
    } catch (Exception $e) {
        // Rollback transaction on any error
        mysqli_rollback($connection);
        echo '<script>alert("Error: Failed to create purchase request. ' . $e->getMessage() . '");</script>';
        error_log("Purchase Request Creation Error: " . $e->getMessage());
    }

    // Redirect back to the Purchase Request page
    echo '<script>window.location.href = "PurchaseRequest.php";</script>';
} else {
    // If accessed directly without POST data, redirect
    header("Location: PurchaseRequest.php");
    exit();
}
