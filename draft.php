<?php
// Include the database connection file
include 'db_connect.php';

// --- Function to fetch all reports from report_tbl and equip_usage_tbl ---
function getAllReports($conn)
{
    $reports = [];

    // Fetch reports from report_tbl (Maintenance, Repair, Breakdown)
    $sql_reports = "SELECT
                        report_id,
                        equipment_id,
                        report_type,
                        report_date,
                        report_status
                    FROM
                        report_tbl
                    ORDER BY
                        report_date DESC, report_id DESC"; // Order by date for display

    $result_reports = $conn->query($sql_reports);

    if ($result_reports && $result_reports->num_rows > 0) {
        while ($row = $result_reports->fetch_assoc()) {
            $reports[] = [
                'type' => 'report', // Differentiate between report_tbl and equip_usage_tbl
                'id' => $row['report_id'],
                'equipment_id' => htmlspecialchars($row['equipment_id']),
                'report_type' => htmlspecialchars($row['report_type']),
                'report_date' => htmlspecialchars($row['report_date']),
                'report_status' => htmlspecialchars($row['report_status'])
            ];
        }
    }

    // Fetch reports from equip_usage_tbl (Equipment Usage)
    $sql_usage = "SELECT
                      usage_id,
                      equipment_id,
                      log_date,
                      'Equipment Usage' AS report_type, -- Manually set report_type for consistency
                      'Resolved' AS report_status -- Assuming usage logs are 'Resolved' or completed
                  FROM
                      equip_usage_tbl
                  ORDER BY
                      log_date DESC, usage_id DESC"; // Order by date for display

    $result_usage = $conn->query($sql_usage);

    if ($result_usage && $result_usage->num_rows > 0) {
        while ($row = $result_usage->fetch_assoc()) {
            $reports[] = [
                'type' => 'usage', // Differentiate between report_tbl and equip_usage_tbl
                'id' => $row['usage_id'],
                'equipment_id' => htmlspecialchars($row['equipment_id']),
                'report_type' => htmlspecialchars($row['report_type']),
                'report_date' => htmlspecialchars($row['log_date']), // Use log_date for report_date
                'report_status' => htmlspecialchars($row['report_status'])
            ];
        }
    }

    // Sort all reports by date, then by ID (descending) to mix them properly
    usort($reports, function ($a, $b) {
        $dateA = strtotime($a['report_date']);
        $dateB = strtotime($b['report_date']);
        if ($dateA == $dateB) {
            return $b['id'] - $a['id']; // Sort by ID if dates are the same
        }
        return $dateB - $dateA; // Sort by date descending
    });

    return $reports;
}

// --- Function to fetch a single report by ID and type ---
function getReportDetails($conn, $reportId, $reportType)
{
    $reportDetails = null;

    if ($reportType === 'report') {
        $stmt = $conn->prepare("SELECT * FROM report_tbl WHERE report_id = ?");
        $stmt->bind_param("i", $reportId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $reportDetails = $result->fetch_assoc();
        }
        $stmt->close();
    } elseif ($reportType === 'usage') {
        $stmt = $conn->prepare("SELECT * FROM equip_usage_tbl WHERE usage_id = ?");
        $stmt->bind_param("i", $reportId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $reportDetails = $result->fetch_assoc();
        }
        $stmt->close();
    }
    return $reportDetails;
}

// --- Handle AJAX requests for fetching report details (for View/Edit modals) ---
if (isset($_GET['action']) && $_GET['action'] === 'get_report_details') {
    header('Content-Type: application/json');
    $reportId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $reportType = isset($_GET['type']) ? $_GET['type'] : '';

    $details = getReportDetails($conn, $reportId, $reportType);
    echo json_encode($details);
    exit; // Stop script execution after sending JSON response
}

// --- Handle form submissions for creating/updating reports (AJAX POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json'); // Respond with JSON for AJAX
    $response = ['success' => false, 'message' => 'An unknown error occurred.'];

    $action = $_POST['action'] ?? '';

    if ($action === 'create_report') {
        $report_type = $_POST['report_type'] ?? '';

        if ($report_type === 'Equipment Usage') {
            // Handle Equipment Usage report creation
            $equipment_id = $_POST['equipment_id'] ?? null;
            $project_id = $_POST['project_id'] ?? null;
            $log_date = $_POST['log_date'] ?? date('Y-m-d'); // Default to today
            $time_in = $_POST['time_in'] ?? null;
            $time_out = $_POST['time_out'] ?? null;
            $operating_hours = $_POST['operating_hours'] ?? null;
            $nature_of_work = $_POST['nature_of_work'] ?? '';
            $log_remarks = $_POST['log_remarks'] ?? '';
            $operator_id = $_POST['operator_id'] ?? null;

            if ($equipment_id && $project_id && $log_date && $time_in && $time_out && $operating_hours && $nature_of_work && $operator_id) {
                $stmt = $conn->prepare("INSERT INTO equip_usage_tbl (equipment_id, project_id, log_date, time_in, time_out, operating_hours, nature_of_work, log_remarks, operator_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iissdsssi", $equipment_id, $project_id, $log_date, $time_in, $time_out, $operating_hours, $nature_of_work, $log_remarks, $operator_id);

                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Equipment Usage Report added successfully!'];
                } else {
                    $response = ['success' => false, 'message' => 'Error adding Equipment Usage Report: ' . $stmt->error];
                }
                $stmt->close();
            } else {
                $response = ['success' => false, 'message' => 'Missing required fields for Equipment Usage Report.'];
            }
        } else { // Maintenance, Repair, Breakdown
            // Handle Maintenance, Repair, Breakdown report creation
            $equipment_id = $_POST['equipment_id'] ?? null;
            $report_date = $_POST['report_date'] ?? date('Y-m-d');
            $operator_id = $_POST['operator_id'] ?? null;
            $report_by = $_POST['report_by'] ?? null; // Assuming this is the user who reported
            $inspected_by = $_POST['inspected_by'] ?? null;
            $problem_encountered = $_POST['problem_encountered'] ?? '';
            $final_diagnosis = $_POST['final_diagnosis'] ?? '';
            $details_of_workdone = $_POST['details_of_workdone'] ?? '';
            $remarks_report_details = $_POST['remarks_report_details'] ?? '';
            $report_status = $_POST['report_status'] ?? 'Open'; // Default status

            // Optional fields that might not be present in initial report creation
            $repaired_by = $_POST['repaired_by'] ?? null;
            $purchase_req_id = $_POST['purchase_req_id'] ?? null;
            $part_id = $_POST['part_id'] ?? null;
            $part_description = $_POST['part_description'] ?? '';
            $quantity = $_POST['quantity'] ?? null;
            $unit = $_POST['unit'] ?? '';
            $last_replacement = $_POST['last_replacement'] ?? null;
            $conducted_by_work = $_POST['conducted_by_work'] ?? null;
            $date_started = $_POST['date_started'] ?? null;
            $time_started = $_POST['time_started'] ?? null;
            $date_completed = $_POST['date_completed'] ?? null;
            $time_completed = $_POST['time_completed'] ?? null;
            $conducted_by_acceptance = $_POST['conducted_by_acceptance'] ?? '';
            $accepted_by = $_POST['accepted_by'] ?? null;
            $job_completion_verified_by = $_POST['job_completion_verified_by'] ?? null;
            $remarks_job_completion = $_POST['remarks_job_completion'] ?? '';

            if ($equipment_id && $report_type && $report_date && $operator_id && $report_by && $inspected_by && $problem_encountered && $final_diagnosis && $details_of_workdone) {
                $stmt = $conn->prepare("INSERT INTO report_tbl (equipment_id, report_type, report_date, operator_id, report_by, inspected_by, problem_encountered, final_diagnosis, details_of_workdone, remarks_report_details, report_status, repaired_by, purchase_req_id, part_id, part_description, quantity, unit, last_replacement, conducted_by_work, date_started, time_started, date_completed, time_completed, conducted_by_acceptance, accepted_by, job_completion_verified_by, remarks_job_completion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                // 's' for string, 'i' for integer, 'd' for double (decimal)
                $stmt->bind_param(
                    "isiiissssssiiisississsssss",
                    $equipment_id,
                    $report_type,
                    $report_date,
                    $operator_id,
                    $report_by,
                    $inspected_by,
                    $problem_encountered,
                    $final_diagnosis,
                    $details_of_workdone,
                    $remarks_report_details,
                    $report_status,
                    $repaired_by,
                    $purchase_req_id,
                    $part_id,
                    $part_description,
                    $quantity,
                    $unit,
                    $last_replacement,
                    $conducted_by_work,
                    $date_started,
                    $time_started,
                    $date_completed,
                    $time_completed,
                    $conducted_by_acceptance,
                    $accepted_by,
                    $job_completion_verified_by,
                    $remarks_job_completion
                );

                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Report added successfully!'];
                } else {
                    $response = ['success' => false, 'message' => 'Error adding report: ' . $stmt->error];
                }
                $stmt->close();
            } else {
                $response = ['success' => false, 'message' => 'Missing required fields for Report.'];
            }
        }
    } elseif ($action === 'update_report') {
        $report_id = $_POST['report_id'] ?? null;
        $report_type_original = $_POST['report_type_original'] ?? ''; // To know which table to update

        if ($report_type_original === 'Equipment Usage') {
            // Handle Equipment Usage report update
            $equipment_id = $_POST['equipment_id'] ?? null;
            $project_id = $_POST['project_id'] ?? null;
            $log_date = $_POST['log_date'] ?? null;
            $time_in = $_POST['time_in'] ?? null;
            $time_out = $_POST['time_out'] ?? null;
            $operating_hours = $_POST['operating_hours'] ?? null;
            $nature_of_work = $_POST['nature_of_work'] ?? '';
            $log_remarks = $_POST['log_remarks'] ?? '';
            $operator_id = $_POST['operator_id'] ?? null;

            if ($report_id && $equipment_id && $project_id && $log_date && $time_in && $time_out && $operating_hours && $nature_of_work && $operator_id) {
                $stmt = $conn->prepare("UPDATE equip_usage_tbl SET equipment_id=?, project_id=?, log_date=?, time_in=?, time_out=?, operating_hours=?, nature_of_work=?, log_remarks=?, operator_id=? WHERE usage_id=?");
                $stmt->bind_param("iissdsssi", $equipment_id, $project_id, $log_date, $time_in, $time_out, $operating_hours, $nature_of_work, $log_remarks, $operator_id, $report_id);

                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Equipment Usage Report updated successfully!'];
                } else {
                    $response = ['success' => false, 'message' => 'Error updating Equipment Usage Report: ' . $stmt->error];
                }
                $stmt->close();
            } else {
                $response = ['success' => false, 'message' => 'Missing required fields for Equipment Usage Report update.'];
            }
        } else { // Maintenance, Repair, Breakdown
            // Handle Maintenance, Repair, Breakdown report update
            $equipment_id = $_POST['equipment_id'] ?? null;
            $report_type = $_POST['report_type'] ?? null; // Can change report type on update
            $report_date = $_POST['report_date'] ?? null;
            $operator_id = $_POST['operator_id'] ?? null;
            $report_by = $_POST['report_by'] ?? null;
            $inspected_by = $_POST['inspected_by'] ?? null;
            $repaired_by = $_POST['repaired_by'] ?? null;
            $report_description = $_POST['report_description'] ?? '';
            $report_status = $_POST['report_status'] ?? 'Open';
            $problem_encountered = $_POST['problem_encountered'] ?? '';
            $final_diagnosis = $_POST['final_diagnosis'] ?? '';
            $details_of_workdone = $_POST['details_of_workdone'] ?? '';
            $remarks_report_details = $_POST['remarks_report_details'] ?? '';
            $purchase_req_id = $_POST['purchase_req_id'] ?? null;
            $part_id = $_POST['part_id'] ?? null;
            $part_description = $_POST['part_description'] ?? '';
            $quantity = $_POST['quantity'] ?? null;
            $unit = $_POST['unit'] ?? '';
            $last_replacement = $_POST['last_replacement'] ?? null;
            $conducted_by_work = $_POST['conducted_by_work'] ?? null;
            $date_started = $_POST['date_started'] ?? null;
            $time_started = $_POST['time_started'] ?? null;
            $date_completed = $_POST['date_completed'] ?? null;
            $time_completed = $_POST['time_completed'] ?? null;
            $conducted_by_acceptance = $_POST['conducted_by_acceptance'] ?? '';
            $accepted_by = $_POST['accepted_by'] ?? null;
            $job_completion_verified_by = $_POST['job_completion_verified_by'] ?? null;
            $remarks_job_completion = $_POST['remarks_job_completion'] ?? '';


            if ($report_id && $equipment_id && $report_type && $report_date && $operator_id && $report_by && $inspected_by) {
                $stmt = $conn->prepare("UPDATE report_tbl SET equipment_id=?, report_type=?, report_date=?, operator_id=?, report_by=?, inspected_by=?, repaired_by=?, report_description=?, report_status=?, problem_encountered=?, final_diagnosis=?, details_of_workdone=?, remarks_report_details=?, purchase_req_id=?, part_id=?, part_description=?, quantity=?, unit=?, last_replacement=?, conducted_by_work=?, date_started=?, time_started=?, date_completed=?, time_completed=?, conducted_by_acceptance=?, accepted_by=?, job_completion_verified_by=?, remarks_job_completion=? WHERE report_id=?");

                $stmt->bind_param(
                    "isiiisssssssssisississsssssi",
                    $equipment_id,
                    $report_type,
                    $report_date,
                    $operator_id,
                    $report_by,
                    $inspected_by,
                    $repaired_by,
                    $report_description,
                    $report_status,
                    $problem_encountered,
                    $final_diagnosis,
                    $details_of_workdone,
                    $remarks_report_details,
                    $purchase_req_id,
                    $part_id,
                    $part_description,
                    $quantity,
                    $unit,
                    $last_replacement,
                    $conducted_by_work,
                    $date_started,
                    $time_started,
                    $date_completed,
                    $time_completed,
                    $conducted_by_acceptance,
                    $accepted_by,
                    $job_completion_verified_by,
                    $remarks_job_completion,
                    $report_id
                );

                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Report updated successfully!'];
                } else {
                    $response = ['success' => false, 'message' => 'Error updating report: ' . $stmt->error];
                }
                $stmt->close();
            } else {
                $response = ['success' => false, 'message' => 'Missing required fields for Report update.'];
            }
        }
    }
    echo json_encode($response);
    exit;
}

