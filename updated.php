<?php
session_start();
include('./database.php');
include('./readReport.php');
include('./readPurchaseRequest.php');
include('./readProfiling.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Moderator') {
    header("Location: LoginPage.php");
    exit();
}

$pendingRepairCount = 0;
foreach ($reportData as $report) {
    if (
        isset($report['report_type']) && $report['report_type'] === 'Repair' &&
        isset($report['report_status']) && !in_array($report['report_status'], ['Completed'])
    ) {
        $pendingRepairCount++;
    }
}

$totalEquipmentCount = 0;
foreach ($equipmentData as $equipment) {
    if (isset($equipment['custom_equip_id']) && !empty($equipment['custom_equip_id'])) {
        $totalEquipmentCount++;
    }
}

$underMaintenanceCount = 0;
foreach ($equipmentData as $equipmentMaintenance) {
    if (isset($equipment['equip_status']) && $equipment['equip_status'] === 'Under Maintenance') {
        $underMaintenanceCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset=" UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Moderator Dashboard</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap");

        body {
            font-family: "Poppins", serif;
            background-color: #f0f2f5;
        }

        img {
            height: 50px;
            width: 50px;
        }

        .offcanvas {
            --bs-offcanvas-width: 280px;
        }

        .navbar-toggler:focus {
            outline: none;
            box-shadow: none;
            border-style: none;
        }

        .chart-container {
            min-height: 250px;
            position: relative;
        }

        .search-input {
            border-radius: 10px;
            width: 350px;
        }

        .search-input {
            width: 200px;
            padding-left: 35px;
            border-radius: 6px;
            border: 1px solid #ced4da;
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

        .form-control {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.10);
        }

        .card-body {
            box-shadow: 0 4px 6px rgba(12, 12, 12, 0.15);
        }

        .card-body:hover {
            transform: translateY(-3px);
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
    </style>
</head>


<body>
    <nav class="navbar bg-success fixed-top">
        <div class="container-fluid">
            <div>
                <button class="navbar-toggler border border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarNav" aria-controls="sidebarNav">
                    <i class="fa-solid fa-bars text-light"></i>
                </button>
                <a class="navbar-brand fw-bold text-white" href="#">
                    <img src="Pictures/LOGO.png" data-bs-toggle="tooltip" data-bs-placement="top" title="Logo" alt="Viking Logo" style="width: 30px; height: 30px;">
                    EMS
                </a>
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
    <div class="container mt-5">
        <div class="d-flex align-items-center justify-content-center mb-3">
            <h3 class="fw-bold mt-3">Moderator Dashboard</h3>
        </div>
        <div class="row">
            <div class="col-lg-9">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card border-success h-100">
                            <div class="card-body d-flex justify-content-between align-items-center mt-4">
                                <div class="text-center">
                                    <h6 class="card-title fw-bold mb-1">Total Equipment</h6>
                                    <h2 class="card-value fw-bold text-success mb-1"><?php echo $totalEquipmentCount; ?></h2>
                                    <p class="card-period mb-0">Registered in System</p>
                                </div>
                                <div>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" class="text-success mt-3">
                                        <path fill="currentColor" d="M11 17h2v-6h-2zm1-15A10 10 0 0 0 2 12a10 10 0 0 0 10 10a10 10 0 0 0 10-10A10 10 0 0 0 12 2m0 18a8 8 0 0 1-8-8a8 8 0 0 1 8-8a8 8 0 0 1 8 8a8 8 0 0 1-8 8m-1-12h2v2h-2z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card border-warning h-100">
                            <div class="card-body d-flex justify-content-between align-items-center mt-4">
                                <div class="text-center">
                                    <h6 class="card-title fw-bold mb-1">Under Maintenance</h6>
                                    <h2 class="card-value fw-bold text-warning mb-1"><?php echo $underMaintenanceCount; ?></h2>
                                    <p class="card-period mb-0">Currently In Progress</p>
                                </div>
                                <div>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" class="text-warning mt-3">
                                        <path fill="currentColor" d="m20.71 7.04l-4.47-4.47a1 1 0 0 0-1.41 0l-12.72 12.72a1 1 0 0 0-.29.71v4a1 1 0 0 0 1 1h4a1 1 0 0 0 .71-.29l12.72-12.72a1 1 0 0 0 0-1.41M5.41 18.59v-2.17l8.72-8.72l2.59 2.59l-8.72 8.72zm12.62-8.62L14 7.04l2.59-2.59l4.04 4.04z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card border-success h-100">
                            <div class="card-body d-flex justify-content-between align-items-center mt-4">
                                <div class="text-center">
                                    <h6 class="card-title fw-bold mb-1">Pending Repair</h6>
                                    <h2 class="card-value fw-bold text-danger mb-1"><?php echo $pendingRepairCount; ?></h2>
                                    <p class="card-period mb-0">Awaiting Approval</p>
                                </div>
                                <div>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 32 32" class="text-danger mt-3 ">
                                        <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" color="currentColor">
                                            <path d="M11 11L6 6M5 7.5L7.5 5l-3-1.5l-1 1zm14.975 1.475a3.5 3.5 0 0 0 .79-3.74l-1.422 1.422h-2v-2l1.422-1.422a3.5
                            3.5 0 0 0-4.529 4.53l-6.47 6.471a3.5 3.5 0 0 0-4.53 4.529l1.421-1.422h2v2l-1.422 1.422a3.5 3.5 0 0 0 4.53-4.528l6.472-6.472a3.5 3.5
                            0 0 0 3.738-.79" />
                                            <path d="m11.797 14.5l5.604 5.604a1.35 1.35 0 0 0 1.911 0l.792-.792a1.35 1.35 0 0 0 0-1.911L14.5 11.797" />
                                        </g>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-7 mb-4">
                        <div class="card border-success h-100">
                            <div class="card-body overflow-auto">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h4 class="fw-bold">Recent Reports</h4>
                                </div>
                                <hr>
                                <div class="reports-container overflow-auto" style="height: 595px;">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <?php
                                            if (!empty($reportData)) {
                                                foreach ($reportData as $report) {
                                            ?>
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <h5 class="fw-bold mb-0">
                                                            <?php

                                                            echo htmlspecialchars($report['report_type'] ?? 'Report');

                                                            if (in_array(($report['report_type'] ?? ''), ['Maintenance', 'Repair', 'Breakdown'])) {
                                                                echo ' Report';
                                                            }
                                                            ?>
                                                        </h5>
                                                        <?php
                                                        $status = $report['status'] ?? '';
                                                        $statusClass = 'bg-secondary';

                                                        if ($status === 'In Progress') {
                                                            $statusClass = 'bg-primary';
                                                        } elseif ($status === 'Completed') {
                                                            $statusClass = 'bg-success';
                                                        } elseif ($status === 'Rescheduled') {
                                                            $statusClass = 'bg-warning';
                                                        }
                                                        ?>
                                                        <div class="badge <?php echo $statusClass; ?>">
                                                            <?php echo htmlspecialchars($status === '' ? 'N/A' : $status); ?>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center mb-2">
                                                        <small>
                                                            <?php
                                                            // Display the report ID based on report type
                                                            if (isset($report['report_id_formatted'])) {
                                                                echo htmlspecialchars($report['report_id_formatted']);
                                                            } elseif (isset($report['usage_id'])) {
                                                                echo 'USG-' . str_pad(htmlspecialchars($report['usage_id']), 3, '0', STR_PAD_LEFT);
                                                            } else {
                                                                echo 'N/A';
                                                            }
                                                            ?>
                                                        </small>
                                                        <div class="vr mx-2" style="height:20px;"></div>
                                                        <small>Date: <?php echo htmlspecialchars($report['date'] ?? 'N/A'); ?></small>
                                                    </div>
                                                    <p class="mb-0">
                                                        <?php
                                                        // Display relevant details based on report type
                                                        $reportType = $report['report_type'] ?? '';
                                                        $customEquipId = htmlspecialchars($report['custom_equip_id'] ?? 'N/A');

                                                        if (in_array($reportType, ['Maintenance', 'Repair', 'Breakdown'])) {
                                                            $problem = htmlspecialchars($report['problem_encountered'] ?? 'N/A');
                                                            echo "Report for {$customEquipId} - {$problem}";
                                                        } elseif ($reportType === 'Equipment Usage') {
                                                            $projectName = htmlspecialchars($report['project_name'] ?? 'N/A');
                                                            echo "Usage log for {$customEquipId} on project: {$projectName}";
                                                        } else {
                                                            echo 'Details not available.';
                                                        }
                                                        ?>
                                                    </p>
                                                    <hr class="my-3"> <?php
                                                                    }
                                                                } else {
                                                                        ?>
                                                <p>No reports found.</p>
                                            <?php
                                                                }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="card border-success mb-4">
                            <div class="card-body">
                                <h5 class="fw-bold">Machine Life Span and Condition</h5>
                                <div class="chart-container">
                                    <canvas id="machineLifespanChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="card border-success mb-4    ">
                            <div class="card-body">
                                <h5 class="fw-bold">Reports </h5>
                                <div class="chart-container">
                                    <canvas id="reportsDistributionChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <!-- Quick Actions -->
                <div class="card bg-success-subtle mb-4">
                    <div class="card-body">
                        <h4 class="fw-bold">Quick Actions</h4>
                        <hr>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-light d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#QuickSubReport">
                                <i class="fas fa-file-alt text-success me-2"></i>
                                Submit New Report
                            </button>
                            <button type="button" class="btn btn-light d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#QuickSubEquipment">
                                <i class="fas fa-plus-circle text-success me-2"></i>
                                Add New Equipment
                            </button>
                            <button type="button" class="btn btn-light d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#QuickSubEquipmentMaintenance">
                                <i class="fa-solid fa-calendar-days text-success me-2"></i>
                                Schedule Maintenance
                            </button>
                            <button type="button" class="btn btn-light d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#PendingRequest">
                                <i class="fas fa-tasks text-success me-2"></i>
                                View Pending Requests
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Quick Report Modals -->
                <form id="reportForm" action="createReport.php" method="post"></form>
                <div class="modal fade" id="QuickSubReport" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog modal-md">
                        <div class="modal-content">
                            <div class="modal-header bg-success">
                                <h5 class="modal-title fw-bold text-white" id="exampleModalLabel">
                                    <i class="fas fa-file-alt text-white me-2"></i>Add report
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="reportForm"></form>
                                <div class="row">
                                    <label for="reportedByInput" class="form-label fw-bold">Reported by: <span class="text-danger">*</span></label>
                                    <?php
                                    $reportedByName = '';
                                    if (isset($profileData['name'])) {
                                        $reportedByName = htmlspecialchars($profileData['name']);
                                    }
                                    echo '<input type="text" class="form-control" id="reportedByInput" name="reportedBy" value="' . $reportedByName . '" readonly form="reportForm">';
                                    ?>
                                    <label for="reportTypeInput" class="form-label fw-bold">Report type: <span class="text-danger">*</span></label>
                                    <select name="reportType" id="reportTypeInput" class="form-select dropdown-toggle" required form="reportForm">
                                        <option selected disabled value="">-- Select Type of Report --</option>
                                        <option value="Maintenance">Maintenance</option>
                                        <option value="Repair">Repair</option>
                                        <option value="Breakdown">Breakdown</option>
                                        <option value="Equipment Usage">Equipment Usage</option>
                                    </select>

                                    <label for="reportStatus" class="form-label fw-bold">Status: <span class="text-danger">*</span></label>
                                    <select class="form-select" id="reportStatus" name="reportStatus" required form="reportForm">
                                        <option value="" selected disabled>-- Select Status --</option>
                                        <option value="Open">Open</option>
                                        <option value="In Progress">In Progress</option>
                                        <option value="Closed">Closed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" id="nextReportModalBtn">Next</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Maintenance/Repair/Breakdown Details -->
                <div class="modal fade" id="MaintenanceRepairBreakdownModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="MaintenanceRepairBreakdownModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-fullscreen">
                        <div class="modal-content">
                            <div class="modal-header bg-success">
                                <h5 class="modal-title fw-bold text-white" id="MaintenanceRepairBreakdownModalLabel">Maintenance/Repair/Breakdown Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row mb-3" id="mrbReportType" name="mrbReportType">
                                    <div class="col-md-6">
                                        <label for="report_date_input" class="form-label fw-bold">Date: <span class="text-danger">*</span></label>
                                        <input type="date" id="report_date_input" class="form-control" name="date" required form="reportForm">
                                        <label for="edit_equipmentID" class="form-label fw-bold">Equipment ID (Custom): <span class="text-danger">*</span></label>
                                        <select class="form-control" id="edit_equipmentID" name="equipmentID" form="reportForm" required>
                                            <option selected disabled value="">-- Select Equipment --</option>
                                            <?php foreach ($equipmentData as $equipment): ?>
                                                <option value="<?php echo htmlspecialchars($equipment['custom_equip_id']); ?>">
                                                    <?php echo htmlspecialchars($equipment['custom_equip_id']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="edit_operatorName" class="form-label fw-bold">Operator Name: <span class="text-danger">*</span></label>
                                        <select class="form-control" id="edit_operatorName" name="operatorName" form="reportForm" required>
                                            <option selected disabled value="">-- Select Operator --</option>
                                            <?php foreach ($operatorData as $operator): ?>
                                                <option value="<?php echo htmlspecialchars($operator['name']); ?>">
                                                    <?php echo htmlspecialchars($operator['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="edit_problemEncounter" class="form-label fw-bold">Problem Encountered: <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="edit_problemEncounter" form="reportForm" name="problemEncounter" rows="3" required></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="edit_finalDiagnosis" class="form-label fw-bold">Final Diagnosis: <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="edit_finalDiagnosis" name="finalDiagnosis" rows="3" form="reportForm" required></textarea>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="edit_detailsOfWork" class="form-label fw-bold">Details of Work Done: <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="edit_detailsOfWork" name="detailsOfWork" rows="3" form="reportForm" required></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="edit_diagnosisRemarks" class="form-label fw-bold">Diagnosis Remarks(Optional)</label>
                                        <textarea class="form-control" id="edit_diagnosisRemarks" name="diagnosisRemarks" rows="3" form="reportForm" required></textarea>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="edit_partId" class="form-label fw-bold">Spare Part/Materials: <span class="text-danger">*</span></label>
                                        <select class="form-control" id="edit_partId" name="partId" form="reportForm" required>
                                            <option selected disabled value="">-- Select Part --</option>
                                            <?php foreach ($inventoryData as $part): ?>
                                                <option value="<?php echo htmlspecialchars($part['part_id']); ?>">
                                                    <?php echo htmlspecialchars($part['part_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="edit_description" class="form-label fw-bold">Description (Part/Material): <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="edit_description" name="description" form="reportForm" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="edit_quantity" class="form-label fw-bold">Quantity: <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="edit_quantity" name="quantity" form="reportForm" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="edit_unit" class="form-label fw-bold">Unit: <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="edit_unit" name="unit" form="reportForm" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="edit_lastReplacement" class="form-label fw-bold">Last Replacement Date: <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="edit_lastReplacement" name="lastReplacement" form="reportForm" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="edit_conductedBy" class="form-label fw-bold">Conducted By: <span class="text-danger">*</span></label>
                                        <select class="form-control" id="edit_conductedBy" name="conductedBy" form="reportForm" required>
                                            <option value="">-- Select Personnel --</option>
                                            <?php foreach ($allPersonnelForConductedByData as $person): ?>
                                                <option value="<?php echo htmlspecialchars($person['name']); ?>">
                                                    <?php echo htmlspecialchars($person['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="edit_conductedByChief" class="form-label fw-bold">Conducted By (Trial Run): <span class="text-danger">*</span></label>
                                        <select class="form-control" id="edit_conductedByChief" name="conductedByChief" form="reportForm" required>
                                            <option selected disabled value="">-- Select Chief Mechanic --</option>
                                            <?php foreach ($chiefMechanicData as $chief): ?>
                                                <option value="<?php echo htmlspecialchars($chief['name']); ?>">
                                                    <?php echo htmlspecialchars($chief['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label for="edit_dateStarted" class="form-label fw-bold">Date Started: <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="edit_dateStarted" name="dateStarted" form="reportForm" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="edit_timeStarted" class="form-label fw-bold">Time Started: <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control" id="edit_timeStarted" name="timeStarted" form="reportForm" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="edit_dateCompleted" class="form-label fw-bold">Date Completed: <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="edit_dateCompleted" name="dateCompleted" form="reportForm" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="edit_timeCompleted" class="form-label fw-bold">Time Completed: <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control" id="edit_timeCompleted" name="timeCompleted" form="reportForm" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="edit_acceptedBy" class="form-label fw-bold">Accepted By: <span class="text-danger">*</span></label>
                                        <select class="form-control" id="edit_acceptedBy" name="acceptedBy" form="reportForm" required>
                                            <option selected disabled value="">-- Select Personnel --</option>
                                            <?php foreach ($allPersonnelForConductedByData as $person): ?>
                                                <option value="<?php echo htmlspecialchars($person['name']); ?>">
                                                    <?php echo htmlspecialchars($person['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="edit_jobCompletionVerifiedBy" class="form-label fw-bold">Job Completion Verified By: <span class="text-danger">*</span></label>
                                        <select class="form-control" id="edit_jobCompletionVerifiedBy" name="jobCompletionVerifiedBy" form="reportForm" required>
                                            <option selected disabled value="">-- Select Personnel --</option>
                                            <?php foreach ($allPersonnelForConductedByData as $person): ?>
                                                <option value="<?php echo htmlspecialchars($person['name']); ?>">
                                                    <?php echo htmlspecialchars($person['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_workDetailsRemarks" class="form-label fw-bold">Work Details Remarks: (Optional)</label>
                                    <textarea class="form-control" id="edit_workDetailsRemarks" name="workDetailsRemarks" form="reportForm" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary" name="submit_main" form="reportForm">Submit Report</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Equipment Usage Details -->
                <div class="modal fade" id="EquipmentUsageModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="EquipmentUsageModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-success">
                                <h5 class="modal-title fw-bold text-white" id="EquipmentUsageModalLabel">Equipment Usage Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="createReport.php" method="post">
                                <div class="modal-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="edit_projectName" class="form-label fw-bold">Project Name: <span class="text-danger">*</span></label>
                                            <select class="form-control" id="edit_projectName" name="projectName" required>
                                                <option selected disabled value="">-- Select Project --</option>
                                                <?php foreach ($projectData as $project): ?>
                                                    <option value="<?php echo htmlspecialchars($project['project_name']); ?>">
                                                        <?php echo htmlspecialchars($project['project_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="edit_equipmentID" class="form-label fw-bold">Equipment ID: <span class="text-danger">*</span></label>
                                            <select class="form-control" id="edit_equipmentID" name="equipmentUsageId" required>
                                                <option selected disabled value="">-- Select Equipment --</option>
                                                <?php foreach ($equipmentData as $equipment): ?>
                                                    <option value="<?php echo htmlspecialchars($equipment['custom_equip_id']); ?>">
                                                        <?php echo htmlspecialchars($equipment['custom_equip_id']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="edit_operatorName" class="form-label fw-bold">Operator Name: <span class="text-danger">*</span></label>
                                            <select class="form-control" id="edit_operatorName" name="operatorName" required>
                                                <option selected disabled value="">-- Select Operator --</option>
                                                <?php foreach ($operatorData as $operator): ?>
                                                    <option value="<?php echo htmlspecialchars($operator['name']); ?>">
                                                        <?php echo htmlspecialchars($operator['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="edit_operatingHours" class="form-label fw-bold">Operating Hours: <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="edit_operatingHours" name="operatingHours" required>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="edit_timeIn" class="form-label fw-bold">Time In: <span class="text-danger">*</span></label>
                                            <input type="time" class="form-control" id="edit_timeIn" name="timeIn" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="edit_timeOut" class="form-label fw-bold">Time Out: <span class="text-danger">*</span></label>
                                            <input type="time" class="form-control" id="edit_timeOut" name="timeOut" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_natureOfWork" class="form-label fw-bold">Nature of Work: <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="edit_natureOfWork" required name="natureOfWork" rows="3"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_logRemarks" class="form-label fw-bold">Log Remarks: (Optional)</label>
                                        <textarea class="form-control" id="edit_logRemarks" name="logRemarks" rows="3"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="remarksUsageInput" class="form-label fw-bold">Remarks: (Optional)</label>
                                        <textarea name="usageRemarks" class="form-control" id="remarksUsageInput"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary" name="submit_usage">Submit Usage</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- Maintenance -->
                <div class="modal fade" id="QuickSubEquipmentMaintenance" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h1 class="modal-title fs-5" id="staticBackdropLabel">Modal title</h1>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                yee
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-success">Add</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Purchase Request -->
                <div class="modal fade" id="PendingRequest" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-primary">
                                <h1 class="modal-title fs-5 fw-bold text-white" id="staticBackdropLabel">
                                    <i class="fas fa-tasks text-white me-2"></i>View Pending Requests
                                </h1>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body overflow-auto">
                                <div class="card mb-2">
                                    <div class="card-body">
                                        <a href="PurchaseRequest.php" style="text-decoration: none;">
                                            <table class="table-responsive table text-center">
                                                <thead>
                                                    <tr>
                                                        <th>
                                                            Purchase Request ID:
                                                        </th>
                                                        <th>
                                                            Purpose
                                                        </th>
                                                        <th>
                                                            Requested By
                                                        </th>
                                                        <th>
                                                            Status
                                                        </th>

                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    mysqli_data_seek($sqlPurchaseRequest, 0);

                                                    while ($PurchaseRequestResults = mysqli_fetch_array($sqlPurchaseRequest)) { ?>
                                                        <tr>
                                                            <td>
                                                                PR-<?php echo $PurchaseRequestResults['id']; ?>
                                                            </td>
                                                            <td>
                                                                <?php echo htmlspecialchars($PurchaseRequestResults['purpose']); ?>
                                                            </td>
                                                            <td>
                                                                <?php echo htmlspecialchars($PurchaseRequestResults['requested_by']); ?>
                                                            </td>
                                                            <td>
                                                                <div class="badge <?php
                                                                                    $status = $PurchaseRequestResults['status'] ?? '';

                                                                                    if ($status === 'pending approval') {
                                                                                        echo 'bg-warning';
                                                                                    } elseif ($status === 'approved') {
                                                                                        echo 'bg-success';
                                                                                    } elseif ($status === 'declined') {
                                                                                        echo 'bg-danger';
                                                                                    } else {
                                                                                        echo 'bg-secondary';
                                                                                    }
                                                                                    ?>">
                                                                    <?php echo htmlspecialchars($status); ?>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card bg-success-subtle">
                    <div class="card-body">
                        <h4 class="fw-bold">Search Equipment</h4>
                        <hr>
                        <div class="search-container mb-4 w-100">
                            <i class="fas fa-search"></i>
                            <input type="text" class="form-control search-input w-100" placeholder="Search equipment">
                        </div>
                        <h6 class=" fw-bold mt-3">Categories</h6>
                        <ul class="list-group">
                            <li class="list-group-item">Equipment</li>
                            <li class="list-group-item">Equipment</li>
                            <li class="list-group-item">Equipment</li>
                            <li class="list-group-item">Equipment</li>
                            <li class="list-group-item">Equipment</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const reportTypeSelect = document.getElementById('reportTypeInput');
            const nextReportModalBtn = document.getElementById('nextReportModalBtn');

            // Get Bootstrap modal instances
            const quickSubReportModal = new bootstrap.Modal(document.getElementById('QuickSubReport'));
            const mrbModal = new bootstrap.Modal(document.getElementById('MaintenanceRepairBreakdownModal'));
            const euModal = new bootstrap.Modal(document.getElementById('EquipmentUsageModal'));

            // --- Next Button Logic ---
            nextReportModalBtn.addEventListener('click', function() {
                const selectedReportType = reportTypeSelect.value;

                // First, hide the current modal
                quickSubReportModal.hide();

                // Determine which modal to show next based on the selection
                if (selectedReportType === 'Maintenance' || selectedReportType === 'Repair' || selectedReportType === 'Breakdown') {
                    document.getElementById('mrbReportType').value = selectedReportType; // Set hidden input value
                    mrbModal.show();
                } else if (selectedReportType === 'Equipment Usage') {
                    euModal.show();
                } else {
                    // Optionally, handle cases where no type is selected or an invalid type
                    alert('Please select a report type before proceeding.');
                    quickSubReportModal.show(); // Show the first modal again if nothing is selected
                }
            });

            // --- Back Button Logic ---
            // When the Maintenance/Repair/Breakdown modal is hidden, show the QuickSubReport modal
            document.getElementById('MaintenanceRepairBreakdownModal').addEventListener('hidden.bs.modal', function() {
                // Check if the 'Back' button was clicked (you might add a specific class to back buttons for this)
                // A simpler way for this multi-step scenario is just to show the previous modal
                quickSubReportModal.show();
            });

            // When the Equipment Usage modal is hidden, show the QuickSubReport modal
            document.getElementById('EquipmentUsageModal').addEventListener('hidden.bs.modal', function() {
                // Similar to above, just show the previous modal
                quickSubReportModal.show();
            });

            // Optional: Clear selected report type when the first modal is shown again
            // This helps reset the state if the user goes back and forth
            document.getElementById('QuickSubReport').addEventListener('show.bs.modal', function() {
                reportTypeSelect.value = ''; // Reset dropdown
            });

            const toast = new bootstrap.Toast(document.querySelector('.toast'));
            toast.show();

            const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltips.forEach(tooltip => new bootstrap.Tooltip(tooltip));
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Reports Distribution Chart
            fetch('graphData.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    // Check if data contains labels and counts
                    if (data.labels && data.counts) {
                        // Reports Distribution Chart
                        const reportsDistributionChart = new Chart(
                            document.getElementById('reportsDistributionChart').getContext('2d'), {
                                type: 'doughnut',
                                data: {
                                    // Use the labels from your PHP data
                                    labels: data.labels,
                                    datasets: [{
                                        data: data.counts,
                                        backgroundColor: ['gold', 'red', 'green', 'purple'],
                                        hoverOffset: 4
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            position: 'bottom',
                                            labels: {
                                                boxWidth: 12,
                                                padding: 10,
                                                font: {
                                                    size: 11
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        );
                    } else {
                        console.error('Error: Data from graphData.php is missing "labels" or "counts".', data);
                    }
                })
                .catch(error => {
                    console.error('There was a problem with the fetch operation:', error);
                });
            // Machine Lifespan Chart
            let machineLifespanChart;

            console.log('Fetching chart data...');
            fetch('fetchMachineLifespan.php')
                .then(response => {
                    console.log('Raw response:', response);
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Chart data:', data);
                    if (data.error) {
                        console.error('Error from server:', data.message);
                        return;
                    }

                    const ctx = document.getElementById('machineLifespanChart');
                    if (!ctx) {
                        console.error('Canvas element not found');
                        return;
                    }

                    machineLifespanChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.categories,
                            datasets: [{
                                    label: 'Good',
                                    data: data.data.good,
                                    backgroundColor: 'teal',
                                    borderColor: 'teal',
                                    borderWidth: 1
                                },
                                {
                                    label: 'Maintenance needed',
                                    data: data.data.maintenance,
                                    backgroundColor: 'gold',
                                    borderColor: 'gold',
                                    borderWidth: 1
                                },
                                {
                                    label: 'Poor',
                                    data: data.data.poor,
                                    backgroundColor: 'maroon',
                                    borderColor: 'maroon',
                                    borderWidth: 1
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top'
                                },
                                title: {
                                    display: true,
                                    text: 'Equipment Condition by Category'
                                }
                            }
                        }
                    });
                })
                .catch(error => {
                    console.error('Error loading chart data:', error);
                    const chartContainer = document.getElementById('machineLifespanChart').parentElement;
                    chartContainer.innerHTML = '<p class="text-danger">Error loading chart data. Please refresh the page.</p>';
                });
        });
    </script>
</body>

</html>