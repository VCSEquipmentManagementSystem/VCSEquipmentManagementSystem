<?php
require('../database.php');

// Handle Add Operator via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['company_emp_id'], $_POST['first_name'], $_POST['last_name'], $_POST['emp_contact_num'])) {
    $emp_id = trim($_POST['company_emp_id']);
    $first = trim($_POST['first_name']);
    $last = trim($_POST['last_name']);
    $contact = trim($_POST['emp_contact_num']);

    if ($emp_id === '' || $first === '' || $last === '' || $contact === '') {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    $position_id = 0;
    $pos = mysqli_query($connection, "SELECT position_id FROM position_tbl WHERE position_name LIKE '%operator%' LIMIT 1");
    if ($row = mysqli_fetch_assoc($pos)) $position_id = $row['position_id'];

    // Insert new operator with contact number
    $stmt = mysqli_prepare($connection, "INSERT INTO employee_tbl (company_emp_id, first_name, last_name, emp_contact_num, position_id, emp_status) VALUES (?, ?, ?, ?, ?, 'Active')");
    mysqli_stmt_bind_param($stmt, 'ssssi', $emp_id, $first, $last, $contact, $position_id);
    if (mysqli_stmt_execute($stmt)) {
        $id = mysqli_insert_id($connection);
        echo json_encode([
            'status' => 'success',
            'employee_id' => $id,
            'display_name' => "$emp_id - $first $last",
            'emp_contact_num' => $contact
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
    exit;
}

// Handle Get Operator via GET
if (isset($_GET['operator_id'])) {
    $operator_id = mysqli_real_escape_string($connection, $_GET['operator_id']);
    
    $query = "SELECT e.*, CONCAT(e.company_emp_id, ' - ', e.first_name, ' ', e.last_name) as full_name 
              FROM employee_tbl e 
              WHERE e.employee_id = ? 
              AND e.emp_status = 'Active'";
    
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, 'i', $operator_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode([
            'status' => 'success', 
            'full_name' => $row['full_name'],
            'operator_id' => $row['employee_id']
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Operator not found']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No operator ID provided']);
}

mysqli_close($connection);
