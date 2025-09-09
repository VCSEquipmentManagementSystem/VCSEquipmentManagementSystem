<?php
require('./database.php');

// readProfiling
$search = isset($_GET['search']) ? mysqli_real_escape_string($connection, $_GET['search']) : '';

$queryEquipment = ""; // Initialize the variable

if (!empty($search)) {
    // Search by equipment ID or equipment type, and include project location
    // Note: If searching by equip_type_id, you might want to join with equip_type_tbl to search by name.
    // For now, assuming equip_type_id is the ID itself.
    $queryEquipment = "
    SELECT e.*, et.equip_type_name, ps.project_location
    FROM equip_tbl e
    LEFT JOIN equip_type_tbl et ON e.equip_type_id = et.equip_type_id
    LEFT JOIN proj_sched_tbl ps ON e.assigned_proj_id = ps.project_id
    WHERE (e.custom_equip_id LIKE '%$search%'
        OR et.equip_type_name LIKE '%$search%') -- Search by type name if applicable
        AND e.equip_status != 'Archived'
    ";
} else {
    // Fetch all active equipment with their type names and project locations
    $queryEquipment = "
        SELECT e.*, et.equip_type_name, ps.project_location
        FROM equip_tbl e
        LEFT JOIN equip_type_tbl et ON e.equip_type_id = et.equip_type_id
        LEFT JOIN proj_sched_tbl ps ON e.assigned_proj_id = ps.project_id
        WHERE e.equip_status != 'Archived'
        ORDER BY e.custom_equip_id ASC -- Added an ORDER BY for consistent results
    ";
}

$sqlEquipment = mysqli_query($connection, $queryEquipment);


$equipmentData = []; // holds all data of equipment profiling

// Fetch all rows from the result set and store them in $equipmentData
if ($sqlEquipment) { // Check if the query was successful
    while ($row = mysqli_fetch_assoc($sqlEquipment)) {
        $equipmentData[] = $row; // Add each row (as an associative array) to the $equipmentData array
    }
} else {
    // Handle query error, e.g., log it or display a user-friendly message
    error_log("Error fetching equipment data: " . mysqli_error($connection));
    // You might want to set a flag or display an error message on the page
}


function getOperators($connection)
{
    // Improved query with error handling
    $query = "SELECT
        e.employee_id,
        e.company_emp_id,
        CONCAT(e.first_name, ' ', e.last_name) as full_name,
        p.position_name
        FROM employee_tbl e
        INNER JOIN position_tbl p ON e.position_id = p.position_id
        WHERE e.emp_status = 'Active'
        AND p.position_name LIKE '%operator%'
        ORDER BY e.company_emp_id ASC";

    $result = mysqli_query($connection, $query);
    if (!$result) {
        error_log("Error fetching operators: " . mysqli_error($connection));
        return false;
    }
    return $result;
}


// Archived (not searched) - this part remains unchanged as it fetches from archivedEquipment_tbl
$queryArchivedEquipment = "SELECT * FROM archivedEquipment_tbl";
$sqlArchivedEquipment = mysqli_query($connection, $queryArchivedEquipment);
// If you also want to fetch archived equipment into an array:
$archivedEquipmentData = [];
if ($sqlArchivedEquipment) {
    while ($row = mysqli_fetch_assoc($sqlArchivedEquipment)) {
        $archivedEquipmentData[] = $row;
    }
} else {
    error_log("Error fetching archived equipment data: " . mysqli_error($connection));
}
