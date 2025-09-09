<?php
// getAvailableEquipment.php

// 1. Include the database connection
require('./database.php');

// 2. Set the content type to JSON for the response
header('Content-Type: application/json');

// 3. Check for the required POST parameter
if (isset($_POST['equip_type_id']) && !empty($_POST['equip_type_id'])) {
    $equipTypeId = mysqli_real_escape_string($connection, $_POST['equip_type_id']);
    
    // An optional project ID to include equipment already assigned to the project being edited
    $projectId = isset($_POST['project_id']) ? mysqli_real_escape_string($connection, $_POST['project_id']) : null;

    // 4. Build the SQL query
    // This query fetches equipment that is 'Idle' AND 'Undeployed'
    $query = "SELECT custom_equip_id, equipment_id FROM equip_tbl
              WHERE equip_type_id = '$equipTypeId' AND
              (
                  (equip_status = 'Idle' AND deployment_status = 'Undeployed')";

    // If editing a project, also include equipment currently assigned to *that* project.
    // This ensures the currently assigned equipment appears in the dropdown for re-selection.
    if ($projectId) {
        $query .= " OR equipment_id IN (SELECT equipment_id FROM proj_eqp_assign_tbl WHERE project_id = '$projectId')";
    }

    $query .= ") ORDER BY custom_equip_id ASC";

    $result = mysqli_query($connection, $query);
    
    // 5. Fetch and return the data
    if ($result) {
        $equipment = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $equipment[] = $row;
        }
        // Echo the data as a JSON object
        echo json_encode($equipment);
    } else {
        // If the query fails, return a JSON error message
        echo json_encode(['error' => 'Database query failed: ' . mysqli_error($connection)]);
    }
} else {
    // If the equip_type_id is not provided, return a JSON error message
    echo json_encode(['error' => 'Equipment Type ID not provided.']);
}

// 6. Terminate the script
exit;
?>