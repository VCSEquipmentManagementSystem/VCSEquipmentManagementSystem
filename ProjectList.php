<?php
session_start();
// Project List
if (empty($_SESSION['userID'])) {
    header('Location: loginPage.php');
    exit;
}
require('./database.php');
require('./readProjectList.php');

// Project list

// Backend logic for getting equipment type
if (isset($_POST['action']) && $_POST['action'] == 'get_equipment_type') {
    if (isset($_POST['custom_equip_id']) && !empty($_POST['custom_equip_id'])) {
        $customEquipId = mysqli_real_escape_string($connection, $_POST['custom_equip_id']);

        // Join equip_tbl with equip_type_tbl to get the equipment type name
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

// Backend logic for search and sort
if (isset($_POST['action']) && $_POST['action'] == 'search_sort_projects') {
    $searchTerm = isset($_POST['search']) ? mysqli_real_escape_string($connection, $_POST['search']) : '';
    $sortBy = isset($_POST['sort']) ? $_POST['sort'] : '';

    $query = "SELECT * FROM proj_sched_tbl WHERE 1=1";

    // Add search condition
    if (!empty($searchTerm)) {
        $query .= " AND (client LIKE '%$searchTerm%' 
                    OR project_name LIKE '%$searchTerm%' 
                    OR project_location LIKE '%$searchTerm%'
                    OR project_id LIKE '%$searchTerm%')";
    }

    // Add sorting
    switch ($sortBy) {
        case 'asc':
            $query .= " ORDER BY client ASC";
            break;
        case 'desc':
            $query .= " ORDER BY client DESC";
            break;
        case 'newest':
            $query .= " ORDER BY start_date DESC";
            break;
        case 'oldest':
            $query .= " ORDER BY start_date ASC";
            break;
        default:
            $query .= " ORDER BY project_id ASC";
    }

    $result = mysqli_query($connection, $query);
    $projects = array();

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Determine status class
            $statusClass = '';
            switch ($row['proj_status']) {
                case 'Ongoing':
                    $statusClass = 'btn-warning';
                    break;
                case 'Delayed':
                    $statusClass = 'btn-danger';
                    break;
                case 'Completed':
                    $statusClass = 'btn-success';
                    break;
                case 'Not yet started':
                    $statusClass = 'btn-primary';
                    break;
                default:
                    $statusClass = 'btn-secondary';
            }
            $row['status_class'] = $statusClass;

            // Fetch assigned equipment for this project
            $project_id = $row['project_id'];
            $equipments = [];
            $equipQuery = "SELECT 
                e.custom_equip_id, 
                et.equip_type_name, 
                e.equip_status,
                CONCAT(emp.first_name, ' ', emp.last_name) AS operator_name
                FROM proj_eqp_assign_tbl a
                LEFT JOIN equip_tbl e ON a.equipment_id = e.equipment_id
                LEFT JOIN equip_type_tbl et ON e.equip_type_id = et.equip_type_id
                LEFT JOIN employee_tbl emp ON e.operator_id = emp.employee_id
                WHERE a.project_id = $project_id";
            $equipResult = mysqli_query($connection, $equipQuery);
            while ($equipRow = mysqli_fetch_assoc($equipResult)) {
                $equipments[] = $equipRow;
            }
            $row['equipments'] = $equipments;

            $projects[] = $row;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($projects);
    exit;
}

// --- ALERT MESSAGE HANDLING ---
$alertMessage = '';
$alertType = 'success';

if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'archived':
            $alertMessage = 'Project(s) archived successfully.';
            $alertType = 'success';
            break;
        case 'restored':
            $alertMessage = 'Project(s) restored successfully.';
            $alertType = 'success';
            break;
        case 'deleted':
            $alertMessage = 'Project(s) deleted permanently.';
            $alertType = 'success';
            break;
    }
} elseif (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'archive_failed':
            $alertMessage = 'Failed to archive project(s).';
            $alertType = 'danger';
            break;
        case 'restore_failed':
            $alertMessage = 'Failed to restore project(s).';
            $alertType = 'danger';
            break;
        case 'delete_failed':
            $alertMessage = 'Failed to delete project(s).';
            $alertType = 'danger';
            break;
        case 'no_selection':
            $alertMessage = 'No project selected.';
            $alertType = 'warning';
            break;
        default:
            $alertMessage = 'An error occurred.';
            $alertType = 'danger';
    }
}

// Function to get Project Engineers
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

$sqlAllEquipment = mysqli_query($connection, "SELECT * FROM equip_tbl ORDER BY custom_equip_id ASC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Project list</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap");

        body {
            background-color: #f0f2f5;
            font-family: "Poppins", sans-serif;
            /* padding-top: ; */
        }

        .offcanvas {
            --bs-offcanvas-width: 280px;
        }


        .navbar-toggler:focus {
            outline: none;
            box-shadow: none;
            border-style: none;
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
            width: 200px;
            padding-left: 35px;
            border-radius: 6px;
            border: 1px solid #ced4da;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .card-option {
            max-width: 600px;
            margin: auto;
        }

        .card img {
            width: 100px;
            border-radius: 10px;
        }

        .search-input {
            border-radius: 10px;
            width: 350px;
        }

        .equipment-details {
            display: flex;
            margin-bottom: 20px;
        }

        .form-control {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.10);
        }

        img {
            height: 50px;
            width: 50px;
        }

        .offcanvas {
            --bs-offcanvas-width: 280px;
        }

        .tooltip-box {
            position: absolute;
            background: black;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 10px;
            display: none;
        }

        .status-delayed {
            background-color: #dc3545 !important;
            color: white !important;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }

        .status-ongoing {
            background-color: #ffc107 !important;
            color: black !important;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }

        .status-completed {
            background-color: #198754 !important;
            color: white !important;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }

        /* .project-card-actions {
            display: flex;
            flex-direction: column;
            gap: 10px !important;
            width: 100%;
        } */

        .project-status-group {
            width: 60%;
            margin: 5 !important;
        }

        .project-status-group .dropdown-toggle {
            width: 60%;
            text-align: center;
        }

        .project-view-details {
            width: 60%;
        }

        .card.card-stats {
            display: flex;
            flex-direction: column;
            min-height: 250px;
            height: 100%;
        }

        .project-status-group {
            width: 100%;
            margin: 5px auto !important;
            display: flex;
            justify-content: center;
        }

        .project-status-group .status-display {
            width: 60%;
            text-align: center;
            cursor: default;
            pointer-events: none;
        }

        /* Media Queries for Responsiveness */
        /* Small devices (landscape phones, 576px and up) */
        @media screen and (max-width: 576px) {
            .search-container {
                max-width: 100%;
            }

            .search-input {
                width: 100%;
                padding-left: 35px;
            }

            .equipment-details {
                flex-direction: column;
                text-align: center;
            }

            .equipment-image {
                margin-right: 0;
                margin-bottom: 15px;
            }

            .equipment-image img {
                width: 200px;
                height: 200px;
            }

            .function-buttons {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 90%;
                margin: 10% auto;
                padding: 15px;
            }
        }

        /* Medium devices (tablets, 768px and up) */
        @media screen and (min-width: 577px) and (max-width: 992px) {
            .search-input {
                width: 100%;
                max-width: 500px;
            }

            .equipment-details {
                flex-direction: column;
                align-items: center;
            }

            .equipment-image {
                margin-right: 0;
                margin-bottom: 15px;
            }

            .equipment-image img {
                width: 250px;
                height: 250px;
            }

            .modal-content {
                width: 85%;
            }
        }

        /* Large devices (desktops, 992px and up) */
        @media screen and (min-width: 993px) {
            .search-container {
                max-width: 500px;
            }

            .search-input {
                width: 100%;
                max-width: 500px;
            }
        }
    </style>
</head>


