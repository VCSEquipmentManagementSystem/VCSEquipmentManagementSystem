<?php
require('./database.php'); // Include your database connection

header('Content-Type: application/json'); // Set header to indicate JSON response

$response = [
    'success' => false,
    'message' => 'An unknown error occurred.'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $idsToArchive = isset($input['ids']) && is_array($input['ids']) ? $input['ids'] : [];

    if (empty($idsToArchive)) {
        $response['message'] = 'No purchase requests selected for archiving.';
        echo json_encode($response);
        exit();
    }

    // Sanitize IDs
    $sanitizedIds = array_map('intval', $idsToArchive);
    $idsString = implode(',', $sanitizedIds);

    mysqli_begin_transaction($connection); // Start transaction

    $archivedCount = 0;
    $failedCount = 0;
    $messages = [];

    try {
        // Fetch current status and pr_number for all selected IDs
        // We need to fetch individual status to store in previous_status
        $selectQuery = "SELECT id, status, pr_number FROM purchase_requests_tbl WHERE id IN ($idsString)";
        $sqlSelect = mysqli_query($connection, $selectQuery);

        if (!$sqlSelect) {
            throw new Exception("Database query for status failed: " . mysqli_error($connection));
        }

        $itemsToProcess = [];
        while ($row = mysqli_fetch_assoc($sqlSelect)) {
            $itemsToProcess[] = $row;
        }

        if (empty($itemsToProcess)) {
            throw new Exception("No valid purchase requests found with the provided IDs.");
        }

        // Prepare update statement outside the loop for efficiency
        $updateStmt = mysqli_prepare($connection, "UPDATE purchase_requests_tbl SET status = 'archived', previous_status = ? WHERE id = ?");
        if (!$updateStmt) {
            throw new Exception("Failed to prepare update statement: " . mysqli_error($connection));
        }

        foreach ($itemsToProcess as $item) {
            $prId = $item['id'];
            $currentStatus = $item['status'];
            $prNumber = $item['pr_number'];

            if ($currentStatus === 'archived') {
                $failedCount++;
                $messages[] = "PR $prNumber (ID: $prId) is already archived.";
                continue; // Skip already archived items
            }

            // Bind parameters and execute for each item
            mysqli_stmt_bind_param($updateStmt, 'si', $currentStatus, $prId);
            if (mysqli_stmt_execute($updateStmt)) {
                if (mysqli_stmt_affected_rows($updateStmt) > 0) {
                    $archivedCount++;
                    $messages[] = "PR $prNumber (ID: $prId) archived successfully. Previous status: '$currentStatus'.";
                } else {
                    $failedCount++;
                    $messages[] = "Failed to archive PR $prNumber (ID: $prId): No rows affected.";
                }
            } else {
                $failedCount++;
                $messages[] = "Database execution failed for PR $prNumber (ID: $prId): " . mysqli_stmt_error($updateStmt);
            }
        }

        mysqli_stmt_close($updateStmt); // Close the prepared statement

        if ($archivedCount > 0) {
            mysqli_commit($connection); // Commit if any items were archived
            $response['success'] = true;
            $response['message'] = "$archivedCount purchase request(s) archived successfully. " . ($failedCount > 0 ? "$failedCount failed." : "") . " Details: " . implode(" ", $messages);
        } else {
            mysqli_rollback($connection); // Rollback if nothing was archived
            $response['message'] = "No purchase requests were archived. " . implode(" ", $messages);
        }

    } catch (Exception $e) {
        mysqli_rollback($connection); // Rollback on any major error
        $response['message'] = 'Bulk archiving failed: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
exit();
?>