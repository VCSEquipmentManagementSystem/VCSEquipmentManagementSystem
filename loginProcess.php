<?php
session_start();
session_regenerate_id(true);
// git commit ian
include './database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['company_emp_id']) || empty($_POST['password'])) {
        die("Employee ID and password are required.");
    }

    $employeeId = $_POST['company_emp_id'];
    $password   = $_POST['password'];

    // Lookup user by company_emp_id
    $stmt = $connection->prepare(
        "SELECT user_id, password, role FROM user_tbl WHERE company_emp_id = ?"
    );
    if (!$stmt) {
        die("Prepare failed: " . $connection->error);
    }

    $stmt->bind_param('s', $employeeId);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($dbUserId, $dbPassword, $role);
        $stmt->fetch();

        // Verify (use password_verify() if using hashes)
        if ($password === $dbPassword) { //(password_verify($password, $dbPassword)) 
            // Clear old session data
            $_SESSION = [];
            session_destroy();

            // Start fresh session
            session_start();
            session_regenerate_id(true);

            // Store user info
            $_SESSION['userID'] = $dbUserId;
            $_SESSION['role']   = $role;

            // Redirect by role
            switch (strtolower($role)) {
                case 'moderator':
                    header('Location: ModeratorDashboard.php');
                    break;
                case 'admin':
                    header('Location: /Thesis-Equipment-Management-System/Admin/AdminDashboard.php');
                    break;
                case 'chief mechanic':
                    header('Location: /Thesis-Equipment-Management-System/ChiefMechanic/ChiefMechanicDashboard.php');
                    break;
                case 'project engineer':
                    header('Location: /Thesis/ProjectManager/ProjectManagerDashboard.php');
                    break;
                case 'employee':
                    header('Location: /Thesis/Employee/EmployeeDashboard.php');
                    break;
                default:
                    echo "Invalid role.";
            }
            exit;
        } else {
            echo "Incorrect password.";
        }
    } else {
        echo "User not found.";
    }

    $stmt->close();
    $connection->close();
} else {
    echo "Invalid request method.";
}
