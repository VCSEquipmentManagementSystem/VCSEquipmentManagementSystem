<?php
session_start();
include('./database.php');

if (empty($_SESSION['userID'])) {
    header('Location: loginPage.php');
    exit;
}

// POST check
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid request token.';
        header('Location: Profile.php');
        exit;
    }

    // Sanitize & fetch
    $userID = $_SESSION['userID'];
    $firstName = trim($_POST['firstname'] ?? '');
    $lastName = trim($_POST['lastname'] ?? '');
    $empEmail = trim($_POST['email'] ?? '');
    $companyEmpId = trim($_POST['employeeid'] ?? '');
    $empContactNum = trim($_POST['phone'] ?? '');

    // Basic validations
    if (!$firstName || !$lastName || !$empEmail || !$companyEmpId) {
        $_SESSION['error'] = 'Please fill in all required fields.';
        header('Location: Profile.php');
        exit;
    }

    if (!filter_var($empEmail, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Invalid email format.';
        header('Location: Profile.php');
        exit;
    }

    if (!preg_match('/^\d{4}-\d{3}-\d{4}$/', $empContactNum)) {
        $_SESSION['error'] = 'Invalid phone format. Use 0000-000-0000 format.';
        header('Location: Profile.php');
        exit;
    }


    // Update profile
    $sql = "UPDATE employee_tbl 
        SET first_name=?, last_name=?, company_emp_id=?, emp_contact_num=? 
        WHERE employee_id=?";
    $stmt = $connection->prepare($sql);

    if ($stmt) {
        $stmt->bind_param('ssssi', $firstName, $lastName, $companyEmpId, $empContactNum, $userID);
        $successEmployee = $stmt->execute();
        $stmt->close();
    } else {
        $_SESSION['error'] = 'Database error (employee_tbl).';
        header('Location: Profile.php');
        exit;
    }

    // Update user_tbl for emp_email
    $sqlUser = "UPDATE user_tbl SET emp_email=? WHERE employee_id=?";
    $stmtUser = $connection->prepare($sqlUser);

    if ($stmtUser) {
        $stmtUser->bind_param('si', $empEmail, $userID);
        $successUser = $stmtUser->execute();
        $stmtUser->close();
    } else {
        $_SESSION['error'] = 'Database error (user_tbl).';
        header('Location: Profile.php');
        exit;
    }

    if ($successEmployee && $successUser) {
        $_SESSION['success'] = 'Profile updated successfully!';
    } else {
        $_SESSION['error'] = 'Failed to update profile.';
    }
    header('Location: Profile.php');
    exit;
}
