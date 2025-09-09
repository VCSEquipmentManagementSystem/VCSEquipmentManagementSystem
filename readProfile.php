<?php

if (empty($_SESSION['userID'])) {
    header('Location: loginPage.php');
    exit;
}

require './database.php';

// Fetch only the logged-in user's profile
$userID = $_SESSION['userID'];

$sql = "
SELECT
    e.first_name,
    e.last_name,
    e.company_emp_id,
    e.emp_contact_num,
    u.role,
    u.emp_email
FROM employee_tbl AS e
JOIN user_tbl     AS u
  ON e.company_emp_id = u.company_emp_id
WHERE u.user_id = ?
";

$stmt = $connection->prepare($sql);
$stmt->bind_param('i', $userID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    session_destroy();
    header('Location: loginPage.php');
    exit;
}

$profileData = $result->fetch_assoc();
$stmt->close();
$connection->close();
