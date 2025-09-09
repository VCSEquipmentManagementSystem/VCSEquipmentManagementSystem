<?php
include('./database.php');

// Helper to verify an employee exists
function employeeExists(mysqli $conn, int $id): bool
{
    $check = $conn->prepare("SELECT 1 FROM employee_tbl WHERE employee_id = ?");
    $check->bind_param('i', $id);
    $check->execute();
    $check->store_result();
    $exists = $check->num_rows > 0;
    $check->close();
    return $exists;
}

// Helper to verify equipment exists and get equipment_id
function getEquipmentId(mysqli $conn, string $custom_equip_id): ?int
{
    $check = $conn->prepare("SELECT equipment_id FROM equip_tbl WHERE custom_equip_id = ?");
    $check->bind_param('s', $custom_equip_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->bind_result($equipment_id);
        $check->fetch();
        $check->close();
        return $equipment_id;
    }

    $check->close();
    return null;
}

// NEW FUNCTION: Fetch all Project Engineers
function getProjectEngineers(mysqli $conn): array
{
    $engineers = [];
    $query = "SELECT employee_id, name FROM user_tbl WHERE role = 'Project Engineer'";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $engineers[] = $row;
        }
    }
    return $engineers;
}

// Handle AJAX request for getting equipment type
if (isset($_POST['action']) && $_POST['action'] == 'get_equipment_type') {
    if (isset($_POST['custom_equip_id']) && !empty($_POST['custom_equip_id'])) {
        $customEquipId = mysqli_real_escape_string($connection, $_POST['custom_equip_id']);

        $query = "SELECT et.equip_type_name
                  FROM equip_tbl e
                  INNER JOIN equip_type_tbl et ON e.equip_type_id = et.equip_type_id
                  WHERE e.custom_equip_id = '$customEquipId'";

        $result = mysqli_query($connection, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $response = array(
                'success' => true,
                'equip_type_name' => $row['equip_type_name']
            );
        } else {
            $response = array(
                'success' => false,
                'message' => 'Equipment type not found'
            );
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Function to redirect with a message
function redirectWithMessage(string $message, string $type = 'success')
{
    header("Location: ProjectList.php?message=" . urlencode($message) . "&type=" . urlencode($type));
    exit();
}

// Check if form is submitted
if (isset($_POST['submit'])) {
    // 1) Gather & normalize inputs
    $project_name   = trim($_POST['project_name']);
    $location       = trim($_POST['project_location'] ?? '');
    $client_name    = trim($_POST['clientname']);
    // Update this line to use employee_id from the dropdown
    $project_engineer_id = trim($_POST['project_engineer'] ?? ''); 
    $start_date     = $_POST['startdate'];
    $end_date       = $_POST['enddate'];

    // Validate required fields
    if (empty($project_name) || empty($client_name) || empty($start_date) || empty($end_date) || empty($project_engineer_id)) {
        redirectWithMessage("Please fill in all required fields", "danger");
    }

    // Validate dates
    if (strtotime($end_date) <= strtotime($start_date)) {
        redirectWithMessage("End date must be after start date", "danger");
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

    // 2) Insert into proj_sched_tbl
    $stmt = $connection->prepare("
        INSERT INTO proj_sched_tbl
        (project_name, project_location, client, project_engineer, start_date, end_date, proj_status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        'sssssss',
        $project_name,
        $location,
        $client_name,
        $project_engineer_name, // Use the fetched name here
        $start_date,
        $end_date,
        $proj_status
    );

    if (! $stmt->execute()) {
        redirectWithMessage("Project creation failed: " . $stmt->error, "danger");
    }

    // 3) Grab the new project_id
    $project_id = $stmt->insert_id;
    $stmt->close();

    // 4) Prepare assignment statement for employees
    $assignStmt = $connection->prepare("
        INSERT INTO assigned_employee_tbl
        (project_id, employee_id)
        VALUES (?, ?)
    ");
    // Check if prepare was successful
    if (!$assignStmt) {
        redirectWithMessage("Failed to prepare employee assignment statement: " . $connection->error, "danger");
    }
    $assignStmt->bind_param('ii', $project_id, $employee_id);

    // 5) Insert Project Engineer
    if (!empty($project_engineer_id)) {
        $employee_id = (int) $project_engineer_id;
        if ($employee_id > 0 && employeeExists($connection, $employee_id)) {
            if (!$assignStmt->execute()) {
                error_log("Failed to assign project engineer: " . $assignStmt->error);
            }
        }
    }

    // 6) Insert Lead Engineer
    if (!empty($_POST['lead_engineer'])) {
        $employee_id = (int) $_POST['lead_engineer'];
        if ($employee_id > 0 && employeeExists($connection, $employee_id)) {
            if (!$assignStmt->execute()) {
                error_log("Failed to assign lead engineer: " . $assignStmt->error);
            }
        }
    }

    // 7) Insert Workers[]
    if (!empty($_POST['workers']) && is_array($_POST['workers'])) {
        foreach ($_POST['workers'] as $wrkId) {
            $employee_id = (int) $wrkId;
            if ($employee_id > 0 && employeeExists($connection, $employee_id)) {
                if (!$assignStmt->execute()) {
                    error_log("Failed to assign worker: " . $assignStmt->error);
                }
            }
        }
    }

    $assignStmt->close();

    // 8) Handle Equipment Assignment
    foreach ($equip_ids as $custom_equip_id) {
        $custom_equip_id = trim($custom_equip_id);
        $equipment_id = getEquipmentId($connection, $custom_equip_id);
        if ($equipment_id !== null) {
            // Assign equipment to project in proj_eqp_assign_tbl
            $equipStmt = $connection->prepare("
                INSERT INTO proj_eqp_assign_tbl
                (project_id, equipment_id)
                VALUES (?, ?)
            ");
            if (!$equipStmt) {
                error_log("Failed to prepare equipment assignment statement: " . $connection->error);
                continue;
            }
            $equipStmt->bind_param('ii', $project_id, $equipment_id);
            if (!$equipStmt->execute()) {
                error_log("Equipment assignment failed for " . $custom_equip_id . ": " . $equipStmt->error);
            }
            $equipStmt->close();
        
            // Update the equipment status and assigned project ID in one query
            $updateStmt = $connection->prepare("
                UPDATE equip_tbl
                SET
                    equip_status = 'Active',
                    deployment_status = 'Deployed',
                    assigned_proj_id = ?
                WHERE
                    equipment_id = ?
            ");
            $updateStmt->bind_param('ii', $project_id, $equipment_id);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            error_log("Equipment with custom ID " . $custom_equip_id . " not found for assignment.");
        }
    }

    // 9) Redirect on success
    redirectWithMessage("Project created successfully", "success");
}

header("Location: ProjectList.php");
exit();
?>