<body>
    <div id="alertPlaceholder" style="position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:9999;width:400px;max-width:90%;"></div>
    <div class="container-fluid" style="margin-top: 70px;">
        <!-- Nav -->
        <nav class="navbar bg-success fixed-top">
            <div class="container-fluid">
                <div>
                    <button class="navbar-toggler border border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarNav" aria-controls="sidebarNav">
                        <i class="fa-solid fa-bars text-light"></i>
                    </button>
                    <!-- <a class="navbar-brand fw-bold text-white" href="#">
                        <img src="Pictures/LOGO.png" data-bs-toggle="tooltip" data-bs-placement="top" title="Logo" alt="Viking Logo" style="width: 30px; height: 30px;">
                        EMS
                    </a> -->

                    <h4 class="d-inline text-white fw-bold ms-3">
                        Project List
                    </h4>
                </div>
                <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarNav" aria-labelledby="sidebarNavLabel">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title fw-bold" id="sidebarNavLabel">
                            <img src="Pictures/LOGO.png" alt="Viking Logo" style="width: 50px; height: 50px;">
                            Viking
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body">
                        <ul class="navbar-nav flex-column">
                            <!-- <form class="d-flex mb-3" role="search">
                            <input class="form-control me-2" list="datalistOptions" id="searchDataList" name="searchbar" type="search" placeholder="Search" aria-label="Search">
                            <button class="btn btn-secondary" type="submit">Search</button>
                        </form> -->
                            <hr>
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
                                <a class="nav-link fw-bold dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-warehouse"></i>
                                    <span class="fw-bold">Inventory</span>
                                </a>
                                <ul class="dropdown-menu border-0 shadow">
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
                            <!-- <li class="nav-item mt-3">
                            <a href="ManageUser.php" class="nav-link">
                                <i class="fa-solid fa-users"></i>
                                <span class="fw-bold">Manage Users</span>
                            </a>
                        </li> -->
                            <hr>
                            <li class="nav-item mt-3">
                                <a href="logout.php" class="link-danger text-decoration-none" id="logout">
                                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                                    <span class="fw-bold">Logout</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <!-- custom nav -->
                <div class="navbar-custom">
                    <div class="d-flex align-items-center justify-content-end">
                        <!-- Notification Dropdown -->
                        <div class="dropdown me-3">
                            <button class="btn btn-link position-relative p-2" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-bell text-light fs-5" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Notifications"></i>
                                <!-- <span class="position-absolute top-0 mt-1 start-100 translate-middle badge rounded-pill bg-danger notification-badge">
                    99+
                </span> -->
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="notificationDropdown">
                                <li>
                                    <h6 class="dropdown-header">Notifications</h6>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center" href="#">
                                        <i class="fa-solid fa-envelope text-primary me-2"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">New message received</div>
                                            <small class="text-muted">2 minutes ago</small>
                                        </div>
                                        <button class="btn btn-sm btn-link text-danger p-0 ms-2" title="Dismiss" type="button">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center" href="#">
                                        <i class="fa-solid fa-user-plus text-success me-2"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">Friend request</div>
                                            <small class="text-muted">5 minutes ago</small>
                                        </div>
                                        <button class="btn btn-sm btn-link text-danger p-0 ms-2" title="Dismiss" type="button">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center" href="#">
                                        <i class="fa-solid fa-heart text-danger me-2"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">Someone liked your post</div>
                                            <small class="text-muted">1 hour ago</small>
                                        </div>
                                        <button class="btn btn-sm btn-link text-danger p-0 ms-2" title="Dismiss" type="button">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item text-center" href="#seeallNotif" data-bs-toggle="modal">
                                        <i class="fa-solid fa-list-ul me-1"></i> See all notifications
                                    </a>
                                </li> <!-- Fixed missing closing li here -->
                                <li>
                                    <a class="dropdown-item text-center" href="#" onclick="markAllRead()">
                                        <i class="fa-solid fa-check me-1"></i> Mark all as read
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <!-- Modal for All Notifications -->
                        <div class="modal fade" id="seeallNotif" tabindex="-1" aria-labelledby="seeallNotifLabel" aria-hidden="true" data-bs-backdrop="false">
                            <div class="modal-dialog modal-fullscreen-sm-down modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <!-- Fixed id here to match aria-labelledby -->
                                        <h5 class="modal-title" id="seeallNotifLabel">
                                            <i class="fa-solid fa-bell me-2"></i>All Notifications
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="list-group list-group-flush">
                                            <div class="list-group-item d-flex align-items-center">
                                                <i class="fa-solid fa-envelope text-primary me-3"></i>
                                                <div class="flex-grow-1">
                                                    <div class="fw-semibold">New message from John Doe</div>
                                                    <small class="text-muted">Hey, how are you doing today?</small>
                                                    <div class="text-muted small">2 minutes ago</div>
                                                </div>
                                                <span class="badge bg-primary rounded-pill">New</span>
                                            </div>
                                            <div class="list-group-item d-flex align-items-center">
                                                <i class="fa-solid fa-user-plus text-success me-3"></i>
                                                <div class="flex-grow-1">
                                                    <div class="fw-semibold">Friend request from Jane Smith</div>
                                                    <small class="text-muted">Wants to connect with you</small>
                                                    <div class="text-muted small">5 minutes ago</div>
                                                </div>
                                                <span class="badge bg-success rounded-pill">New</span>
                                            </div>
                                            <div class="list-group-item d-flex align-items-center">
                                                <i class="fa-solid fa-heart text-danger me-3"></i>
                                                <div class="flex-grow-1">
                                                    <div class="fw-semibold">Someone liked your post</div>
                                                    <small class="text-muted">"Amazing sunset photo!"</small>
                                                    <div class="text-muted small">1 hour ago</div>
                                                </div>
                                            </div>
                                            <div class="list-group-item d-flex align-items-center">
                                                <i class="fa-solid fa-comment text-info me-3"></i>
                                                <div class="flex-grow-1">
                                                    <div class="fw-semibold">New comment on your post</div>
                                                    <small class="text-muted">"Great work on this project!"</small>
                                                    <div class="text-muted small">2 hours ago</div>
                                                </div>
                                            </div>
                                            <div class="list-group-item d-flex align-items-center">
                                                <i class="fa-solid fa-share text-warning me-3"></i>
                                                <div class="flex-grow-1">
                                                    <div class="fw-semibold">Your post was shared</div>
                                                    <small class="text-muted">Mike shared your latest update</small>
                                                    <div class="text-muted small">3 hours ago</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" onclick="markAllRead()">
                                            <i class="fa-solid fa-check me-1"></i> Mark all as read
                                        </button>
                                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- User Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-link p-2" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-user text-white fs-5" data-bs-toggle="tooltip" data-bs-placement="bottom" title="User Menu"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                                <li>
                                    <h6 class="dropdown-header">Account</h6>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="Profile.php">
                                        <i class="fa-solid fa-user me-2"></i> Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" id="themeToggle">
                                        <i class="fa-solid fa-moon me-2"></i> Toggle dark mode
                                    </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a href="logout.php" class="dropdown-item text-danger">
                                        <i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>


            </div>
        </nav>
        <div class="d-flex justify-content-end mt-3">
            <div class="d-flex justify-content-end mb-3">
                <!-- Search -->
                <div class="search-container me-2">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control search-input" id="mainSearchInput" placeholder="Search projects...">
                </div>
                <!-- Select Button -->
                <button class="btn btn-outline-secondary me-2 d-none" type="button" id="select-all-btn">
                    <i class="fa-solid fa-square-check"></i>
                </button>
                <!-- Archive -->
                <button type="button" class="btn btn-outline-secondary me-2" onclick="archiveSelected()">
                    <i class="fa-solid fa-box-archive"></i>
                </button>
                <!-- Sort -->
                <button class="btn btn-outline-secondary me-2" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-arrow-down-wide-short"></i>
                </button>
                <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                    <li><a href="#" class="dropdown-item sort-option" data-sort="">Unsort</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a href="#" class="dropdown-item sort-option" data-sort="newest">Newest to Oldest</a></li>
                    <li><a href="#" class="dropdown-item sort-option" data-sort="oldest">Oldest to Newest</a></li>
                </ul>
                <!-- View Archived -->
                <button name="view_archived" class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#archive">
                    <i class="fa-solid fa-box-archive"></i>
                    <span class="badge bg-danger rounded-pill archive-badge" id="archiveCount">
                        <?php
                        $archiveCount = 0;
                        $queryArchivedCount = "SELECT COUNT(*) as count FROM archived_project_tbl";
                        $resultArchivedCount = mysqli_query($connection, $queryArchivedCount);
                        if ($resultArchivedCount) {
                            $rowCount = mysqli_fetch_assoc($resultArchivedCount);
                            $archiveCount = $rowCount['count'];
                        }
                        echo $archiveCount;
                        ?>
                    </span>
                </button>
                <!-- Archive Modal -->
                <div class="modal fade" id="archive" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdrop" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-success">
                                <h5 class="modal-title fw-bold text-white" id="staticBackdropLabel">Archived</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <!-- Archive project store here -->
                                <form method="post" action="archiveProject.php" id="restoreForm">
                                    <?php
                                    // Reset the archived projects query
                                    $sqlArchivedProjects = mysqli_query($connection, "SELECT * FROM archived_project_tbl ORDER BY archived_date DESC");
                                    if (mysqli_num_rows($sqlArchivedProjects) > 0):
                                    ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th><input type="checkbox" id="selectAllArchived"></th>
                                                        <th>Project Name</th>
                                                        <th>Client</th>
                                                        <th>Location</th>
                                                        <th>Status</th>
                                                        <th>Archived Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($archivedProject = mysqli_fetch_array($sqlArchivedProjects)): ?>
                                                        <tr>
                                                            <td><input type="checkbox" name="archive_ids[]" value="<?php echo $archivedProject['archive_id']; ?>" class="archive-checkbox"></td>
                                                            <td><?php echo htmlspecialchars($archivedProject['project_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($archivedProject['client']); ?></td>
                                                            <td><?php echo htmlspecialchars($archivedProject['project_location']); ?></td>
                                                            <td>
                                                                <?php
                                                                $archivedStatusClass = '';
                                                                switch ($archivedProject['proj_status']) {
                                                                    case 'Ongoing':
                                                                        $archivedStatusClass = 'badge bg-warning';
                                                                        break;
                                                                    case 'Delayed':
                                                                        $archivedStatusClass = 'badge bg-danger';
                                                                        break;
                                                                    case 'Completed':
                                                                        $archivedStatusClass = 'badge bg-success';
                                                                        break;
                                                                    default:
                                                                        $archivedStatusClass = 'badge bg-secondary';
                                                                }
                                                                ?>
                                                                <span class="<?php echo $archivedStatusClass; ?>"><?php echo $archivedProject['proj_status']; ?></span>
                                                            </td>
                                                            <td><?php echo date('M d, Y', strtotime($archivedProject['archived_date'])); ?></td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-center text-muted">No archived projects found.</p>
                                    <?php endif; ?>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-success" onclick="restoreSelected()">Restore</button>
                                <button type="button" class="btn btn-danger" onclick="deleteSelected()">Delete</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Add Project -->
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                    <i class="fas fa-plus"></i>
                </button>
                <!-- Add Project Modal -->
                <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false"
                    tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header bg-success">
                                <h5 class="modal-title fw-bold text-white" id="exampleModalLabel">+ Add Project</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close">
                                </button>
                            </div>
                            <!-- Inputs -->
                            <form action="createProjectList.php" method="post">
                                <?php ($equipmentResults = mysqli_fetch_array($sqlEquipment)) ?>
                                <div class="modal-body text-start">
                                    <!-- Project Name -->
                                    <label for="ProjectDescriptionInput" class="form-label fw-bold">
                                        Project Name: <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" name="project_name" id="ProjectDescriptionInput" required>
                                    <!-- Project Location -->
                                    <label for="ProjectLocationInput" class="form-label fw-bold">
                                        Project Location: <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" name="project_location" id="ProjectLocationInput" required>
                                    <!-- Client Name -->
                                    <label for="ClientNameInput" class="form-label fw-bold">
                                        Client Name: <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" name="clientname" id="ClientNameInput" required>
                                    <!-- Project Engineer -->
                                    <label for="project_engineer" class="form-label fw-bold">Project Engineer <span class="text-danger">*</span></label>
                                    <select class="form-control" name="project_engineer" id="project_engineer" required>
                                        <option value="" selected disabled>Select Project Engineer</option>
                                        <?php
                                        // Assuming database connection is available here
                                        // The getProjectEngineers function should be defined or included
                                        $engineers = getProjectEngineers($connection);
                                        foreach ($engineers as $engineer) {
                                            echo '<option value="' . htmlspecialchars($engineer['employee_id']) . '">' . htmlspecialchars($engineer['name']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <!-- Start Date -->
                                    <label for="StartDateInput" class="form-label fw-bold">
                                        Start Date: <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control" name="startdate" id="StartDateInput" required min="<?php echo date('Y-m-d'); ?>">
                                    <!-- Expected Completion Date -->
                                    <label for="ExpectedCompletionDateInput" class="form-label fw-bold">
                                        Expected Completion Date: <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control" name="enddate" id="ExpectedCompletionDateInput" required min="<?php echo date('Y-m-d'); ?>">
                                    <div class="col-md-12 text-start mt-3">
                                        <h5 class="fw-bold">Assigned Equipment <span class="text-danger">*</span></h5>
                                        <div class="assigned-equipment-container" id="equipment-container-add">
                                            <div class="equipment-row mb-3 p-3 border rounded">
                                                <div class="row g-2 align-items-center">
                                                    <div class="col-md-6">
                                                        <label class="form-label visually-hidden">Equipment Type</label>
                                                        <select class="form-select equip-type-select" name="equip_type_id[]" required>
                                                            <option value="" selected disabled>Select Equipment Type</option>
                                                            <?php
                                                            // Query to get all available equipment types that have 'Idle' and 'Undeployed' equipment
                                                            $typeQuery = "SELECT DISTINCT et.equip_type_id, et.equip_type_name
                                                                          FROM equip_type_tbl et
                                                                          INNER JOIN equip_tbl e ON et.equip_type_id = e.equip_type_id
                                                                          WHERE e.equip_status = 'Idle' AND e.deployment_status = 'Undeployed'
                                                                          ORDER BY et.equip_type_name";
                                                            $typeResult = mysqli_query($connection, $typeQuery);
                                                            if ($typeResult) {
                                                                while ($typeRow = mysqli_fetch_assoc($typeResult)) {
                                                                    echo "<option value='{$typeRow['equip_type_id']}'>" . htmlspecialchars($typeRow['equip_type_name']) . "</option>";
                                                                }
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <label class="form-label visually-hidden">Equipment ID</label>
                                                        <select class="form-select custom-equip-id" name="custom_equip_id[]" required disabled>
                                                            <option value="" selected disabled>Select Equipment ID</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-1 text-end">
                                                        <button type="button" class="btn btn-danger btn-sm remove-equip-row">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-success mt-3 add-equip-row" data-container="equipment-container-add">
                                            <i class="fas fa-plus"></i> Add Equipment
                                        </button>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" name="submit" class="btn btn-success">Add</button>
                                </div>
                                <?php ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-center">
            <div class="btn-group w-100" role="group" aria-label="Basic example">
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#ongoingProjectModal">Ongoing</button>
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#delayedProjectModal">Delayed</button>
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#completedProjectModal">Completed</button>
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#notyetstartedProjectModal">Not yet started</button>
            </div>
        </div>
        <!-- Ongoing Project Modal -->
        <div class="modal fade" id="ongoingProjectModal" tabindex="-1" aria-labelledby="ongoingProjectModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-success">
                        <h5 class="modal-title fw-bold text-white" id="ongoingProjectModalLabel">Ongoing Projects</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Project ID</th>
                                        <th>Project Name</th>
                                        <th>Client</th>
                                        <th>Location</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Query for ongoing projects
                                    $ongoingQuery = "SELECT * FROM proj_sched_tbl WHERE proj_status = 'Ongoing' ORDER BY start_date DESC";
                                    $ongoingResult = mysqli_query($connection, $ongoingQuery);

                                    if (mysqli_num_rows($ongoingResult) > 0) {
                                        while ($ongoingProject = mysqli_fetch_array($ongoingResult)) {
                                    ?>
                                            <tr>
                                                <td><?php echo $ongoingProject['project_id']; ?></td>
                                                <td><?php echo htmlspecialchars($ongoingProject['project_name']); ?></td>
                                                <td><?php echo htmlspecialchars($ongoingProject['client']); ?></td>
                                                <td><?php echo htmlspecialchars($ongoingProject['project_location']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($ongoingProject['start_date'])); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($ongoingProject['end_date'])); ?></td>
                                            </tr>
                                        <?php
                                        }
                                    } else {
                                        ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">No ongoing projects found.</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Delayed ProjectModal -->
        <div class="modal fade" id="delayedProjectModal" tabindex="-1" aria-labelledby="delayedProjectModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-success">
                        <h5 class="modal-title fw-bold text-white" id="delayedProjectModalLabel">Delayed Projects</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Project ID</th>
                                        <th>Project Name</th>
                                        <th>Client</th>
                                        <th>Location</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Query for delayed projects
                                    $delayedQuery = "SELECT * FROM proj_sched_tbl WHERE proj_status = 'Delayed' ORDER BY start_date DESC";
                                    $delayedResult = mysqli_query($connection, $delayedQuery);

                                    if (mysqli_num_rows($delayedResult) > 0) {
                                        while ($delayedProject = mysqli_fetch_array($delayedResult)) {
                                            // Calculate days overdue
                                            $endDate = new DateTime($delayedProject['end_date']);
                                            $currentDate = new DateTime();
                                            $daysOverdue = $currentDate > $endDate ? $currentDate->diff($endDate)->days : 0;
                                    ?>
                                            <tr>
                                                <td><?php echo $delayedProject['project_id']; ?></td>
                                                <td><?php echo htmlspecialchars($delayedProject['project_name']); ?></td>
                                                <td><?php echo htmlspecialchars($delayedProject['client']); ?></td>
                                                <td><?php echo htmlspecialchars($delayedProject['project_location']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($delayedProject['start_date'])); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($delayedProject['end_date'])); ?></td>
                                            </tr>
                                        <?php
                                        }
                                    } else {
                                        ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">No delayed projects found.</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Completed ProjectModal -->
        <div class="modal fade" id="completedProjectModal" tabindex="-1" aria-labelledby="completedProjectModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-success">
                        <h5 class="modal-title fw-bold text-white" id="completedProjectModalLabel">Completed Projects</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Project ID</th>
                                        <th>Project Name</th>
                                        <th>Client</th>
                                        <th>Location</th>
                                        <th>Start Date</th>
                                        <th>Completion Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Query for completed projects
                                    $completedQuery = "SELECT * FROM proj_sched_tbl WHERE proj_status = 'Completed' ORDER BY end_date DESC";
                                    $completedResult = mysqli_query($connection, $completedQuery);

                                    if (mysqli_num_rows($completedResult) > 0) {
                                        while ($completedProject = mysqli_fetch_array($completedResult)) {
                                            // Calculate project duration
                                            $startDate = new DateTime($completedProject['start_date']);
                                            $endDate = new DateTime($completedProject['end_date']);
                                            $duration = $startDate->diff($endDate)->days;
                                    ?>
                                            <tr>
                                                <td><?php echo $completedProject['project_id']; ?></td>
                                                <td><?php echo htmlspecialchars($completedProject['project_name']); ?></td>
                                                <td><?php echo htmlspecialchars($completedProject['client']); ?></td>
                                                <td><?php echo htmlspecialchars($completedProject['project_location']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($completedProject['start_date'])); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($completedProject['end_date'])); ?></td>
                                            </tr>
                                        <?php
                                        }
                                    } else {
                                        ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">No completed projects found.</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Not yet started ProjectModal -->
        <div class="modal fade" id="notyetstartedProjectModal" tabindex="-1" aria-labelledby="notyetstartedProjectModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-success">
                        <h5 class="modal-title fw-bold text-white" id="notyetstartedProjectModalLabel">Not Yet Started Projects</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Project ID</th>
                                        <th>Project Name</th>
                                        <th>Client</th>
                                        <th>Location</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Query for not yet started projects
                                    $notStartedQuery = "SELECT *, 
                                        DATEDIFF(start_date, CURDATE()) as days_until_start 
                                        FROM proj_sched_tbl 
                                        WHERE proj_status = 'Not yet started' 
                                        ORDER BY start_date ASC";
                                    $notStartedResult = mysqli_query($connection, $notStartedQuery);

                                    if (mysqli_num_rows($notStartedResult) > 0) {
                                        while ($notStartedProject = mysqli_fetch_array($notStartedResult)) {
                                            $daysUntilStart = max(0, $notStartedProject['days_until_start']);
                                    ?>
                                            <tr>
                                                <td><?php echo $notStartedProject['project_id']; ?></td>
                                                <td><?php echo htmlspecialchars($notStartedProject['project_name']); ?></td>
                                                <td><?php echo htmlspecialchars($notStartedProject['client']); ?></td>
                                                <td><?php echo htmlspecialchars($notStartedProject['project_location']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($notStartedProject['start_date'])); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($notStartedProject['end_date'])); ?></td>
                                            </tr>
                                        <?php
                                        }
                                    } else {
                                        ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">No projects pending to start.</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!--Card Display Modal-->
        <form method="post" action="archiveProject.php" id="archiveForm">
            <div class="row mb-4 mt-4">
                <?php while ($projectListResults = mysqli_fetch_array($sqlProjectList)) { ?>
                    <div class="col-sm-3 mt-4">
                        <div class="card card-stats border-success d-flex">
                            <div class="card-body d-flex flex-column justify-content-between">
                                <input type="checkbox" name="project_ids[]" value="<?php echo $projectListResults['project_id']; ?>"
                                    class="form-check-input project-checkbox" id="card<?php echo $projectListResults['project_id']; ?>">
                                <div class="project-number text-center mb-2">
                                    <span class="fs-3 fw-bold text-danger">
                                        <?php echo $projectListResults['project_id'] ?>
                                    </span>
                                </div>
                                <div class="content text-center flex-grow-1 d-flex flex-column flex-grow-1">
                                    <div>
                                        <!-- Project Name -->
                                        <p class="fw-bold fs-5 mb-1"><?php echo $projectListResults['project_name'] ?></p>
                                        <!-- Client Name -->
                                        <p class="fw-bold mb-1"><?php echo $projectListResults['client'] ?></p>
                                        <!-- Project Location -->
                                        <p class="fw-bold mb-2">Location: <?php echo $projectListResults['project_location'] ?></p>
                                        <!-- Project Status -->
                                        <div class="flex-spacer project-status-group mb-2">
                                            <?php
                                            $statusClass = '';
                                            $statusText = $projectListResults['proj_status'];
                                            switch ($statusText) {
                                                case 'Ongoing':
                                                    $statusClass = 'btn-warning';
                                                    break;
                                                case 'Delayed':
                                                    $statusClass = 'btn-danger';
                                                    break;
                                                case 'Completed':
                                                    $statusClass = 'btn-success';
                                                    break;
                                                case 'Not yet started':
                                                    $statusClass = 'btn-primary';
                                                    break;
                                                default:
                                                    $statusClass = 'btn-secondary';
                                            }
                                            ?>
                                            <span class="btn <?php echo $statusClass; ?> status-display"><?php echo $statusText; ?></span>
                                        </div>
                                        <!-- Button trigger modal Details -->
                                        <button type="button" class="btn btn-secondary project-view-details" data-bs-toggle="modal" data-bs-target="#projectModal<?php echo $projectListResults['project_id']; ?>">
                                            View Details
                                        </button>
                                        <br>
                                        <?php if (!empty($projectListResults['latest_date_modified'])): ?>
                                            <small class="text-muted mt-2">
                                                Latest Date Modified: <?php echo date('M d, Y', strtotime($projectListResults['latest_date_modified'])); ?>
                                            </small>
                                        <?php endif; ?>
                                        <!-- View Details Modal -->
                                        <div class="modal fade" id="projectModal<?php echo $projectListResults['project_id']; ?>" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdrop2" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-success">
                                                        <h5 class="modal-title fw-bold text-white" id="projectModalLabel<?php echo $projectListResults['project_id']; ?>">Project Description</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6 text-start mb-4">
                                                                <p><span class="fw-bold">Client Name: <?php echo $projectListResults['client'] ?></span></p>
                                                                <p><span class="fw-bold">Location: <?php echo $projectListResults['project_location'] ?></span></p>
                                                                <p><span class="fw-bold">Project Engineer: <?php echo htmlspecialchars($projectListResults['project_engineer'] ?? ''); ?></span></p>
                                                                <p><span class="fw-bold">Status: </span>
                                                                    <?php
                                                                    $statusBadgeClass = '';
                                                                    switch ($projectListResults['proj_status']) {
                                                                        case 'Ongoing':
                                                                            $statusBadgeClass = 'badge bg-warning';
                                                                            break;
                                                                        case 'Delayed':
                                                                            $statusBadgeClass = 'badge bg-danger';
                                                                            break;
                                                                        case 'Completed':
                                                                            $statusBadgeClass = 'badge bg-success';
                                                                            break;
                                                                        case 'Not yet started':
                                                                            $statusBadgeClass = 'badge bg-primary';
                                                                            break;
                                                                        default:
                                                                            $statusBadgeClass = 'badge bg-secondary';
                                                                    }
                                                                    ?>
                                                                    <span class="<?php echo $statusBadgeClass; ?>"><?php echo $projectListResults['proj_status'] ?></span>
                                                                </p>
                                                                <p><span class="fw-bold">Start of Project: </span><?php echo $projectListResults['start_date'] ?></p>
                                                                <p><span class="fw-bold">End of Project: </span><?php echo $projectListResults['end_date'] ?></p>
                                                            </div>
                                                            <div class="col-md-12 text-start">
                                                                <h5 class="fw-bold">List of Equipment</h5>
                                                                <div class="table-responsive">
                                                                    <table class="table table-bordered">
                                                                        <thead class="table-dark">
                                                                            <tr>
                                                                                <th>Equip ID</th>
                                                                                <th>Type</th>
                                                                                <th>Status</th>
                                                                                <th>Operator</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <?php
                                                                            // Fetch assigned equipment for this project
                                                                            $project_id = $projectListResults['project_id'];
                                                                            $assignedEquipQuery = "
                                                                                SELECT 
                                                                                    e.custom_equip_id, 
                                                                                    et.equip_type_name, 
                                                                                    e.equip_status,
                                                                                    emp.first_name,
                                                                                    emp.last_name
                                                                                FROM proj_eqp_assign_tbl a
                                                                                LEFT JOIN equip_tbl e ON a.equipment_id = e.equipment_id
                                                                                LEFT JOIN equip_type_tbl et ON e.equip_type_id = et.equip_type_id
                                                                                LEFT JOIN employee_tbl emp ON e.operator_id = emp.employee_id
                                                                                WHERE a.project_id = $project_id
                                                                            ";
                                                                            $assignedEquipResult = mysqli_query($connection, $assignedEquipQuery);
                                                                            if ($assignedEquipResult && mysqli_num_rows($assignedEquipResult) > 0) {
                                                                                while ($equipmentResults = mysqli_fetch_assoc($assignedEquipResult)) {
                                                                            ?>
                                                                                    <tr>
                                                                                        <td><?php echo htmlspecialchars($equipmentResults['custom_equip_id']); ?></td>
                                                                                        <td><?php echo htmlspecialchars($equipmentResults['equip_type_name']); ?></td>
                                                                                        <td><?php echo htmlspecialchars($equipmentResults['equip_status'] ?? ''); ?></td>
                                                                                        <td>
                                                                                            <?php
                                                                                            if (!empty($equipmentResults['first_name']) || !empty($equipmentResults['last_name'])) {
                                                                                                echo htmlspecialchars($equipmentResults['first_name'] . ' ' . $equipmentResults['last_name']);
                                                                                            } else {
                                                                                                echo 'N/A';
                                                                                            }
                                                                                            ?>
                                                                                        </td>
                                                                                    </tr>
                                                                                <?php
                                                                                }
                                                                            } else {
                                                                                ?>
                                                                                <tr>
                                                                                    <td colspan="4" class="text-center text-muted">No equipment assigned.</td>
                                                                                </tr>
                                                                            <?php
                                                                            }
                                                                            ?>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <form action="">
                                                            <button type="button" name="edit" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $projectListResults['project_id']; ?>" data-bs-dismiss="modal">Edit</button>
                                                        </form>
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </form>
    </div>

    <!-- Edit Modal -->
    <?php
    // Reset the query for edit modals
    mysqli_data_seek($sqlProjectList, 0);
    while ($editProject = mysqli_fetch_array($sqlProjectList)) {
    ?>
        <div class="modal fade" id="editModal<?php echo $editProject['project_id']; ?>" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="editModalLabel<?php echo $editProject['project_id']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-success">
                        <h5 class="modal-title fw-bold text-white" id="editModalLabel<?php echo $editProject['project_id']; ?>">Edit Project</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="editProject.php" method="post">
                            <input type="hidden" name="project_id" value="<?php echo $editProject['project_id']; ?>">

                            <div class="text-start">
                                <!-- Project Name -->
                                <label for="ProjectDescriptionInput<?php echo $editProject['project_id']; ?>" class="form-label fw-bold">
                                    Project Name: <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" name="project_name" id="ProjectDescriptionInput<?php echo $editProject['project_id']; ?>" value="<?php echo htmlspecialchars($editProject['project_name']); ?>" required>

                                <!-- Project Location -->
                                <label for="ProjectLocationInput<?php echo $editProject['project_id']; ?>" class="form-label fw-bold">
                                    Project Location: <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" name="project_location" id="ProjectLocationInput<?php echo $editProject['project_id']; ?>" value="<?php echo htmlspecialchars($editProject['project_location']); ?>" required>

                                <!-- Client Name -->
                                <label for="ClientNameInput<?php echo $editProject['project_id']; ?>" class="form-label fw-bold">
                                    Client Name: <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" name="clientname" id="ClientNameInput<?php echo $editProject['project_id']; ?>" value="<?php echo htmlspecialchars($editProject['client']); ?>" required>

                                <!-- Project Engineer -->
                                <label for="project_engineer" class="form-label fw-bold">
                                    Project Engineer: <span class="text-danger">*</span>
                                </label>
                                <select class="form-control" name="project_engineer" id="project_engineer" required>
                                    <option value="" disabled>Select Project Engineer</option>
                                    <?php
                                    // Assume you have already fetched the current project's details into an array like $editProject.
                                    // The current project engineer's name is stored in this array.
                                    $current_project_engineer_name = $editProject['project_engineer'];

                                    // Get the list of all project engineers
                                    $engineers = getProjectEngineers($connection);

                                    foreach ($engineers as $engineer) {
                                        // Compare the name from the fetched list with the current project's engineer name
                                        $selected = ($engineer['name'] === $current_project_engineer_name) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($engineer['employee_id']) . '" ' . $selected . '>' . htmlspecialchars($engineer['name']) . '</option>';
                                    }
                                    ?>
                                </select>

                                <!-- Start Date -->
                                <label for="StartDateInput<?php echo $editProject['project_id']; ?>" class="form-label fw-bold">
                                    Start Date: <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" name="startdate" id="StartDateInput<?php echo $editProject['project_id']; ?>" value="<?php echo $editProject['start_date']; ?>" required min="<?php echo date('Y-m-d'); ?>">

                                <!-- Expected Completion Date -->
                                <label for="ExpectedCompletionDateInput<?php echo $editProject['project_id']; ?>" class="form-label fw-bold">
                                    Expected Completion Date: <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" name="enddate" id="ExpectedCompletionDateInput<?php echo $editProject['project_id']; ?>" value="<?php echo $editProject['end_date']; ?>" required min="<?php echo date('Y-m-d'); ?>">

                                <!-- Edit Assigned Equip -->
                                <div class="col-md-12 text-start mt-3">
                                    <h5 class="fw-bold">Assigned Equipment <span class="text-danger">*</span></h5>
                                    <div class="assigned-equipment-container" id="equipment-container-edit<?php echo $editProject['project_id']; ?>">
                                        <?php
                                        // Fetch assigned equipment for this project
                                        $assignedEquip = mysqli_query(
                                            $connection,
                                            "SELECT a.equipment_id, e.custom_equip_id, e.equip_type_id
                                             FROM proj_eqp_assign_tbl a
                                             LEFT JOIN equip_tbl e ON a.equipment_id = e.equipment_id
                                             WHERE a.project_id = {$editProject['project_id']}"
                                        );

                                        if (mysqli_num_rows($assignedEquip) > 0) {
                                            while ($row = mysqli_fetch_assoc($assignedEquip)):
                                        ?>
                                                <div class="equipment-row mb-3 p-3 border rounded">
                                                    <div class="row g-2 align-items-center">
                                                        <div class="col-md-6">
                                                            <label class="form-label visually-hidden">Equipment Type</label>
                                                            <select class="form-select equip-type-select" name="equip_type_id[]" required>
                                                                <option value="" disabled>Select Equipment Type</option>
                                                                <?php
                                                                $typeQuery = "SELECT DISTINCT et.equip_type_id, et.equip_type_name
                                                                              FROM equip_type_tbl et
                                                                              INNER JOIN equip_tbl e ON et.equip_type_id = e.equip_type_id
                                                                              ORDER BY et.equip_type_name";
                                                                $typeResult = mysqli_query($connection, $typeQuery);
                                                                if ($typeResult) {
                                                                    while ($typeRow = mysqli_fetch_assoc($typeResult)) {
                                                                        $selected = ($typeRow['equip_type_id'] == $row['equip_type_id']) ? 'selected' : '';
                                                                        echo "<option value='{$typeRow['equip_type_id']}' {$selected}>{$typeRow['equip_type_name']}</option>";
                                                                    }
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-5">
                                                            <label class="form-label visually-hidden">Equipment ID</label>
                                                            <select class="form-select custom-equip-id" name="custom_equip_id[]" required>
                                                                <option value="" disabled>Select Equipment ID</option>
                                                                <?php
                                                                $equipQuery = "SELECT custom_equip_id
                                                                               FROM equip_tbl
                                                                               WHERE equip_type_id = '{$row['equip_type_id']}'
                                                                               AND (equip_status = 'Idle' OR custom_equip_id = '{$row['custom_equip_id']}')";
                                                                $equipResult = mysqli_query($connection, $equipQuery);
                                                                if ($equipResult) {
                                                                    while ($equipRow = mysqli_fetch_assoc($equipResult)) {
                                                                        $selected = ($equipRow['custom_equip_id'] == $row['custom_equip_id']) ? 'selected' : '';
                                                                        echo "<option value='{$equipRow['custom_equip_id']}' {$selected}>{$equipRow['custom_equip_id']}</option>";
                                                                    }
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-1 text-end">
                                                            <button type="button" class="btn btn-danger btn-sm remove-equip-row">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php
                                            endwhile;
                                        } else {
                                            // Display an empty row if no equipment is assigned
                                            ?>
                                            <div class="equipment-row mb-3 p-3 border rounded">
                                                <div class="row g-2 align-items-center">
                                                    <div class="col-md-6">
                                                        <label class="form-label visually-hidden">Equipment Type</label>
                                                        <select class="form-select equip-type-select" name="equip_type_id[]" required>
                                                            <option value="" selected disabled>Select Equipment Type</option>
                                                            <?php
                                                            $typeQuery = "SELECT DISTINCT et.equip_type_id, et.equip_type_name FROM equip_type_tbl et INNER JOIN equip_tbl e ON et.equip_type_id = e.equip_type_id ORDER BY et.equip_type_name";
                                                            $typeResult = mysqli_query($connection, $typeQuery);
                                                            if ($typeResult) {
                                                                while ($typeRow = mysqli_fetch_assoc($typeResult)) {
                                                                    echo "<option value='{$typeRow['equip_type_id']}'>{$typeRow['equip_type_name']}</option>";
                                                                }
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <label class="form-label visually-hidden">Equipment ID</label>
                                                        <select class="form-select custom-equip-id" name="custom_equip_id[]" required disabled>
                                                            <option value="" selected disabled>Select Equipment ID</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-1 text-end">
                                                        <button type="button" class="btn btn-danger btn-sm remove-equip-row">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <button type="button" class="btn btn-success mt-3 add-equip-row" data-container="equipment-container-edit<?php echo $editProject['project_id']; ?>">
                                        <i class="fas fa-plus"></i> Add Equipment
                                    </button>
                                </div>
                            </div>
                            <div class="modal-footer mt-3">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="edit_project" class="btn btn-success">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
    <?php
    // Reset the query for project details modals
    mysqli_data_seek($sqlProjectList, 0);
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global variables for current filter state
        let currentSort = '';
        let currentFilter = 'all';
        let allSelected = false;

        // Function to load equipment type via AJAX
        function loadEquipmentType(selectElement) {
            const customEquipId = selectElement.value;
            // Assuming the span is the next sibling to the select
            const typeDisplaySpan = selectElement.nextElementSibling;

            if (customEquipId) {
                fetch('ProjectList.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=get_equipment_type&custom_equip_id=${customEquipId}`
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            typeDisplaySpan.textContent = `${data.equip_type_name}`;
                        } else {
                            typeDisplaySpan.textContent = 'Type not found';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching equipment type:', error);
                        typeDisplaySpan.textContent = 'Error loading type';
                    });
            } else {
                typeDisplaySpan.textContent = ''; // Clear if no equipment selected
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Global function for removing equipment rows
            window.removeEquipmentRow = function(button) {
                const row = button.closest('tr');
                const tbody = row.closest('tbody');
                if (tbody && tbody.querySelectorAll('tr').length > 1) {
                    row.remove();
                } else {
                    alert('Cannot remove the last equipment row. At least one equipment is required.');
                }
            };


            // Initialize equipment types when edit modal is shown
            document.addEventListener('shown.bs.modal', function(event) {
                if (event.target.id.startsWith('editModal')) {
                    const projectId = event.target.id.replace('editModal', '');
                    const tbody = document.getElementById(`equipmentTableBodyEdit${projectId}`);

                    if (tbody) {
                        tbody.querySelectorAll('select.custom-equip-id').forEach(select => {
                            loadEquipmentType(select);
                        });
                    }
                }
            });

            // Search and Sort Functionality
            // Helper to debounce search input
            function debounce(func, wait) {
                let timeout;
                return function(...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), wait);
                };
            }

            // Attach search input event
            document.getElementById('mainSearchInput').addEventListener('input', debounce(function(e) {
                const search = e.target.value;
                fetchProjects(search, currentSort);
            }, 300));

            // Attach sort option click event
            document.querySelectorAll('.sort-option').forEach(option => {
                option.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Unset the 'sort' parameter if 'unsort' is selected
                    const sortType = this.dataset.sort;
                    currentSort = sortType === 'unsort' ? '' : sortType;

                    const search = document.getElementById('mainSearchInput').value;
                    fetchProjects(search, currentSort);
                });
            });

            // AJAX fetch function
            function fetchProjects(search, sort) {
                const formData = new URLSearchParams();
                formData.append('action', 'search_sort_projects');
                formData.append('search', search || '');
                formData.append('sort', sort || '');

                fetch(window.location.pathname, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: formData.toString()
                    })
                    .then(res => res.json())
                    .then(data => {
                        updateProjectCards(data);
                    })
                    .catch(err => {
                        console.error('Error fetching projects:', err);
                    });
            }

            // Optionally, fetch all projects on page load
            fetchProjects('', ''); // Call immediately on page load

            // Select All Functionality
            const selectAllBtn = document.getElementById('select-all-btn');
            let allSelected = false;
            if (selectAllBtn) {
                selectAllBtn.addEventListener('click', function() {
                    const checkboxes = document.querySelectorAll('.project-checkbox');
                    const button = this;

                    if (!allSelected) {
                        // Select all
                        checkboxes.forEach(checkbox => {
                            checkbox.checked = true;
                            const card = checkbox.closest('.card');
                            if (card) card.classList.add('selected');
                        });

                        button.innerHTML = '<i class="fa-solid fa-square-xmark"></i> Cancel';
                        button.classList.remove('btn-outline-secondary');
                        button.classList.add('btn-danger', 'selected');
                        allSelected = true;
                    } else {
                        // Unselect all and hide button
                        checkboxes.forEach(checkbox => {
                            checkbox.checked = false;
                            const card = checkbox.closest('.card');
                            if (card) card.classList.remove('selected');
                        });

                        button.innerHTML = '<i class="fa-solid fa-square-check"></i> Select All';
                        button.classList.remove('btn-danger', 'selected');
                        button.classList.add('btn-outline-secondary');
                        allSelected = false;
                        button.classList.add('d-none');
                    }
                });
            }

            // Individual checkbox change handler
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('project-checkbox')) {
                    const card = e.target.closest('.card');
                    if (e.target.checked) {
                        if (card) card.classList.add('selected');
                    } else {
                        if (card) card.classList.remove('selected');
                    }
                    updateSelectAllButton();
                }
            });

            // Add CSS for visual feedback
            const style = document.createElement('style');
            style.textContent = `
                .card {
                    transition: all 0.3s ease;
                }
                .card.selected {
                    border: 2px solid #007bff;
                    transform: translateY(-2px);
                    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
                }
                .filter-option.active, .sort-option.active {
                    background-color: #007bff !important;
                    color: white !important;
                }
            `;
            document.head.appendChild(style);
        });

        // Update project cards display
        function updateProjectCards(projects) {
            const container = document.querySelector('.row.mb-4.mt-4');
            if (!container) return;

            if (projects.length === 0) {
                container.innerHTML = '<div class="col-12 text-center"><p class="text-muted">No projects found matching your criteria.</p></div>';
                return;
            }

            let html = '';
            projects.forEach(project => {
                // Status class and badge
                let statusClass = '';
                let statusBadgeClass = '';
                switch (project.proj_status) {
                    case 'Ongoing':
                        statusClass = 'btn-warning';
                        statusBadgeClass = 'badge bg-warning';
                        break;
                    case 'Delayed':
                        statusClass = 'btn-danger';
                        statusBadgeClass = 'badge bg-danger';
                        break;
                    case 'Completed':
                        statusClass = 'btn-success';
                        statusBadgeClass = 'badge bg-success';
                        break;
                    case 'Not yet started':
                        statusClass = 'btn-primary';
                        statusBadgeClass = 'badge bg-primary';
                        break;
                    default:
                        statusClass = 'btn-secondary';
                        statusBadgeClass = 'badge bg-secondary';
                }

                html += `
                <div class="col-sm-3 mt-4">
                    <div class="card card-stats border-success d-flex">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <input type="checkbox" name="project_ids[]" value="${project.project_id}"
                                class="form-check-input project-checkbox" id="card${project.project_id}">
                            <div class="project-number text-center mb-2">
                                <span class="fs-3 fw-bold text-danger">
                                    ${project.project_id}
                                </span>
                            </div>
                            <div class="content text-center flex-grow-1 d-flex flex-column flex-grow-1">
                                <div>
                                    <p class="fw-bold fs-5 mb-1" style="color: green;">${project.project_name}</p>
                                    <p class="fw-bold mb-1">${project.client}</p>
                                    <p class="fw-bold mb-2">Location: ${project.project_location}</p>
                                    <div class="flex-spacer project-status-group mb-2">
                                        <span class="btn ${statusClass} status-display">${project.proj_status}</span>
                                    </div>
                                    <button type="button" class="btn btn-secondary project-view-details" data-bs-toggle="modal" data-bs-target="#projectModal${project.project_id}">
                                        View Details
                                    </button>
                                    <br>
                                    ${project.date_modified ? `<small class="text-muted mt-2">Modified: ${new Date(project.date_modified).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })}</small>` : (project.date_added ? `<small class="text-muted mt-2">Added: ${new Date(project.date_added).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })}</small>` : '')}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="projectModal${project.project_id}" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdrop2" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-success">
                                <h5 class="modal-title fw-bold text-white" id="projectModalLabel${project.project_id}">Project Description</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6 text-start mb-4">
                                        <p><span class="fw-bold">Client Name: </span>${project.client}</p>
                                        <p><span class="fw-bold">Location: </span>${project.project_location}</p>
                                        <p><span class="fw-bold">Project Engineer: </span>${project.project_engineer || ''}</p>
                                        <p><span class="fw-bold">Status: </span>
                                            <span class="${statusBadgeClass}">${project.proj_status}</span>
                                        </p>
                                        <p><span class="fw-bold">Start of Project: </span>${project.start_date}</p>
                                        <p><span class="fw-bold">End of Project: </span>${project.end_date}</p>
                                    </div>
                                    <div class="col-md-12 text-start">
                                        <h5 class="fw-bold">List of Equipment</h5>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>Type</th>
                                                        <th>Equip ID</th>
                                                        <th>Status</th>
                                                        <th>Operators</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${
                                                        project.equipments && project.equipments.length > 0
                                                        ? project.equipments.map(eq => `
                                                            <tr>
                                                                <td>${eq.equip_type_name || 'N/A'}</td>
                                                                <td>${eq.custom_equip_id || 'N/A'}</td>
                                                                <td>
                                                                    <span class="badge ${eq.equip_status === 'Active' ? 'bg-success' : 'bg-secondary'}">
                                                                        ${eq.equip_status || 'N/A'}
                                                                    </span>
                                                                </td>
                                                                <td>${eq.operator_name && eq.operator_name.trim() !== '' ? eq.operator_name : 'N/A'}</td>
                                                            </tr>
                                                        `).join('')
                                                        : `<tr><td colspan="4" class="text-center text-muted">No equipment assigned.</td></tr>`
                                                    }
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <form action="">
                                    <button type="button" name="edit" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#editModal${project.project_id}" data-bs-dismiss="modal">Edit</button>
                                </form>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                `;
            });

            container.innerHTML = html;
            updateSelectAllButton();
        }

        // Your existing functions remain the same
        function archiveSelected() {
            const checkedBoxes = document.querySelectorAll('.project-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('Please select at least one project to archive.');
                return;
            }
            if (confirm('Are you sure you want to archive the selected projects?')) {
                const form = document.getElementById('archiveForm');
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'archive_project';
                input.value = '1';
                form.appendChild(input);
                form.submit();
            }
        }

        function restoreSelected() {
            const checkedBoxes = document.querySelectorAll('.archive-checkbox:checked');
            if (checkedBoxes.length === 0) {
                showAlert('Please select at least one project to restore.', 'warning');
                return;
            }

            if (confirm('Are you sure you want to restore the selected projects?')) {
                const form = document.getElementById('restoreForm');
                if (!form) {
                    console.error('Restore form not found');
                    return;
                }

                // Add hidden input for restore action
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'restore_project';
                input.value = '1';
                form.appendChild(input);

                form.submit();
            }
            const style = document.createElement('style');
            style.textContent = `
                .alert {
                    animation: slideIn 0.5s ease-out;
                }
                @keyframes slideIn {
                    0% {
                        transform: translate(-50%, -40%);
                        opacity: 0;
                    }
                    100% {
                        transform: translate(-50%, -50%);
                        opacity: 1;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        function deleteSelected() {
            const checkedBoxes = document.querySelectorAll('.archive-checkbox:checked');
            if (checkedBoxes.length === 0) {
                showAlert('Please select at least one project to delete permanently.', 'warning');
                return;
            }

            if (confirm('Are you sure you want to permanently delete the selected projects? This action cannot be undone.')) {
                const form = document.getElementById('restoreForm');
                if (!form) {
                    console.error('Restore form not found');
                    return;
                }

                // Add hidden input for delete action (fix: use correct name)
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'delete_archived';
                input.value = '1';
                form.appendChild(input);

                form.submit();
            }
        }

        function showAlert(message, type) {
            const alertPlaceholder = document.getElementById('alertPlaceholder');
            if (!alertPlaceholder) {
                console.error('Alert placeholder not found.');
                return;
            }

            const wrapper = document.createElement('div');
            wrapper.innerHTML = [
                `<div class="alert alert-${type} alert-dismissible fade show" role="alert">`,
                `   <div>${message}</div>`,
                '   <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>',
                '</div>'
            ].join('');

            alertPlaceholder.append(wrapper);

            // Auto-dismiss after 5 second
            setTimeout(() => {
                bootstrap.Alert.getInstance(wrapper.querySelector('.alert'))?.close();
            }, 5000);
        }

        // Function to update the "Select All" button state
        function updateSelectAllButton() {
            const checkboxes = document.querySelectorAll('.project-checkbox');
            const checkedCount = document.querySelectorAll('.project-checkbox:checked').length;
            const selectAllBtn = document.getElementById('select-all-btn');

            if (checkedCount > 0) {
                selectAllBtn.classList.remove('d-none');
                // Check if all are selected to update the button's icon and text
                if (checkedCount === checkboxes.length) {
                    selectAllBtn.innerHTML = '<i class="fa-solid fa-square-xmark"></i> Cancel';
                    selectAllBtn.classList.remove('btn-outline-secondary');
                    selectAllBtn.classList.add('btn-danger', 'selected');
                } else {
                    selectAllBtn.innerHTML = '<i class="fa-solid fa-square-check"></i> Select All';
                    selectAllBtn.classList.remove('btn-danger', 'selected');
                    selectAllBtn.classList.add('btn-outline-secondary');
                }
            } else {
                selectAllBtn.classList.add('d-none');
            }
        }

        // Archive checkbox functionality
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllArchived = document.getElementById('selectAllArchived');
            if (selectAllArchived) {
                selectAllArchived.addEventListener('change', function() {
                    const checked = this.checked;
                    document.querySelectorAll('.archive-checkbox').forEach(cb => {
                        cb.checked = checked;
                    });
                });
            }

            // Optional: Uncheck "Select All" if any individual is unchecked
            document.querySelectorAll('.archive-checkbox').forEach(cb => {
                cb.addEventListener('change', function() {
                    if (!this.checked && selectAllArchived.checked) {
                        selectAllArchived.checked = false;
                    }
                });
            });
        });

        // Show alert 
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($alertMessage)): ?>
                showAlert("<?php echo $alertMessage; ?>", "<?php echo $alertType; ?>");
                // Remove query string so alert doesn't show on refresh
                if (window.history.replaceState) {
                    const url = window.location.protocol + "//" + window.location.host + window.location.pathname;
                    window.history.replaceState({
                        path: url
                    }, '', url);
                }
            <?php endif; ?>
        });

        // Show alert when a status is changed
        document.body.addEventListener('click', function(e) {
            if (e.target.closest('.status-change')) {
                showAlert('Status successfully changed', 'success');
            }
        });

        // Date validation for Add Project and Edit Project Modals
        document.addEventListener('DOMContentLoaded', function() {
            // For Add Project Modal
            const startDate = document.getElementById('StartDateInput');
            const endDate = document.getElementById('ExpectedCompletionDateInput');
            [startDate, endDate].forEach(function(input) {
                if (input) {
                    input.addEventListener('change', function() {
                        const today = new Date().toISOString().split('T')[0];
                        if (input.value < today) {
                            alert('You cannot select a past date.');
                            input.value = today;
                        }
                    });
                }
            });

            // For Edit Project Modal(s)
            document.querySelectorAll('input[type="date"][id^="StartDateInput"], input[type="date"][id^="ExpectedCompletionDateInput"]').forEach(function(input) {
                input.addEventListener('change', function() {
                    const today = new Date().toISOString().split('T')[0];
                    if (input.value < today) {
                        alert('You cannot select a past date.');
                        input.value = today;
                    }
                });
            });
        });

        // Update the checkDuplicate function
        function checkDuplicate(equipId, currentSelect) {
            const form = currentSelect.closest('form');
            // Corrected selector to target the equipment ID dropdowns
            const allIdSelects = form.querySelectorAll('.custom-equip-id');
            let count = 0;

            for (const select of allIdSelects) {
                // Count how many times the selected ID appears
                if (select.value && select.value === equipId) {
                    count++;
                }
            }

            // If count is greater than 1, it's a duplicate
            return count > 1;
        }

        // --- UPDATED EVENT LISTENER ---
        // This single listener now handles both equipment type and equipment ID changes
        document.body.addEventListener('change', function(e) {
            // Handles changing the equipment TYPE (existing functionality)
            if (e.target.classList.contains('equip-type-select')) {
                loadEquipmentIds(e.target);
            }

            // Handles changing the equipment ID to check for duplicates (new functionality)
            if (e.target.classList.contains('custom-equip-id')) {
                const selectElement = e.target;
                const selectedId = selectElement.value;

                // Only proceed if a valid equipment ID is selected
                if (!selectedId) {
                    return;
                }

                // Check for duplicates
                if (checkDuplicate(selectedId, selectElement)) {
                    alert('Duplicate Error: This equipment is already selected for the project.');
                    selectElement.value = ''; // Reset the invalid selection
                }
            }
        });

        // Update the handleEquipmentIdSelect function
        function handleEquipmentIdSelect(select) {
            const selectedId = select.value;
            if (selectedId && checkDuplicate(selectedId, select)) {
                alert('This equipment is already assigned to the project!');
                select.value = ''; // Reset selection
                return false;
            }
            return true;
        }

        // Load equipment IDs based on selected type
        document.addEventListener('DOMContentLoaded', function() {
            function loadEquipmentIds(equipTypeSelect, selectedEquipId = null) {
                const typeId = equipTypeSelect.value;
                const row = equipTypeSelect.closest('.equipment-row');
                const idSelect = row.querySelector('.custom-equip-id');

                let projectId = null;
                const form = equipTypeSelect.closest('form');
                const projectIdInput = form.querySelector('[name="edit_project_id"]');
                if (projectIdInput) {
                    projectId = projectIdInput.value;
                }

                idSelect.disabled = true;
                idSelect.innerHTML = '<option value="" selected disabled>Loading...</option>';

                if (typeId) {
                    const formData = new URLSearchParams();
                    formData.append('equip_type_id', typeId);
                    if (projectId) {
                        formData.append('project_id', projectId);
                    }

                    fetch('getAvailableEquipment.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            let options = '<option value="" selected disabled>Select Equipment ID</option>';
                            data.forEach(equip => {
                                const isSelected = (selectedEquipId && equip.custom_equip_id === selectedEquipId) ? 'selected' : '';
                                options += `<option value="${equip.custom_equip_id}" ${isSelected}>${equip.custom_equip_id}</option>`;
                            });
                            idSelect.innerHTML = options;
                            idSelect.disabled = false;
                        })
                        .catch(error => {
                            console.error('Error fetching equipment IDs:', error);
                            idSelect.innerHTML = '<option value="" selected disabled>Error loading</option>';
                        });
                } else {
                    idSelect.innerHTML = '<option value="" selected disabled>Select Equipment ID</option>';
                }
            }

            function createNewEquipmentRow() {
                return `
                    <div class="equipment-row mb-3 p-3 border rounded">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-6">
                                <label class="form-label visually-hidden">Equipment Type</label>
                                <select class="form-select equip-type-select" name="equip_type_id[]" required>
                                    <option value="" selected disabled>Select Equipment Type</option>
                                    <?php
                                    $typeQuery = "SELECT DISTINCT et.equip_type_id, et.equip_type_name FROM equip_type_tbl et INNER JOIN equip_tbl e ON et.equip_type_id = e.equip_type_id ORDER BY et.equip_type_name";
                                    $typeResult = mysqli_query($connection, $typeQuery);
                                    if ($typeResult) {
                                        while ($typeRow = mysqli_fetch_assoc($typeResult)) {
                                            echo "<option value='{$typeRow['equip_type_id']}'>{$typeRow['equip_type_name']}</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label visually-hidden">Equipment ID</label>
                                <select class="form-select custom-equip-id" name="custom_equip_id[]" required disabled>
                                    <option value="" selected disabled>Select Equipment ID</option>
                                </select>
                            </div>
                            <div class="col-md-1 text-end">
                                <button type="button" class="btn btn-danger btn-sm remove-equip-row">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }

            document.body.addEventListener('click', function(e) {
                // Add new row functionality
                if (e.target.closest('.add-equip-row')) {
                    const addBtn = e.target.closest('.add-equip-row');
                    const containerId = addBtn.dataset.container;
                    const container = document.getElementById(containerId);
                    container.insertAdjacentHTML('beforeend', createNewEquipmentRow());
                }

                // Remove row functionality
                if (e.target.closest('.remove-equip-row')) {
                    const rowToRemove = e.target.closest('.equipment-row');
                    const container = rowToRemove.closest('.assigned-equipment-container');
                    const rows = container.querySelectorAll('.equipment-row');
                    if (rows.length > 1) {
                        rowToRemove.remove();
                    } else {
                        alert('At least one equipment entry is required.');
                    }
                }
            });

            // Event listener for changes in equipment type dropdowns
            document.body.addEventListener('change', function(e) {
                if (e.target.classList.contains('equip-type-select')) {
                    loadEquipmentIds(e.target);
                }
            });

            // Initial load for existing rows in edit modal
            document.querySelectorAll('.edit-project-modal .equip-type-select').forEach(select => {
                const selectedEquipId = select.closest('.equipment-row').querySelector('.custom-equip-id').value;
                loadEquipmentIds(select, selectedEquipId);
            });
        });
    </script>
</body>

</html>