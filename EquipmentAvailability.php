<?php
require('./readAvailability.php');

// Query to get equipment with project locations
$availability_query = "
    SELECT e.*,
           COALESCE(p.project_location, 'Not Assigned') as location,
           COALESCE(p.project_name, 'No Project') as project_name,
           COALESCE(p.proj_status, '') as project_status    
    FROM equip_tbl e     
    LEFT JOIN proj_sched_tbl p ON e.assigned_proj_id = p.project_id     
    WHERE e.equip_status != 'archived'
    ORDER BY
        CASE
            WHEN e.equip_status = 'Idle' THEN 1
            WHEN e.equip_status = 'Active' THEN 2
            ELSE 3
        END,
        e.custom_equip_id";

$sqlavailability = mysqli_query($connection, $availability_query);

if (!$sqlavailability) {
    die("Query failed: " . mysqli_error($connection));
}

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['equipment_id']) && isset($data['status'])) {
    $equipment_id = mysqli_real_escape_string($connection, $data['equipment_id']);
    $status = mysqli_real_escape_string($connection, $data['status']);

    $query = "UPDATE equip_tbl 
              SET deployment_status = ? 
              WHERE custom_equip_id = ?";

    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "ss", $status, $equipment_id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($connection)]);
    }

    mysqli_stmt_close($stmt);
}

