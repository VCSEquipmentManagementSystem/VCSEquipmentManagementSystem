<?php
session_start();
// up Report
if (empty($_SESSION['userID'])) {
    header('Location: loginPage.php');
    exit;
}
include('./database.php');
include('./readReport.php'); // This file now fetches all necessary data
$currentReportStatus = "Your Current Status Here";
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

        .navbar-toggler:focus {
            outline: none;
            box-shadow: none;
            border-style: none;
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

        .modal-dialog-fullscreen {
            width: 100vw;
            height: 100vh;
            margin: 0;
            max-width: none;
        }

        .modal-content.fullscreen-modal-content {
            height: 100%;
            border-radius: 0;
            overflow-y: auto;
        }
    </style>
</head>

<body>
    <div class="container-fluid" style="margin-top: 70px;">
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
                        Reports
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
        <div class="row container-header mt-5">
            <!-- <div class="title-header d-flex justify-content-between">
                <h3 class="mb-4 fw-bold mt-3">
                    Reports
                </h3>
            </div> -->
        </div>
        <?php
        if (isset($_SESSION['message'])) {
            $alertClass = (isset($_SESSION['msg_type']) && $_SESSION['msg_type'] === 'danger') ? 'alert-danger' : 'alert-info';
            echo '<div id="session-alert" class="alert ' . $alertClass . '">' . htmlspecialchars($_SESSION['message']) . '</div>';
            unset($_SESSION['message']);
            unset($_SESSION['msg_type']);
        }
        ?>
        <div class="d-flex justify-content-end mt-3">
            <div class="search-container me-2">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control search-input" placeholder="Search">
            </div>
            <div class="justify-content-end mb-3">
                <button class="btn btn-outline-secondary me-2" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-arrow-down-wide-short"></i>
                </button>
                <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                    <li><a href="#" class="dropdown-item sort-option" data-sort="unsort">Unsort</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a href="#" class="dropdown-item sort-option" data-sort="asc">Sort by A-Z</a></li>
                    <li><a href="#" class="dropdown-item sort-option" data-sort="desc">Sort by Z-A</a></li>
                </ul>
                <button class="btn btn-outline-secondary me-2" type="submit" id="archive-selected">
                    <i class="fa-solid fa-box-archive"></i>
                </button>
                <button class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#archiveModal">
                    <i class="fa-solid fa-box-archive"></i>
                    <span class="badge bg-danger rounded-pill archive-badge" id="archiveCount">

                    </span>
                </button>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" id="AddReport" data-bs-target="#staticBackdrop">
                    <i class="fas fa-plus me-1"></i> Add Report
                </button>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card border-success h-100">
                    <div class="card-body text-center d-flex justify-content-between align-items-center">
                        <div class="labels">
                            <h6 class="card-title fw-bold">Total Reports</h6>
                            <span class="card-value fw-bold text-success"><?php echo count($reportData); ?></span>
                            <p class="card-period">For this month</p>
                        </div>
                        <div class="svg text-success">
                            <path fill="none" stroke="currentColor" stroke-linejoin="round" d="M7.563 1.545H2.5v10.91h9V5.364M7.563 1.545L11.5 5.364M7.563 1.545v3.819H11.5m-7 9.136h9v-7M4 7.5h6M4 5h2m-2 5h6" stroke-width="1" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card border-success h-100">
                    <div class="card-body text-center d-flex justify-content-between align-items-center">
                        <div class="labels">
                            <h6 class="card-title fw-bold">Reports Pending Review</h6>
                            <span class="card-value fw-bold text-warning"><?php echo count(array_filter($reportData, function ($row) {
                                                                                return $row['status'] == 'Pending';
                                                                            })); ?></span>
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
            <div class="col-md-4 mb-3">
                <div class="card border-success h-100">
                    <div class="card-body text-center d-flex justify-content-between align-items-center">
                        <div class="labels">
                            <h6 class="card-title fw-bold">In Progress Report</h6>
                            <span class="card-value fw-bold text-primary"><?php echo count(array_filter($reportData, function ($row) {
                                                                                return $row['status'] == 'In Progress';
                                                                            })); ?></span>
                            <p class="card-period">For this month</p>
                        </div>
                        <div class="svg text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M9 7H5.145a8.5 8.5 0 0 1 8.274-3.387a.5.5 0 0 0 .162-.986A10 10 0 0 0 12 2.5a9.52 9.52 0 0 0-7.5 3.677V2.5a.5.5 0 0 0-1 0v5A.5.5 0 0 0 4 8h5a.5.5 0 0 0 0-1m-1.5 7.5a.5.5 0 0 0-.5.5v3.855a8.5 8.5 0 0 1-3.387-8.274a.5.5 0 0 0-.986-.162a9.52 9.52 0 0 0 3.55 9.081H2.5a.5.5 0 0 0 0 1h5A.5.5 0 0 0 8 20v-5a.5.5 0 0 0-.5-.5M20 16h-5a.5.5 0 0 0 0 1h3.855a8.5 8.5 0 0 1-8.274 3.387a.5.5 0 0 0-.162.986A10 10 0 0 0 12 21.5a9.52 9.52 0 0 0 7.5-3.677V21.5a.5.5 0 0 0 1 0v-5a.5.5 0 0 0-.5-.5m1.5-12.5h-5a.5.5 0 0 0-.5.5v5a.5.5 0 0 0 1 0V5.14a8.3 8.3 0 0 1 2.358 2.612A8.44 8.44 0 0 1 20.5 12q0 .714-.113 1.419a.499.499 0 1 0 .986.162A10 10 0 0 0 21.5 12a9.44 9.44 0 0 0-1.275-4.747A9.3 9.3 0 0 0 17.828 4.5H21.5a.5.5 0 0 0 0-1" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card card-option text-center">
            <div class="btn-group" role="group" aria-label="Basic example">
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#pendingReportModal">Pending</button>
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#inprogressReportModal">In progress</button>
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#completedReportModal">Completed</button>
            </div>
        </div>
        <!-- In Progress ReportModal -->
        <div class="modal fade" id="inprogressReportModal" tabindex="-1" aria-labelledby="inprogressReportModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-success">
                        <h5 class="modal-title fw-bold text-white" id="inprogressReportModalLabel">In Progress Reports</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <!-- views all in progress reports history -->
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Report ID</th>
                                        <th>Equipment ID</th>
                                        <th>Report Type</th>
                                        <th>Date</th>
                                        <th>Reported By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $inProgressReports = array_filter($reportData, function ($row) {
                                        return $row['status'] == 'In Progress';
                                    });
                                    if (!empty($inProgressReports)):
                                        foreach ($inProgressReports as $report):
                                    ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($report['report_id']); ?></td>
                                                <td><?php echo htmlspecialchars($report['custom_equip_id'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($report['report_type']); ?></td>
                                                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($report['date']))); ?></td>
                                                <td><?php echo htmlspecialchars($report['reported_by_name'] ?? 'N/A'); ?></td>
                                            </tr>
                                        <?php
                                        endforeach;
                                    else:
                                        ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">No in-progress reports found.</td>
                                        </tr>
                                    <?php endif; ?>
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
        <!-- Completed ReportModal -->
        <div class="modal fade" id="completedReportModal" tabindex="-1" aria-labelledby="completedReportModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-success">
                        <h5 class="modal-title fw-bold text-white" id="completedReportModalLabel">Completed Reports</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <!-- views all completed reports history -->
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Report ID</th>
                                        <th>Equipment ID</th>
                                        <th>Report Type</th>
                                        <th>Date Completed</th>
                                        <th>Reported By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $completedReports = array_filter($reportData, function ($row) {
                                        return $row['status'] == 'Completed';
                                    });
                                    if (!empty($completedReports)):
                                        foreach ($completedReports as $report):
                                    ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($report['report_id']); ?></td>
                                                <td><?php echo htmlspecialchars($report['custom_equip_id'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($report['report_type']); ?></td>
                                                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($report['date_completed'] ?? $report['date']))); ?></td>
                                                <td><?php echo htmlspecialchars($report['reported_by_name'] ?? 'N/A'); ?></td>
                                            </tr>
                                        <?php
                                        endforeach;
                                    else:
                                        ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">No completed reports found.</td>
                                        </tr>
                                    <?php endif; ?>
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
        <!-- Pending ReportModal -->
        <div class="modal fade" id="pendingReportModal" tabindex="-1" aria-labelledby="pendingReportModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-success">
                        <h5 class="modal-title fw-bold text-white" id="pendingReportModalLabel">Pending Reports</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <!-- views all pending reports history -->
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Report ID</th>
                                        <th>Equipment ID</th>
                                        <th>Report Type</th>
                                        <th>Date</th>
                                        <th>Reported By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $pendingReports = array_filter($reportData, function ($row) {
                                        return $row['status'] == 'Pending';
                                    });
                                    if (!empty($pendingReports)):
                                        foreach ($pendingReports as $report):
                                    ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($report['report_id']); ?></td>
                                                <td><?php echo htmlspecialchars($report['custom_equip_id'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($report['report_type']); ?></td>
                                                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($report['date']))); ?></td>
                                                <td><?php echo htmlspecialchars($report['reported_by_name'] ?? 'N/A'); ?></td>
                                            </tr>
                                        <?php
                                        endforeach;
                                    else:
                                        ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">No pending reports found.</td>
                                        </tr>
                                    <?php endif; ?>
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
        <br>
        <div class="reports-container">
            <div class="reports-header">
                <div class="d-flex">
                    <!-- Form for report_tbl submission -->
                    <form id="reportForm" action="createReport.php" method="post"></form>
                    <!-- Modal 1: Add Report -->
                    <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel1" aria-hidden="true">
                        <div class="modal-dialog modal-md">
                            <div class="modal-content">
                                <div class="modal-header bg-success">
                                    <h5 class="modal-title fw-bold text-white" id="exampleModalLabel"> + Add report</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <label for="reportedByInput" class="form-label fw-bold">Reported by: <span class="text-danger">*</span></label>
                                        <?php
                                        $reportedByName = '';
                                        if (isset($profileData['name'])) { // Use 'name' from user_tbl
                                            $reportedByName = htmlspecialchars($profileData['name']);
                                        }
                                        echo '<input type="text" class="form-control" id="reportedByInput" name="reportedBy" value="' . $reportedByName . '" readonly form="reportForm">';
                                        ?>
                                        <label for="reportTypeInput" class="form-label fw-bold">Report type: <span class="text-danger">*</span></label>
                                        <select name="reportType" id="reportTypeInput" class="form-select" required>
                                            <option value="" selected disabled>-- Select Report Type --</option>
                                            <option value="Maintenance">Maintenance</option>
                                            <option value="Repair">Repair</option>
                                            <option value="Breakdown">Breakdown</option>
                                            <option value="Equipment Usage">Equipment Usage</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary next-modal-btn" data-current-modal="#staticBackdrop" data-next-modal-logic="true" data-fields-to-validate="reportedByInput,reportTypeInput" id="NextModal">Next</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Merged Modal for Report Details - REPLACE the existing mergedReportModal -->
                    <div class="modal fade" id="mergedReportModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="mergedReportModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-fullscreen">
                            <div class="modal-content fullscreen-modal-content">
                                <form id="mergedReportForm" action="createReport.php" method="post">
                                    <div class="modal-header bg-success">
                                        <h5 class="modal-title fw-bold text-white" id="mergedReportModalLabel">Report Details</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="reportedBy" id="hiddenReportedBy" value="<?php echo htmlspecialchars($profileData['name'] ?? ''); ?>">
                                        <input type="hidden" name="reportType" value="" id="hiddenReportType">

                                        <div id="jobOrderSection">
                                            <h5 class="fw-bold mt-3">Job Order</h5>
                                            <label for="EquipmentIdInput" class="form-label fw-bold">Equipment ID: <span class="text-danger">*</span></label>
                                            <select name="equipmentID" id="EquipmentIdInput" class="form-select" required>
                                                <option value="" selected disabled>-- Select Equipment ID --</option>
                                                <?php
                                                if (isset($equipmentData) && is_array($equipmentData)) {
                                                    foreach ($equipmentData as $equipment) { ?>
                                                        <option value="<?php echo htmlspecialchars($equipment['custom_equip_id']); ?>">
                                                            <?php echo htmlspecialchars($equipment['custom_equip_id']); ?>
                                                        </option>
                                                <?php }
                                                }
                                                ?>
                                            </select>

                                            <label for="OperatorInput" class="form-label fw-bold">Operator: <span class="text-danger">*</span></label>
                                            <select name="operatorName" id="OperatorInput" required class="form-select">
                                                <option selected disabled value="">-- Select Operator --</option>
                                                <?php
                                                if (isset($operatorData) && is_array($operatorData)) {
                                                    foreach ($operatorData as $Operator) { ?>
                                                        <option value="<?php echo htmlspecialchars($Operator['name']); ?>">
                                                            <?php echo htmlspecialchars($Operator['name']); ?>
                                                        </option>
                                                <?php }
                                                }
                                                ?>
                                            </select>

                                            <label for="InspectInput" class="form-label fw-bold">Inspect by: <span class="text-danger">*</span></label>
                                            <select type="text" name="inspectedBy" class="form-select" id="InspectInput" required>
                                                <option selected disabled value="">-- Select Personnel --</option>
                                                <?php
                                                // Add Chief Mechanic options
                                                if (isset($chiefMechanicData) && is_array($chiefMechanicData)) {
                                                    foreach ($chiefMechanicData as $ChiefMechanic) { ?>
                                                        <option value="<?php echo htmlspecialchars($ChiefMechanic['name']); ?>">
                                                            <?php echo htmlspecialchars($ChiefMechanic['name']); ?> (Chief Mechanic)
                                                        </option>
                                                    <?php }
                                                }
                                                // Add Operator options
                                                if (isset($operatorData) && is_array($operatorData)) {
                                                    foreach ($operatorData as $Operator) { ?>
                                                        <option value="<?php echo htmlspecialchars($Operator['name']); ?>">
                                                            <?php echo htmlspecialchars($Operator['name']); ?> (Operator)
                                                        </option>
                                                <?php }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <hr>
                                        <div id="problemDiagnosisSection">
                                            <h5 class="fw-bold mt-3">Problem Diagnosis</h5>
                                            <label for="ProblemEncounteredInput" class="form-label fw-bold">Problem Encountered: <span class="text-danger">*</span></label>
                                            <input type="text" name="problemEncounter" class="form-control" id="ProblemEncounteredInput" required>

                                            <label for="FinalDiagnosisInput" class="form-label fw-bold">Final Diagnosis: <span class="text-danger">*</span></label>
                                            <input type="text" name="finalDiagnosis" class="form-control" id="FinalDiagnosisInput" required>

                                            <label for="DetailsOfWorkdoneInput" class="form-label fw-bold">Details of workdone: <span class="text-danger">*</span></label>
                                            <input type="text" name="detailsOfWorkDone" class="form-control" id="DetailsOfWorkdoneInput" required>

                                            <label for="RemarksInput" class="form-label fw-bold">Remarks: (Optional)</label>
                                            <textarea name="remarksReport" class="form-control" id="RemarksInput"></textarea>

                                            <div class="form-check mt-3">
                                                <input class="form-check-input" type="checkbox" id="needPartRequirement" name="needPartRequirement">
                                                <label class="form-check-label fw-bold" for="needPartRequirement"> Need Part Requirement? </label>
                                            </div>
                                        </div>
                                        <hr>
                                        <div id="sparePartsSection" style="display: none;">
                                            <h5 class="fw-bold mt-3">Spare Part/Materials Requirement</h5>
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="request_date" class="form-label fw-bold">Request Date:</label>
                                                    <input type="date" id="request_date" class="form-control part-field" name="requestDate" readonly>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="date_needed" class="form-label fw-bold">Date Needed: <span class="text-danger">*</span></label>
                                                    <input type="date" name="dateNeeded" class="form-control part-field" id="date_needed">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="location" class="form-label fw-bold">Location: <span class="text-danger">*</span></label>
                                                    <select class="form-select part-field" name="location" id="location">
                                                        <option selected disabled value="">-- Select Hub --</option>
                                                        <option value="Manila">Manila</option>
                                                        <option value="Cagayan">Cagayan</option>
                                                        <option value="Ilocos Sur">Ilocos Sur</option>
                                                        <option value="Ilocos Norte">Ilocos Norte</option>
                                                        <option value="Laoag">Laoag</option>
                                                        <option value="Siquijor">Siquijor</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="requested_by" class="form-label fw-bold">Requested by:</label>
                                                    <input type="text" class="form-control" id="requested_by" name="requestedBy" value="<?php echo htmlspecialchars($profileData['name'] ?? ''); ?>" readonly>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="purpose_dropdown" class="form-label fw-bold">Purpose: <span class="text-danger">*</span></label>
                                                    <select class="form-select part-field" name="purpose" id="purpose_dropdown">
                                                        <option selected disabled value="">-- Select Purpose --</option>
                                                        <option value="For Maintenance">For Maintenance</option>
                                                        <option value="For Repair">For Repair</option>
                                                        <option value="For Breakdown">For Breakdown</option>
                                                        <option value="For Disposal">For Disposal</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div id="partListDisplay">
                                                <?php
                                                if (isset($report['spare_parts']) && is_array($report['spare_parts'])) {
                                                    foreach ($report['spare_parts'] as $part) {
                                                ?>
                                                        <div class="row mt-2 part-item-entry align-items-end">
                                                            <div class="col-sm-2">
                                                                <p><?php echo htmlspecialchars($part['quantity'] ?? 'N/A'); ?></p>
                                                            </div>
                                                            <div class="col-sm-2">
                                                                <p><?php echo htmlspecialchars($part['unit'] ?? 'N/A'); ?></p>
                                                            </div>
                                                            <div class="col-sm-3">
                                                                <p><?php echo htmlspecialchars($part['part_description'] ?? 'N/A'); ?></p>
                                                            </div>
                                                            <div class="col-sm-4">
                                                                <p><?php echo htmlspecialchars($part['remarks'] ?? 'N/A'); ?></p>
                                                            </div>
                                                            <div class="col-sm-1"></div>
                                                        </div>
                                                <?php
                                                    }
                                                } else {
                                                    echo "<p>No spare parts listed for this report.</p>";
                                                }
                                                ?>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-2"><label class="form-label fw-bold text-nowrap">Qty:<span class="text-danger">*</span></label></div>
                                                <div class="col-sm-2"><label class="form-label fw-bold text-nowrap">Unit:<span class="text-danger">*</span></label></div>
                                                <div class="col-sm-3"><label class="form-label fw-bold">Item Description:<span class="text-danger">*</span></label></div>
                                                <div class="col-sm-4"><label class="form-label fw-bold text-nowrap">Remarks:<span class="text-danger">*</span></label></div>
                                                <div class="col-sm-1"></div>
                                            </div>
                                            <div id="partItemContainer">
                                                <div class="row mt-2 part-item-entry align-items-end">
                                                    <div class="col-sm-2">
                                                        <input type="number" name="part_qty[]" class="form-control part-field" min="1">
                                                    </div>
                                                    <div class="col-sm-2">
                                                        <input type="text" name="part_unit[]" class="form-control part-field">
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <input type="text" name="part_description[]" class="form-control part-field">
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <input type="text" name="part_remarks[]" class="form-control part-field">
                                                    </div>
                                                    <div class="col-sm-1"></div>
                                                </div>
                                            </div>
                                            <div class="mt-4 text-start">
                                                <button type="button" id="addPartItemBtn" class="btn btn-primary mt-2"><i class="fa-solid fa-square-plus"></i></button>
                                            </div>
                                            <div class="row mt-3">
                                                <div class="col-md-6">
                                                    <label for="last_replacement_input" class="form-label fw-bold">Last Replacement Date: <span class="text-danger">*</span></label>
                                                    <input type="date" id="last_replacement_input" class="form-control part-field" name="lastReplacement">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="approval_status" class="form-label fw-bold">Approval Status:</label>
                                                    <input type="text" class="form-control" id="approval_status" name="approvalStatus" value="Pending Approval" readonly>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-success" id="mergedModalSubmitBtn" name="submit_main">Submit</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- Modal 5: Work Details -->
                    <div class="modal fade" id="staticBackdrop5" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel5" aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <!-- Form for Work Details submission -->
                                <form action="createReport.php" method="post" id="workDetailsForm">
                                    <input type="hidden" name="report_id" id="report_id_completed">
                                    <div class="modal-header bg-success">
                                        <h5 class="modal-title fw-bold text-white" id="staticBackdropLabel5">Work Details</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="card mb-2">
                                            <div class="card-body ">
                                                <label for="ConductedByInput" class="form-label fw-bold">Conducted by: <span class="text-danger">*</span></label>
                                                <select name="conductedBy" id="ConductedByInput" class="form-select" required>
                                                    <option selected disabled value="">-- Select Personnel --</option>
                                                    <?php
                                                    // Fetch all users who have an employee_id for this dropdown
                                                    // This is for conducted_by which is INT(11)
                                                    $allPersonnelQuery = "SELECT name FROM user_tbl WHERE employee_id IS NOT NULL ORDER BY name ASC";
                                                    $allPersonnelResult = mysqli_query($connection, $allPersonnelQuery);
                                                    if ($allPersonnelResult) {
                                                        while ($person = mysqli_fetch_assoc($allPersonnelResult)) { ?>
                                                            <option value="<?php echo htmlspecialchars($person['name']); ?>">
                                                                <?php echo htmlspecialchars($person['name']); ?>
                                                            </option>
                                                    <?php }
                                                    }
                                                    ?>
                                                </select>
                                                <label for="date_started_input" class="form-label fw-bold">Date Started: <span class="text-danger">*</span></label>
                                                <input type="date" id="date_started_input" class="form-control" name="dateStarted" required>
                                                <label for="date_completed_input" class="form-label fw-bold">Date Completed: <span class="text-danger">*</span></label>
                                                <input type="date" id="date_completed_input" class="form-control" name="dateCompleted" required>
                                                <label for="TimeStartedInput" class="form-label fw-bold">Time started: <span class="text-danger">*</span></label>
                                                <input type="time" name="timeStarted" class="form-control" id="TimeStartedInput" required>
                                                <label for="TimeCompletedInput" class="form-label fw-bold">Time completed: <span class="text-danger">*</span></label>
                                                <input type="time" name="timeCompleted" class="form-control" id="TimeCompletedInput" required>
                                            </div>
                                        </div>
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="card-header">
                                                    <h4 class="fw-bold mt-3">Trial Run/Turn Over</h4>
                                                </div>
                                                <label for="ConductedByInput2" class="form-label fw-bold">Conducted by: <span class="text-danger">*</span></label>
                                                <input type="text" name="conductedByChief" class="form-control mt-2" id="ConductedByInput2" required>
                                                <label for="AcceptedByInput" class="form-label fw-bold">Accepted by: <span class="text-danger">*</span></label>
                                                <select name="acceptedBy" id="AcceptedByInput" class="form-select" required>
                                                    <option selected disabled value="">-- Select Accepted by --</option>
                                                    <?php
                                                    // This is for acceptedBy which is VARCHAR(255)
                                                    // Fetch all users who have an employee_id for this dropdown
                                                    if (isset($allPersonnelForConductedByData) && is_array($allPersonnelForConductedByData)) {
                                                        foreach ($allPersonnelForConductedByData as $person) { ?>
                                                            <option value="<?php echo htmlspecialchars($person['name']); ?>">
                                                                <?php echo htmlspecialchars($person['name']); ?>
                                                            </option>
                                                    <?php }
                                                    }
                                                    ?>
                                                </select>
                                                <label for="JobCompletionInput" class="form-label fw-bold">Job Completion Verified by: <span class="text-danger">*</span></label>
                                                <select name="jobCompletionVerifiedBy" id="JobCompletionInput" class="form-select" required>
                                                    <option selected disabled value="">-- Select Job Completion Verified by --</option>
                                                    <?php
                                                    // This is for jobCompletionVerifiedBy which is VARCHAR(255)
                                                    // Fetch all users who have an employee_id for this dropdown
                                                    if (isset($allPersonnelForConductedByData) && is_array($allPersonnelForConductedByData)) {
                                                        foreach ($allPersonnelForConductedByData as $person) { ?>
                                                            <option value="<?php echo htmlspecialchars($person['name']); ?>">
                                                                <?php echo htmlspecialchars($person['name']); ?>
                                                            </option>
                                                    <?php }
                                                    }
                                                    ?>
                                                </select>

                                                <label for="RemarksInput5" class="form-label fw-bold">Remarks: (Optional)</label>
                                                <textarea name="workDetailsRemarks" class="form-control" id="RemarksInput5"></textarea>

                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-success" name="submit_work_details">Submit</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- Modal 6: Equipment Usage Report -->
                    <div class="modal fade" id="staticBackdrop6" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel6" aria-hidden="true">
                        <div class="modal-dialog modal-fullscreen">
                            <div class="modal-content">
                                <!-- Form for equip_usage_tbl submission -->
                                <form action="createReport.php" method="post">
                                    <div class="modal-header bg-success">
                                        <h5 class="modal-title fw-bold text-white" id="staticBackdropLabel6">Equipment Usage Report</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label for="ProjectNameInput" class="form-label fw-bold">Project Name: <span class="text-danger">*</span></label>
                                                <select name="projectName" id="ProjectNameInput" class="form-select" required>
                                                    <option value="" selected disabled>-- Select Project --</option>
                                                    <?php foreach ($projectData as $project): ?>
                                                        <option value="<?php echo htmlspecialchars($project['project_name']); ?>">
                                                            <?php echo htmlspecialchars($project['project_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <label for="TimeInInput" class="form-label fw-bold">Time in: <span class="text-danger">*</span></label>
                                                <input type="time" name="timeIn" class="form-control" id="TimeInInput" required>
                                                <label for="TimeOutInput" class="form-label fw-bold">Time out: <span class="text-danger">*</span></label>
                                                <input type="time" name="timeOut" class="form-control" id="TimeOutInput" required>
                                                <label for="OperatorUsageInput" class="form-label fw-bold">Operator: <span class="text-danger">*</span></label>
                                                <select name="operatorName" id="OperatorUsageInput" required class="form-select">
                                                    <option selected disabled value="">-- Select Operator --</option>
                                                    <?php
                                                    if (isset($operatorData) && is_array($operatorData)) {
                                                        foreach ($operatorData as $Operator) { ?>
                                                            <option value="<?php echo htmlspecialchars($Operator['name']); ?>">
                                                                <?php echo htmlspecialchars($Operator['name']); ?>
                                                            </option>
                                                    <?php }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="OperatingHoursInput" class="form-label fw-bold">Operating Hours: <span class="text-danger">*</span></label>
                                                <input type="text" name="operatingHours" class="form-control" id="OperatingHoursInput" required>
                                                <label for="NatureOfWorkInput" class="form-label fw-bold">Nature Of Work: <span class="text-danger">*</span></label>
                                                <input type="text" name="natureOfWork" class="form-control" id="NatureOfWorkInput" required>
                                                <label for="LogRemarksInput" class="form-label fw-bold">Log Remarks: (Optional)</label>
                                                <input type="text" name="logRemarks" class="form-control" id="LogRemarksInput" placeholder="Enter any remarks or notes">
                                                <label for="remarksUsageInput" class="form-label fw-bold">Remarks: (Optional)</label>
                                                <textarea name="usageRemarks" class="form-control" id="remarksUsageInput"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary back-modal-btn" data-current-modal="#staticBackdrop6" data-prev-modal="#staticBackdrop">Back</button>
                                        <button type="submit" class="btn btn-success" name="submit_usage">Submit</button>
                                    </div>
                                </form>
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
                            <!-- <th>Report ID</th> -->
                            <th>Equipment ID</th>
                            <th>Report Type</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData as $row): ?>
                            <tr>
                                <td>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" value="<?php echo htmlspecialchars($row['report_id']) ?>"></div>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($row['custom_equip_id']) ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($row['report_type'] ?? 'N/A') ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($row['date']) ?>
                                </td>
                                <td>
                                    <?php
                                    $status = $row['status'];
                                    $badgeClass = '';
                                    switch ($status) {
                                        case 'Pending':
                                            $badgeClass = 'bg-warning';
                                            break;
                                        case 'In Progress':
                                            $badgeClass = 'bg-primary';
                                            break;
                                        case 'Completed':
                                            $badgeClass = 'bg-success';
                                            break;
                                        default:
                                            $badgeClass = 'bg-secondary';
                                            break;
                                    }
                                    ?>
                                    <div class="dropdown">
                                        <button class="badge <?php echo $badgeClass; ?> text-white dropdown-toggle border-0" type="button" id="statusDropdown_<?php echo htmlspecialchars($row['report_id']); ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                            <?php echo htmlspecialchars($status); ?>
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="statusDropdown_<?php echo htmlspecialchars($row['report_id']); ?>">
                                            <li><button type="button" class="dropdown-item completed-button" data-bs-toggle="modal" data-bs-target="#staticBackdrop5" data-report-id="<?= $row['report_id'] ?>">Completed</button></li>
                                        </ul>
                                    </div>
                                </td>
                                <td class="actions-buttons">
                                    <button type="button" class="btn btn-secondary edit-report-btn"
                                        data-bs-toggle="modal" data-bs-target="#editReportModal"
                                        data-report-id="<?php echo htmlspecialchars($row['report_id']); ?>"
                                        data-usage-id="<?php echo htmlspecialchars($row['usage_id'] ?? ''); ?>"
                                        data-report-type="<?php echo htmlspecialchars($row['report_type']); ?>"
                                        data-report-status="<?php echo htmlspecialchars($row['status']); ?>"
                                        data-report-date="<?php echo htmlspecialchars($row['date'] ?? ''); ?>"

                                        data-equipment-id="<?php echo htmlspecialchars($row['custom_equip_id'] ?? ''); ?>"
                                        data-operator-name="<?php echo htmlspecialchars($row['operator_name'] ?? ''); ?>"
                                        data-inspected-by="<?php echo htmlspecialchars($row['inspected_by'] ?? ''); ?>"
                                        data-problem-encountered="<?php echo htmlspecialchars($row['problem_encountered'] ?? ''); ?>"
                                        data-final-diagnosis="<?php echo htmlspecialchars($row['final_diagnosis'] ?? ''); ?>"
                                        data-details-of-work-done="<?php echo htmlspecialchars($row['details_of_work_done'] ?? ''); ?>"
                                        data-remarks-report="<?php echo htmlspecialchars($row['remarks_report'] ?? ''); ?>"

                                        data-parts-list="<?php echo htmlspecialchars(json_encode($row['spare_parts'] ?? [])); ?>"
                                        data-purchase-request-id="<?php echo htmlspecialchars($row['purchase_req_id'] ?? ''); ?>"
                                        data-purchase-request-date="<?php echo htmlspecialchars($row['purchase_request_date'] ?? ''); ?>"
                                        data-date-needed="<?php echo htmlspecialchars($row['date_needed'] ?? ''); ?>"
                                        data-location="<?php echo htmlspecialchars($row['location'] ?? ''); ?>"
                                        data-purchase-requested-by="<?php echo htmlspecialchars($row['purchase_requested_by'] ?? ''); ?>"
                                        data-purpose="<?php echo htmlspecialchars($row['purpose'] ?? ''); ?>"
                                        data-purchase-request-status="<?php echo htmlspecialchars($row['purchase_request_status'] ?? ''); ?>"
                                        data-last-replacement-date="<?php echo htmlspecialchars($row['last_replacement_date'] ?? ''); ?>"

                                        data-conducted-by="<?php echo htmlspecialchars($row['conducted_by_name'] ?? ''); ?>"
                                        data-date-started="<?php echo htmlspecialchars($row['date_started'] ?? ''); ?>"
                                        data-time-started="<?php echo htmlspecialchars($row['time_started'] ?? ''); ?>"
                                        data-date-completed="<?php echo htmlspecialchars($row['date_completed'] ?? ''); ?>"
                                        data-time-completed="<?php echo htmlspecialchars($row['time_completed'] ?? ''); ?>"
                                        data-accepted-by="<?php echo htmlspecialchars($row['accepted_by'] ?? ''); ?>"
                                        data-job-completion-verified-by="<?php echo htmlspecialchars($row['job_completion_verified_by'] ?? ''); ?>"
                                        data-remarks-job-completion="<?php echo htmlspecialchars($row['remarks_job_completion'] ?? ''); ?>"

                                        data-project-name="<?php echo htmlspecialchars($row['project_name'] ?? ''); ?>"
                                        data-operating-hours="<?php echo htmlspecialchars($row['operating_hours'] ?? ''); ?>"
                                        data-time-in="<?php echo htmlspecialchars($row['time_in'] ?? ''); ?>"
                                        data-time-out="<?php echo htmlspecialchars($row['time_out'] ?? ''); ?>"
                                        data-nature-of-work="<?php echo htmlspecialchars($row['nature_of_work'] ?? ''); ?>"
                                        data-log-remarks="<?php echo htmlspecialchars($row['log_remarks'] ?? ''); ?>">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                        Edit
                                    </button>
                                    <button type="button" class="btn action-button bg-primary text-white view-report-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#viewModal"
                                        data-report-id="<?php echo htmlspecialchars($row['report_id'] ?? '') ?>"
                                        data-report-type="<?php echo htmlspecialchars($row['report_type'] ?? '') ?>"
                                        data-report-status="<?php echo htmlspecialchars($row['report_status'] ?? '') ?>"
                                        data-report-date="<?php echo htmlspecialchars($row['date'] ?? '') ?>"
                                        data-report-equipment-id="<?php echo htmlspecialchars($row['custom_equip_id'] ?? '') ?>"
                                        data-report-operator-name="<?php echo htmlspecialchars($row['operator_name'] ?? '') ?>"
                                        data-report-inspected-by-name="<?php echo htmlspecialchars($row['inspected_by'] ?? '') ?>"
                                        data-report-problem-encountered="<?php echo htmlspecialchars($row['problem_encountered'] ?? '') ?>"
                                        data-report-final-diagnosis="<?php echo htmlspecialchars($row['final_diagnosis'] ?? '') ?>"
                                        data-report-details-of-work-done="<?php echo htmlspecialchars($row['details_of_work_done'] ?? '') ?>"
                                        data-report-remarks-report="<?php echo htmlspecialchars($row['remarks_report'] ?? '') ?>"
                                        data-parts-list="<?php echo htmlspecialchars(json_encode($row['spare_parts'] ?? [])); ?>"
                                        data-report-part-name="<?php echo htmlspecialchars($row['part_name'] ?? '') ?>"
                                        data-report-quantity="<?php echo htmlspecialchars($row['quantity'] ?? ''); ?>"
                                        data-report-unit="<?php echo htmlspecialchars($row['unit'] ?? ''); ?>"
                                        data-report-part-description="<?php echo htmlspecialchars($row['part_description'] ?? $row['description'] ?? ''); ?>"
                                        data-report-part-remarks="<?php echo htmlspecialchars($row['part_remarks'] ?? ''); ?>"
                                        data-report-last-replacement-date="<?php echo htmlspecialchars($row['last_replacement_date'] ?? '') ?>"
                                        data-report-conducted-by-name="<?php echo htmlspecialchars($row['conducted_by_name'] ?? '') ?>"
                                        data-report-date-started="<?php echo htmlspecialchars($row['date_started'] ?? '') ?>"
                                        data-report-time-started="<?php echo htmlspecialchars($row['time_started'] ?? '') ?>"
                                        data-report-date-completed="<?php echo htmlspecialchars($row['date_completed'] ?? '') ?>"
                                        data-report-time-completed="<?php echo htmlspecialchars($row['time_completed'] ?? '') ?>"
                                        data-report-accepted-by="<?php echo htmlspecialchars($row['accepted_by'] ?? '') ?>"
                                        data-report-job-completion-verified-by="<?php echo htmlspecialchars($row['job_completion_verified_by'] ?? '') ?>"
                                        data-report-remarks-job-completion="<?php echo htmlspecialchars($row['remarks_job_completion'] ?? '') ?>"
                                        data-report-trial-conducted-by="<?php echo htmlspecialchars($row['conducted_by_chief'] ?? '') ?>"
                                        data-report-request-date="<?php echo htmlspecialchars($row['purchase_request_date'] ?? '') ?>"
                                        data-report-date-needed="<?php echo htmlspecialchars($row['date_needed'] ?? '') ?>"
                                        data-report-location="<?php echo htmlspecialchars($row['location'] ?? '') ?>"
                                        data-report-requested-by="<?php echo htmlspecialchars($row['purchase_requested_by'] ?? '') ?>"
                                        data-report-purpose="<?php echo htmlspecialchars($row['purpose'] ?? '') ?>"
                                        data-report-last-replacement-date="<?php echo htmlspecialchars($row['last_replacement'] ?? '') ?>"
                                        data-report-approval-status="<?php echo htmlspecialchars($row['purchase_request_status'] ?? 'Pending Approval') ?>"
                                        data-report-remarks-job-completion="<?php echo htmlspecialchars($row['work_details_remarks'] ?? '') ?>"

                                        data-usage-log-date="<?php echo htmlspecialchars($row['log_date'] ?? '') ?>"
                                        data-usage-time-in="<?php echo htmlspecialchars($row['time_in'] ?? '') ?>"
                                        data-usage-time-out="<?php echo htmlspecialchars($row['time_out'] ?? '') ?>"
                                        data-usage-operating-hours="<?php echo htmlspecialchars($row['operating_hours'] ?? '') ?>"
                                        data-usage-nature-of-work="<?php echo htmlspecialchars($row['nature_of_work'] ?? '') ?>"
                                        data-usage-log-remarks="<?php echo htmlspecialchars($row['log_remarks'] ?? '') ?>"
                                        data-usage-project-name="<?php echo htmlspecialchars($row['project_name'] ?? '') ?>"
                                        data-usage-equipment-id="<?php echo htmlspecialchars($row['custom_equip_id'] ?? '') ?>"
                                        data-usage-operator-name="<?php echo htmlspecialchars($row['operator_name'] ?? '') ?>">
                                        <i class="fa-solid fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- View Modal -->
            <div class="modal fade" id="viewModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="editModal" aria-hidden="true">
                <div class="modal-dialog modal-fullscreen">
                    <div class="modal-content">
                        <div class="modal-header bg-primary">
                            <h5 class="modal-title text-white" id="staticBackdropLabel">
                                <i class="fas fa-file-alt me-2"></i>Full Description Report
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <!-- Content will be loaded here via JavaScript -->
                            <div id="viewModalContent">
                                <!-- Job Order Information -->
                                <div class="card mb-4">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Job Order Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label fw-bold text-muted">Date:</label>
                                                <div class="border-bottom pb-1" id="view-job-date"></div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label fw-bold text-muted">Equipment ID:</label>
                                                <div class="border-bottom pb-1" id="view-equipment-id"></div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label fw-bold text-muted">Operator:</label>
                                                <div class="border-bottom pb-1" id="view-operator-name"></div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label fw-bold text-muted">Inspect by:</label>
                                                <div class="border-bottom pb-1" id="view-inspected-by-name"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Problem Diagnosis -->
                                <div class="card mb-4">
                                    <div class="card-header bg-warning text-dark">
                                        <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Problem Diagnosis</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold text-muted">Problem Encountered:</label>
                                                <div class="p-3 bg-light rounded" id="view-problem-encountered"></div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold text-muted">Final Diagnosis:</label>
                                                <div class="p-3 bg-light rounded" id="view-final-diagnosis"></div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold text-muted">Details of Work Done:</label>
                                            <div class="p-3 bg-light rounded" id="view-work-details"></div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold text-muted">Remarks:</label>
                                            <div class="p-3 bg-light rounded" id="view-diagnosis-remarks"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Spare Parts/Materials Requirement -->
                                <div class="card mb-4" id="view-spare-parts-card">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="fas fa-cogs me-2"></i>Spare Parts/Materials Requirement</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-bold text-muted">Request Date:</label>
                                                <div class="border-bottom pb-1" id="view-request-date"></div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-bold text-muted">Date Needed:</label>
                                                <div class="border-bottom pb-1" id="view-date-needed"></div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-bold text-muted">Location:</label>
                                                <div class="border-bottom pb-1" id="view-location"></div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold text-muted">Requested By:</label>
                                                <div class="border-bottom pb-1" id="view-requested-by"></div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold text-muted">Purpose:</label>
                                                <div class="border-bottom pb-1" id="view-purpose"></div>
                                            </div>
                                        </div>

                                        <div class="mt-4">
                                            <h6 class="text-success border-bottom pb-2">Parts/Materials List</h6>
                                            <div class="table-responsive mt-3">
                                                <table class="table table-striped table-hover">
                                                    <thead class="table-success">
                                                        <tr>
                                                            <th>Qty</th>
                                                            <th>Unit</th>
                                                            <th>Item Description</th>
                                                            <th>Remarks</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="view-spare-parts-table">
                                                        <!-- Data will be populated by JS -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="row mt-4">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold text-muted">Last Replacement Date:</label>
                                                <div class="border-bottom pb-1" id="view-last-replacement"></div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold text-muted">Approval Status:</label>
                                                <div class="border-bottom pb-1">
                                                    <span class="badge bg-warning" id="view-approval-status">Pending Approval</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Work Details -->
                                <div class="card mb-4" id="view-work-details-card">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0"><i class="fas fa-wrench me-2"></i>Work Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-bold text-muted">Conducted by:</label>
                                                <div class="border-bottom pb-1" id="view-conducted-by"></div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-bold text-muted">Date Started:</label>
                                                <div class="border-bottom pb-1" id="view-date-started"></div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-bold text-muted">Time Started:</label>
                                                <div class="border-bottom pb-1" id="view-time-started"></div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-bold text-muted">Date Completed:</label>
                                                <div class="border-bottom pb-1" id="view-date-completed"></div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-bold text-muted">Time Completed:</label>
                                                <div class="border-bottom pb-1" id="view-time-completed"></div>
                                            </div>
                                        </div>

                                        <div class="mt-4">
                                            <h6 class="text-primary border-bottom pb-2">Trial Run/Turn Over</h6>
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label fw-bold text-muted">Conducted by:</label>
                                                    <div class="border-bottom pb-1" id="view-trial-conducted-by"></div>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label fw-bold text-muted">Accepted by:</label>
                                                    <div class="border-bottom pb-1" id="view-accepted-by"></div>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label fw-bold text-muted">Job Completion Verified by:</label>
                                                    <div class="border-bottom pb-1" id="view-verified-by"></div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold text-muted">Remarks:</label>
                                                <div class="p-3 bg-light rounded" id="view-work-remarks"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Equipment Usage (Only if report_type is 'Equipment Usage') -->
                                <div class="card mb-4" id="view-equipment-usage-card" style="display: none;">
                                    <div class="card-header bg-dark text-white">
                                        <h6 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Equipment Usage</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label fw-bold text-muted">Equipment ID:</label>
                                                <div class="border-bottom pb-1" id="view-usage-equipment-id"></div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label fw-bold text-muted">Project Name:</label>
                                                <div class="border-bottom pb-1" id="view-project-name"></div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label fw-bold text-muted">Log Date:</label>
                                                <div class="border-bottom pb-1" id="view-log-date"></div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label fw-bold text-muted">Time In:</label>
                                                <div class="border-bottom pb-1" id="view-time-in"></div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label fw-bold text-muted">Time Out:</label>
                                                <div class="border-bottom pb-1" id="view-time-out"></div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label fw-bold text-muted">Operating Hours:</label>
                                                <div class="border-bottom pb-1" id="view-operating-hours"></div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label fw-bold text-muted">Operator:</label>
                                                <div class="border-bottom pb-1" id="view-usage-operator-name"></div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label fw-bold text-muted">Nature of Work:</label>
                                                <div class="border-bottom pb-1" id="view-nature-of-work"></div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold text-muted">Log Remarks:</label>
                                                <div class="p-3 bg-light rounded" id="view-log-remarks"></div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold text-muted">Remarks:</label>
                                                <div class="p-3 bg-light rounded" id="view-usage-remarks"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-primary" onclick="printReport()">
                                <i class="fas fa-print me-2"></i>Print Report
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Edit Modal -->
            <div class="modal fade" id="editReportModal" tabindex="-1" aria-labelledby="editReportModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-fullscreen">
                    <div class="modal-content fullscreen-modal-content">
                        <form id="editReportForm" action="updateReport.php" method="POST">
                            <div class="modal-header bg-secondary text-white">
                                <h5 class="modal-title fw-bold" id="editReportModalLabel">Edit Report</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <!-- Hidden fields for IDs and type -->
                                <input type="hidden" id="edit_report_id" name="report_id">
                                <input type="hidden" id="edit_usage_id" name="usage_id">
                                <input type="hidden" id="edit_report_type" name="reportType">
                                <input type="hidden" id="edit_purchase_request_id" name="purchase_request_id">

                                <!-- SECTION 1: REPORT DETAILS (Maintenance/Repair/Breakdown) -->
                                <div id="edit-report-details-section" style="display: none;">
                                    <div class="card mb-4">
                                        <div class="card-header bg-info text-white">
                                            <h6>Job Order & Diagnosis</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-3">
                                                <div class="col-md-3"><label class="form-label fw-bold">Date</label><input type="date" class="form-control" id="edit_date" name="date"></div>
                                                <div class="col-md-3"><label class="form-label fw-bold">Equipment ID</label>
                                                    <select class="form-select" id="edit_equipmentID" name="equipmentID">
                                                        <option value="">-- Select --</option>
                                                        <?php foreach ($equipmentData as $eq): ?><option value="<?= htmlspecialchars($eq['custom_equip_id']); ?>"><?= htmlspecialchars($eq['custom_equip_id']); ?></option><?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3"><label class="form-label fw-bold">Operator</label>
                                                    <select class="form-select" id="edit_operatorName" name="operatorName">
                                                        <option value="">-- Select --</option>
                                                        <?php foreach ($operatorData as $op): ?><option value="<?= htmlspecialchars($op['name']); ?>"><?= htmlspecialchars($op['name']); ?></option><?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3"><label class="form-label fw-bold">Inspected By</label>
                                                    <select class="form-select" id="edit_inspectedBy" name="inspectedBy">
                                                        <option value="">-- Select --</option>
                                                        <?php foreach ($allPersonnelForConductedByData as $p): ?><option value="<?= htmlspecialchars($p['name']); ?>"><?= htmlspecialchars($p['name']); ?></option><?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6"><label class="form-label fw-bold">Problem Encountered</label><textarea class="form-control" id="edit_problemEncountered" name="problemEncounter" rows="3"></textarea></div>
                                                <div class="col-md-6"><label class="form-label fw-bold">Final Diagnosis</label><textarea class="form-control" id="edit_finalDiagnosis" name="finalDiagnosis" rows="3"></textarea></div>
                                            </div>
                                            <div class="row mt-3">
                                                <div class="col-md-6"><label class="form-label fw-bold">Details of Work Done</label><textarea class="form-control" id="edit_detailsOfWorkDone" name="detailsOfWorkDone" rows="3"></textarea></div>
                                                <div class="col-md-6"><label class="form-label fw-bold">Remarks</label><textarea class="form-control" id="edit_remarksReport" name="remarksReport" rows="3"></textarea></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- SECTION 2: SPARE PARTS (Conditional & Editable) -->
                                <div id="edit-spare-parts-section" style="display: none;">
                                    <div class="card mb-4">
                                        <div class="card-header bg-success text-white">
                                            <h6>Spare Parts / Materials Requirement</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="edit_request_date" class="form-label fw-bold">Request Date:</label>
                                                    <input type="date" id="edit_request_date" class="form-control" name="requestDate">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="edit_date_needed" class="form-label fw-bold">Date Needed:</label>
                                                    <input type="date" name="dateNeeded" class="form-control" id="edit_date_needed">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="edit_location" class="form-label fw-bold">Location:</label>
                                                    <select class="form-select" name="location" id="edit_location">
                                                        <option selected disabled value="">-- Select Hub --</option>
                                                        <option value="Manila">Manila</option>
                                                        <option value="Cagayan">Cagayan</option>
                                                        <option value="Ilocos Sur">Ilocos Sur</option>
                                                        <option value="Ilocos Norte">Ilocos Norte</option>
                                                        <option value="Laoag">Laoag</option>
                                                        <option value="Siquijor">Siquijor</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="edit_requested_by" class="form-label fw-bold">Requested by:</label>
                                                    <input type="text" class="form-control" id="edit_requested_by" name="requestedBy" readonly>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="edit_purpose_dropdown" class="form-label fw-bold">Purpose:</label>
                                                    <select class="form-select" name="purpose" id="edit_purpose_dropdown">
                                                        <option selected disabled value="">-- Select Purpose --</option>
                                                        <option value="For Maintenance">For Maintenance</option>
                                                        <option value="For Repair">For Repair</option>
                                                        <option value="For Breakdown">For Breakdown</option>
                                                        <option value="For Disposal">For Disposal</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="row">
                                                <div class="col-sm-2"><label class="form-label fw-bold">Qty:</label></div>
                                                <div class="col-sm-2"><label class="form-label fw-bold">Unit:</label></div>
                                                <div class="col-sm-3"><label class="form-label fw-bold">Item Description:</label></div>
                                                <div class="col-sm-4"><label class="form-label fw-bold">Remarks:</label></div>
                                                <div class="col-sm-1"></div>
                                            </div>
                                            <div id="editPartItemContainer">
                                                <!-- Part items will be dynamically inserted here by JS -->
                                            </div>
                                            <div class="mt-2 text-start">
                                                <button type="button" id="editAddPartItemBtn" class="btn btn-primary mt-2"><i class="fa-solid fa-square-plus"></i> </button>
                                            </div>
                                            <hr>
                                            <div class="row mt-3">
                                                <div class="col-md-6">
                                                    <label for="edit_last_replacement_input" class="form-label fw-bold">Last Replacement Date:</label>
                                                    <input type="date" id="edit_last_replacement_input" class="form-control" name="lastReplacement">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="edit_approval_status" class="form-label fw-bold">Approval Status:</label>
                                                    <input type="text" class="form-control" id="edit_approval_status" name="approvalStatus" readonly>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- SECTION 3: WORK DETAILS (Conditional) -->
                                <div id="edit-work-details-section" style="display: none;">
                                    <div class="card mb-4">
                                        <div class="card-header bg-secondary text-white">
                                            <h6>Work Completion Details</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-3">
                                                <div class="col-md-4"><label class="form-label fw-bold">Conducted By</label>
                                                    <select class="form-select" id="edit_conductedBy" name="conductedBy">
                                                        <option value="">-- Select --</option>
                                                        <?php foreach ($allPersonnelForConductedByData as $p): ?><option value="<?= htmlspecialchars($p['name']); ?>"><?= htmlspecialchars($p['name']); ?></option><?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4"><label class="form-label fw-bold">Date Started</label><input type="date" class="form-control" id="edit_dateStarted" name="dateStarted"></div>
                                                <div class="col-md-4"><label class="form-label fw-bold">Time Started</label><input type="time" class="form-control" id="edit_timeStarted" name="timeStarted"></div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-4"><label class="form-label fw-bold">Accepted By</label>
                                                    <select class="form-select" id="edit_acceptedBy" name="acceptedBy">
                                                        <option value="">-- Select --</option>
                                                        <?php foreach ($allPersonnelForConductedByData as $p): ?><option value="<?= htmlspecialchars($p['name']); ?>"><?= htmlspecialchars($p['name']); ?></option><?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4"><label class="form-label fw-bold">Date Completed</label><input type="date" class="form-control" id="edit_dateCompleted" name="dateCompleted"></div>
                                                <div class="col-md-4"><label class="form-label fw-bold">Time Completed</label><input type="time" class="form-control" id="edit_timeCompleted" name="timeCompleted"></div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-4"><label class="form-label fw-bold">Job Completion Verified By</label>
                                                    <select class="form-select" id="edit_jobCompletionVerifiedBy" name="jobCompletionVerifiedBy">
                                                        <option value="">-- Select --</option>
                                                        <?php foreach ($allPersonnelForConductedByData as $p): ?><option value="<?= htmlspecialchars($p['name']); ?>"><?= htmlspecialchars($p['name']); ?></option><?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-8"><label class="form-label fw-bold">Work Remarks</label><textarea class="form-control" id="edit_remarksJobCompletion" name="remarks_job_completion" rows="1"></textarea></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- SECTION 4: EQUIPMENT USAGE -->
                                <div id="edit-equipment-usage-section" style="display: none;">
                                    <div class="card mb-4">
                                        <div class="card-header bg-dark text-white">
                                            <h6>Equipment Usage Details</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-3">
                                                <div class="col-md-4"><label class="form-label fw-bold">Equipment ID</label>
                                                    <select class="form-select" id="edit_usage_equipmentID" name="equipmentUsageId">
                                                        <option value="">-- Select --</option>
                                                        <?php foreach ($equipmentData as $eq): ?><option value="<?= htmlspecialchars($eq['custom_equip_id']); ?>"><?= htmlspecialchars($eq['custom_equip_id']); ?></option><?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4"><label class="form-label fw-bold">Project Name</label>
                                                    <select class="form-select" id="edit_usage_projectName" name="projectName">
                                                        <option value="">-- Select --</option>
                                                        <?php foreach ($projectData as $proj): ?><option value="<?= htmlspecialchars($proj['project_name']); ?>"><?= htmlspecialchars($proj['project_name']); ?></option><?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4"><label class="form-label fw-bold">Operator</label>
                                                    <select class="form-select" id="edit_usage_operatorName" name="operatorNameUsage">
                                                        <option value="">-- Select --</option>
                                                        <?php foreach ($operatorData as $op): ?><option value="<?= htmlspecialchars($op['name']); ?>"><?= htmlspecialchars($op['name']); ?></option><?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-3"><label class="form-label fw-bold">Time In</label><input type="time" class="form-control" id="edit_timeIn" name="timeIn"></div>
                                                <div class="col-md-3"><label class="form-label fw-bold">Time Out</label><input type="time" class="form-control" id="edit_timeOut" name="timeOut"></div>
                                                <div class="col-md-6"><label class="form-label fw-bold">Operating Hours</label><input type="text" class="form-control" id="edit_operatingHours" name="operatingHours"></div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6"><label class="form-label fw-bold">Nature of Work</label><textarea class="form-control" id="edit_natureOfWork" name="natureOfWork" rows="3"></textarea></div>
                                                <div class="col-md-6"><label class="form-label fw-bold">Log Remarks</label><textarea class="form-control" id="edit_logRemarks" name="logRemarks" rows="3"></textarea></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" name="editSubmit">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="archiveModal" tabindex="-1" aria-labelledby="archiveModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <form action="archiveReport.php" method="POST" id="archiveModalForm">
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title fw-bold" id="archiveModalLabel"><i class="fa-solid fa-box-archive me-2"></i>Archived Reports</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th><input class="form-check-input" type="checkbox" id="selectAllArchived"></th>
                                                <th>Report ID</th>
                                                <th>Equipment ID</th>
                                                <th>Report Type</th>
                                                <th>Report Date</th>
                                                <th>Date Archived</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($archivedReportData)): ?>
                                                <?php foreach ($archivedReportData as $archivedReport): ?>
                                                    <tr>
                                                        <td><input class="form-check-input" type="checkbox" name="report_ids[]" value="<?php echo htmlspecialchars($archivedReport['report_id']); ?>"></td>
                                                        <td><?php echo htmlspecialchars($archivedReport['report_id']); ?></td>
                                                        <td><?php echo htmlspecialchars($archivedReport['custom_equip_id'] ?? 'N/A'); ?></td>
                                                        <td><?php echo htmlspecialchars($archivedReport['report_type']); ?></td>
                                                        <td><?php echo htmlspecialchars(date('M d, Y', strtotime($archivedReport['report_date']))); ?></td>
                                                        <td><?php echo htmlspecialchars(date('M d, Y, h:i A', strtotime($archivedReport['archived_at']))); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">No archived reports found.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <div>
                                    <button type="submit" name="restore_selected" class="btn btn-success" onclick="return confirm('Are you sure you want to restore the selected reports?');">
                                        <i class="fa-solid fa-undo me-1"></i> Restore
                                    </button>
                                    <button type="submit" name="delete_selected" class="btn btn-danger" onclick="return confirm('Are you sure you want to PERMANENTLY DELETE the selected reports? This action cannot be undone.');">
                                        <i class="fa-solid fa-trash me-1"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide session alert
            const sessionAlert = document.getElementById('session-alert');
            if (sessionAlert) {
                setTimeout(() => {
                    const alertInstance = new bootstrap.Alert(sessionAlert);
                    alertInstance.close();
                }, 3000);
            }

            // Modal navigation function
            const modal1 = document.getElementById('staticBackdrop');
            const nextButton1 = modal1.querySelector('.next-modal-btn');
            const reportTypeInput = document.getElementById('reportTypeInput');
            const hiddenReportType = document.getElementById('hiddenReportType');
            const reportStatus = document.getElementById('reportStatus');

            const mergedModal = new bootstrap.Modal(document.getElementById('mergedReportModal'));
            const workDetailsModal = new bootstrap.Modal(document.getElementById('staticBackdrop5'));
            const equipmentUsageModal = new bootstrap.Modal(document.getElementById('staticBackdrop6'));

            const mergedModalNextBtn = document.getElementById('mergedModalNextBtn');
            const mergedModalSubmitBtn = document.getElementById('mergedModalSubmitBtn');

            nextButton1.addEventListener('click', function() {
                let allFieldsValid = true;
                const fieldsToValidate = ['reportedByInput', 'reportTypeInput', 'reportStatus'];
                fieldsToValidate.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (!field.value) {
                        allFieldsValid = false;
                        field.classList.add('is-invalid');
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });

                if (allFieldsValid) {
                    const reportTypeValue = reportTypeInput.value;
                    const reportStatusValue = reportStatus.value;

                    // Set the hidden field value BEFORE hiding the modal
                    if (hiddenReportType) {
                        hiddenReportType.value = reportTypeValue;
                    }

                    const currentModal = new bootstrap.Modal(modal1);
                    currentModal.hide();

                    if (reportTypeValue === 'Equipment Usage') {
                        equipmentUsageModal.show();
                    } else {
                        mergedModal.show();

                        // Show appropriate buttons based on status
                        if (reportStatusValue === 'Completed') {
                            mergedModalNextBtn.style.display = 'block';
                            mergedModalSubmitBtn.style.display = 'none';
                        } else {
                            mergedModalNextBtn.style.display = 'none';
                            mergedModalSubmitBtn.style.display = 'block';
                        }
                    }
                } else {
                    alert('Please fill out all required fields.');
                }
            });

            reportTypeInput.addEventListener('change', function() {
                if (hiddenReportType) {
                    hiddenReportType.value = this.value;
                }
            });

            // When switching modals, ensure the report type is preserved
            document.querySelectorAll('.next-modal-btn').forEach(button => {
                button.addEventListener('click', function() {
                    if (reportTypeInput && hiddenReportType) {
                        hiddenReportType.value = reportTypeInput.value;
                    }
                });
            });

            // Part requirement checkbox functionality
            const needPartCheckbox = document.getElementById('needPartRequirement');
            if (needPartCheckbox) {
                needPartCheckbox.addEventListener('change', function() {
                    const sparePartsSection = document.getElementById('sparePartsSection');
                    if (this.checked) {
                        sparePartsSection.style.display = 'block';

                        // Auto-populate request date with current date
                        const today = new Date();
                        const dateString = today.getFullYear() + '-' +
                            String(today.getMonth() + 1).padStart(2, '0') + '-' +
                            String(today.getDate()).padStart(2, '0');
                        document.getElementById('request_date').value = dateString;

                    } else {
                        sparePartsSection.style.display = 'none';
                        // Clear all part fields when unchecked
                        const partFields = document.querySelectorAll('.part-field');
                        partFields.forEach(field => {
                            field.classList.remove('is-invalid');
                            field.value = '';
                        });
                    }
                });
            }

            // Add part item functionality
            const addPartItemBtn = document.getElementById('addPartItemBtn');
            const partItemContainer = document.getElementById('partItemContainer');

            if (addPartItemBtn && partItemContainer) {
                addPartItemBtn.addEventListener('click', function() {
                    const newPartItem = document.createElement('div');
                    newPartItem.className = 'row mt-2 part-item-entry align-items-end';
                    newPartItem.innerHTML = `
                        <div class="col-sm-2">
                            <input type="number" name="part_qty[]" class="form-control part-field" min="1" form="reportForm">
                        </div>
                        <div class="col-sm-2">
                            <input type="text" name="part_unit[]" class="form-control part-field" form="reportForm">
                        </div>
                        <div class="col-sm-3">
                            <input type="text" name="part_description[]" class="form-control part-field" form="reportForm">
                        </div>
                        <div class="col-sm-4">
                            <input type="text" name="part_remarks[]" class="form-control part-field" form="reportForm">
                        </div>
                        <div class="col-sm-1">
                            <button type="button" class="btn btn-danger remove-part-item"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    `;

                    partItemContainer.appendChild(newPartItem);

                    // Add event listener for remove button
                    const removeBtn = newPartItem.querySelector('.remove-part-item');
                    removeBtn.addEventListener('click', function() {
                        newPartItem.remove();
                    });

                    // Set required attribute if checkbox is checked
                    if (needPartCheckbox.checked) {
                        const newPartFields = newPartItem.querySelectorAll('.part-field');
                        newPartFields.forEach(field => {
                            if (field.name !== 'part_remarks[]') {
                                field.required = true;
                            }
                        });
                    }
                });
            }

            // Handle completed status click to show work details modal
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('update-status-completed')) {
                    e.preventDefault();
                    const reportId = e.target.getAttribute('data-report-id');

                    // Hide current modal and show work details modal
                    const workDetailsModal = new bootstrap.Modal(document.getElementById('staticBackdrop5'));
                    workDetailsModal.show();

                    // Store report ID for later use if needed
                    document.getElementById('staticBackdrop5').setAttribute('data-current-report-id', reportId);
                }
            });

            // Auto-populate current date for report
            document.addEventListener('DOMContentLoaded', function() {
                // Set current date for report creation
                const today = new Date();
                const dateString = today.getFullYear() + '-' +
                    String(today.getMonth() + 1).padStart(2, '0') + '-' +
                    String(today.getDate()).padStart(2, '0');

                // If there's a date input in the report form, set it to today
                const reportDateInput = document.getElementById('report_date_input');
                if (reportDateInput) {
                    reportDateInput.value = dateString;
                    reportDateInput.style.display = 'none'; // Hide from user since it's auto-filled
                }
            });

            // Handle form submission for merged modal
            const reportForm = document.getElementById('reportForm');
            if (reportForm) {
                reportForm.addEventListener('submit', function(e) {

                    // Ensure report type is set before submission
                    const reportTypeFromFirstModal = document.getElementById('reportTypeInput').value;
                    const hiddenReportTypeField = document.getElementById('hiddenReportType');

                    if (hiddenReportTypeField && reportTypeFromFirstModal && !hiddenReportTypeField.value) {
                        hiddenReportTypeField.value = reportTypeFromFirstModal;
                    }

                    // First validate main required fields
                    const requiredMainFields = [
                        'equipmentID', 'operatorName', 'inspectedBy',
                        'problemEncounter', 'finalDiagnosis', 'detailsOfWorkDone'
                    ];

                    let mainFieldsValid = true;
                    requiredMainFields.forEach(fieldName => {
                        const field = document.querySelector(`[name="${fieldName}"]`);
                        if (field && !field.value.trim()) {
                            mainFieldsValid = false;
                            field.classList.add('is-invalid');
                        } else if (field) {
                            field.classList.remove('is-invalid');
                        }
                    });

                    if (!mainFieldsValid) {
                        e.preventDefault();
                        showCustomAlert('Please fill in all required main fields.');
                        return false;
                    }

                    // Check if spare parts section is visible and validate required fields
                    const sparePartsSection = document.getElementById('sparePartsSection');
                    const needPartCheckbox = document.getElementById('needPartRequirement');

                    if (needPartCheckbox && needPartCheckbox.checked && sparePartsSection.style.display !== 'none') {
                        // Validate purchase request required fields
                        const purchaseRequestFields = ['dateNeeded', 'location', 'purpose'];
                        let purchaseFieldsValid = true;

                        purchaseRequestFields.forEach(fieldName => {
                            const field = document.querySelector(`[name="${fieldName}"]`);
                            if (field && !field.value.trim()) {
                                purchaseFieldsValid = false;
                                field.classList.add('is-invalid');
                            } else if (field) {
                                field.classList.remove('is-invalid');
                            }
                        });

                        // Validate at least one part item row
                        const firstRowQty = document.querySelector('input[name="part_qty[]"]');
                        const firstRowUnit = document.querySelector('input[name="part_unit[]"]');
                        const firstRowDesc = document.querySelector('input[name="part_description[]"]');

                        if (!firstRowQty.value.trim() || !firstRowUnit.value.trim() || !firstRowDesc.value.trim()) {
                            purchaseFieldsValid = false;
                            if (!firstRowQty.value.trim()) firstRowQty.classList.add('is-invalid');
                            if (!firstRowUnit.value.trim()) firstRowUnit.classList.add('is-invalid');
                            if (!firstRowDesc.value.trim()) firstRowDesc.classList.add('is-invalid');
                        }

                        if (!purchaseFieldsValid) {
                            e.preventDefault();
                            showCustomAlert('Please fill in all required spare parts and purchase request fields.');
                            return false;
                        }
                    }

                    // If validation passes, allow form submission
                    console.log('Form validation passed, submitting...');
                    return true;
                });
            }

            // Function for edit modal
            const editReportModal = document.getElementById('editReportModal');
            if (editReportModal) {
                editReportModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const form = document.getElementById('editReportForm');
                    form.reset();

                    const reportType = button.dataset.reportType;
                    const reportStatus = button.dataset.reportStatus;

                    document.getElementById('edit_report_id').value = button.dataset.reportId;
                    document.getElementById('edit_usage_id').value = button.dataset.usageId || '';
                    document.getElementById('edit_report_type').value = reportType;
                    document.getElementById('edit_purchase_request_id').value = button.dataset.purchaseRequestId || '';

                    const reportDetailsSection = document.getElementById('edit-report-details-section');
                    const sparePartsSection = document.getElementById('edit-spare-parts-section');
                    const workDetailsSection = document.getElementById('edit-work-details-section');
                    const equipmentUsageSection = document.getElementById('edit-equipment-usage-section');

                    reportDetailsSection.style.display = 'none';
                    sparePartsSection.style.display = 'none';
                    workDetailsSection.style.display = 'none';
                    equipmentUsageSection.style.display = 'none';

                    if (reportType === 'Equipment Usage') {
                        equipmentUsageSection.style.display = 'block';
                        document.getElementById('editReportModalLabel').textContent = `Edit Equipment Usage Report #${button.dataset.reportId}`;

                        document.getElementById('edit_usage_equipmentID').value = button.dataset.equipmentId;
                        document.getElementById('edit_usage_projectName').value = button.dataset.projectName;
                        document.getElementById('edit_usage_operatorName').value = button.dataset.operatorName;
                        document.getElementById('edit_timeIn').value = button.dataset.timeIn;
                        document.getElementById('edit_timeOut').value = button.dataset.timeOut;
                        document.getElementById('edit_operatingHours').value = button.dataset.operatingHours;
                        document.getElementById('edit_natureOfWork').value = button.dataset.natureOfWork;
                        document.getElementById('edit_logRemarks').value = button.dataset.logRemarks;

                    } else {
                        reportDetailsSection.style.display = 'block';
                        document.getElementById('editReportModalLabel').textContent = `Edit ${reportType} Report #${button.dataset.reportId}`;

                        document.getElementById('edit_date').value = button.dataset.reportDate;
                        document.getElementById('edit_equipmentID').value = button.dataset.equipmentId;
                        document.getElementById('edit_operatorName').value = button.dataset.operatorName;
                        document.getElementById('edit_inspectedBy').value = button.dataset.inspectedBy;
                        document.getElementById('edit_problemEncountered').value = button.dataset.problemEncountered;
                        document.getElementById('edit_finalDiagnosis').value = button.dataset.finalDiagnosis;
                        document.getElementById('edit_detailsOfWorkDone').value = button.dataset.detailsOfWorkDone;
                        document.getElementById('edit_remarksReport').value = button.dataset.remarksReport;

                        const partItemContainer = document.getElementById('editPartItemContainer');
                        partItemContainer.innerHTML = ''; // Clear previous items

                        const partsList = JSON.parse(button.dataset.partsList || '[]');

                        if (button.dataset.purchaseRequestId) {
                            sparePartsSection.style.display = 'block';
                            // Assuming the PR data is on the main button dataset, not inside partsList
                            document.getElementById('edit_request_date').value = button.dataset.purchaseRequestDate || '';
                            document.getElementById('edit_date_needed').value = button.dataset.dateNeeded || '';
                            document.getElementById('edit_location').value = button.dataset.location || '';
                            document.getElementById('edit_requested_by').value = button.dataset.purchaseRequestedBy || '';
                            document.getElementById('edit_purpose_dropdown').value = button.dataset.purpose || '';
                            document.getElementById('edit_approval_status').value = button.dataset.purchaseRequestStatus || 'Pending Approval';
                            document.getElementById('edit_last_replacement_input').value = button.dataset.lastReplacementDate || '';

                            if (partsList.length > 0) {
                                partsList.forEach(part => {
                                    addPartItemRow(partItemContainer, part);
                                });
                            }
                        }

                        if (reportStatus === 'Completed') {
                            workDetailsSection.style.display = 'block';
                            document.getElementById('edit_conductedBy').value = button.dataset.conductedBy;
                            document.getElementById('edit_dateStarted').value = button.dataset.dateStarted;
                            document.getElementById('edit_timeStarted').value = button.dataset.timeStarted;
                            document.getElementById('edit_acceptedBy').value = button.dataset.acceptedBy;
                            document.getElementById('edit_dateCompleted').value = button.dataset.dateCompleted;
                            document.getElementById('edit_timeCompleted').value = button.dataset.timeCompleted;
                            document.getElementById('edit_jobCompletionVerifiedBy').value = button.dataset.jobCompletionVerifiedBy;
                            document.getElementById('edit_remarksJobCompletion').value = button.dataset.remarksJobCompletion;
                        }
                    }
                });

                const addPartBtn = document.getElementById('editAddPartItemBtn');
                const partContainer = document.getElementById('editPartItemContainer');
                if (addPartBtn && partContainer) {
                    addPartBtn.addEventListener('click', function() {
                        addPartItemRow(partContainer);
                    });
                }

                function addPartItemRow(container, part = {}) {
                    const newPartItem = document.createElement('div');
                    newPartItem.className = 'row mt-2 part-item-entry align-items-end';
                    newPartItem.innerHTML = `
                        <input type="hidden" name="part_item_id[]" value="${part.id || ''}">
                        <div class="col-sm-2">
                            <input type="number" name="part_qty[]" class="form-control" min="1" value="${part.qty || ''}">
                        </div>
                        <div class="col-sm-2">
                            <input type="text" name="part_unit[]" class="form-control" value="${part.unit || ''}">
                        </div>
                        <div class="col-sm-3">
                            <input type="text" name="part_description[]" class="form-control" value="${part.item_description || ''}">
                        </div>
                        <div class="col-sm-4">
                            <input type="text" name="part_remarks[]" class="form-control" value="${part.remarks || ''}">
                        </div>
                        <div class="col-sm-1">
                            <button type="button" class="btn btn-danger remove-part-item"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    `;
                    container.appendChild(newPartItem);
                    newPartItem.querySelector('.remove-part-item').addEventListener('click', function() {
                        newPartItem.remove();
                    });
                }
            }

            // Function for view modal
            const viewModal = document.getElementById('viewModal');
            if (viewModal) {
                viewModal.addEventListener('show.bs.modal', function(event) {
                    // Button that triggered the modal
                    const button = event.relatedTarget;

                    // Extract data from data-* attributes
                    const reportType = button.getAttribute('data-report-type');

                    // Job Order Information
                    const reportDate = button.getAttribute('data-report-date');
                    const equipmentId = button.getAttribute('data-report-equipment-id');
                    const operatorName = button.getAttribute('data-report-operator-name');
                    const inspectedByName = button.getAttribute('data-report-inspected-by-name');

                    // Problem Diagnosis
                    const problemEncountered = button.getAttribute('data-report-problem-encountered');
                    const finalDiagnosis = button.getAttribute('data-report-final-diagnosis');
                    const detailsOfWorkDone = button.getAttribute('data-report-details-of-work-done');
                    const diagnosisRemarks = button.getAttribute('data-report-remarks-report');
                    const partsData = JSON.parse(button.getAttribute('data-parts-list') || '[]');

                    // Spare Parts/Materials Requirement
                    const requestDate = button.getAttribute('data-report-request-date');
                    const dateNeeded = button.getAttribute('data-report-date-needed');
                    const location = button.getAttribute('data-report-location');
                    const requestedBy = button.getAttribute('data-report-requested-by');
                    const purpose = button.getAttribute('data-report-purpose');
                    const lastReplacement = button.getAttribute('data-report-last-replacement-date');
                    const approvalStatus = button.getAttribute('data-report-approval-status');

                    // Work Details
                    const conductedByName = button.getAttribute('data-report-conducted-by-name');
                    const dateStarted = button.getAttribute('data-report-date-started');
                    const timeStarted = button.getAttribute('data-report-time-started');
                    const dateCompleted = button.getAttribute('data-report-date-completed');
                    const timeCompleted = button.getAttribute('data-report-time-completed');

                    // Trial Run/Turn Over
                    const trialConductedBy = button.getAttribute('data-report-trial-conducted-by');
                    const acceptedBy = button.getAttribute('data-report-accepted-by');
                    const jobCompletionVerifiedBy = button.getAttribute('data-report-job-completion-verified-by');
                    const remarksJobCompletion = button.getAttribute('data-report-remarks-job-completion');

                    // Equipment Usage Specific Data
                    const usageLogDate = button.getAttribute('data-usage-log-date');
                    const usageTimeIn = button.getAttribute('data-usage-time-in');
                    const usageTimeOut = button.getAttribute('data-usage-time-out');
                    const usageOperatingHours = button.getAttribute('data-usage-operating-hours');
                    const usageNatureOfWork = button.getAttribute('data-usage-nature-of-work');
                    const usageLogRemarks = button.getAttribute('data-usage-log-remarks');
                    const usageProjectName = button.getAttribute('data-usage-project-name');
                    const usageEquipmentId = button.getAttribute('data-usage-equipment-id');
                    const usageOperatorName = button.getAttribute('data-usage-operator-name');


                    // Get the HTML elements
                    const viewJobDate = document.getElementById('view-job-date');
                    const viewEquipmentId = document.getElementById('view-equipment-id');
                    const viewOperatorName = document.getElementById('view-operator-name');
                    const viewInspectedByName = document.getElementById('view-inspected-by-name');

                    const viewProblemEncountered = document.getElementById('view-problem-encountered');
                    const viewFinalDiagnosis = document.getElementById('view-final-diagnosis');
                    const viewWorkDetails = document.getElementById('view-work-details');
                    const viewDiagnosisRemarks = document.getElementById('view-diagnosis-remarks');

                    const viewSparePartsTable = document.getElementById('view-spare-parts-table');

                    const viewConductedBy = document.getElementById('view-conducted-by');
                    const viewDateStarted = document.getElementById('view-date-started');
                    const viewTimeStarted = document.getElementById('view-time-started');
                    const viewDateCompleted = document.getElementById('view-date-completed');
                    const viewTimeCompleted = document.getElementById('view-time-completed');

                    const viewTrialConductedBy = document.getElementById('view-trial-conducted-by');
                    const viewAcceptedBy = document.getElementById('view-accepted-by');
                    const viewVerifiedBy = document.getElementById('view-verified-by');
                    const viewWorkRemarks = document.getElementById('view-work-remarks');

                    // Equipment Usage Specific Elements
                    const viewEquipmentUsageCard = document.getElementById('view-equipment-usage-card');
                    const viewUsageEquipmentId = document.getElementById('view-usage-equipment-id');
                    const viewProjectName = document.getElementById('view-project-name');
                    const viewLogDate = document.getElementById('view-log-date');
                    const viewTimeIn = document.getElementById('view-time-in');
                    const viewTimeOut = document.getElementById('view-time-out');
                    const viewOperatingHours = document.getElementById('view-operating-hours');
                    const viewNatureOfWork = document.getElementById('view-nature-of-work');
                    const viewLogRemarks = document.getElementById('view-log-remarks');
                    const viewUsageOperatorName = document.getElementById('view-usage-operator-name');
                    const viewUsageRemarks = document.getElementById('view-usage-remarks');

                    // Populate Spare Parts Request Fields
                    const viewRequestDate = document.getElementById('view-request-date');
                    const viewDateNeeded = document.getElementById('view-date-needed');
                    const viewLocation = document.getElementById('view-location');
                    const viewRequestedBy = document.getElementById('view-requested-by');
                    const viewPurpose = document.getElementById('view-purpose');
                    const viewLastReplacement = document.getElementById('view-last-replacement');
                    const viewApprovalStatus = document.getElementById('view-approval-status');


                    // Populate the modal fields based on report type
                    if (reportType === 'Equipment Usage') {
                        // Show Equipment Usage card, hide others
                        if (viewEquipmentUsageCard) viewEquipmentUsageCard.style.display = '';
                        document.querySelector('.card:has(#view-job-date)').style.display = 'none';
                        document.querySelector('.card:has(#view-problem-encountered)').style.display = 'none';
                        document.querySelector('.card:has(#view-spare-parts-table)').style.display = 'none';
                        document.querySelector('.card:has(#view-conducted-by)').style.display = 'none';

                        // Populate Equipment Usage fields
                        if (viewUsageEquipmentId) viewUsageEquipmentId.textContent = usageEquipmentId || 'N/A';
                        if (viewProjectName) viewProjectName.textContent = usageProjectName || 'N/A';
                        if (viewLogDate) viewLogDate.textContent = usageLogDate || 'N/A';
                        if (viewTimeIn) viewTimeIn.textContent = usageTimeIn || 'N/A';
                        if (viewTimeOut) viewTimeOut.textContent = usageTimeOut || 'N/A';
                        if (viewOperatingHours) viewOperatingHours.textContent = usageOperatingHours || 'N/A';
                        if (viewNatureOfWork) viewNatureOfWork.textContent = usageNatureOfWork || 'N/A';
                        if (viewLogRemarks) viewLogRemarks.textContent = usageLogRemarks || 'N/A';
                        if (viewUsageOperatorName) viewUsageOperatorName.textContent = usageOperatorName || 'N/A';
                        if (viewUsageRemarks) viewUsageRemarks.textContent = usageLogRemarks || 'N/A';
                    } else {
                        // --- SETUP: Hide usage card, show standard cards ---
                        if (viewEquipmentUsageCard) viewEquipmentUsageCard.style.display = 'none';
                        document.querySelector('.card:has(#view-job-date)').style.display = '';
                        document.querySelector('.card:has(#view-problem-encountered)').style.display = '';

                        // --- POPULATE: Standard Information (Always visible for these report types) ---
                        if (viewJobDate) viewJobDate.textContent = reportDate || 'N/A';
                        if (viewEquipmentId) viewEquipmentId.textContent = equipmentId || 'N/A';
                        if (viewOperatorName) viewOperatorName.textContent = operatorName || 'N/A';
                        if (viewInspectedByName) viewInspectedByName.textContent = inspectedByName || 'N/A';
                        if (viewProblemEncountered) viewProblemEncountered.textContent = problemEncountered || 'N/A';
                        if (viewFinalDiagnosis) viewFinalDiagnosis.textContent = finalDiagnosis || 'N/A';
                        if (viewWorkDetails) viewWorkDetails.textContent = detailsOfWorkDone || 'N/A';
                        if (viewDiagnosisRemarks) viewDiagnosisRemarks.textContent = diagnosisRemarks || 'N/A';

                        // --- LOGIC & POPULATE: Spare Parts Section ---
                        const sparePartsCard = document.getElementById('view-spare-parts-card');
                        if (viewSparePartsTable && sparePartsCard) {
                            viewSparePartsTable.innerHTML = ''; // Always clear previous table content

                            const tempDecoder = document.createElement('div');
                            tempDecoder.innerHTML = button.getAttribute('data-parts-list') || '[]';
                            const decodedPartsDataString = tempDecoder.textContent;
                            const partsList = JSON.parse(decodedPartsDataString);

                            if (partsList && partsList.length > 0) {
                                sparePartsCard.style.display = ''; // SHOW the card

                                // Populate all fields within the card now that we know it's visible
                                if (viewRequestDate) viewRequestDate.textContent = requestDate || 'N/A';
                                if (viewDateNeeded) viewDateNeeded.textContent = dateNeeded || 'N/A';
                                if (viewLocation) viewLocation.textContent = location || 'N/A';
                                if (viewRequestedBy) viewRequestedBy.textContent = requestedBy || 'N/A';
                                if (viewPurpose) viewPurpose.textContent = purpose || 'N/A';
                                if (viewLastReplacement) viewLastReplacement.textContent = lastReplacement || 'N/A';
                                if (viewApprovalStatus) {
                                    viewApprovalStatus.textContent = approvalStatus || 'Pending Approval';
                                    viewApprovalStatus.className = 'badge ' + (approvalStatus === 'approved' ? 'bg-success' : 'bg-warning');
                                }

                                // Populate the parts list table
                                partsList.forEach(part => {
                                    const newRow = viewSparePartsTable.insertRow();
                                    newRow.insertCell().textContent = part.qty || 'N/A';
                                    newRow.insertCell().textContent = part.unit || 'N/A';
                                    newRow.insertCell().textContent = part.item_description || 'N/A';
                                    newRow.insertCell().textContent = part.remarks || 'N/A';
                                });
                            } else {
                                sparePartsCard.style.display = 'none'; // HIDE the card if no parts exist
                            }
                        }

                        // --- LOGIC & POPULATE: Work Details Section ---
                        const workDetailsCard = document.getElementById('view-work-details-card');
                        if (workDetailsCard) {
                            const reportStatus = button.getAttribute('data-report-status');
                            const hasWorkDetails = button.getAttribute('data-report-conducted-by-name') ||
                                button.getAttribute('data-report-date-started') ||
                                button.getAttribute('data-report-date-completed');

                            if (reportStatus === 'Completed' && hasWorkDetails) {
                                workDetailsCard.style.display = ''; // SHOW the card

                                // Populate all fields within the card now that we know it's visible
                                if (viewConductedBy) viewConductedBy.textContent = conductedByName || 'N/A';
                                if (viewDateStarted) viewDateStarted.textContent = dateStarted || 'N/A';
                                if (viewTimeStarted) viewTimeStarted.textContent = timeStarted || 'N/A';
                                if (viewDateCompleted) viewDateCompleted.textContent = dateCompleted || 'N/A';
                                if (viewTimeCompleted) viewTimeCompleted.textContent = timeCompleted || 'N/A';

                                // Also populate the Trial Run section which is part of the same card
                                if (viewTrialConductedBy) viewTrialConductedBy.textContent = trialConductedBy || 'N/A';
                                if (viewAcceptedBy) viewAcceptedBy.textContent = acceptedBy || 'N/A';
                                if (viewVerifiedBy) viewVerifiedBy.textContent = jobCompletionVerifiedBy || 'N/A';
                                if (viewWorkRemarks) viewWorkRemarks.textContent = remarksJobCompletion || 'N/A';
                            } else {
                                workDetailsCard.style.display = 'none'; // HIDE the card
                            }
                        }
                    }
                });
            }

            // Handle project name selection
            const projectNameSelect = document.querySelector('[name="projectName"]');
            if (projectNameSelect) {
                projectNameSelect.addEventListener('change', function() {
                    const formDataset = this.closest('form').dataset;
                    if (this.value) {
                        formDataset.projectSelected = 'true';
                    } else {
                        formDataset.projectSelected = 'false';
                    }
                });
            }

            // Handle 'Next' buttons
            document.querySelectorAll('.next-modal-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const currentModalId = this.dataset.currentModal;
                    const fieldsToValidate = this.dataset.fieldsToValidate;
                    let isValid = true;

                    if (fieldsToValidate) {
                        const fieldIds = fieldsToValidate.split(',');
                        fieldIds.forEach(fieldId => {
                            const inputElement = document.getElementById(fieldId.trim());
                            if (inputElement) {
                                if (inputElement.tagName === 'SELECT' && inputElement.value === '') {
                                    isValid = false;
                                    inputElement.classList.add('is-invalid');
                                } else if (!inputElement.value.trim()) {
                                    isValid = false;
                                    inputElement.classList.add('is-invalid');
                                } else {
                                    inputElement.classList.remove('is-invalid');
                                }
                            }
                        });
                    }

                    if (!isValid) {
                        showCustomAlert('Please fill in all required fields.');
                        return;
                    }

                    if (this.id === 'NextModal') {
                        const reportTypeSelect = document.getElementById('reportTypeInput');
                        const selectedReportType = reportTypeSelect ? reportTypeSelect.value : '';

                        if (selectedReportType === 'Equipment Usage') {
                            showModal(currentModalId, '#staticBackdrop6');
                        } else if (selectedReportType === 'Maintenance' || selectedReportType === 'Repair' || selectedReportType === 'Breakdown') {
                            showModal(currentModalId, '#mergedReportModal');
                        } else {
                            showCustomAlert('Please select a Report Type.');
                        }
                    } else {
                        const nextModalId = this.dataset.nextModal;
                        if (nextModalId) {
                            showModal(currentModalId, nextModalId);
                        }
                    }
                });
            });

            // Handle Archive Selected button click
            const archiveBtn = document.getElementById('archive-selected');
            if (archiveBtn) {
                archiveBtn.addEventListener('click', function(e) {
                    e.preventDefault(); // Stop default button action

                    const checkedBoxes = document.querySelectorAll('#reportTable tbody input[type="checkbox"]:checked');

                    if (checkedBoxes.length === 0) {
                        // Use your custom alert function if available, otherwise use standard alert
                        if (typeof showCustomAlert === 'function') {
                            showCustomAlert('Please select at least one report to archive.');
                        } else {
                            alert('Please select at least one report to archive.');
                        }
                        return;
                    }

                    if (confirm('Are you sure you want to archive ' + checkedBoxes.length + ' selected report(s)? This action cannot be undone.')) {
                        // Create a form element dynamically to submit the data
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'archiveReport.php';

                        // Append a hidden input for each checked checkbox
                        checkedBoxes.forEach(checkbox => {
                            const hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = 'report_ids[]';
                            hiddenInput.value = checkbox.value;
                            form.appendChild(hiddenInput);
                        });

                        // Append the form to the body, submit it, and then remove it
                        document.body.appendChild(form);
                        form.submit();
                        document.body.removeChild(form);
                    }
                });
            }

            // Archive modal checkbox 
            const selectAllArchivedCheckbox = document.getElementById('selectAllArchived');
            if (selectAllArchivedCheckbox) {
                selectAllArchivedCheckbox.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('#archiveModalForm tbody input[type="checkbox"]');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = selectAllArchivedCheckbox.checked;
                    });
                });
            }

            // Handle 'Back' buttons
            document.querySelectorAll('.back-modal-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const currentModalId = this.dataset.currentModal;
                    const prevModalId = this.dataset.prevModal;
                    if (prevModalId) {
                        showModal(currentModalId, prevModalId);
                    }
                });
            });

            // Helper function to show/hide modals
            function showModal(currentModalId, nextModalId) {
                const currentModal = bootstrap.Modal.getInstance(document.querySelector(currentModalId));
                if (currentModal) {
                    currentModal.hide();
                }

                setTimeout(() => {
                    const nextModal = new bootstrap.Modal(document.querySelector(nextModalId));
                    nextModal.show();
                }, 300);
            }


            // Custom Alert function (replaces window.alert)
            function showCustomAlert(message) {
                const alertModalHtml = `
                    <div class="modal fade" id="customAlertModal" tabindex="-1" aria-labelledby="customAlertModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-sm">
                            <div class="modal-content">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title" id="customAlertModalLabel">Error</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    ${message}
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">OK</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Remove existing alert modal if any
                const existingAlertModal = document.getElementById('customAlertModal');
                if (existingAlertModal) {
                    existingAlertModal.remove();
                }

                // Append new alert modal to body
                document.body.insertAdjacentHTML('beforeend', alertModalHtml);

                // Show the new alert modal
                const customAlertModal = new bootstrap.Modal(document.getElementById('customAlertModal'));
                customAlertModal.show();

                // Make the modal vanish after 3 seconds
                setTimeout(() => {
                    customAlertModal.hide();
                }, 2000);
            }

            // Reset form button handling (for all forms within modals)
            document.querySelectorAll('.modal').forEach(modalEl => {
                modalEl.addEventListener('hidden.bs.modal', function() {
                    const form = this.querySelector('form');
                    if (form) {
                        form.reset(); // Resets all form fields
                        form.querySelectorAll('.is-invalid').forEach(input => {
                            input.classList.remove('is-invalid');
                        });
                    }
                });
            });

            // Select all checkbox handling
            const selectAllCheckbox = document.getElementById('selectAll');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('tbody .form-check-input');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = selectAllCheckbox.checked;
                    });
                });
            }

            // Search input handling
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    const tableRows = document.querySelectorAll('#reportTable tbody tr');
                    tableRows.forEach(row => {
                        const equipIdCell = row.querySelector('td:nth-child(2)'); // Column for Equipment ID
                        const reportTypeCell = row.querySelector('td:nth-child(3)'); // Column for Report Type

                        const equipIdText = equipIdCell ? equipIdCell.textContent.toLowerCase().trim() : '';
                        const reportTypeText = reportTypeCell ? reportTypeCell.textContent.toLowerCase().trim() : '';

                        // Show row if search term is found in either Equipment ID or Report Type
                        if (equipIdText.includes(searchTerm) || reportTypeText.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }

            // Sorting functionality
            const sortDropdown = document.querySelector('.dropdown-menu[aria-labelledby="sortDropdown"]');
            const tableBody = document.querySelector('#reportTable tbody');
            const originalRows = Array.from(tableBody.querySelectorAll('tr')); // Store original order for 'unsort'

            if (sortDropdown) {
                sortDropdown.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (e.target.classList.contains('sort-option')) {
                        const sortType = e.target.dataset.sort;
                        const rows = Array.from(tableBody.querySelectorAll('tr'));

                        if (sortType === 'unsort') {
                            // Restore original order
                            tableBody.innerHTML = ''; // Clear table
                            originalRows.forEach(row => tableBody.appendChild(row));
                            return;
                        }

                        rows.sort((a, b) => {
                            const equipIdA = a.querySelector('td:nth-child(2)').textContent.trim();
                            const equipIdB = b.querySelector('td:nth-child(2)').textContent.trim();

                            if (sortType === 'asc') {
                                return equipIdA.localeCompare(equipIdB);
                            } else if (sortType === 'desc') {
                                return equipIdB.localeCompare(equipIdA);
                            }
                            return 0;
                        });

                        // Re-append sorted rows
                        tableBody.innerHTML = ''; // Clear table
                        rows.forEach(row => tableBody.appendChild(row));
                    }
                });
            }

            window.printReport = function() {
                // Clone the modal's content to avoid altering the live page
                const printContent = document.getElementById('viewModalContent').cloneNode(true);

                // Create a new hidden iframe to print from
                const iframe = document.createElement('iframe');
                iframe.style.position = 'absolute';
                iframe.style.width = '0';
                iframe.style.height = '0';
                iframe.style.border = '0';
                document.body.appendChild(iframe);

                const doc = iframe.contentDocument || iframe.contentWindow.document;

                // Write a new HTML document into the iframe
                doc.open();
                doc.write('<html><head><title>Viking EMS - Report</title>');
                // Include Bootstrap for basic styling
                doc.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">');
                // Add custom styles for a cleaner print layout
                doc.write('<style>');
                doc.write(`
                    body { 
                        margin: 25px; 
                        -webkit-print-color-adjust: exact; /* Ensures background colors print in Chrome/Safari */
                        print-color-adjust: exact; /* Standard */
                    }
                    .card {
                        border: 1px solid #dee2e6 !important;
                        box-shadow: none !important;
                    }
                    .card-header {
                        background-color: #f8f9fa !important;
                    }
                    .bg-light {
                        background-color: #f8f9fa !important;
                    }
                `);
                doc.write('</style></head><body>');
                doc.write('<h3>Full Description Report</h3><hr>'); // Add a title to the printed page
                doc.write(printContent.innerHTML); // Add the report content
                doc.write('</body></html>');
                doc.close();

                // The print dialog must be called after the iframe has fully loaded
                iframe.onload = function() {
                    iframe.contentWindow.focus(); // Focus on the iframe
                    iframe.contentWindow.print(); // Trigger print dialog
                    // Remove the iframe after printing
                    setTimeout(() => {
                        document.body.removeChild(iframe);
                    }, 500);
                };
            };
        });

        // Populate hidden input in Completed Work Details modal
        document.addEventListener('DOMContentLoaded', function() {
            // Handle completed button click
            document.querySelectorAll('.completed-button').forEach(button => {
                button.addEventListener('click', function() {
                    const reportId = this.getAttribute('data-report-id');
                    document.getElementById('report_id_completed').value = reportId;
                });
            });
        });
    </script>
</body>

</html>