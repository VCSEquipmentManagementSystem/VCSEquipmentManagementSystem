<?php
require('./database.php');

// Check if form is submitted for editing
if (isset($_POST['edit_project']) && isset($_POST['project_id'])) {
    // Get project ID with validation
    $project_id = filter_var($_POST['project_id'], FILTER_VALIDATE_INT);

    if (!$project_id) {
        header("Location: ProjectList.php?error=invalid_project_id");
        exit();
    }

    // Gather & validate inputs
    $project_name = trim($_POST['project_name']);
    $location = trim($_POST['project_location'] ?? '');
    $client_name = trim($_POST['clientname']);
    $project_engineer_id = trim($_POST['project_engineer'] ?? ''); // Get the employee_id from the dropdown
    $start_date = $_POST['startdate'];
    $end_date = $_POST['enddate'];
    
    // Validate required fields
    if (empty($project_name) || empty($client_name) || empty($start_date) || empty($end_date) || empty($project_engineer_id)) {
        header("Location: ProjectList.php?error=missing_fields");
        exit();
    }
    
    // Validate dates
    if (strtotime($end_date) <= strtotime($start_date)) {
        header("Location: ProjectList.php?error=invalid_dates");
        exit();
    }
    
    // Fetch the name of the Project Engineer
    $engineerNameQuery = $connection->prepare("SELECT name FROM user_tbl WHERE employee_id = ? AND role = 'Project Engineer'");
    $engineerNameQuery->bind_param('i', $project_engineer_id);
    $engineerNameQuery->execute();
    $engineerNameQuery->bind_result($project_engineer_name);
    $engineerNameQuery->fetch();
    $engineerNameQuery->close();
    
    // --- AUTOMATICALLY DETERMINE PROJECT STATUS ---
    $today = date('Y-m-d');
    if (strtotime($start_date) > strtotime($today)) {
        $proj_status = 'Not yet started';
    } elseif (strtotime($end_date) < strtotime($today)) {
        $proj_status = 'Completed';
    } else {
        $proj_status = 'Ongoing';
    }
    
    // Update project in database
    $updateQuery = "UPDATE proj_sched_tbl
                SET project_name = ?,
                    project_location = ?,
                    client = ?,
                    project_engineer = ?,
                    start_date = ?,
                    end_date = ?,
                    proj_status = ?,
                    date_modified = NOW()
                WHERE project_id = ?";

    $stmt = mysqli_prepare($connection, $updateQuery);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssssssi",
            $project_name,
            $location,
            $client_name,
            $project_engineer_name, // Use the fetched name
            $start_date,
            $end_date,
            $proj_status,
            $project_id
        );

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);

            // Logic to update `assigned_employee_tbl`
            // Remove previous Project Engineer assignment
            $connection->query("DELETE FROM assigned_employee_tbl WHERE project_id = $project_id AND employee_id IN (SELECT employee_id FROM user_tbl WHERE role = 'Project Engineer')");
            
            // Insert the new Project Engineer
            $assignStmt = $connection->prepare("INSERT INTO assigned_employee_tbl (project_id, employee_id) VALUES (?, ?)");
            $assignStmt->bind_param('ii', $project_id, $project_engineer_id);
            $assignStmt->execute();
            $assignStmt->close();

            // --- Equipment assignment logic here ---
            if (!empty($_POST['custom_equip_id'])) {
                $equip_ids = $_POST['custom_equip_id'];

                // Remove all previous assignments for this project
                $connection->query("DELETE FROM proj_eqp_assign_tbl WHERE project_id = $project_id");

                // If only one equipment, make array
                if (!is_array($equip_ids)) $equip_ids = [$equip_ids];

                foreach ($equip_ids as $custom_equip_id) {
                    $custom_equip_id = trim($custom_equip_id);

                    // Get equipment_id
                    $equipment_id = null;
                    $stmtEquip = $connection->prepare("SELECT equipment_id FROM equip_tbl WHERE custom_equip_id = ?");
                    $stmtEquip->bind_param('s', $custom_equip_id);
                    $stmtEquip->execute();
                    $stmtEquip->bind_result($equipment_id);
                    $stmtEquip->fetch();
                    $stmtEquip->close();

                    if ($equipment_id !== null) {
                        // Insert new assignment (no operator_name)
                        $equipStmt = $connection->prepare("
                            INSERT INTO proj_eqp_assign_tbl
                            (project_id, equipment_id)
                            VALUES (?, ?)
                        ");
                        $equipStmt->bind_param('ii', $project_id, $equipment_id);
                        $equipStmt->execute();
                        $equipStmt->close();

                        // Optionally update equipment status
                        $updateEquipStmt = $connection->prepare("
                            UPDATE equip_tbl
                            SET deployment_status = 'Deployed', assigned_proj_id = ?
                            WHERE custom_equip_id = ?
                        ");
                        $updateEquipStmt->bind_param('is', $project_id, $custom_equip_id);
                        $updateEquipStmt->execute();
                        $updateEquipStmt->close();
                    }
                }
            }

            // --- NEW LOGIC: UPDATE EQUIPMENT STATUS WHEN PROJECT IS COMPLETED ---
            if ($proj_status === 'Completed') {
                $getEquipQuery = "SELECT equipment_id FROM proj_eqp_assign_tbl WHERE project_id = ?";
                $stmtGetEquip = $connection->prepare($getEquipQuery);
                $stmtGetEquip->bind_param('i', $project_id);
                $stmtGetEquip->execute();
                $resultEquip = $stmtGetEquip->get_result();

                while ($row = $resultEquip->fetch_assoc()) {
                    $equipment_id_to_update = $row['equipment_id'];
                    $updateStatusQuery = "UPDATE equip_tbl SET equip_status = 'Idle', deployment_status = 'Idle', assigned_proj_id = NULL WHERE equipment_id = ?";
                    $stmtUpdateStatus = $connection->prepare($updateStatusQuery);
                    $stmtUpdateStatus->bind_param('i', $equipment_id_to_update);
                    $stmtUpdateStatus->execute();
                    $stmtUpdateStatus->close();
                }
                $stmtGetEquip->close();
            }

// --- Handle new equipment assignments ---
if (isset($_POST['custom_equip_id']) && is_array($_POST['custom_equip_id'])) {
    foreach ($_POST['custom_equip_id'] as $key => $custom_equip_id) {
        $custom_equip_id = trim($custom_equip_id);
        if (!empty($custom_equip_id)) {
            // Get the equipment_id from the custom_equip_id
            $equipIdQuery = $connection->prepare("SELECT equipment_id FROM equip_tbl WHERE custom_equip_id = ?");
            $equipIdQuery->bind_param('s', $custom_equip_id);
            $equipIdQuery->execute();
            $equipIdResult = $equipIdQuery->get_result();
            
            if ($equipIdResult->num_rows > 0) {
                $equipment_id = $equipIdResult->fetch_assoc()['equipment_id'];
                
                // Insert into proj_eqp_assign_tbl
                $assignQuery = $connection->prepare("INSERT INTO proj_eqp_assign_tbl (project_id, equipment_id) VALUES (?, ?)");
                $assignQuery->bind_param('ii', $project_id, $equipment_id);
                
                if (!$assignQuery->execute()) {
                    error_log("Failed to assign equipment " . $custom_equip_id . " to project " . $project_id . ": " . $assignQuery->error);
                }
                $assignQuery->close();
            }
        }
    }
}

            header("Location: ProjectList.php?success=updated");
            exit();
        } else {
            die("Database update failed: " . mysqli_error($connection));
        }
    } else {
        die("Prepare statement failed: " . mysqli_error($connection));
    }
}

// If no valid action, redirect back
header("Location: ProjectList.php");
exit();
?>