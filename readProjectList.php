<?php
require('./database.php');
require('./readProfiling.php');

// Automatic status update logic
$currentDate = date('Y-m-d');
$updateQueries = [
    "UPDATE proj_sched_tbl SET proj_status = 'Ongoing' WHERE start_date <= '$currentDate' AND end_date >= '$currentDate' AND proj_status != 'Completed' AND proj_status != 'Ongoing'",
    "UPDATE proj_sched_tbl SET proj_status = 'Delayed' WHERE end_date < '$currentDate' AND proj_status != 'Completed' AND proj_status != 'Delayed'",
    "UPDATE proj_sched_tbl SET proj_status = 'Completed' WHERE end_date < '$currentDate' AND proj_status = 'Ongoing'",
];

foreach ($updateQueries as $query) {
    if (!mysqli_query($connection, $query)) {
        error_log("Error updating project status: " . mysqli_error($connection));
    }
}

// Function to execute query with error handling
function executeQuery($connection, $query, $errorMessage)
{
    $result = mysqli_query($connection, $query);
    if (!$result) {
        error_log($errorMessage . ": " . mysqli_error($connection));
        die($errorMessage . ": " . mysqli_error($connection));
    }
    return $result;
}

// Main projects query
$sqlProjectList = mysqli_query($connection, "SELECT * FROM proj_sched_tbl ORDER BY project_id DESC");
if (!$sqlProjectList) {
    die("Error fetching projects: " . mysqli_error($connection));
}

// Assigned employees query
$queryassignedEmployee = "SELECT * FROM assigned_employee_tbl";
$sqlassinedEmployee = executeQuery($connection, $queryassignedEmployee, "Error fetching assigned employees");

// Equipment query
$queryEquipment = "SELECT * FROM equip_tbl";
$sqlEquipment = executeQuery($connection, $queryEquipment, "Error fetching equipment");

// Equipment type query
$queryEquipTypeName = "SELECT * FROM equip_type_tbl";
$sqlEquipTypeName = executeQuery($connection, $queryEquipTypeName, "Error fetching equipment types");

// Get archived projects
$queryArchivedProjects = "SELECT * FROM archived_project_tbl ORDER BY archived_date DESC";
$sqlArchivedProjects = executeQuery($connection, $queryArchivedProjects, "Error fetching archived projects");
