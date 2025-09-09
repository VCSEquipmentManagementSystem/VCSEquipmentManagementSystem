<?php
require('./database.php');

if (isset($_GET['project_id']) && isset($_GET['status'])) {
    $project_id = mysqli_real_escape_string($connection, $_GET['project_id']);
    $new_status = mysqli_real_escape_string($connection, $_GET['status']);
    
    // Validate status
    $valid_statuses = ['Ongoing', 'Delayed', 'Completed', 'Not yet started'];
    if (!in_array($new_status, $valid_statuses)) {
        header("Location: ProjectList.php?error=invalid_status");
        exit();
    }
    
    // Use prepared statement for better security
    $updateQuery = "UPDATE proj_sched_tbl SET proj_status = ? WHERE project_id = ?";
    $stmt = mysqli_prepare($connection, $updateQuery);
    mysqli_stmt_bind_param($stmt, "ss", $new_status, $project_id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header("Location: ProjectList.php?success=status_updated");
    } else {
        mysqli_stmt_close($stmt);
        header("Location: ProjectList.php?error=status_update_failed");
    }
} else {
    header("Location: ProjectList.php");
}

mysqli_close($connection);
?>