mysqli_close($connection);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Availability</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap");

        body {
            background-color: #e8edf2;
            font-family: "Poppins", sans-serif;
        }

        .card-header h5 {
            margin: 0;
        }

        img {
            height: 50px;
            width: 50px;
        }

        .offcanvas {
            --bs-offcanvas-width: 260px;
        }


        .navbar-toggler:focus {
            outline: none;
            box-shadow: none;
            border-style: none;
        }

        .card-body table {
            margin-bottom: 0;
        }

        .details-panel p {
            margin-bottom: 10px;
        }

        .statistics-card h6 {
            margin-bottom: 5px;
        }

        .statistics-card h3 {
            margin: 0;
        }

        table tbody .equipment,
        .tr .td {
            align-items: center;
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

        .details-panel {
            bottom: 79px;
        }

        table {
            height: 150px;
        }

        .card-body {
            box-shadow: 0 4px 6px rgba(12, 12, 12, 0.15);
        }

        /* .card-body:hover {
            transform: translateY(-3px);
        } */

        .main-content {
            margin-top: 80px;
        }

        .stats-container {
            margin-top: 20px;
        }


        /* .main-content .row {
            margin-top: 70px;
        } */
    </style>
</head>

<body>
    <!-- Nav -->
    <nav class="navbar bg-success fixed-top">
        <div class="container-fluid">
            <div>
                <button class="navbar-toggler border border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarNav" aria-controls="sidebarNav">
                    <i class="fa-solid fa-bars text-light"></i>
                </button>

                <h4 class="d-inline text-white fw-bold ms-3">
                    Equipment Availability
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
                    <div class="modal fade" id="seeallNotif" tabindex="-1" aria-labelledby="seeallNotifLabel" aria-hidden="true">
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

    <!-- Main Content -->
    <div class="container-fluid main-content" style="margin-top: 70px;">
        <!-- Page Header -->
        <!-- <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold">Equipment Availability</h3>
                </div>
            </div>
        </div> -->

        <!-- Statistics Cards Row -->
        <div class="row">
            <!-- Card 1 -->
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-success text-center statistics-card h-100">
                    <div class="card-body d-flex justify-content-between">
                        <div class="labels">
                            <span class="statistics-text fw-bold">Total Equipment</span>
                            <span class="fs-3 fw-bold text-success d-flex"><?php echo $stats['total_equipment']; ?></span>
                        </div>
                        <div class="svg">
                            <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" class="text-success mt-3 me-4">
                                <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
                                    <path d="M2 17a2 2 0 1 0 4 0a2 2 0 1 0-4 0m9 0a2 2 0 1 0 4 0a2 2 0 1 0-4 0m2 2H4m0-4h9" />
                                    <path d="M8 12V7h2a3 3 0 0 1 3 3v5" />
                                    <path d="M5 15v-2a1 1 0 0 1 1-1h7m8.12-2.12L18 5l-5 5m8.12-.12A3 3 0 0 1 19 15a3 3 0 0 1-2.12-.88z" />
                                </g>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            <!-- <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-success text-center statistics-card h-100">
                    <div class="card-body">
                        <span class="fw-bold">Total Equipment</span>
                        <p class="fs-3 fw-bold text-success mb-0"></p>
                    </div>
                </div>
            </div> -->
            <!-- Card 2 -->
            <div class=" col-lg-3 col-md-6 mb-3">
                <div class="card border-primary text-center statistics-card h-100 ">
                    <div class="card-body d-flex justify-content-between">
                        <div class="labels">
                            <span class="statistics-text fw-bold">Deployed Equipment</span>
                            <span class="fs-3 fw-bold text-primary d-flex"><?php echo $stats['deployed_count']; ?></span>
                        </div>
                        <div class="svg">
                            <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 48 48" class="text-primary mt-3 me-4">
                                <defs>
                                    <mask id="ipSCheckOne0">
                                        <g fill="none" stroke-linejoin="round" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">
                                            <path fill="#fff" stroke="#fff" d="M24 44a19.94 19.94 0 0 0 14.142-5.858A19.94 19.94 0 0 0 44 24a19.94 19.94 0 0 0-5.858-14.142A19.94 19.94 0 0 0 24 4A19.94 19.94 0 0 0 9.858 9.858A19.94 19.94 0 0 0 4 24a19.94 19.94 0 0 0 5.858 14.142A19.94 19.94 0 0 0 24 44Z" />
                                            <path stroke="#000" stroke-linecap="round" d="m16 24l6 6l12-12" />
                                        </g>
                                    </mask>
                                </defs>
                                <path fill="currentColor" d="M0 0h48v48H0z" mask="url(#ipSCheckOne0)" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            <!-- <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-primary text-center statistics-card h-100">
                    <div class="card-body">
                        <span class="fw-bold">Deployed Equipment</span>
                        <p class="fs-3 fw-bold text-primary mb-0"></p>
                    </div>
                </div>
            </div> -->
            <!-- Card 3 -->
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-warning text-center statistics-card h-100">
                    <div class="card-body d-flex justify-content-between">
                        <div class="labels">
                            <span class="statistics-text fw-bold">Available Equipment</span>
                            <span class="fs-3 fw-bold text-warning d-flex"><?php echo $stats['undeployed_count']; ?></span>
                        </div>
                        <div class="svg">
                            <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" class="text-warning mt-3 me-4">
                                <path fill="currentColor" fill-rule="evenodd"
                                    d="M11.217 3.553a1.75 1.75 0 0 1 1.566 0l7 3.5c.592.296.967.902.967 1.565V20a.75.75 0 0 1-1.5 0V8.618a.25.25 0 0 0-.138-.224l-7-3.5a.25.25 0 0 0-.224 0l-7 3.5a.25.25 
                            0 0 0-.138.224V20a.75.75 0 0 1-1.5 0V8.618c0-.663.375-1.269.967-1.565zM6.25 12c0-.966.784-1.75 1.75-1.75h8c.966 0 1.75.784 1.75 
                            1.75v7A1.75 1.75 0 0 1 16 20.75H8A1.75 1.75 0 0 1 6.25 19zM8 11.75a.25.25 0 0 0-.25.25v1.25h8.5V12a.25.25 0 0 0-.25-.25zm8.25 3h-8.5v1.5h8.5zm0 3h-8.5V19c0 .138.112.25.25.25h8a.25.25 0 0 0 .25-.25z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            <!-- <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-warning text-center statistics-card h-100">
                    <div class="card-body">
                        <span class="fw-bold">Undeployed Equipment</span>
                        <p class="fs-3 fw-bold text-warning mb-0"></p>
                    </div>
                </div>
            </div> -->
            <!-- Card 4 -->
            <div class="col-lg-3 col-md-4 mb-3">
                <div class="card border-danger text-center statistics-card">
                    <div class="card-body d-flex justify-content-between">
                        <div class="labels">
                            <span class="statistics-text fw-bold">Total Serviced</span>
                            <span class="fs-3 fw-bold text-danger d-flex"><?php echo $stats['serviced_count']; ?></span>
                        </div>
                        <div class="svg">
                            <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" class="text-danger mt-3 me-4">
                                <path fill="currentColor" d="M23.47 10.86L2.45.12a1 1 0 0 0-.91 1.78l16.89 8.63a.25.25 0 0 1 .13.28a.26.26 0 0 1-.24.19h-5.83a1 1 0 0 0-1 1v8.49a.25.25 0 0 1-.25.25h-1a.25.25 0 0 1-.25-.25V12a1.5 1.5 0 0 0-1.5-1.5H5.28a1.55 1.55 0 0 0-1.34.85l-2 4.56a.23.23 0 0 1-.12.12l-1.33.61a.93.93 0 0 0-.44.81v3.79a1 1 0 0 0 1 1h.51a.27.27 0 0 0 .19-.08a.3.3 0 0 0 .06-.2a2 2 0 0 1 0-.22a3.25 3.25 0 1 1 6.49 0a2 2 0 0 1 0 .22a.25.25 0 0 0 .06.2a.27.27 0 0 0 .19.08h7a.24.24 0 0 0 .24-.28v-.22a3.25 3.25 0 1 1 6.49 0a2 2 0 0 1 0 .22a.3.3 0 0 0 .06.2a.27.27 0 0 0 .19.08H23a1 1 0 0 0 1-1V12a1 1 0 0 0-.53-1.14M8 15.75a.25.25 0 0 1-.25.25H4.43a.24.24 0 0 1-.21-.12a.23.23 0 0 1 0-.24l1.35-3a.25.25 0 0 1 .22-.15h2a.25.25 0 0 1 .25.25Z" />
                                <path fill="currentColor" d="M8.74 9.5a.75.75 0 0 0 .75-.75v-.5a1.75 1.75 0 0 0-1.75-1.74h-1A1.75 1.75 0 0 0 5 8.25v.5a.75.75 0 0 0 .75.75Zm-6 12.24a2.25 2.25 0 1 0 4.5 0a2.25 2.25 0 1 0-4.5 0m13.99 0a2.25 2.25 0 1 0 4.5 0a2.25 2.25 0 1 0-4.5 0" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            <!-- <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-danger text-center statistics-card h-100">
                    <div class="card-body">
                        <span class="fw-bold">Total Serviced</span>
                        <p class="fs-3 fw-bold text-danger mb-0"></p>
                    </div>
                </div>
            </div> -->
        </div>
        <!-- Equipment Table and Details -->
        <div class="row">
            <!-- Equipment List -->
            <div class="col-lg-7 mb-4">
                <div class="card border-success h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Equipment List</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="search-container mb-3">
                                <i class="fas fa-search"></i>
                                <input type="text" class="form-control search-input" placeholder="Search equipment...">
                            </div>
                        </div>
                        <div class="table-responsive" style="height: 550px;">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Select</th>
                                        <th>Equipment ID </th><!-- get data from profiling -->
                                        <th>Deployment Status</th><!-- comes from availability -->
                                        <th>Location</th> <!-- get the location from project List -->
                                        <th>Condition</th><!-- get data from profiling -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($sqlavailability && mysqli_num_rows($sqlavailability) > 0) {
                                        while ($equipment = mysqli_fetch_assoc($sqlavailability)) {
                                            $equipStatus = $equipment['equip_status'] ?? 'Unknown';
                                            $deploymentStatus = 'Unavailable';
                                            $conditionClass = 'bg-danger';

                                            switch (strtolower($equipStatus)) {
                                                case 'active':
                                                    $deploymentStatus = 'Deployed';
                                                    $conditionClass = 'bg-success';
                                                    break;
                                                case 'idle':
                                                    $deploymentStatus = 'Available';
                                                    $conditionClass = 'bg-primary';
                                                    break;
                                                case 'under maintenance':
                                                case 'for maintenance':
                                                case 'for repair':
                                                case 'for reconditioning':
                                                case 'assemble process':
                                                case 'breakdown':
                                                case 'for demolization':
                                                case 'for disposal':
                                                case 'condemned':
                                                case 'for scrap':
                                                    $deploymentStatus = 'Unavailable';
                                                    $conditionClass = 'bg-danger';
                                                    break;
                                                default:
                                                    $deploymentStatus = 'Available';
                                                    $conditionClass = 'bg-secondary';
                                                    break;
                                            }

                                            $statusClass = '';
                                            switch (strtolower($deploymentStatus)) {
                                                case 'deployed':
                                                    $statusClass = 'bg-success';
                                                    break;
                                                case 'available':
                                                    $statusClass = 'bg-primary';
                                                    break;
                                                case 'unavailable':
                                                default:
                                                    $statusClass = 'bg-danger';
                                                    break;
                                            }
                                    ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" class="equipment" name="equipment[]"
                                                        value="<?php echo htmlspecialchars($equipment['custom_equip_id']); ?>">
                                                </td>
                                                <td><?php echo htmlspecialchars($equipment['custom_equip_id']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $statusClass; ?> text-white">
                                                        <?php echo htmlspecialchars(ucfirst($deploymentStatus)); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($equipment['location'] ?? 'Not Assigned'); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $conditionClass; ?> text-white">
                                                        <?php echo htmlspecialchars($equipStatus); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php
                                        }
                                    } else {
                                        ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No equipment found</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Equipment Details -->
            <div class="col-lg-5 mb-4">
                <div class="card border-success h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Equipment Details</h5>
                    </div>
                    <div class="card-body">

                        <div class="card mb-2">
                            <div class="card-body">
                                <h6 class="fw-bold mb-1">Equipment ID:</h6>
                                <p id="EquipmentDetails" class="mb-0">Select an equipment to view details</p>
                            </div>
                        </div>
                        <div class="card mb-2">
                            <div class="card-body">
                                <h6 class="fw-bold mb-1">Current Deployment Status:</h6>
                                <p id="CurrentDeploymentStatusDetails" class="mb-0">-</p>
                            </div>
                        </div>
                        <div class="card mb-2">
                            <div class="card-body">
                                <h6 class="fw-bold mb-1">Location:</h6>
                                <p id="LocationDetails" class="mb-0">-</p>
                            </div>
                        </div>
                        <div class="card mb-2">
                            <div class="card-body">
                                <h6 class="fw-bold mb-1">Deployment Start Date:</h6>
                                <p id="DeploymentStartDateDetails" class="mb-0">-</p>
                            </div>
                        </div>
                        <div class="card mb-2">
                            <div class="card-body">
                                <h6 class="fw-bold mb-1">Deployment End Date:</h6>
                                <p id="DeploymentEndDateDetails" class="mb-0">-</p>
                            </div>
                        </div>
                        <div class="card mb-2">
                            <div class="card-body">
                                <h6 class="fw-bold mb-1">Condition:</h6>
                                <p id="ConditionDetails" class="mb-0">-</p>
                            </div>
                        </div>
                        <div class="card mb-2">
                            <div class="card-body">
                                <h6 class="fw-bold mb-1">Maintenance Schedule:</h6>
                                <p id="MaintenanceScheduleDetails" class="mb-0">-</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Debug logging
            console.log('DOM fully loaded');

            // Handle equipment selection
            const equipmentCheckboxes = document.querySelectorAll('input.equipment');
            console.log('Found checkboxes:', equipmentCheckboxes.length);

            equipmentCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    console.log('Checkbox changed:', this.value, this.checked);
                    if (this.checked) {
                        const equipmentId = this.value;
                        console.log('Fetching details for:', equipmentId);
                        fetchEquipmentDetails(equipmentId);

                        // Uncheck other checkboxes
                        equipmentCheckboxes.forEach(cb => {
                            if (cb !== this) {
                                cb.checked = false;
                            }
                        });
                    }
                });
            });

            // Make the entire row clickable
            const tableRows = document.querySelectorAll('tbody tr');
            console.log('Found rows:', tableRows.length);

            tableRows.forEach(row => {
                row.style.cursor = 'pointer'; // Add pointer cursor to indicate clickability

                row.addEventListener('click', function(e) {
                    console.log('Row clicked');
                    // Don't trigger if clicking on the checkbox itself
                    if (e.target.type !== 'checkbox') {
                        const checkbox = this.querySelector('input.equipment');
                        if (checkbox) {
                            console.log('Toggling checkbox:', checkbox.value);
                            checkbox.checked = !checkbox.checked;

                            // Manually trigger the change event
                            const event = new Event('change');
                            checkbox.dispatchEvent(event);
                        }
                    }
                });
            });

            // Handle date range submission
            const dateRangeForm = document.getElementById('dateRangeForm');
            if (dateRangeForm) {
                dateRangeForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);

                    // Add the selected equipment IDs
                    const selectedEquipment = Array.from(document.querySelectorAll('.equipment:checked'))
                        .map(cb => cb.value);

                    if (selectedEquipment.length === 0) {
                        alert('Please select at least one equipment');
                        return;
                    }

                    formData.append('equipment_id', selectedEquipment[0]);

                    fetch('createAvailability.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                alert('Equipment availability updated successfully');
                                location.reload();
                            } else {
                                alert('Error: ' + data.message);
                            }
                        });
                });
            }

            // Enable search functionality
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('tbody tr');
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(searchTerm) ? '' : 'none';
                    });
                });
            }

            // Function to fetch equipment details
            function fetchEquipmentDetails(equipmentId) {
                console.log('Fetching details for:', equipmentId);

                // Make the AJAX request
                fetch('getEquipmentDetails.php?id=' + encodeURIComponent(equipmentId))
                    .then(response => {
                        console.log('Response status:', response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Received data:', data);

                        if (data.error) {
                            console.error('Error:', data.error);
                            return;
                        }

                        // Update the details panel with your existing IDs
                        if (document.getElementById('EquipmentDetails')) {
                            document.getElementById('EquipmentDetails').textContent = data.custom_equip_id || 'N/A';
                        }

                        if (document.getElementById('CurrentDeploymentStatusDetails')) {
                            document.getElementById('CurrentDeploymentStatusDetails').textContent = data.deployment_status || 'Undeployed';
                        }

                        // Updated to use formatted_location
                        if (document.getElementById('LocationDetails')) {
                            document.getElementById('LocationDetails').textContent = data.formatted_location || 'Not assigned';
                        }

                        if (document.getElementById('DeploymentStartDateDetails')) {
                            document.getElementById('DeploymentStartDateDetails').textContent = data.deployment_start_date || 'Not set';
                        }
                        if (document.getElementById('DeploymentEndDateDetails')) {
                            document.getElementById('DeploymentEndDateDetails').textContent = data.deployment_end_date || 'Not set';
                        }

                        if (document.getElementById('ConditionDetails')) {
                            document.getElementById('ConditionDetails').textContent = data.equip_status || 'Unknown';
                        }

                        if (document.getElementById('MaintenanceScheduleDetails')) {
                            document.getElementById('MaintenanceScheduleDetails').textContent = 'No maintenance scheduled';
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                    });
            }


            // Helper function to format dates
            function formatDate(dateString) {
                if (!dateString) return '';

                const date = new Date(dateString);
                if (isNaN(date.getTime())) return dateString; // Return as is if invalid

                const options = {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                };
                return date.toLocaleDateString('en-US', options);
            }

            // Update statistics counts
            function updateStatistics(stats) {
                if (document.getElementById('totalEquipment')) {
                    document.getElementById('totalEquipment').textContent = stats.total_equipment;
                }
                if (document.getElementById('deployedEquipment')) {
                    document.getElementById('deployedEquipment').textContent = stats.deployed_count;
                }
                if (document.getElementById('undeployedEquipment')) {
                    document.getElementById('undeployedEquipment').textContent = stats.undeployed_count;
                }
                if (document.getElementById('servicedEquipment')) {
                    document.getElementById('servicedEquipment').textContent = stats.serviced_count;
                }
            }
        });

        document.getElementById('saveDates')?.addEventListener('click', function() {
            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;
            if (start && end && new Date(start) <= new Date(end)) {
                document.getElementById('dateRangeInput').value = `${start} to ${end}`;
                const modal = bootstrap.Modal.getInstance(document.getElementById('dateRangeModal'));
                modal.hide();
            } else {
                alert("Please ensure both dates are filled and the end date is not earlier than the start date.");
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Add click event to all equipment rows
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                // Add pointer cursor to indicate clickability
                row.style.cursor = 'pointer';

                // Add click event
                row.addEventListener('click', function(e) {
                    // Don't trigger if clicking on checkbox
                    if (e.target.type === 'checkbox') return;

                    // Find the equipment ID from the row
                    const equipmentIdCell = this.querySelector('td:nth-child(2)');
                    if (equipmentIdCell) {
                        const equipmentId = equipmentIdCell.textContent.trim();
                        console.log('Clicked on equipment:', equipmentId);

                        // Check the checkbox in this row
                        const checkbox = this.querySelector('input[type="checkbox"]');
                        if (checkbox) {
                            // Uncheck all other checkboxes
                            document.querySelectorAll('input[type="checkbox"].equipment').forEach(cb => {
                                cb.checked = false;
                            });

                            // Check this checkbox
                            checkbox.checked = true;
                        }

                        // Highlight the selected row
                        rows.forEach(r => r.classList.remove('table-primary'));
                        this.classList.add('table-primary');

                        // Fetch and display equipment details
                        fetchEquipmentDetails(equipmentId);
                    }
                });
            });

            // Also handle checkbox changes directly
            document.querySelectorAll('input[type="checkbox"].equipment').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        const equipmentId = this.value;
                        console.log('Checkbox selected for equipment:', equipmentId);

                        // Uncheck all other checkboxes
                        document.querySelectorAll('input[type="checkbox"].equipment').forEach(cb => {
                            if (cb !== this) cb.checked = false;
                        });

                        // Highlight the selected row
                        rows.forEach(r => r.classList.remove('table-primary'));
                        this.closest('tr').classList.add('table-primary');

                        // Fetch and display equipment details
                        fetchEquipmentDetails(equipmentId);
                    }
                });
            });

            // Function to fetch equipment details
            function fetchEquipmentDetails(equipmentId) {
                console.log('Fetching details for:', equipmentId);

                // Make the AJAX request
                fetch('getEquipmentDetails.php?id=' + encodeURIComponent(equipmentId))
                    .then(response => {
                        console.log('Response status:', response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Received data:', data);

                        if (data.error) {
                            console.error('Error:', data.error);
                            return;
                        }

                        // Update the details panel with your existing IDs
                        if (document.getElementById('EquipmentDetails')) {
                            document.getElementById('EquipmentDetails').textContent = data.custom_equip_id || 'N/A';
                        }

                        if (document.getElementById('CurrentDeploymentStatusDetails')) {
                            document.getElementById('CurrentDeploymentStatusDetails').textContent = data.deployment_status || 'Undeployed';
                        }

                        if (document.getElementById('LocationDetails')) {
                            document.getElementById('LocationDetails').textContent = data.location || 'Not assigned';
                        }

                        if (document.getElementById('DeploymentStartDateDetails')) {
                            document.getElementById('DeploymentStartDateDetails').textContent = data.deployment_start_date || 'Not set';
                        }

                        if (document.getElementById('DeploymentEndDateDetails')) {
                            document.getElementById('DeploymentEndDateDetails').textContent = data.deployment_end_date || 'Not set';
                        }

                        if (document.getElementById('ConditionDetails')) {
                            document.getElementById('ConditionDetails').textContent = data.equip_status || 'Unknown';
                        }

                        if (document.getElementById('MaintenanceScheduleDetails')) {
                            document.getElementById('MaintenanceScheduleDetails').textContent = 'No maintenance scheduled';
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                    });
            }
        });
    </script>

</body>

</html>