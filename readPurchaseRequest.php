<?php
require('./database.php');

// Default sort order
$orderBy = "id DESC"; // Default sort by ID descending

// Initialize WHERE clause for search and status filtering
// Start with the condition to exclude archived items
$whereClauseParts = ["status != 'archived'"];

// Check if a 'search' parameter is present in the URL
if (isset($_GET['search']) && !empty($_GET['search'])) {
    // Sanitize the search term to prevent SQL injection
    $searchTerm = mysqli_real_escape_string($connection, $_GET['search']);
    
    // Construct the search conditions
    // Include pr_number in search
    $searchConditions = " (id LIKE '%$searchTerm%' OR
                          pr_number LIKE '%$searchTerm%' OR
                          purpose LIKE '%$searchTerm%' OR
                          requested_by LIKE '%$searchTerm%' OR
                          status LIKE '%$searchTerm%' OR
                          date_prepared LIKE '%$searchTerm%') ";
                          
    // Add search conditions to the where clause parts
    $whereClauseParts[] = $searchConditions;
}

// Combine all WHERE clause parts with 'AND'
if (!empty($whereClauseParts)) {
    $whereClause = " WHERE " . implode(" AND ", $whereClauseParts);
} else {
    $whereClause = ""; // Should not happen with 'status != archived' always present
}


// Check if a 'sort' parameter is present in the URL
if (isset($_GET['sort'])) {
    if ($_GET['sort'] === 'date_desc') {
        // Sort by date_prepared in descending order, then by id descending for stable sort
        $orderBy = "date_prepared DESC, id DESC";
    } elseif ($_GET['sort'] === 'date_asc') {
        // Sort by date_prepared in ascending order, then by id ascending for stable sort
        $orderBy = "date_prepared ASC, id ASC";
    }
    // You can add more sort options here later if needed
}

// Construct the final query by combining the WHERE clause and ORDER BY clause
// Explicitly select columns, including pr_number
$queryPurchaseRequest = "SELECT id, pr_number, purpose, requested_by, status, date_prepared, date_needed, location FROM purchase_requests_tbl" . $whereClause . " ORDER BY " . $orderBy;
$sqlPurchaseRequest = mysqli_query($connection, $queryPurchaseRequest);

// Basic error handling for the query (optional, but good for debugging)
if (!$sqlPurchaseRequest) {
    die('Query failed: ' . mysqli_error($connection));
}

// For purchase requests items (this part remains unchanged as it's for individual item details)
$queryPurchaseRequestItem = "SELECT * FROM purchase_request_items_tbl ORDER BY id DESC";
$sqlPurchaseRequestItems = mysqli_query($connection, $queryPurchaseRequestItem);
?>