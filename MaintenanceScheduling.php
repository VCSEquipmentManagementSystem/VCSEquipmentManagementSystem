<?php
date_default_timezone_set('Asia/Manila');
include './readSchedule.php';
require('./database.php');

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Maintenance Schedule</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap");

        body {
            font-family: "Poppins", sans-serif;
            background-color: #f0f2f5;
        }

        img {
            height: 50px;
            width: 50px;
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

        .offcanvas {
            --bs-offcanvas-width: 280px;
        }

        .card {
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .calendar-day {
            width: 36px;
            height: 36px;
            line-height: 36px;
            text-align: center;
            border-radius: 50%;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .calendar-day.real-time-highlight {
            background-color: #007bff;
            /* Blue */
            color: #fff;
        }

        .calendar-day.scheduled-date-highlight {
            background-color: #6c757d;
            /* Gray */
            color: #fff;
        }

        .calendar-day:hover {
            background-color: #e2e6ea;
        }

        .weekend {
            color: red;
        }

        .current-day {
            background-color: #f0f0f0;
        }

        .status-pill {
            border-radius: 16px;
            padding: 4px 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            background-color: #f8f9fa;
        }

        .maintenance-count {
            font-size: 48px;
            font-weight: bold;
            text-align: center;
        }

        .chart-container {
            height: 250px;
        }

        .form-control {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.10);
        }

        .history-items {
            scrollbar-width: thin;
            scrollbar-color: #888 #f0f0f0;
        }

        .history-items::-webkit-scrollbar {
            width: 6px;
        }

        .history-items::-webkit-scrollbar-track {
            background: #f0f0f0;
            border-radius: 3px;
        }

        .history-items::-webkit-scrollbar-thumb {
            background-color: #888;
            border-radius: 3px;
        }

        .history-item {
            transition: background-color 0.2s ease;
        }

        .history-item:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }

        .history-item:last-child {
            border-bottom: none !important;
        }

        /* Full Calendar Styles */
        .full-calendar-container {
            display: flex;
            flex-wrap: wrap;
            border-top: 1px solid #e9ecef;
            border-left: 1px solid #e9ecef;
            width: 100%;
        }

        .full-calendar-day {
            width: calc(100% / 7);
            height: 100px;
            padding: 5px;
            border-bottom: 1px solid #e9ecef;
            border-right: 1px solid #e9ecef;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
            position: relative;
            cursor: pointer;
            overflow-y: auto;
        }

        .full-calendar-day::-webkit-scrollbar {
            width: 3px;
        }

        .full-calendar-day::-webkit-scrollbar-thumb {
            background-color: #ccc;
            border-radius: 3px;
        }

        .full-calendar-day .day-number {
            font-weight: normal;
            font-size: 1rem;
            position: absolute;
            top: 5px;
            right: 5px;
        }

        .full-calendar-day.real-time-highlight {
            background-color: #e6f2ff;
            /* Lighter Blue */
        }

        .full-calendar-day.scheduled-date-highlight {
            background-color: #e9ecef;
            /* Lighter Gray */
        }

        .full-calendar-day:hover {
            background-color: #d1e7ff;
        }

        .full-calendar-event {
            font-size: 0.75rem;
            padding: 2px 4px;
            border-radius: 4px;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }


        .navbar-toggler:focus {
            outline: none;
            box-shadow: none;
            border-style: none;
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
                        Maintenance Schedule
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
        <!-- <div class="row mt-5">
            <div class="title-header d-flex justify-content-between">
                <h3 class="fw-bold mt-3">
                    Maintenance Schedule
                </h3>
            </div>
        </div> -->
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="header">
                            <h5 class="mb-0 fw-bold">Schedule</h5>
                            <div>
                                <button class="btn btn-outline-secondary btn-sm me-2" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-arrow-down-wide-short"></i>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                                    <li><a href="#" class="dropdown-item sort-option" data-sort="asc">Sort by A-Z</a></li>
                                    <li><a href="#" class="dropdown-item sort-option" data-sort="desc">Sort by Z-A</a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a href="#" class="dropdown-item sort-option" data-sort="newest">Newest to Oldest</a></li>
                                    <li><a href="#" class="dropdown-item sort-option" data-sort="oldest">Oldest to Newest</a></li>
                                </ul>
                                <!-- Archive -->
                                <div class="btn-group btn-group-sm me-2">
                                    <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#archiveModal">
                                        <i class="fa-solid fa-box-archive"></i>
                                    </button>
                                </div>
                                <!-- Add -->
                                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-success">
                                                <h5 class="modal-title fw-bold text-white" id="staticBackdropLabel">+ Add Schedule</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form action="createSchedule.php" method="POST" id="addScheduleForm">
                                                    <label for="EquipmenIdInput" class="form-label fw-bold">Equipment ID: <span class="text-danger">*</span></label>
                                                    <select class="form-control" id="EquipmenIdInput" name="equipment_id" required>
                                                        <option value="">Select Equipment</option>
                                                        <?php
                                                        $sql_equip = "SELECT equipment_id, custom_equip_id FROM equip_tbl ORDER BY custom_equip_id ASC";
                                                        $result_equip = $connection->query($sql_equip);
                                                        while ($row_equip = $result_equip->fetch_assoc()) {
                                                            echo '<option value="' . $row_equip['equipment_id'] . '">' . htmlspecialchars($row_equip['custom_equip_id']) . '</option>';
                                                        }
                                                        ?>
                                                    </select>

                                                    <label for="DateInput" class="form-label fw-bold">Date: <span class="text-danger">*</span></label>
                                                    <input type="date" id="DateInput" name="schedule_date" class="form-control" required>

                                                    <label for="StatusInput" class="form-label fw-bold">Status: <span class="text-danger">*</span></label>
                                                    <select class="form-control" id="StatusInput" name="pms_status" required>
                                                        <option value="Not yet started">Not yet started</option>
                                                        <option value="In Progress">In Progress</option>
                                                        <option value="Completed">Completed</option>
                                                        <option value="Rescheduled">Rescheduled</option>
                                                    </select>
                                                    <br>
                                                    <label for="allRequiredFields"><span class="text-danger">*</span> Required Fields</label>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-success" name="create" id="liveToastBtn">Add</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive overflow-auto" style="max-height: 750px;">
                                <table class="table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Equipment ID</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (!empty($schedules)) {
                                            foreach ($schedules as $schedule) {
                                                $status_class = '';
                                                switch ($schedule['pms_status']) {
                                                    case 'Not yet started':
                                                        $status_class = 'bg-info';
                                                        break;
                                                    case 'In Progress':
                                                        $status_class = 'bg-warning';
                                                        break;
                                                    case 'Completed':
                                                        $status_class = 'bg-success';
                                                        break;
                                                    case 'Rescheduled':
                                                        $status_class = 'bg-primary';
                                                        break;
                                                    default:
                                                        $status_class = 'bg-secondary';
                                                        break;
                                                }
                                                echo '<tr>';
                                                echo '<td>' . htmlspecialchars($schedule['custom_equip_id']) . '</td>';
                                                echo '<td>' . date('M d, Y', strtotime($schedule['schedule_date'])) . '</td>';
                                                echo '<td>';
                                                echo '<div class="dropdown">';
                                                echo '<button class="badge ' . $status_class . ' text-white dropdown-toggle border-0" type="button" id="statusDropdown_' . $schedule['report_id'] . '" data-bs-toggle="dropdown" aria-expanded="false">';
                                                echo htmlspecialchars($schedule['pms_status']);
                                                echo '</button>';
                                                echo '<ul class="dropdown-menu" aria-labelledby="statusDropdown_' . $schedule['report_id'] . '">';
                                                echo '<div class="overflow-auto" style="max-height: 200px;">';
                                                echo '<li><a class="dropdown-item update-status" href="#" data-report-id="' . $schedule['report_id'] . '" data-status="Not yet started"><span>Not yet started</span></a></li>';
                                                echo '<li><a class="dropdown-item update-status" href="#" data-report-id="' . $schedule['report_id'] . '" data-status="In Progress"><span>In Progress</span></a></li>';
                                                echo '<li><a class="dropdown-item update-status" href="#" data-report-id="' . $schedule['report_id'] . '" data-status="Completed"><span>Completed</span></a></li>';
                                                echo '<li><a class="dropdown-item update-status" href="#" data-report-id="' . $schedule['report_id'] . '" data-status="Rescheduled"><span>Rescheduled</span></a></li>';
                                                echo '</div>';
                                                echo '</ul>';
                                                echo '</div>';
                                                echo '</td>';
                                                echo '<td>';
                                                echo '<button class="btn btn-sm btn-link text-muted" type="button" id="ellipsisDropdown_' . $schedule['report_id'] . '" data-bs-toggle="dropdown" aria-expanded="false">';
                                                echo '<i class="fas fa-ellipsis-v"></i>';
                                                echo '</button>';
                                                echo '<ul class="dropdown-menu" aria-labelledby="ellipsisDropdown_' . $schedule['report_id'] . '">';
                                                echo '<li><a class="dropdown-item edit-schedule" href="#" data-bs-toggle="modal" data-bs-target="#editScheduleModal" data-report-id="' . $schedule['report_id'] . '" data-equipment-id="' . $schedule['equipment_id'] . '" data-custom-equip-id="' . htmlspecialchars($schedule['custom_equip_id']) . '" data-schedule-date="' . $schedule['schedule_date'] . '" data-pms-status="' . htmlspecialchars($schedule['pms_status']) . '">Edit</a></li>';
                                                echo '<li><a class="dropdown-item archive-schedule-action" href="#"
                                                    data-report-id="' . $schedule['report_id'] . '"
                                                    data-custom-equip-id="' . htmlspecialchars($schedule['custom_equip_id']) . '"
                                                    data-maintenance-id="' . ($schedule['maintenance_id'] ?? '') . '">Archive</a></li>';
                                                echo '</ul>';
                                                echo '</td>';
                                                echo '</tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="4" class="text-center">No maintenance schedules found.</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title fw-bold text-center">Machine Lifespan and Condition</h5>
                            <div class="chart-container">
                                <canvas id="machineLifespanChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h5 class="card-title fw-bold">History</h5>
                                        <span class="badge bg-secondary" id="historyCount">
                                            <?php
                                            $historyCountQuery = "SELECT COUNT(*) as total_history FROM report_tbl WHERE report_type = 'Maintenance' AND report_status = 'Completed'";
                                            $historyCountResult = $connection->query($historyCountQuery);
                                            if ($historyCountResult && $historyCountResult->num_rows > 0) {
                                                $countRow = $historyCountResult->fetch_assoc();
                                                echo $countRow['total_history'];
                                            } else {
                                                echo '0';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <hr class="divider">
                                    <div class="history-items" style="max-height: 300px; overflow-y: auto;">
                                        <?php
                                        $historyQuery = "
                                            SELECT
                                                r.report_id,
                                                e.custom_equip_id,
                                                r.report_date,
                                                r.report_status,
                                                CONCAT(u.name) as conducted_by
                                            FROM report_tbl r
                                            JOIN equip_tbl e ON r.equipment_id = e.equipment_id
                                            LEFT JOIN user_tbl u ON r.conducted_by = u.user_id
                                            WHERE r.report_type = 'Maintenance' AND r.report_status = 'Completed'
                                            ORDER BY r.report_date DESC
                                            LIMIT 5";

                                        $historyResult = $connection->query($historyQuery);
                                        if ($historyResult && $historyResult->num_rows > 0) {
                                            while ($history = $historyResult->fetch_assoc()) {
                                                $statusClass = match ($history['report_status']) {
                                                    'Completed' => 'bg-success',
                                                    'In Progress' => 'bg-warning',
                                                    'Not yet started' => 'bg-info',
                                                    'Rescheduled' => 'bg-primary',
                                                    default => 'bg-secondary'
                                                };
                                        ?>
                                                <div class="history-item p-2 mb-2 border-bottom">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <span class="fw-bold"><?= htmlspecialchars($history['custom_equip_id']) ?></span>
                                                            <br>
                                                            <small class="text-muted">
                                                                <?= date('M d, Y', strtotime($history['report_date'])) ?>
                                                            </small>
                                                        </div>
                                                        <span class="badge <?= $statusClass ?> text-white">
                                                            <?= htmlspecialchars($history['report_status']) ?>
                                                        </span>
                                                    </div>
                                                    <small class="text-muted d-block mt-1">
                                                        By: <?= htmlspecialchars($history['conducted_by'] ?? 'N/A') ?>
                                                    </small>
                                                </div>
                                        <?php
                                            }
                                        } else {
                                            echo '<div class="text-center text-muted">No completed history available</div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h5 class="card-title fw-bold">Total<br>Maintenance</h5>
                                    </div>
                                    <div class="maintenance-count text-success">
                                        <?php
                                        $count = isset($totalMaintenanceCount) ? (int)$totalMaintenanceCount : 0;
                                        echo $count;
                                        ?>
                                    </div>
                                    <div class="text-muted small">For this month</div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <button class="btn btn-sm btn-light" id="prevMonth"><i class="fas fa-chevron-left"></i></button>
                                        <h6 class="mb-0" id="currentMonthYear"></h6>
                                        <button class="btn btn-sm btn-light" id="nextMonth"><i class="fas fa-chevron-right"></i></button>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <div class="calendar-day weekend">Sun</div>
                                        <div class="calendar-day">Mon</div>
                                        <div class="calendar-day">Tue</div>
                                        <div class="calendar-day">Wed</div>
                                        <div class="calendar-day">Thu</div>
                                        <div class="calendar-day">Fri</div>
                                        <div class="calendar-day weekend">Sat</div>
                                    </div>
                                    <div class="calendar-grid d-flex flex-wrap" id="maintenance-calendar">
                                    </div>
                                    <hr>
                                    <button class="btn btn-outline-success w-100 mt-2" data-bs-toggle="modal" data-bs-target="#fullCalendarModal">Full Calendar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editScheduleModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="editScheduleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success">
                        <h5 class="modal-title fw-bold text-white" id="editScheduleModalLabel">Edit Schedule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="editSchedule.php" method="POST" id="editScheduleForm">
                            <input type="hidden" name="report_id" id="editReportId">
                            <input type="hidden" name="maintenance_id" id="editMaintenanceId">

                            <label for="editEquipmentId" class="form-label fw-bold">Equipment ID:</label>
                            <input type="text" id="editCustomEquipId" class="form-control" readonly>
                            <input type="hidden" name="equipment_id" id="editEquipmentId">

                            <label for="editScheduleDate" class="form-label fw-bold">Date: <span class="text-danger">*</span></label>
                            <input type="date" id="editScheduleDate" name="schedule_date" class="form-control" required>

                            <label for="editPmsStatus" class="form-label fw-bold">Status: <span class="text-danger">*</span></label>
                            <select class="form-control" id="editPmsStatus" name="pms_status" required>
                                <option value="Not yet started">Not yet started</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                                <option value="Rescheduled">Rescheduled</option>
                            </select>
                            <br>
                            <label for="allRequiredFields"><span class="text-danger">*</span> Required Fields</label>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success" name="update">Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="archiveModal" tabindex="-1" aria-labelledby="archiveModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="archiveModalLabel">Archived Items</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="archivedItemsTableContainer">
                            <p>Loading archived items...</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" id="delete-selected-schedules-btn">Delete</button>
                        <button type="button" class="btn btn-success" id="restore-selected-schedules-btn">Restore</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="historyDetailsModal" tabindex="-1" aria-labelledby="historyDetailsModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fw-bold" id="historyDetailsModalLabel">Maintenance Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <dl class="row">
                            <dt class="col-sm-4">Equipment ID:</dt>
                            <dd class="col-sm-8" id="modalEquipmentId"></dd>

                            <dt class="col-sm-4">Date:</dt>
                            <dd class="col-sm-8" id="modalDate"></dd>

                            <dt class="col-sm-4">Status:</dt>
                            <dd class="col-sm-8"><span id="modalStatus" class="badge"></span></dd>
                        </dl>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="calendarDetailsModal" tabindex="-1" aria-labelledby="calendarDetailsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="calendarDetailsModalLabel">Maintenance Schedules for <span id="modal-date"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="calendar-details-list">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="fullCalendarModal" tabindex="-1" aria-labelledby="fullCalendarModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fw-bold" id="fullCalendarModalLabel">Full Calendar</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <button class="btn btn-sm btn-light" id="full-prevMonth"><i class="fas fa-chevron-left"></i></button>
                            <h4 class="mb-0" id="full-currentMonthYear"></h4>
                            <button class="btn btn-sm btn-light" id="full-nextMonth"><i class="fas fa-chevron-right"></i></button>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-secondary btn-sm" id="full-calendar-month-view">Month</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="full-calendar-week-view">Week</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="full-calendar-agenda-view">Agenda</button>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap full-calendar-container" id="full-maintenance-calendar">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
        <script>
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

            // JavaScript for Edit Modal
            document.addEventListener('DOMContentLoaded', function() {
                var editScheduleModal = document.getElementById('editScheduleModal');
                editScheduleModal.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget;
                    var reportId = button.getAttribute('data-report-id');
                    var equipmentId = button.getAttribute('data-equipment-id');
                    var customEquipId = button.getAttribute('data-custom-equip-id');
                    var scheduleDate = button.getAttribute('data-schedule-date');
                    var pmsStatus = button.getAttribute('data-pms-status');
                    var maintenanceId = button.getAttribute('data-maintenance-id'); // Get maintenance_id if available

                    var modalEditReportId = editScheduleModal.querySelector('#editReportId');
                    var modalEditMaintenanceId = editScheduleModal.querySelector('#editMaintenanceId');
                    var modalEditEquipmentId = editScheduleModal.querySelector('#editEquipmentId');
                    var modalEditCustomEquipId = editScheduleModal.querySelector('#editCustomEquipId');
                    var modalEditScheduleDate = editScheduleModal.querySelector('#editScheduleDate');
                    var modalEditPmsStatus = editScheduleModal.querySelector('#editPmsStatus');

                    modalEditReportId.value = reportId;
                    modalEditMaintenanceId.value = maintenanceId; // Set maintenance_id
                    modalEditEquipmentId.value = equipmentId;
                    modalEditCustomEquipId.value = customEquipId;
                    modalEditScheduleDate.value = scheduleDate;
                    modalEditPmsStatus.value = pmsStatus;
                });

                // JavaScript for Archive Modal
                var archiveScheduleModal = document.getElementById('archiveScheduleModal');
                archiveScheduleModal.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget;
                    var reportId = button.getAttribute('data-report-id');
                    var maintenanceId = button.getAttribute('data-maintenance-id'); // Get maintenance_id if available

                    var modalArchiveReportId = archiveScheduleModal.querySelector('#archiveReportId');
                    var modalArchiveMaintenanceId = archiveScheduleModal.querySelector('#archiveMaintenanceId');

                    modalArchiveReportId.value = reportId;
                    modalArchiveMaintenanceId.value = maintenanceId; // Set maintenance_id
                });

                // JavaScript for Status Update (using AJAX for a smoother experience)
                document.querySelectorAll('.update-status').forEach(item => {
                    item.addEventListener('click', function(event) {
                        event.preventDefault();
                        const reportId = this.dataset.reportId;
                        const newStatus = this.dataset.status;

                        fetch('updateScheduleStatus.php', { // A new file to handle status updates
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `report_id=${reportId}&new_status=${newStatus}`
                            })
                            .then(response => response.text())
                            .then(data => {
                                if (data.includes('success')) {
                                    // Update the displayed status directly without full page reload
                                    const statusButton = document.getElementById(`statusDropdown_${reportId}`);
                                    statusButton.textContent = newStatus;
                                    // Also update the badge class based on the new status
                                    statusButton.className = `badge text-white dropdown-toggle border-0 ${getBootstrapClassForStatus(newStatus)}`;
                                } else {
                                    alert('Failed to update status: ' + data);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred while updating status.');
                            });
                    });
                });

                function getBootstrapClassForStatus(status) {
                    switch (status) {
                        case 'Not yet started':
                            return 'bg-info';
                        case 'In Progress':
                            return 'bg-warning';
                        case 'Completed':
                            return 'bg-success';
                        case 'Rescheduled':
                            return 'bg-primary';
                        default:
                            return 'bg-secondary';
                    }
                }

            });

            document.addEventListener('DOMContentLoaded', function() {
                var archiveModal = document.getElementById('archiveModal');

                // Get references to the action buttons in the modal footer
                const restoreBtn = document.getElementById('restore-selected-schedules-btn');
                const deleteBtn = document.getElementById('delete-selected-schedules-btn');

                // Function to update "Select All" checkbox state
                function updateSelectionStates() {
                    const archivedSchedulesTbody = document.getElementById('archived-schedules-tbody');
                    const selectAllCheckbox = document.getElementById('select-all-archived-checkbox');

                    if (!archivedSchedulesTbody || !selectAllCheckbox) {
                        // Elements might not be present if the table hasn't been rendered yet
                        return;
                    }

                    const individualCheckboxes = archivedSchedulesTbody.querySelectorAll('.archive-checkbox');
                    const checkedCheckboxes = archivedSchedulesTbody.querySelectorAll('.archive-checkbox:checked');

                    // Buttons are explicitly NOT disabled, as per your instruction.

                    if (individualCheckboxes.length === 0) {
                        selectAllCheckbox.checked = false;
                        selectAllCheckbox.indeterminate = false;
                    } else if (checkedCheckboxes.length === individualCheckboxes.length && individualCheckboxes.length > 0) {
                        selectAllCheckbox.checked = true;
                        selectAllCheckbox.indeterminate = false;
                    } else if (checkedCheckboxes.length > 0) {
                        selectAllCheckbox.checked = false;
                        selectAllCheckbox.indeterminate = true;
                    } else {
                        selectAllCheckbox.checked = false;
                        selectAllCheckbox.indeterminate = false;
                    }
                }

                archiveModal.addEventListener('shown.bs.modal', function() {
                    const container = document.getElementById('archivedItemsTableContainer');
                    container.innerHTML = '<p>Loading archived items...</p>';

                    fetch('fetchArchivedSchedule.php')
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            let tableHtml = `
                                <div class="table-responsive">
                                    <table class="table table-hover" id="archived-schedules-table">
                                        <thead>
                                            <tr>
                                                <th scope="col" style="width: 50px;">
                                                    <input type="checkbox" id="select-all-archived-checkbox">
                                                </th>
                                                <th>Equipment ID</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="archived-schedules-tbody">`;

                            if (data.length > 0) {
                                data.forEach(item => {
                                    const scheduleId = item.archive_id || '';
                                    const statusClass = getBootstrapClassForStatus(item.report_status || 'Not yet started');

                                    tableHtml += `
                                        <tr>
                                            <td><input type="checkbox" class="archive-checkbox" data-id="${scheduleId}"></td>
                                            <td>${item.custom_equip_id || ''}</td>
                                            <td>${item.schedule_date || ''}</td>
                                            <td><span class="badge ${statusClass}">${item.report_status || 'Not yet started'}</span></td>
                                        </tr>`;
                                });
                            } else {
                                tableHtml += '<tr><td colspan="4" class="text-center">No archived maintenance schedules found.</td></tr>';
                            }

                            tableHtml += `
                                        </tbody>
                                    </table>
                                </div>`;

                            container.innerHTML = tableHtml;

                            // Attach event listeners after table is rendered
                            const selectAllCheckbox = document.getElementById('select-all-archived-checkbox');
                            const archivedSchedulesTbody = document.getElementById('archived-schedules-tbody');

                            if (selectAllCheckbox) {
                                selectAllCheckbox.addEventListener('change', function() {
                                    const isChecked = this.checked;
                                    archivedSchedulesTbody.querySelectorAll('.archive-checkbox').forEach(checkbox => {
                                        checkbox.checked = isChecked;
                                    });
                                    updateSelectionStates();
                                });
                            }

                            if (archivedSchedulesTbody) {
                                archivedSchedulesTbody.addEventListener('change', function(event) {
                                    if (event.target.classList.contains('archive-checkbox')) {
                                        updateSelectionStates();
                                    }
                                });
                            }

                            updateSelectionStates();
                        })
                        .catch(error => {
                            console.error('Error fetching archived schedules:', error);
                            container.innerHTML = '<p class="text-danger">Failed to load archived items. Please try again.</p>';
                        });
                });

                // Update or add the getBootstrapClassForStatus function
                function getBootstrapClassForStatus(status) {
                    switch (status) {
                        case 'Not yet started':
                            return 'bg-info text-white';
                        case 'In Progress':
                            return 'bg-warning text-white';
                        case 'Completed':
                            return 'bg-success text-white';
                        case 'Rescheduled':
                            return 'bg-primary text-white';
                        default:
                            return 'bg-secondary text-white';
                    }
                }

                // --- Restore Selected Schedules Button Click ---
                if (restoreBtn) {
                    restoreBtn.addEventListener('click', function() {
                        const selectedCheckboxes = document.querySelectorAll('.archive-checkbox:checked');
                        const archiveIds = Array.from(selectedCheckboxes).map(cb => cb.dataset.id);

                        if (archiveIds.length === 0) {
                            alert('Please select at least one schedule to restore.');
                            return;
                        }

                        if (confirm('Are you sure you want to restore the selected schedules?')) {
                            fetch('restoreSchedule.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify({
                                        archive_ids: archiveIds
                                    })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.status === 'success') {
                                        alert(`Successfully restored ${data.count} schedule(s)`);

                                        // Remove restored items from archive table
                                        archiveIds.forEach(id => {
                                            const row = document.querySelector(`.archive-checkbox[data-id="${id}"]`).closest('tr');
                                            if (row) row.remove();
                                        });

                                        // Update checkbox states and reload main table
                                        updateSelectionStates();
                                        location.reload();
                                    } else {
                                        alert('Error restoring schedules: ' + data.message);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('An error occurred during restoration');
                                });
                        }
                    });
                }

                // --- Delete Selected Schedules Permanently Button Click ---
                if (deleteBtn) {
                    deleteBtn.addEventListener('click', function() {
                        const selectedCheckboxes = document.querySelectorAll('.archive-checkbox:checked');
                        const archiveIds = Array.from(selectedCheckboxes).map(cb => cb.dataset.id);

                        if (archiveIds.length === 0) {
                            alert('Please select at least one schedule to delete.');
                            return;
                        }

                        if (confirm('Are you sure you want to permanently delete the selected schedules? This action cannot be undone.')) {
                            fetch('deleteSchedule.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify({
                                        archive_ids: archiveIds
                                    })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.status === 'success') {
                                        alert(`Successfully deleted ${data.count} schedule(s)`);

                                        // Remove deleted items from archive table
                                        archiveIds.forEach(id => {
                                            const row = document.querySelector(`.archive-checkbox[data-id="${id}"]`).closest('tr');
                                            if (row) row.remove();
                                        });

                                        // Update checkbox states
                                        updateSelectionStates();
                                    } else {
                                        alert('Error deleting schedules: ' + data.message);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('An error occurred during deletion');
                                });
                        }
                    });
                }

                // Function to update selection states (keep your existing function)
                function updateSelectionStates() {
                    const archivedSchedulesTbody = document.getElementById('archived-schedules-tbody');
                    const selectAllCheckbox = document.getElementById('select-all-archived-checkbox');

                    if (!archivedSchedulesTbody || !selectAllCheckbox) {
                        return;
                    }

                    const individualCheckboxes = archivedSchedulesTbody.querySelectorAll('.archive-checkbox');
                    const checkedCheckboxes = archivedSchedulesTbody.querySelectorAll('.archive-checkbox:checked');

                    if (individualCheckboxes.length === 0) {
                        selectAllCheckbox.checked = false;
                        selectAllCheckbox.indeterminate = false;
                    } else if (checkedCheckboxes.length === individualCheckboxes.length && individualCheckboxes.length > 0) {
                        selectAllCheckbox.checked = true;
                        selectAllCheckbox.indeterminate = false;
                    } else if (checkedCheckboxes.length > 0) {
                        selectAllCheckbox.checked = false;
                        selectAllCheckbox.indeterminate = true;
                    } else {
                        selectAllCheckbox.checked = false;
                        selectAllCheckbox.indeterminate = false;
                    }
                }
            });

            // JavaScript for direct archiving action
            document.querySelectorAll('.archive-schedule-action').forEach(function(link) {
                link.addEventListener('click', function(event) {
                    event.preventDefault(); // Prevent default link behavior

                    const reportId = this.getAttribute('data-report-id');
                    const customEquipId = this.getAttribute('data-custom-equip-id');
                    const maintenanceId = this.getAttribute('data-maintenance-id'); // Retrieve maintenance_id

                    if (!reportId) {
                        alert('Error: Missing Report ID for archiving.');
                        return;
                    }

                    if (!confirm(`Are you sure you want to archive Report ID: ${reportId} (Equipment: ${customEquipId})?`)) {
                        return; // User cancelled
                    }

                    // Perform AJAX call to archiveSchedule.php
                    fetch('archiveSchedule.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            // Pass report_id, custom_equip_id, and maintenance_id
                            body: `report_id=${reportId}&custom_equip_id=${customEquipId}&maintenance_id=${maintenanceId}`
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json(); // Expect JSON response from PHP
                        })
                        .then(data => {
                            if (data.status === 'success') {
                                // Show the "archived (1) PL03" style alert
                                const archivedMessage = `Archived (1) ${data.custom_equip_id}`;
                                alert(archivedMessage);

                                // Reload the main table (to remove the archived item from active view)
                                location.reload();

                                // Open the "Archived Items" display modal
                                // Ensure the #archiveModal exists in your HTML and has the correct ID
                                var archiveDisplayModal = new bootstrap.Modal(document.getElementById('archiveModal'));
                                archiveDisplayModal.show();

                                // The 'shown.bs.modal' listener on #archiveModal (already present in your file)
                                // will automatically trigger fetchArchivedSchedules.php to populate it.

                            } else {
                                alert('Error archiving schedule: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred during archiving: ' + error.message);
                        });
                });
            });

            document.addEventListener('DOMContentLoaded', function() {
                // --- CALENDAR LOGIC ---
                const calendarElement = document.getElementById('maintenance-calendar');
                const fullCalendarElement = document.getElementById('full-maintenance-calendar');
                const currentMonthYearElement = document.getElementById('currentMonthYear');
                const fullCurrentMonthYearElement = document.getElementById('full-currentMonthYear');
                const prevMonthBtn = document.getElementById('prevMonth');
                const nextMonthBtn = document.getElementById('nextMonth');
                const fullPrevMonthBtn = document.getElementById('full-prevMonth');
                const fullNextMonthBtn = document.getElementById('full-nextMonth');

                let currentMonth = new Date().getMonth();
                let currentYear = new Date().getFullYear();
                const today = new Date();
                const todayString = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

                const maintenanceDates = <?php echo json_encode($maintenanceDates); ?>;
                let fullCalendarEvents = {};

                function getBootstrapClassForStatus(status) {
                    switch (status) {
                        case 'Not yet started':
                            return 'bg-info text-white';
                        case 'In Progress':
                            return 'bg-warning text-white';
                        case 'Completed':
                            return 'bg-success text-white';
                        case 'Rescheduled':
                            return 'bg-primary text-white';
                        default:
                            return 'bg-secondary text-white';
                    }
                }

                function showDailyScheduleModal(date) {
                    const modalDate = document.getElementById('modal-date');
                    const modalList = document.getElementById('calendar-details-list');
                    const calendarDetailsModal = new bootstrap.Modal(document.getElementById('calendarDetailsModal'));
                    const fullCalendarModal = bootstrap.Modal.getInstance(document.getElementById('fullCalendarModal'));

                    // Check if the full calendar modal is open and hide it
                    if (fullCalendarModal) {
                        fullCalendarModal.hide();
                    }

                    modalDate.textContent = new Date(date + 'T00:00:00').toLocaleDateString('en-US', {
                        month: 'long',
                        day: 'numeric',
                        year: 'numeric'
                    });
                    modalList.innerHTML = '<p class="text-center">Loading...</p>';

                    fetch(`fetchCalendarSchedules.php?date=${date}`)
                        .then(response => response.json())
                        .then(data => {
                            modalList.innerHTML = '';
                            if (data.success && data.data.length > 0) {
                                data.data.forEach(item => {
                                    const listItem = document.createElement('div');
                                    listItem.className = 'd-flex justify-content-between align-items-center p-2 mb-2 bg-light rounded';
                                    listItem.innerHTML = `
                                        <div>
                                            <i class="fa-solid fa-wrench me-2 text-success"></i>
                                            <strong>${item.custom_equip_id}</strong>
                                        </div>
                                        <span class="badge ${getBootstrapClassForStatus(item.status)}">${item.status}</span>
                                    `;
                                    modalList.appendChild(listItem);
                                });
                            } else {
                                modalList.innerHTML = '<p class="text-muted">No maintenance found for this date.</p>';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching data:', error);
                            modalList.innerHTML = '<p class="text-danger">Failed to load data. Please try again.</p>';
                        });

                    calendarDetailsModal.show();
                }

                // New listener for when the daily details modal is closed
                document.getElementById('calendarDetailsModal').addEventListener('hidden.bs.modal', function() {
                    const fullCalendarModal = bootstrap.Modal.getInstance(document.getElementById('fullCalendarModal'));
                    if (fullCalendarModal) {
                        fullCalendarModal.show();
                    }
                });


                function renderCalendar(month, year, element, isFull = false) {
                    const firstDayOfMonth = new Date(year, month, 1);
                    const lastDayOfMonth = new Date(year, month + 1, 0);
                    const daysInMonth = lastDayOfMonth.getDate();
                    const startingDay = firstDayOfMonth.getDay();
                    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

                    if (isFull) {
                        fullCurrentMonthYearElement.textContent = `${monthNames[month]} ${year}`;
                    } else {
                        currentMonthYearElement.textContent = `${monthNames[month]} ${year}`;
                    }

                    element.innerHTML = '';

                    // Add day headers for full calendar
                    if (isFull) {
                        const dayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                        dayLabels.forEach(label => {
                            const labelDiv = document.createElement('div');
                            labelDiv.className = 'full-calendar-day fw-bold text-center';
                            labelDiv.textContent = label;
                            element.appendChild(labelDiv);
                        });
                    }

                    // Add empty cells for days before start of month
                    for (let i = 0; i < startingDay; i++) {
                        const dayDiv = document.createElement('div');
                        dayDiv.className = isFull ? 'full-calendar-day' : 'calendar-day';
                        dayDiv.innerHTML = '&nbsp;';
                        element.appendChild(dayDiv);
                    }

                    // Add days of the month
                    for (let day = 1; day <= daysInMonth; day++) {
                        const dayDiv = document.createElement('div');
                        dayDiv.className = isFull ? 'full-calendar-day' : 'calendar-day';

                        const currentDateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                        dayDiv.dataset.date = currentDateString;

                        // Check if it's today's date
                        if (currentDateString === todayString) {
                            dayDiv.classList.add('real-time-highlight');
                        }

                        // Add events for the current day
                        const dateEvents = maintenanceDates.filter(d => d.date === currentDateString);
                        if (dateEvents.length > 0) {
                            if (isFull) {
                                // For full calendar view, show all events
                                const dayNumber = document.createElement('div');
                                dayNumber.className = 'day-number';
                                dayNumber.textContent = day;
                                dayDiv.appendChild(dayNumber);

                                dateEvents.forEach(event => {
                                    const eventDiv = document.createElement('div');
                                    eventDiv.className = `full-calendar-event ${getBootstrapClassForStatus(event.status)}`;
                                    eventDiv.textContent = event.custom_equip_id;
                                    dayDiv.appendChild(eventDiv);
                                });
                            } else {
                                // For mini calendar view, just highlight the day
                                dayDiv.textContent = day;
                                dayDiv.classList.add(dateEvents[0].source === 'schedule' ? 'real-time-highlight' : 'scheduled-date-highlight');
                            }
                        } else {
                            // No events for this day
                            if (isFull) {
                                const dayNumber = document.createElement('div');
                                dayNumber.className = 'day-number';
                                dayNumber.textContent = day;
                                dayDiv.appendChild(dayNumber);
                            } else {
                                dayDiv.textContent = day;
                            }
                        }

                        // Add click event
                        dayDiv.addEventListener('click', () => showDailyScheduleModal(currentDateString));
                        element.appendChild(dayDiv);
                    }

                    // Fill remaining calendar grid
                    const totalDays = startingDay + daysInMonth;
                    const remainingDays = (Math.ceil(totalDays / 7) * 7) - totalDays;
                    for (let i = 0; i < remainingDays; i++) {
                        const dayDiv = document.createElement('div');
                        dayDiv.className = isFull ? 'full-calendar-day' : 'calendar-day';
                        dayDiv.innerHTML = '&nbsp;';
                        element.appendChild(dayDiv);
                    }
                }

                renderCalendar(currentMonth, currentYear, calendarElement);

                prevMonthBtn.addEventListener('click', () => {
                    currentMonth--;
                    if (currentMonth < 0) {
                        currentMonth = 11;
                        currentYear--;
                    }
                    renderCalendar(currentMonth, currentYear, calendarElement);
                });

                nextMonthBtn.addEventListener('click', () => {
                    currentMonth++;
                    if (currentMonth > 11) {
                        currentMonth = 0;
                        currentYear++;
                    }
                    renderCalendar(currentMonth, currentYear, calendarElement);
                });

                const fullCalendarModal = document.getElementById('fullCalendarModal');
                fullCalendarModal.addEventListener('show.bs.modal', function() {
                    fetch(`fetchFullCalendarEvents.php?month=${currentMonth + 1}&year=${currentYear}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                fullCalendarEvents = data.data;
                            }
                            renderCalendar(currentMonth, currentYear, fullCalendarElement, true);
                        })
                        .catch(error => {
                            console.error('Error fetching full calendar data:', error);
                            renderCalendar(currentMonth, currentYear, fullCalendarElement, true);
                        });
                });

                fullPrevMonthBtn.addEventListener('click', () => {
                    currentMonth--;
                    if (currentMonth < 0) {
                        currentMonth = 11;
                        currentYear--;
                    }
                    fetch(`fetchFullCalendarEvents.php?month=${currentMonth + 1}&year=${currentYear}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                fullCalendarEvents = data.data;
                            }
                            renderCalendar(currentMonth, currentYear, fullCalendarElement, true);
                        });
                });

                fullNextMonthBtn.addEventListener('click', () => {
                    currentMonth++;
                    if (currentMonth > 11) {
                        currentMonth = 0;
                        currentYear++;
                    }
                    fetch(`fetchFullCalendarEvents.php?month=${currentMonth + 1}&year=${currentYear}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                fullCalendarEvents = data.data;
                            }
                            renderCalendar(currentMonth, currentYear, fullCalendarElement, true);
                        });
                });

                document.getElementById('full-calendar-week-view').addEventListener('click', () => {
                    alert('Week view not yet implemented. Please use the Month view.');
                });
                document.getElementById('full-calendar-agenda-view').addEventListener('click', () => {
                    alert('Agenda view not yet implemented. Please use the Month view or click on a date in the small calendar.');
                });
            });
        </script>
</body>

</html>