// Fetch all reports to display in the table
$allReports = getAllReports($conn);

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap");

        body {
            background-color: #f0f2f5;
            font-family: "Poppins", sans-serif;
        }

        img {
            height: 50px;
            width: 50px;
        }

        .offcanvas {
            --bs-offcanvas-width: 280px;
        }

        .card-stats {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border: none;
        }

        .card-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }

        .bg-green-light {
            background-color: rgba(25, 195, 125, 0.2);
        }

        .text-green {
            color: #19c37d;
        }

        .bg-red-light {
            background-color: rgba(255, 107, 107, 0.2);
        }

        .text-red {
            color: #ff6b6b;
        }

        .bg-yellow-light {
            background-color: rgba(255, 196, 0, 0.2);
        }

        .text-yellow {
            color: #ffc400;
        }

        .card-title {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .card-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .card-period {
            font-size: 0.75rem;
            color: #6c757d;
        }

        .reports-container {
            background-color: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .reports-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .reports-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #212529;
        }

        .table thead th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            border-bottom: none;
        }

        .table td,
        .table th {
            vertical-align: middle;
            padding: 0.75rem;
        }

        .action-button {
            color: #6c757d;
            background: none;
            border: none;
            cursor: pointer;
        }

        .action-button:hover {
            color: #495057;
        }

        .search-container {
            position: relative;
            max-width: 300px;
        }

        .search-container i {
            position: absolute;
            left: 10px;
            top: 10px;
            color: #6c757d;
        }

        .search-input {
            padding-left: 35px;
            border-radius: 6px;
            border: 1px solid #ced4da;
        }

        .col-md-4:hover {
            transform: translateY(-5px);
        }

        .form-control {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.10);
        }

        /* Custom alert/message box styling */
        .message-box {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: none;
            /* Hidden by default */
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        .message-box.show {
            display: block;
            opacity: 1;
        }

        .message-box.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message-box.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <!-- Nav -->
        <nav class="navbar bg-success fixed-top">
            <div class="container-fluid">
                <div>
                    <button class="navbar-toggler bg-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarNav" aria-controls="sidebarNav">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <a class="navbar-brand fw-bold text-white" href="#">
                        <img src="./Pictures/LOGO.png" data-bs-toggle="tooltip" data-bs-placement="top" title="Logo" alt="Viking Logo" style="width: 30px; height: 30px;">
                        Viking
                    </a>
                </div>
                <!-- Sidebar Navigation -->
                <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarNav" aria-labelledby="sidebarNavLabel">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title fw-bold" id="sidebarNavLabel">
                            <img src="./Pictures/LOGO.png" alt="Viking Logo" style="width: 50px; height: 50px;">
                            EMS
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>

                    <div class="offcanvas-body">
                        <ul class="navbar-nav flex-column">
                            <form class="d-flex mb-3" role="search">
                                <input class="form-control me-2" list="datalistOptions" id="searchDataList" name="searchbar" type="search" placeholder="Search" aria-label="Search">
                                <button class="btn btn-secondary" type="submit">Search</button>
                            </form>
                            <li class="nav-item mt-3">
                                <a href="Profile.php" class="nav-link">
                                    <i class="fa-solid fa-user"></i>
                                    <span class="fw-bold">Profile</span>
                                </a>
                            </li>
                            <li class="nav-item mt-2">
                                <a href="ModeratorDashboard.php" class="nav-link">
                                    <i class="fa-solid fa-house"></i>
                                    <span class="fw-bold">Dashboard</span>
                                </a>
                            </li>
                            <li class="nav-item mt-3">
                                <a href="ProjectList.php" class="nav-link">
                                    <i class="fa-solid fa-table-list"></i>
                                    <span class="fw-bold">Project List</span>
                                </a>
                            </li>
                            <li class="nav-item mt-3">
                                <a href="EquipmentProfiling.php" class="nav-link">
                                    <i class="fa-solid fa-list-check"></i>
                                    <span class="fw-bold">Equipment Profiling</span>
                                </a>
                            </li>
                            <li class="nav-item dropdown mt-3">
                                <a class="nav-link dropdown-toggle fw-bold" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-warehouse"></i>
                                    <span class="fw-bold">Inventory</span>
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item text-secondary" href="Inventory.php">
                                            <i class="fa-solid fa-boxes-stacked"></i>
                                            <span class="fw-bold">Main Inventory</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item text-secondary" href="SupplierInformation.php">
                                            <i class="fa-solid fa-users"></i>
                                            <span class="fw-bold">Supplier Information</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item text-secondary" href="PurchaseRequest.php">
                                            <i class="fa-solid fa-money-bill-1"></i>
                                            <span class="fw-bold">Purchase Request</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="nav-item mt-3">
                                <a href="EquipmentAvailability.php" class="nav-link">
                                    <i class="fa-solid fa-truck-fast"></i>
                                    <span class="fw-bold">Equipment Availability</span>
                                </a>
                            </li>
                            <li class="nav-item mt-3">
                                <a href="MaintenanceScheduling.php" class="nav-link">
                                    <i class="fa-solid fa-calendar-days"></i>
                                    <span class="fw-bold">Maintenance Scheduling</span>
                                </a>
                            </li>
                            <li class="nav-item mt-3">
                                <a href="Reports.php" class="nav-link">
                                    <i class="fa-solid fa-paper-plane"></i>
                                    <span class="fw-bold">Reports</span>
                                </a>
                            </li>
                            <li class="nav-item mt-3">
                                <a href="ManageUser.php" class="nav-link">
                                    <i class="fa-solid fa-users"></i>
                                    <span class="fw-bold">Manage Users</span>
                                </a>
                            </li>
                            <li class="nav-item mt-3">
                                <a href="logout.php" class="link-danger text-decoration-none" id="logout">
                                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                                    <span class="fw-bold">Logout</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <!-- Right side of navbar -->
                <div class="d-flex align-items-center">
                    <!-- Notifications -->
                    <div class="dropdown me-2">
                        <button class="btn position-relative" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-bell" data-bs-toggle="tooltip" data-bs-placement="top" title="Notification"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 10px; padding: 4px;">
                                99+
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                            <li>
                                <a class="dropdown-item d-flex" href="#">
                                    <span>Notification</span>
                                    <i class="fa-solid fa-xmark ms-auto text-danger"></i>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex" href="#">
                                    <span>Notification</span>
                                    <i class="fa-solid fa-xmark ms-auto text-danger"></i>
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item" href="#">
                                    <i class="fa-solid fa-list-ul"></i> See all
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#">
                                    <i class="fa-solid fa-check"></i> Mark all read
                                </a>
                            </li>
                        </ul>
                    </div>
                    <!-- User menu -->
                    <div class="dropdown">
                        <button class="btn dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-user" data-bs-toggle="tooltip" data-bs-placement="top" title="User"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="Profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="#">Settings</a></li>
                            <li><a class="dropdown-item" id="themeToggle">Toggle dark mode</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a href="logout.php" class="dropdown-item text-danger">
                                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
        <div class="row container-header mt-5">
            <div class="title-header d-flex justify-content-between">
                <h3 class="mb-4 fw-bold mt-3">
                    Reports
                </h3>
            </div>
        </div>
        <div class="d-flex justify-content-between mb-3">
            <div class="search-container me-2">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control search-input" placeholder="Search">
            </div>
            <div class="justify-content-end mb-3">
                <button class="btn btn-outline-secondary me-2" type="submit" id="archive-selected">
                    <i class="fa-solid fa-box-archive"></i> Archive Selected
                </button>
                <button class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#archiveModal">
                    <i class="fa-solid fa-box-archive"></i> View Archive
                    <span class="badge bg-danger rounded-pill archive-badge" id="archiveCount">
                        <!-- This will be dynamically updated with the number of archived reports -->
                        0
                    </span>
                </button>
                <!-- Add Reports Modal -->
                <!-- Button trigger modal -->
                <button type="button" class="btn btn-success" data-bs-toggle="modal" id="AddReport" data-bs-target="#staticBackdrop1">
                    <i class="fas fa-plus me-1"></i> Add Report
                </button>
            </div>
        </div>
        <!-- Stats Cards -->
        <div class="row mb-4">
            <!-- Total Reports -->
            <div class="col-md-4 mb-3">
                <div class="card border-success h-100">
                    <div class="card-body text-center d-flex justify-content-between align-items-center">
                        <div class="labels">
                            <h6 class="card-title fw-bold">Total Reports</h6>
                            <h2 class="card-value fw-bold text-success">8</h2>
                            <p class="card-period">For this month</p>
                        </div>
                        <div class="svg text-success ">
                            <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 16 16">
                                <path fill="none" stroke="currentColor" stroke-linejoin="round" d="M7.563 1.545H2.5v10.91h9V5.364M7.563 1.545L11.5 5.364M7.563 1.545v3.819H11.5m-7 9.136h9v-7M4 7.5h6M4 5h2m-2 5h6" stroke-width="1" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Report Pedning Review -->
            <div class="col-md-4 mb-3">
                <div class="card border-success h-100">
                    <div class="card-body text-center d-flex justify-content-between align-items-center">
                        <div class="labels">
                            <h6 class="card-title fw-bold">Reports Pending Review</h6>
                            <h2 class="card-value fw-bold text-warning">4</h2>
                            <p class="card-period">For this month</p>
                        </div>
                        <div class="svg text-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 24 24">
                                <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
                                    <path d="M8 5H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h5.697M18 14v4h4m-4-7V7a2 2 0 0 0-2-2h-2" />
                                    <path d="M8 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v0a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2m6 13a4 4 0 1 0 8 0a4 4 0 1 0-8 0m-6-7h4m-4 4h3" />
                                </g>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Ongoing Report -->
            <div class="col-md-4 mb-3">
                <div class="card border-success h-100">
                    <div class="card-body text-center d-flex justify-content-between align-items-center">
                        <div class="labels">
                            <h6 class="card-title fw-bold">Ongoing Report</h6>
                            <h2 class="card-value fw-bold text-primary">10</h2>
                            <p class="card-period">For this month</p>
                        </div>
                        <div class="svg text-primary">
                            <path fill="currentColor" d="M9 7H5.145a8.5 8.5 0 0 1 8.274-3.387a.5.5 0 0 0 .162-.986A10 10 0 0 0 12 2.5a9.52 9.52 0 0 0-7.5 3.677V2.5a.5.5 0 0 0-1 0v5A.5.5 0 0 0 4 8h5a.5.5 0 0 0 0-1m-1.5 7.5a.5.5 0 0 0-.5.5v3.855a8.5 8.5 0 0 1-3.387-8.274a.5.5 0 0 0-.986-.162a9.52 9.52 0 0 0 3.55 9.081H2.5a.5.5 0 0 0 0 1h5A.5.5 0 0 0 8 20v-5a.5.5 0 0 0-.5-.5M20 16h-5a.5.5 0 0 0 0 1h3.855a8.5 8.5 0 0 1-8.274 3.387a.5.5 0 0 0-.162.986A10 10 0 0 0 12 21.5a9.52 9.52 0 0 0 7.5-3.677V21.5a.5.5 0 0 0 1 0v-5a.5.5 0 0 0-.5-.5m1.5-12.5h-5a.5.5 0 0 0-.5.5v5a.5.5 0 0 0 1 0V5.14a8.3 8.3 0 0 1 2.358 2.612A8.44 8.44 0 0 1 20.5 12q0 .714-.113 1.419a.499.499 0 1 0 .986.162A10 10 0 0 0 21.5 12a9.44 9.44 0 0 0-1.275-4.747A9.3 9.3 0 0 0 17.828 4.5H21.5a.5.5 0 0 0 0-1" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports Section -->
        <div class="reports-container">
            <div class="reports-header">
                <div class="d-flex">
                    <!-- Add Reports Modal -->
                    <div class="modal fade" id="staticBackdrop1" data-bs-backdrop="static" data-bs-keyboard="false"
                        tabindex="-1" aria-labelledby="staticBackdropLabel1" aria-hidden="true">
                        <div class="modal-dialog modal-md">
                            <div class="modal-content">
                                <div class="modal-header bg-success">
                                    <h5 class="modal-title fw-bold text-white" id="exampleModalLabel"> +Add report</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close">
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <!-- Reported by -->
                                    <label for="UserInput" class="form-label fw-bold">Reported by: <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="UserInput" name="report_by_name" required>
                                    <!-- Report Type -->
                                    <label for="ReportInput" class="form-label fw-bold">Report type: <span class="text-danger">*</span></label>
                                    <select name="report_type_select" id="ReportInput" class="form-select dropdown-toggle" required>
                                        <option selected disabled value="">-- Select Type of Report --</option>
                                        <option value="Maintenance">Maintenance</option>
                                        <option value="Repair">Repair</option>
                                        <option value="Breakdown">Breakdown</option>
                                        <option value="Equipment Usage">Equipment Usage</option>
                                    </select>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" id="NextModal">Next</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Job Order Modal/Modal 2  -->
                    <div class="modal fade" id="modal2" data-bs-backdrop="static" data-bs-keyboard="false"
                        tabindex="-1" aria-labelledby="staticBackdropLabel2" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-success">
                                    <h5 class="modal-title fw-bold text-white">
                                        Job Order
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" id="modal2_report_type">
                                    <input type="hidden" id="modal2_report_by_name">
                                    <!-- Date -->
                                    <label for="DateInput" class="form-label fw-bold">Date: <span class="text-danger">*</span></label>
                                    <input type="date" id="DateInput" class="form-control" required>
                                    <!-- Equipment ID -->
                                    <label for="EquipmentIdInput" class="form-label fw-bold">Equipment ID: <span class="text-danger">*</span></label>
                                    <select name="equipmentID" id="EquipmentIdInput" class="form-select" required>
                                        <option selected disabled value="">-- Select Equipment ID --</option>
                                        <option value="1">BH38 CAT3200B</option>
                                        <option value="2">Excavator 1</option>
                                        <option value="3">Bulldozer 2</option>
                                    </select>
                                    <!-- Operator -->
                                    <label for="OperatorInput" class="form-label fw-bold">Operator: <span class="text-danger">*</span></label>
                                    <select name="operatorName" id="OperatorInput" required class="form-select">
                                        <option selected disabled value="">-- Select Operator --</option>
                                        <option value="1">John Doe</option>
                                        <option value="2">Jane Smith</option>
                                    </select>
                                    <!-- Inspect by -->
                                    <label for="InspectInput" class="form-label fw-bold">Inspect by: <span class="text-danger">*</span></label>
                                    <input type="text" name="inspectBy" class="form-control" id="InspectInput" required>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" id="modal2BackBtn">Back</button>
                                    <button type="button" class="btn btn-secondary" id="modal2NextBtn">Next</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Problem Diagnosis/Modal 3 -->
                    <div class="modal fade" id="staticBackdrop3" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
                        aria-labelledby="staticBackdropLabel3" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-success">
                                    <h5 class="modal-title fw-bold text-white">Problem Diagnosis</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" id="modal3_report_type">
                                    <input type="hidden" id="modal3_report_by_name">
                                    <input type="hidden" id="modal3_date">
                                    <input type="hidden" id="modal3_equipment_id">
                                    <input type="hidden" id="modal3_operator_id">
                                    <input type="hidden" id="modal3_inspected_by">
                                    <!-- Problem Encountered -->
                                    <label for="ProblemEncounteredInput" class="form-label fw-bold">Problem Encountered: <span class="text-danger">*</span></label>
                                    <input type="text" name="problemEncounter" class="form-control" id="ProblemEncounteredInput" required>
                                    <!-- Final Diagnosis -->
                                    <label for="FinalDiagnosisInput" class="form-label fw-bold">Final Diagnosis: <span class="text-danger">*</span></label>
                                    <input type="text" name="finalDiagnosis" class="form-control" id="FinalDiagnosisInput" required>
                                    <!-- Details of workdone -->
                                    <label for="DetailsOfWorkdoneInput" class="form-label fw-bold">Details of workdone: <span class="text-danger">*</span></label>
                                    <input type="text" name="detailsOfWork" class="form-control" id="DetailsOfWorkdoneInput" required>
                                    <!-- Remarks -->
                                    <label for="RemarksInput" class="form-label fw-bold">Remarks: (Optional)</label>
                                    <textarea name="remarks_report_details" class="form-control" id="RemarksInput"></textarea>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" id="modal3BackBtn">Back</button>
                                    <button type="button" class="btn btn-secondary" id="modal3NextBtn">Next</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Spare Part/Materials Requirement/Modal 4 -->
                    <div class="modal fade" id="staticBackdrop4" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
                        aria-labelledby="staticBackdropLabel4" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-success">
                                    <h5 class="modal-title fw-bold text-white">Spare Part/Materials Requirement</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <input type="hidden" id="modal4_report_type">
                                    <input type="hidden" id="modal4_report_by_name">
                                    <input type="hidden" id="modal4_date">
                                    <input type="hidden" id="modal4_equipment_id">
                                    <input type="hidden" id="modal4_operator_id">
                                    <input type="hidden" id="modal4_inspected_by">
                                    <input type="hidden" id="modal4_problem_encountered">
                                    <input type="hidden" id="modal4_final_diagnosis">
                                    <input type="hidden" id="modal4_details_of_workdone">
                                    <input type="hidden" id="modal4_remarks_report_details">
                                    <!-- Part Details -->
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label for="partId" class="form-label fw-bold">Part Name: (Optional)</label>
                                            <select name="partName" id="partId" class="form-select">
                                                <option selected disabled value="">-- Select Part Name --</option>
                                                <option value="1">Engine Oil</option>
                                                <option value="2">Air Filter</option>
                                            </select>
                                        </div>
                                        <div class="col-md-5 mb-3">
                                            <label for="description" class="form-label fw-bold">Description (Optional)</label>
                                            <input type="text" name="part_description" class="form-control" id="description">
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label for="qty" class="form-label fw-bold">Qty: (Optional)</label>
                                            <input type="number" name="quantity" class="form-control" id="qty">
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label for="unit" class="form-label fw-bold">Unit: (Optional)</label>
                                            <input type="text" name="unit" class="form-control" id="unit">
                                        </div>
                                    </div>
                                    <!-- Last Replacement -->
                                    <div class="mb-3">
                                        <label for="lastReplacement" class="form-label fw-bold">Last Replacement: (Optional)</label>
                                        <input type="date" name="last_replacement" class="form-control" id="lastReplacement">
                                    </div>
                                    <!-- Purchase Request ID (Optional) -->
                                    <div class="mb-3">
                                        <label for="purchaseReqId" class="form-label fw-bold">Purchase Request ID: (Optional)</label>
                                        <input type="number" name="purchase_req_id" class="form-control" id="purchaseReqId">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" id="modal4BackBtn">Back</button>
                                    <button type="button" class="btn btn-secondary" id="modal4NextBtn">Next</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Work Details/Modal 5 -->
                    <div class="modal fade" id="staticBackdrop5" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdrop" aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <div class="modal-header bg-success">
                                    <h5 class="modal-title fw-bold text-white" id="staticBackdropLabel5">Work Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close">
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" id="modal5_report_type">
                                    <input type="hidden" id="modal5_report_by_name">
                                    <input type="hidden" id="modal5_date">
                                    <input type="hidden" id="modal5_equipment_id">
                                    <input type="hidden" id="modal5_operator_id">
                                    <input type="hidden" id="modal5_inspected_by">
                                    <input type="hidden" id="modal5_problem_encountered">
                                    <input type="hidden" id="modal5_final_diagnosis">
                                    <input type="hidden" id="modal5_details_of_workdone">
                                    <input type="hidden" id="modal5_remarks_report_details">
                                    <input type="hidden" id="modal5_part_id">
                                    <input type="hidden" id="modal5_part_description">
                                    <input type="hidden" id="modal5_quantity">
                                    <input type="hidden" id="modal5_unit">
                                    <input type="hidden" id="modal5_last_replacement">
                                    <input type="hidden" id="modal5_purchase_req_id">
                                    <!-- Conducted by -->
                                    <label for="ConductedByInput" class="form-label fw-bold">Conducted by: <span class="text-danger">*</span></label>
                                    <input type="text" name="conducted_by_work" class="form-control" id="ConductedByInput" required>
                                    <!-- Date Started -->
                                    <label for="DateStartedInput" class="form-label fw-bold">Date started: <span class="text-danger">*</span></label>
                                    <input type="date" name="date_started" class="form-control" id="DateStartedInput" required>
                                    <!-- Date Completed -->
                                    <label for="DateCompletedInput" class="form-label fw-bold">Date completed: <span class="text-danger">*</span></label>
                                    <input type="date" name="date_completed" class="form-control" id="DateCompletedInput" required>
                                    <!-- Time Started  -->
                                    <label for="TimeStartedInput" class="form-label fw-bold">Time started: <span class="text-danger">*</span></label>
                                    <input type="time" name="time_started" class="form-control" id="TimeStartedInput" required>
                                    <!-- Time Completed -->
                                    <label for="TimeCompletedInput" class="form-label fw-bold">Time completed: <span class="text-danger">*</span></label>
                                    <input type="time" name="time_completed" class="form-control" id="TimeCompletedInput" required>
                                    <!-- Trial run/ Turn Over -->
                                    <h4 class="fw-bold mt-3">
                                        Trial Run/Turn Over
                                    </h4>
                                    <!-- Conducted by Acceptance -->
                                    <label for="ConductedByAcceptanceInput" class="form-label fw-bold">Conducted by (Acceptance): <span class="text-danger">*</span></label>
                                    <input type="text" name="conducted_by_acceptance" class="form-control mt-2" id="ConductedByAcceptanceInput" required>
                                    <!-- Accepted by -->
                                    <label for="AcceptedByInput" class="form-label fw-bold">Accepted by: <span class="text-danger">*</span></label>
                                    <select name="accepted_by" id="AcceptedByInput" class="form-select" required>
                                        <option selected disabled value="">-- Select Accepted by --</option>
                                        <option value="1">Manager A</option>
                                        <option value="2">Supervisor B</option>
                                    </select>
                                    <!-- Job Completion -->
                                    <label for="JobCompletionInput" class="form-label fw-bold">Job Completion Verified by: <span class="text-danger">*</span></label>
                                    <select name="job_completion_verified_by" id="JobCompletionInput" class="form-select" required>
                                        <option selected disabled value="">-- Select Job Completion Verified by --</option>
                                        <option value="1">Engineer X</option>
                                        <option value="2">Technician Y</option>
                                    </select>
                                    <!-- Remarks Job Completion -->
                                    <div class="card mt-2">
                                        <div class="card-body">
                                            <label for="RemarksJobCompletionInput" class="form-label fw-bold">Remarks (Job Completion): (Optional)</label>
                                            <textarea name="remarks_job_completion" class="form-control" id="RemarksJobCompletionInput"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" id="modal5BackBtn">Back</button>
                                    <button type="button" class="btn btn-success" id="submitReportBtn">Submit</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Equipment usage report/Modal 6 -->
                    <div class="modal fade" id="staticBackdrop6" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdrop" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-success">
                                    <h5 class="modal-title fw-bold text-white" id="staticBackdropLabel6">Equipment Usage Report</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close">
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" id="modal6_report_by_name">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <!-- Equipment ID -->
                                            <label for="equipmentIDinputUsage" class="form-label fw-bold">Equipment ID: <span class="text-danger">*</span></label>
                                            <select name="equipmentID" id="equipmentIDinputUsage" required class="form-select">
                                                <option selected disabled value="">-- Select Equipment ID --</option>
                                                <option value="1">BH38 CAT3200B</option>
                                                <option value="2">Excavator 1</option>
                                                <option value="3">Bulldozer 2</option>
                                            </select>
                                            <!-- Project ID -->
                                            <label for="projectIDinputUsage" class="form-label fw-bold">Project Name: <span class="text-danger">*</span></label>
                                            <select name="projectName" id="projectIDinputUsage" required class="form-select">
                                                <option selected disabled value="">-- Select Project Name --</option>
                                                <option value="1">Project A</option>
                                                <option value="2">Project B</option>
                                            </select>
                                            <!-- Date -->
                                            <label for="logDateInputUsage" class="form-label fw-bold">Log Date: <span class="text-danger">*</span></label>
                                            <input type="date" name="logDate" class="form-control" id="logDateInputUsage" required>
                                            <!-- Time in  -->
                                            <label for="TimeInInputUsage" class="form-label fw-bold">Time in: <span class="text-danger">*</span></label>
                                            <input type="time" name="timeIn" class="form-control" id="TimeInInputUsage" required>
                                            <!-- Time out -->
                                            <label for="TimeOutInputUsage" class="form-label fw-bold">Time out: <span class="text-danger">*</span></label>
                                            <input type="time" name="timeOut" class="form-control" id="TimeOutInputUsage" required>
                                        </div>
                                        <div class="col-md-6">
                                            <!-- Operating Hours -->
                                            <label for="OperatingHoursInputUsage" class="form-label fw-bold">Operating Hours: <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" name="operatingHours" class="form-control" id="OperatingHoursInputUsage" required>
                                            <!-- Nature of Work -->
                                            <label for="NatureOfWorkInputUsage" class="form-label fw-bold">Nature Of Work: <span class="text-danger">*</span></label>
                                            <input type="text" name="natureOfWork" class="form-control" id="NatureOfWorkInputUsage" required>
                                            <!-- Log Remarks -->
                                            <label for="LogRemarksInputUsage" class="form-label fw-bold">Log Remarks: (Optional)</label>
                                            <textarea name="logRemarks" class="form-control" id="LogRemarksInputUsage" placeholder="Enter any remarks or notes"></textarea>
                                            <!-- Operator -->
                                            <label for="OperatorInputUsage" class="form-label fw-bold">Operator: <span class="text-danger">*</span></label>
                                            <select name="operatorName" id="OperatorInputUsage" required class="form-select">
                                                <option selected disabled value="">-- Select Operator Name --</option>
                                                <option value="1">Operator Alpha</option>
                                                <option value="2">Operator Beta</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" id="modal6BackBtn">Back</button>
                                    <button type="button" class="btn btn-success" id="submitUsageReportBtn">Submit</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Reports Table -->
            <div class="table-responsive">
                <table class="table text-center" id="reportTable">
                    <thead>
                        <tr>
                            <th>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                </div>
                            </th>
                            <th>Report ID</th>
                            <th>Equipment ID</th>
                            <th>Report Type</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($allReports)): ?>
                            <?php foreach ($allReports as $report): ?>
                                <tr data-report-id="<?= $report['id'] ?>" data-report-type-db="<?= $report['type'] ?>">
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><?= $report['id'] ?></td>
                                    <td><?= $report['equipment_id'] ?></td>
                                    <td><?= $report['report_type'] ?></td>
                                    <td><?= date('M d, Y', strtotime($report['report_date'])) ?></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        switch ($report['report_status']) {
                                            case 'Open':
                                                $status_class = 'bg-danger';
                                                break;
                                            case 'In Progress':
                                                $status_class = 'bg-warning text-dark';
                                                break;
                                            case 'Resolved':
                                                $status_class = 'bg-success';
                                                break;
                                            default:
                                                $status_class = 'bg-secondary';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?= $status_class ?> text-white"><?= $report['report_status'] ?></span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn action-button bg-secondary text-white edit-btn"
                                            data-id="<?= $report['id'] ?>" data-type-db="<?= $report['type'] ?>"
                                            data-report-type="<?= $report['report_type'] ?>"
                                            data-bs-toggle="modal" data-bs-target="#editModal">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </button>
                                        <button type="button" class="btn action-button bg-primary text-white view-btn"
                                            data-id="<?= $report['id'] ?>" data-type-db="<?= $report['type'] ?>"
                                            data-report-type="<?= $report['report_type'] ?>"
                                            data-bs-toggle="modal" data-bs-target="#viewModal">
                                            <i class="fa-solid fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">No reports found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Full description Modal -->
            <div class="modal fade" id="viewModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-fullscreen">
                    <div class="modal-content">
                        <div class="modal-header bg-primary">
                            <h5 class="modal-title text-white" id="viewModalLabel">Report Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="viewModalBody">
                            <!-- Content will be loaded here via JavaScript -->
                            Loading report details...
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Edit Modal -->
            <div class="modal fade" id="editModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-fullscreen">
                    <div class="modal-content">
                        <div class="modal-header bg-secondary">
                            <h5 class="modal-title fw-bold text-white" id="editModalLabel">Edit Report</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="editReportForm">
                            <input type="hidden" id="edit_report_id" name="report_id">
                            <input type="hidden" id="edit_report_type_original" name="report_type_original">
                            <div class="modal-body" id="editModalBody">
                                <!-- Content will be loaded here via JavaScript -->
                                Loading report data for editing...
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-success">Save changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Message Box -->
        <div id="messageBox" class="message-box"></div>

        <!-- Bootstrap JS Bundle with Popper -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const modal1 = new bootstrap.Modal(document.getElementById('staticBackdrop1'));
                const modal2 = new bootstrap.Modal(document.getElementById('modal2'));
                const modal3 = new bootstrap.Modal(document.getElementById('staticBackdrop3'));
                const modal4 = new bootstrap.Modal(document.getElementById('staticBackdrop4'));
                const modal5 = new bootstrap.Modal(document.getElementById('staticBackdrop5'));
                const modal6 = new bootstrap.Modal(document.getElementById('staticBackdrop6'));
                const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
                const editModal = new bootstrap.Modal(document.getElementById('editModal'));

                const addReportBtn = document.getElementById('AddReport');
                const nextModalBtn1 = document.getElementById('NextModal');
                const modal2NextBtn = document.getElementById('modal2NextBtn');
                const modal3NextBtn = document.getElementById('modal3NextBtn');
                const modal4NextBtn = document.getElementById('modal4NextBtn');
                const submitReportBtn = document.getElementById('submitReportBtn');
                const submitUsageReportBtn = document.getElementById('submitUsageReportBtn');

                const modal2BackBtn = document.getElementById('modal2BackBtn');
                const modal3BackBtn = document.getElementById('modal3BackBtn');
                const modal4BackBtn = document.getElementById('modal4BackBtn');
                const modal5BackBtn = document.getElementById('modal5BackBtn');
                const modal6BackBtn = document.getElementById('modal6BackBtn');


                // Helper function to show messages
                function showMessage(message, type) {
                    const messageBox = document.getElementById('messageBox');
                    messageBox.textContent = message;
                    messageBox.className = `message-box ${type} show`;
                    setTimeout(() => {
                        messageBox.classList.remove('show');
                    }, 3000); // Hide after 3 seconds
                }

                // --- Modal Navigation Logic (Create Report) ---

                addReportBtn.addEventListener('click', function() {
                    modal1.show();
                });

                nextModalBtn1.addEventListener('click', function() {
                    const reportType = document.getElementById('ReportInput').value;
                    const reportedBy = document.getElementById('UserInput').value;

                    if (!reportedBy) {
                        showMessage('Please enter who reported the issue.', 'error');
                        return;
                    }
                    if (!reportType) {
                        showMessage('Please select a report type.', 'error');
                        return;
                    }

                    modal1.hide();

                    if (reportType === 'Equipment Usage') {
                        document.getElementById('modal6_report_by_name').value = reportedBy;
                        modal6.show();
                    } else {
                        document.getElementById('modal2_report_type').value = reportType;
                        document.getElementById('modal2_report_by_name').value = reportedBy;
                        modal2.show();
                    }
                });

                modal2NextBtn.addEventListener('click', function() {
                    const date = document.getElementById('DateInput').value;
                    const equipmentId = document.getElementById('EquipmentIdInput').value;
                    const operatorId = document.getElementById('OperatorInput').value;
                    const inspector = document.getElementById('InspectInput').value;

                    if (!date || !equipmentId || !operatorId || !inspector) {
                        showMessage('Please fill in all required fields for Job Order.', 'error');
                        return;
                    }

                    // Pass data to next modal's hidden inputs
                    document.getElementById('modal3_report_type').value = document.getElementById('modal2_report_type').value;
                    document.getElementById('modal3_report_by_name').value = document.getElementById('modal2_report_by_name').value;
                    document.getElementById('modal3_date').value = date;
                    document.getElementById('modal3_equipment_id').value = equipmentId;
                    document.getElementById('modal3_operator_id').value = operatorId;
                    document.getElementById('modal3_inspected_by').value = inspector;

                    modal2.hide();
                    modal3.show();
                });

                modal3NextBtn.addEventListener('click', function() {
                    const problemEncountered = document.getElementById('ProblemEncounteredInput').value;
                    const finalDiagnosis = document.getElementById('FinalDiagnosisInput').value;
                    const detailsOfWorkdone = document.getElementById('DetailsOfWorkdoneInput').value;
                    const remarksReportDetails = document.getElementById('RemarksInput').value;

                    if (!problemEncountered || !finalDiagnosis || !detailsOfWorkdone) {
                        showMessage('Please fill in all required fields for Problem Diagnosis.', 'error');
                        return;
                    }

                    // Pass data to next modal's hidden inputs
                    document.getElementById('modal4_report_type').value = document.getElementById('modal3_report_type').value;
                    document.getElementById('modal4_report_by_name').value = document.getElementById('modal3_report_by_name').value;
                    document.getElementById('modal4_date').value = document.getElementById('modal3_date').value;
                    document.getElementById('modal4_equipment_id').value = document.getElementById('modal3_equipment_id').value;
                    document.getElementById('modal4_operator_id').value = document.getElementById('modal3_operator_id').value;
                    document.getElementById('modal4_inspected_by').value = document.getElementById('modal3_inspected_by').value;
                    document.getElementById('modal4_problem_encountered').value = problemEncountered;
                    document.getElementById('modal4_final_diagnosis').value = finalDiagnosis;
                    document.getElementById('modal4_details_of_workdone').value = detailsOfWorkdone;
                    document.getElementById('modal4_remarks_report_details').value = remarksReportDetails;

                    modal3.hide();
                    modal4.show();
                });

                modal4NextBtn.addEventListener('click', function() {
                    // Optional fields, no strict validation here, but pass values
                    const partId = document.getElementById('partId').value;
                    const description = document.getElementById('description').value;
                    const quantity = document.getElementById('qty').value;
                    const unit = document.getElementById('unit').value;
                    const lastReplacement = document.getElementById('lastReplacement').value;
                    const purchaseReqId = document.getElementById('purchaseReqId').value;

                    // Pass data to next modal's hidden inputs
                    document.getElementById('modal5_report_type').value = document.getElementById('modal4_report_type').value;
                    document.getElementById('modal5_report_by_name').value = document.getElementById('modal4_report_by_name').value;
                    document.getElementById('modal5_date').value = document.getElementById('modal4_date').value;
                    document.getElementById('modal5_equipment_id').value = document.getElementById('modal4_equipment_id').value;
                    document.getElementById('modal5_operator_id').value = document.getElementById('modal4_operator_id').value;
                    document.getElementById('modal5_inspected_by').value = document.getElementById('modal4_inspected_by').value;
                    document.getElementById('modal5_problem_encountered').value = document.getElementById('modal4_problem_encountered').value;
                    document.getElementById('modal5_final_diagnosis').value = document.getElementById('modal4_final_diagnosis').value;
                    document.getElementById('modal5_details_of_workdone').value = document.getElementById('modal4_details_of_workdone').value;
                    document.getElementById('modal5_remarks_report_details').value = document.getElementById('modal4_remarks_report_details').value;
                    document.getElementById('modal5_part_id').value = partId;
                    document.getElementById('modal5_part_description').value = description;
                    document.getElementById('modal5_quantity').value = quantity;
                    document.getElementById('modal5_unit').value = unit;
                    document.getElementById('modal5_last_replacement').value = lastReplacement;
                    document.getElementById('modal5_purchase_req_id').value = purchaseReqId;

                    modal4.hide();
                    modal5.show();
                });

                // --- Back Buttons ---
                modal2BackBtn.addEventListener('click', function() {
                    modal2.hide();
                    modal1.show();
                });

                modal3BackBtn.addEventListener('click', function() {
                    modal3.hide();
                    modal2.show();
                });

                modal4BackBtn.addEventListener('click', function() {
                    modal4.hide();
                    modal3.show();
                });

                modal5BackBtn.addEventListener('click', function() {
                    modal5.hide();
                    modal4.show();
                });

                modal6BackBtn.addEventListener('click', function() {
                    modal6.hide();
                    modal1.show();
                });

                // --- Submit Report (Maintenance, Repair, Breakdown) ---
                submitReportBtn.addEventListener('click', function() {
                    const conductedByWork = document.getElementById('ConductedByInput').value;
                    const dateStarted = document.getElementById('DateStartedInput').value;
                    const dateCompleted = document.getElementById('DateCompletedInput').value;
                    const timeStarted = document.getElementById('TimeStartedInput').value;
                    const timeCompleted = document.getElementById('TimeCompletedInput').value;
                    const conductedByAcceptance = document.getElementById('ConductedByAcceptanceInput').value;
                    const acceptedBy = document.getElementById('AcceptedByInput').value;
                    const jobCompletionVerifiedBy = document.getElementById('JobCompletionInput').value;
                    const remarksJobCompletion = document.getElementById('RemarksJobCompletionInput').value;

                    if (!conductedByWork || !dateStarted || !dateCompleted || !timeStarted || !timeCompleted || !conductedByAcceptance || !acceptedBy || !jobCompletionVerifiedBy) {
                        showMessage('Please fill in all required fields for Work Details.', 'error');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('action', 'create_report');
                    formData.append('report_type', document.getElementById('modal5_report_type').value);
                    formData.append('report_by', document.getElementById('modal5_report_by_name').value);
                    formData.append('report_date', document.getElementById('modal5_date').value);
                    formData.append('equipment_id', document.getElementById('modal5_equipment_id').value);
                    formData.append('operator_id', document.getElementById('modal5_operator_id').value);
                    formData.append('inspected_by', document.getElementById('modal5_inspected_by').value);
                    formData.append('problem_encountered', document.getElementById('modal5_problem_encountered').value);
                    formData.append('final_diagnosis', document.getElementById('modal5_final_diagnosis').value);
                    formData.append('details_of_workdone', document.getElementById('modal5_details_of_workdone').value);
                    formData.append('remarks_report_details', document.getElementById('modal5_remarks_report_details').value);
                    formData.append('purchase_req_id', document.getElementById('modal5_purchase_req_id').value);
                    formData.append('part_id', document.getElementById('modal5_part_id').value);
                    formData.append('part_description', document.getElementById('modal5_part_description').value);
                    formData.append('quantity', document.getElementById('modal5_quantity').value);
                    formData.append('unit', document.getElementById('modal5_unit').value);
                    formData.append('last_replacement', document.getElementById('modal5_last_replacement').value);
                    formData.append('conducted_by_work', conductedByWork);
                    formData.append('date_started', dateStarted);
                    formData.append('time_started', timeStarted);
                    formData.append('date_completed', dateCompleted);
                    formData.append('time_completed', timeCompleted);
                    formData.append('conducted_by_acceptance', conductedByAcceptance);
                    formData.append('accepted_by', acceptedBy);
                    formData.append('job_completion_verified_by', jobCompletionVerifiedBy);
                    formData.append('remarks_job_completion', remarksJobCompletion);
                    formData.append('report_status', 'In Progress'); // Default for new reports of this type

                    fetch('Reports.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showMessage(data.message, 'success');
                                modal5.hide();
                                // Reload the page or update the table dynamically
                                location.reload();
                            } else {
                                showMessage(data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showMessage('An error occurred while submitting the report.', 'error');
                        });
                });

                // --- Submit Equipment Usage Report ---
                submitUsageReportBtn.addEventListener('click', function() {
                    const equipmentId = document.getElementById('equipmentIDinputUsage').value;
                    const projectId = document.getElementById('projectIDinputUsage').value;
                    const logDate = document.getElementById('logDateInputUsage').value;
                    const timeIn = document.getElementById('TimeInInputUsage').value;
                    const timeOut = document.getElementById('TimeOutInputUsage').value;
                    const operatingHours = document.getElementById('OperatingHoursInputUsage').value;
                    const natureOfWork = document.getElementById('NatureOfWorkInputUsage').value;
                    const logRemarks = document.getElementById('LogRemarksInputUsage').value;
                    const operatorId = document.getElementById('OperatorInputUsage').value;
                    const reportedBy = document.getElementById('modal6_report_by_name').value; // Get from hidden input

                    if (!equipmentId || !projectId || !logDate || !timeIn || !timeOut || !operatingHours || !natureOfWork || !operatorId) {
                        showMessage('Please fill in all required fields for Equipment Usage Report.', 'error');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('action', 'create_report');
                    formData.append('report_type', 'Equipment Usage');
                    formData.append('equipment_id', equipmentId);
                    formData.append('project_id', projectId);
                    formData.append('log_date', logDate);
                    formData.append('time_in', timeIn);
                    formData.append('time_out', timeOut);
                    formData.append('operating_hours', operatingHours);
                    formData.append('nature_of_work', natureOfWork);
                    formData.append('log_remarks', logRemarks);
                    formData.append('operator_id', operatorId);
                    formData.append('report_by', reportedBy); // Add reported_by for consistency, though not in usage table schema

                    fetch('Reports.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showMessage(data.message, 'success');
                                modal6.hide();
                                location.reload();
                            } else {
                                showMessage(data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showMessage('An error occurred while submitting the usage report.', 'error');
                        });
                });

                // --- Reset Modals on Hide ---
                const allModals = [
                    document.getElementById('staticBackdrop1'),
                    document.getElementById('modal2'),
                    document.getElementById('staticBackdrop3'),
                    document.getElementById('staticBackdrop4'),
                    document.getElementById('staticBackdrop5'),
                    document.getElementById('staticBackdrop6'),
                    document.getElementById('viewModal'),
                    document.getElementById('editModal')
                ];

                allModals.forEach(modalEl => {
                    modalEl.addEventListener('hidden.bs.modal', function() {
                        // Reset form fields within the modal
                        const inputs = modalEl.querySelectorAll('input, select, textarea');
                        inputs.forEach(input => {
                            if (input.type === 'checkbox' || input.type === 'radio') {
                                input.checked = false;
                            } else if (input.tagName === 'SELECT') {
                                input.selectedIndex = 0; // Reset to first option (assuming it's a disabled placeholder)
                            } else if (input.type !== 'hidden') { // Don't clear hidden inputs holding data for next modals
                                input.value = '';
                            }
                        });
                        // Clear content of view/edit modals
                        if (modalEl.id === 'viewModal') {
                            document.getElementById('viewModalBody').innerHTML = 'Loading report details...';
                        }
                        if (modalEl.id === 'editModal') {
                            document.getElementById('editModalBody').innerHTML = 'Loading report data for editing...';
                        }
                    });
                });

                // --- Search Functionality ---
                const searchInput = document.querySelector('.search-input');
                if (searchInput) {
                    searchInput.addEventListener('input', function() {
                        const searchTerm = this.value.toLowerCase();
                        const tableRows = document.querySelectorAll('tbody tr');

                        tableRows.forEach(row => {
                            const text = row.textContent.toLowerCase();
                            row.style.display = text.includes(searchTerm) ? '' : 'none';
                        });
                    });
                }

                // --- Select All Checkbox ---
                const selectAllCheckbox = document.getElementById('selectAll');
                if (selectAllCheckbox) {
                    selectAllCheckbox.addEventListener('change', function() {
                        const checkboxes = document.querySelectorAll('tbody .form-check-input');
                        checkboxes.forEach(checkbox => {
                            checkbox.checked = selectAllCheckbox.checked;
                        });
                    });
                }

                // --- View Report Details (AJAX) ---
                document.querySelectorAll('.view-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const reportId = this.dataset.id;
                        const reportTypeDb = this.dataset.typeDb; // 'report' or 'usage'
                        const reportTypeDisplay = this.dataset.reportType; // 'Maintenance', 'Equipment Usage', etc.
                        const viewModalBody = document.getElementById('viewModalBody');
                        viewModalBody.innerHTML = 'Loading report details...'; // Show loading message

                        fetch(`Reports.php?action=get_report_details&id=${reportId}&type=${reportTypeDb}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data) {
                                    let htmlContent = `<h4 class="mb-3">Report ID: ${reportId} (${reportTypeDisplay})</h4>`;

                                    if (reportTypeDb === 'report') {
                                        htmlContent += `
                                            <div class="row mb-2">
                                                <div class="col-md-6"><strong>Equipment ID:</strong> ${data.equipment_id || 'N/A'}</div>
                                                <div class="col-md-6"><strong>Report Type:</strong> ${data.report_type || 'N/A'}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-md-6"><strong>Report Date:</strong> ${data.report_date || 'N/A'}</div>
                                                <div class="col-md-6"><strong>Report Status:</strong> <span class="badge bg-info">${data.report_status || 'N/A'}</span></div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-md-6"><strong>Reported By:</strong> ${data.report_by || 'N/A'}</div>
                                                <div class="col-md-6"><strong>Operator ID:</strong> ${data.operator_id || 'N/A'}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-md-12"><strong>Inspected By:</strong> ${data.inspected_by || 'N/A'}</div>
                                            </div>
                                            <hr>
                                            <h5 class="mt-4 mb-3">Problem & Diagnosis</h5>
                                            <div class="mb-2"><strong>Problem Encountered:</strong> ${data.problem_encountered || 'N/A'}</div>
                                            <div class="mb-2"><strong>Final Diagnosis:</strong> ${data.final_diagnosis || 'N/A'}</div>
                                            <div class="mb-2"><strong>Details of Work Done:</strong> ${data.details_of_workdone || 'N/A'}</div>
                                            <div class="mb-2"><strong>Remarks (Report Details):</strong> ${data.remarks_report_details || 'N/A'}</div>
                                            <hr>
                                            <h5 class="mt-4 mb-3">Spare Parts & Materials</h5>
                                            <div class="row mb-2">
                                                <div class="col-md-6"><strong>Part ID:</strong> ${data.part_id || 'N/A'}</div>
                                                <div class="col-md-6"><strong>Part Description:</strong> ${data.part_description || 'N/A'}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-md-6"><strong>Quantity:</strong> ${data.quantity || 'N/A'}</div>
                                                <div class="col-md-6"><strong>Unit:</strong> ${data.unit || 'N/A'}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-md-6"><strong>Last Replacement:</strong> ${data.last_replacement || 'N/A'}</div>
                                                <div class="col-md-6"><strong>Purchase Request ID:</strong> ${data.purchase_req_id || 'N/A'}</div>
                                            </div>
                                            <hr>
                                            <h5 class="mt-4 mb-3">Work & Acceptance Details</h5>
                                            <div class="row mb-2">
                                                <div class="col-md-6"><strong>Conducted By (Work):</strong> ${data.conducted_by_work || 'N/A'}</div>
                                                <div class="col-md-6"><strong>Repaired By:</strong> ${data.repaired_by || 'N/A'}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-md-6"><strong>Date Started:</strong> ${data.date_started || 'N/A'}</div>
                                                <div class="col-md-6"><strong>Time Started:</strong> ${data.time_started || 'N/A'}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-md-6"><strong>Date Completed:</strong> ${data.date_completed || 'N/A'}</div>
                                                <div class="col-md-6"><strong>Time Completed:</strong> ${data.time_completed || 'N/A'}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-md-6"><strong>Conducted By (Acceptance):</strong> ${data.conducted_by_acceptance || 'N/A'}</div>
                                                <div class="col-md-6"><strong>Accepted By:</strong> ${data.accepted_by || 'N/A'}</div>
                                            </div>
                                            <div class="mb-2"><strong>Job Completion Verified By:</strong> ${data.job_completion_verified_by || 'N/A'}</div>
                                            <div class="mb-2"><strong>Remarks (Job Completion):</strong> ${data.remarks_job_completion || 'N/A'}</div>
                                        `;
                                    } else if (reportTypeDb === 'usage') {
                                        htmlContent += `
                                            <div class="row mb-2">
                                                <div class="col-md-6"><strong>Equipment ID:</strong> ${data.equipment_id || 'N/A'}</div>
                                                <div class="col-md-6"><strong>Project ID:</strong> ${data.project_id || 'N/A'}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-md-6"><strong>Log Date:</strong> ${data.log_date || 'N/A'}</div>
                                                <div class="col-md-6"><strong>Operator ID:</strong> ${data.operator_id || 'N/A'}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-md-6"><strong>Time In:</strong> ${data.time_in || 'N/A'}</div>
                                                <div class="col-md-6"><strong>Time Out:</strong> ${data.time_out || 'N/A'}</div>
                                            </div>
                                            <div class="mb-2"><strong>Operating Hours:</strong> ${data.operating_hours || 'N/A'}</div>
                                            <div class="mb-2"><strong>Nature of Work:</strong> ${data.nature_of_work || 'N/A'}</div>
                                            <div class="mb-2"><strong>Log Remarks:</strong> ${data.log_remarks || 'N/A'}</div>
                                        `;
                                    }
                                    viewModalBody.innerHTML = htmlContent;
                                } else {
                                    viewModalBody.innerHTML = '<p class="text-danger">Failed to load report details.</p>';
                                }
                            })
                            .catch(error => {
                                console.error('Error fetching report details:', error);
                                viewModalBody.innerHTML = '<p class="text-danger">An error occurred while fetching report details.</p>';
                            });
                    });
                });

                // --- Edit Report Details (AJAX) ---
                document.querySelectorAll('.edit-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const reportId = this.dataset.id;
                        const reportTypeDb = this.dataset.typeDb;
                        const reportTypeDisplay = this.dataset.reportType;
                        const editModalBody = document.getElementById('editModalBody');
                        editModalBody.innerHTML = 'Loading report data for editing...'; // Show loading message

                        // Set hidden inputs for the form submission
                        document.getElementById('edit_report_id').value = reportId;
                        document.getElementById('edit_report_type_original').value = reportTypeDb;

                        fetch(`Reports.php?action=get_report_details&id=${reportId}&type=${reportTypeDb}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data) {
                                    let formHtml = `<h4 class="mb-3">Editing Report ID: ${reportId} (${reportTypeDisplay})</h4>`;

                                    if (reportTypeDb === 'report') {
                                        formHtml += `
                                            <div class="mb-3">
                                                <label for="edit_equipment_id" class="form-label fw-bold">Equipment ID: <span class="text-danger">*</span></label>
                                                <select class="form-select" id="edit_equipment_id" name="equipment_id" required>
                                                    <option value="1" ${data.equipment_id == 1 ? 'selected' : ''}>BH38 CAT3200B</option>
                                                    <option value="2" ${data.equipment_id == 2 ? 'selected' : ''}>Excavator 1</option>
                                                    <option value="3" ${data.equipment_id == 3 ? 'selected' : ''}>Bulldozer 2</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_report_type" class="form-label fw-bold">Report Type: <span class="text-danger">*</span></label>
                                                <select class="form-select" id="edit_report_type" name="report_type" required>
                                                    <option value="Maintenance" ${data.report_type === 'Maintenance' ? 'selected' : ''}>Maintenance</option>
                                                    <option value="Repair" ${data.report_type === 'Repair' ? 'selected' : ''}>Repair</option>
                                                    <option value="Breakdown" ${data.report_type === 'Breakdown' ? 'selected' : ''}>Breakdown</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_report_date" class="form-label fw-bold">Report Date: <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="edit_report_date" name="report_date" value="${data.report_date || ''}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_report_status" class="form-label fw-bold">Report Status: <span class="text-danger">*</span></label>
                                                <select class="form-select" id="edit_report_status" name="report_status" required>
                                                    <option value="Open" ${data.report_status === 'Open' ? 'selected' : ''}>Open</option>
                                                    <option value="In Progress" ${data.report_status === 'In Progress' ? 'selected' : ''}>In Progress</option>
                                                    <option value="Resolved" ${data.report_status === 'Resolved' ? 'selected' : ''}>Resolved</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_operator_id" class="form-label fw-bold">Operator ID: <span class="text-danger">*</span></label>
                                                <select class="form-select" id="edit_operator_id" name="operator_id" required>
                                                    <option value="1" ${data.operator_id == 1 ? 'selected' : ''}>John Doe</option>
                                                    <option value="2" ${data.operator_id == 2 ? 'selected' : ''}>Jane Smith</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_report_by" class="form-label fw-bold">Reported By: <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="edit_report_by" name="report_by" value="${data.report_by || ''}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_inspected_by" class="form-label fw-bold">Inspected By: <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="edit_inspected_by" name="inspected_by" value="${data.inspected_by || ''}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_repaired_by" class="form-label fw-bold">Repaired By: (Optional)</label>
                                                <input type="text" class="form-control" id="edit_repaired_by" name="repaired_by" value="${data.repaired_by || ''}">
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_report_description" class="form-label fw-bold">Report Description: (Optional)</label>
                                                <textarea class="form-control" id="edit_report_description" name="report_description">${data.report_description || ''}</textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_problem_encountered" class="form-label fw-bold">Problem Encountered: <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="edit_problem_encountered" name="problem_encountered" value="${data.problem_encountered || ''}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_final_diagnosis" class="form-label fw-bold">Final Diagnosis: <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="edit_final_diagnosis" name="final_diagnosis" value="${data.final_diagnosis || ''}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_details_of_workdone" class="form-label fw-bold">Details of Work Done: <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="edit_details_of_workdone" name="details_of_workdone" value="${data.details_of_workdone || ''}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_remarks_report_details" class="form-label fw-bold">Remarks (Report Details): (Optional)</label>
                                                <textarea class="form-control" id="edit_remarks_report_details" name="remarks_report_details">${data.remarks_report_details || ''}</textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_purchase_req_id" class="form-label fw-bold">Purchase Request ID: (Optional)</label>
                                                <input type="number" class="form-control" id="edit_purchase_req_id" name="purchase_req_id" value="${data.purchase_req_id || ''}">
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_part_id" class="form-label fw-bold">Part ID: (Optional)</label>
                                                <select class="form-select" id="edit_part_id" name="part_id">
                                                    <option value="">-- Select Part Name --</option>
                                                    <option value="1" ${data.part_id == 1 ? 'selected' : ''}>Engine Oil</option>
                                                    <option value="2" ${data.part_id == 2 ? 'selected' : ''}>Air Filter</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_part_description" class="form-label fw-bold">Part Description: (Optional)</label>
                                                <input type="text" class="form-control" id="edit_part_description" name="part_description" value="${data.part_description || ''}">
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_quantity" class="form-label fw-bold">Quantity: (Optional)</label>
                                                <input type="number" class="form-control" id="edit_quantity" name="quantity" value="${data.quantity || ''}">
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_unit" class="form-label fw-bold">Unit: (Optional)</label>
                                                <input type="text" class="form-control" id="edit_unit" name="unit" value="${data.unit || ''}">
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_last_replacement" class="form-label fw-bold">Last Replacement: (Optional)</label>
                                                <input type="date" class="form-control" id="edit_last_replacement" name="last_replacement" value="${data.last_replacement || ''}">
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_conducted_by_work" class="form-label fw-bold">Conducted By (Work): <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="edit_conducted_by_work" name="conducted_by_work" value="${data.conducted_by_work || ''}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_date_started" class="form-label fw-bold">Date Started: <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="edit_date_started" name="date_started" value="${data.date_started || ''}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_time_started" class="form-label fw-bold">Time Started: <span class="text-danger">*</span></label>
                                                <input type="time" class="form-control" id="edit_time_started" name="time_started" value="${data.time_started || ''}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_date_completed" class="form-label fw-bold">Date Completed: <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="edit_date_completed" name="date_completed" value="${data.date_completed || ''}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_time_completed" class="form-label fw-bold">Time Completed: <span class="text-danger">*</span></label>
                                                <input type="time" class="form-control" id="edit_time_completed" name="time_completed" value="${data.time_completed || ''}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_conducted_by_acceptance" class="form-label fw-bold">Conducted By (Acceptance): <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="edit_conducted_by_acceptance" name="conducted_by_acceptance" value="${data.conducted_by_acceptance || ''}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_accepted_by" class="form-label fw-bold">Accepted By: <span class="text-danger">*</span></label>
                                                <select class="form-select" id="edit_accepted_by" name="accepted_by" required>
                                                    <option value="1" ${data.accepted_by == 1 ? 'selected' : ''}>Manager A</option>
                                                    <option value="2" ${data.accepted_by == 2 ? 'selected' : ''}>Supervisor B</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_job_completion_verified_by" class="form-label fw-bold">Job Completion Verified By: <span class="text-danger">*</span></label>
                                                <select class="form-select" id="edit_job_completion_verified_by" name="job_completion_verified_by" required>
                                                    <option value="1" ${data.job_completion_verified_by == 1 ? 'selected' : ''}>Engineer X</option>
                                                    <option value="2" ${data.job_completion_verified_by == 2 ? 'selected' : ''}>Technician Y</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_remarks_job_completion" class="form-label fw-bold">Remarks (Job Completion): (Optional)</label>
                                                <textarea class="form-control" id="edit_remarks_job_completion" name="remarks_job_completion">${data.remarks_job_completion || ''}</textarea>
                                            </div>
                                        `;
                                    } else if (reportTypeDb === 'usage') {
                                        formHtml += `
                                            <div class="mb-3">
                                                <label for="edit_usage_equipment_id" class="form-label fw-bold">Equipment ID: <span class="text-danger">*</span></label>
                                                <select class="form-select" id="edit_usage_equipment_id" name="equipment_id" required>
                                                    <option value="1" ${data.equipment_id == 1 ? 'selected' : ''}>BH38 CAT3200B</option>
                                                    <option value="2" ${data.equipment_id == 2 ? 'selected' : ''}>Excavator 1</option>
                                                    <option value="3" ${data.equipment_id == 3 ? 'selected' : ''}>Bulldozer 2</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_usage_project_id" class="form-label fw-bold">Project Name: <span class="text-danger">*</span></label>
                                                <select class="form-select" id="edit_usage_project_id" name="project_id" required>
                                                    <option value="1" ${data.project_id == 1 ? 'selected' : ''}>Project A</option>
                                                    <option value="2" ${data.project_id == 2 ? 'selected' : ''}>Project B</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_usage_log_date" class="form-label fw-bold">Log Date: <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="edit_usage_log_date" name="log_date" value="${data.log_date || ''}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_usage_time_in" class="form-label fw-bold">Time In: <span class="text-danger">*</span></label>
                                                <input type="time" class="form-control" id="edit_usage_time_in" name="time_in" value="${data.time_in || ''}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_usage_time_out" class="form-label fw-bold">Time Out: <span class="text-danger">*</span></label>
                                                <input type="time" class="form-control" id="edit_usage_time_out" name="time_out" value="${data.time_out || ''}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_usage_operating_hours" class="form-label fw-bold">Operating Hours: <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" class="form-control" id="edit_usage_operating_hours" name="operating_hours" value="${data.operating_hours || ''}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_usage_nature_of_work" class="form-label fw-bold">Nature of Work: <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="edit_usage_nature_of_work" name="nature_of_work" value="${data.nature_of_work || ''}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_usage_log_remarks" class="form-label fw-bold">Log Remarks: (Optional)</label>
                                                <textarea class="form-control" id="edit_usage_log_remarks" name="log_remarks">${data.log_remarks || ''}</textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_usage_operator_id" class="form-label fw-bold">Operator ID: <span class="text-danger">*</span></label>
                                                <select class="form-select" id="edit_usage_operator_id" name="operator_id" required>
                                                    <option value="1" ${data.operator_id == 1 ? 'selected' : ''}>Operator Alpha</option>
                                                    <option value="2" ${data.operator_id == 2 ? 'selected' : ''}>Operator Beta</option>
                                                </select>
                                            </div>
                                        `;
                                    }
                                    editModalBody.innerHTML = formHtml;
                                } else {
                                    editModalBody.innerHTML = '<p class="text-danger">Failed to load report data for editing.</p>';
                                }
                            })
                            .catch(error => {
                                console.error('Error fetching report details for edit:', error);
                                editModalBody.innerHTML = '<p class="text-danger">An error occurred while fetching report data for editing.</p>';
                            });
                    });
                });

                // --- Handle Edit Form Submission (AJAX) ---
                document.getElementById('editReportForm').addEventListener('submit', function(event) {
                    event.preventDefault(); // Prevent default form submission

                    const formData = new FormData(this);
                    formData.append('action', 'update_report'); // Add action for PHP script

                    fetch('Reports.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showMessage(data.message, 'success');
                                editModal.hide();
                                location.reload(); // Reload to reflect changes
                            } else {
                                showMessage(data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showMessage('An error occurred while updating the report.', 'error');
                        });
                });
            });
        </script>
</body>

</html>