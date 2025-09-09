<?php
session_start();
// Equipment Profiling
if (empty($_SESSION['userID'])) {
    header('Location: loginPage.php');
    exit;
}
require('./database.php');
require('./readProfiling.php');

$filterQuery = "";
if (isset($_GET['filter'])) {
    $filter = $_GET['filter'];

    switch ($filter) {
        case 'equipment_id':
            $filterQuery = "ORDER BY equipment_id ASC";
            break;
        case 'category':
            $filterQuery = "ORDER BY equip_type_id ASC";
            break;
        case 'model':
            $filterQuery = "ORDER BY model ASC";
            break;
        case 'plate_num':
            $filterQuery = "ORDER BY plate_num ASC";
            break;
        default:
            $filterQuery = "";
    }
}

$archiveCountQuery = "SELECT COUNT(*) as count 
                     FROM (
                         SELECT equipment_id FROM equip_tbl 
                         WHERE equip_status = 'Archived'
                         UNION ALL
                         SELECT equipment_id FROM archivedequipment_tbl
                     ) as combined_archives";

$archiveResult = mysqli_query($connection, $archiveCountQuery);
$archiveCount = 0;

if ($archiveResult && $row = mysqli_fetch_assoc($archiveResult)) {
    $archiveCount = $row['count'];
}

$operators = getOperators($connection);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Equipment Profiling</title>
</head>
<style>
    @import url("https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap");

    /* Base Styles */
    body {
        background-color: #f0f2f5;
        font-family: "Poppins", sans-serif;
    }

    /* Layout Components */
    .offcanvas {
        --bs-offcanvas-width: 280px;
    }


    .navbar-toggler:focus {
        outline: none;
        box-shadow: none;
        border-style: none;
    }

    /* Search Components */
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
        border-radius: 10px;
        width: 350px;
    }

    /* Card Components */
    .card {
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .card img {
        width: 100px;
        border-radius: 10px;
    }

    .card-body .content {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    /* Button Styles */
    .btn {
        margin-bottom: 5px;
    }

    .btn i {
        margin-right: 0.5rem;
    }

    .card .btn {
        margin-bottom: 5px;
        width: 100%;
    }

    .card .btn:last-child {
        margin-bottom: 0;
    }

    .btn-group,
    .more-details,
    .equipment-specification {
        display: block;
        margin-bottom: 5px !important;
        margin-top: 8px !important;
    }

    .btn.active {
        background-color: #dc3545;
        color: white;
        border-color: #dc3545;
    }

    /* Modal Footer Button Consistency */
    .modal-footer .btn {
        width: auto !important;
        min-width: 100px;
        max-width: 150px;
        margin-bottom: 0 !important;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        white-space: nowrap;
        flex: none;
    }

    /* Ensure both Add and Edit modal buttons have same width */
    #staticBackdrop .modal-footer .btn,
    #edit .modal-footer .btn {
        width: auto !important;
        min-width: 100px;
        max-width: 150px;
    }

    /* Status Button Styles */
    .btn-status-active {
        background-color: #198754 !important;
        border-color: #198754 !important;
        color: white !important;
    }

    .btn-status-idle {
        background-color: #6c757d !important;
        border-color: #6c757d !important;
        color: white !important;
    }

    .btn-status-maintenance {
        background-color: #ffc107 !important;
        border-color: #ffc107 !important;
        color: black !important;
    }

    .btn-status-repair {
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
        color: white !important;
    }

    /* Function Buttons */
    .function-buttons {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }

    .function-btn {
        display: flex;
        align-items: center;
        padding: 15px;
        border-radius: 10px;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .function-btn i {
        margin-right: 1rem;
        width: 24px;
        text-align: center;
    }

    .function-btn:hover {
        background-color: #e9ecef;
        transform: translateY(-2px);
    }

    .function-btn i {
        font-size: 24px;
        margin-right: 10px;
        color: #19c37d;
    }

    /* Modal Styles */
    .details-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
        background-color: #fff;
        margin: 5% auto;
        padding: 10px;
        border-radius: 20px;
        width: 80%;
        max-width: 800px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .modal-header.bg-success {
        background-color: #198754 !important;
        color: #fff !important;
        border-top-left-radius: 15px !important;
        border-top-right-radius: 15px !important;
        margin: -10px -10px 0 -10px;
        padding-top: 1.5rem;
        padding-bottom: 1.5rem;
    }

    /* Equipment Display */
    .equipment-details {
        display: flex;
        margin-bottom: 20px;
    }

    .equipment-details img {
        width: 200px;
        height: 200px;
        object-fit: contain;
        margin-bottom: 20px;
    }

    .equipment-image {
        width: 120px;
        height: 120px;
        object-fit: contain;
        margin: 10px auto;
        display: block;
    }

    .equipment-image img {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 10px;
    }

    .equipment-info {
        flex-grow: 1;
    }

    #equipmentDetailsModal .equipment-image {
        width: 180px;
        height: 180px;
        object-fit: contain;
        margin-bottom: 1rem;
    }

    /* Form Controls */
    .form-control {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.10);
    }

    /* Status and Dropdown Styles */
    .status-option .badge {
        width: 100%;
        text-align: left;
        font-size: 0.9rem;
        padding: 0.5rem;
    }

    .dropdown-item:hover .badge {
        opacity: 0.9;
    }

    /* Alert and Feedback Styles */
    .alert-success {
        position: fixed !important;
        top: 50% !important;
        left: 50% !important;
        transform: translate(-50%, -50%) !important;
        z-index: 1050 !important;
        min-width: 300px;
        text-align: center;
        padding: 15px 25px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .upload-feedback {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 1060;
        min-width: 300px;
        text-align: center;
    }

    /* Upload and Validation Styles */
    #uploadProgress {
        height: 5px;
        margin-top: 10px;
        transition: all 0.3s ease;
    }

    .preview-table {
        max-height: 200px;
        overflow-y: auto;
        margin-top: 15px;
    }

    .validation-error {
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 5px;
    }

    .nav-link i {
        margin-right: 0.75rem;
        width: 20px;
        text-align: center;
    }

    .card.card-stats {
        display: flex;
        flex-direction: column;
        flex: 1 1 100%;
        min-height: 510px;
        height: 100%;
    }

    .archived-card-stats {
        display: flex;
        flex-direction: column;
        flex: 1 1 100%;
        min-height: 210px;
        height: 100%;
        background: #f8f9fa;
        border: 1px solid #dc3545;
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
                        Equipment Profiling
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
        <div class="container-fluid" style="margin-top: 70px;">
            <!-- <div class="row mb-4">
                <div class="title-header d-flex justify-content-between">
                    <h3 class="mb-4 fw-bold mt-3">
                        Equipment Profiling
                    </h3>
                </div>
            </div> -->
            <div class="d-flex justify-content-end">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mt-3">
                        <div class="search-container me-2">
                            <i class="fas fa-search"></i>
                            <input type="text" class="form-control search-input" placeholder="Search equipment..." id="searchInput">
                        </div>
                        <!-- Select All Cards -->
                        <button class="btn btn-outline-secondary me-2" type="button" id="select-all-btn" style="display: none;">
                            <i class="fa-solid fa-square-check"></i>Select All
                        </button>
                        <!-- Archive -->
                        <button class="btn btn-outline-secondary me-2" type="button" id="archive-all-btn" disabled>
                            <i class="fa-solid fa-box-archive"></i>
                        </button>
                        <!-- Filter -->
                        <button class="btn btn-outline-secondary me-2" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-filter"></i>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                            <li><a href="#" class="dropdown-item filter-option" data-filter="all">Unfiltered</a></li>
                            <li class="dropdown-submenu">
                                <a href="#" class="dropdown-item" data-filter="category">
                                    Filter by Category <i class="fa-solid fa-chevron-right ms-2"></i>
                                </a>
                                <ul class="dropdown-menu" id="categorySubmenu">
                                    <!-- Categories will be populated dynamically -->
                                </ul>
                            </li>
                        </ul>
                        <!-- Sort -->
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
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a href="#" class="dropdown-item sort-option" data-sort="newest">Newest to Oldest</a></li>
                            <li><a href="#" class="dropdown-item sort-option" data-sort="oldest">Oldest to Newest</a></li>
                        </ul>
                        <!-- Archive Modal -->
                        <button name="view_archived" class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#archiveModal">
                            <i class="fa-solid fa-box-archive"></i>
                            <span class="badge bg-danger rounded-pill archive-badge" id="archiveCount">
                                <?php echo $archiveCount; ?>
                            </span>
                        </button>
                        <div class="modal fade" id="archiveModal" tabindex="-1" aria-labelledby="archiveModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-xl">
                                <div class="modal-content">
                                    <div class="modal-header bg-success">
                                        <h5 class="modal-title fw-bold text-white" id="archiveModalLabel">Archived Equipment</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <button type="button" class="btn btn-outline-secondary me-2" id="select-all-archived-btn"> Select All</button>
                                        <div class="row" id="archive-modal-body">
                                            <div class="text-center">
                                                <div class="spinner-border" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p>Loading archived equipment...</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger" id="delete-archived-btn">Delete</button>
                                        <button type="button" class="btn btn-success" id="restore-archived-btn">Restore</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Add Equipment -->
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                            <i class="fas fa-plus me-1"></i>
                        </button>
                        <!-- Batch Upload -->
                        <div class="modal fade" id="batchUploadModal">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header bg-success">
                                        <h5 class="modal-title fw-bold text-white">Batch Upload Equipment</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form id="batchUploadForm">
                                            <div class="mb-3">
                                                <label for="batchExcelFileInput" class="form-label">Select Excel Files</label>
                                                <input type="file" class="form-control" id="batchExcelFileInput"
                                                    name="excelFiles[]" accept=".xlsx,.xls,.csv" multiple required>
                                                <div class="form-text">Upload Excel (.xlsx, .xls) or CSV files (multiple selection supported)</div>
                                            </div>
                                            <!-- Selected Files Preview -->
                                            <div id="selectedFilesPreview" class="mb-3 d-none">
                                                <label class="form-label">Selected Files:</label>
                                                <div id="filesList" class="border rounded p-2 bg-light" style="max-height: 200px; overflow-y: auto;">
                                                    <!-- Selected files will be displayed here -->
                                                </div>
                                            </div>
                                            <div class="progress d-none" id="batchUploadProgressBar">
                                                <div class="progress-bar progress-bar-striped progress-bar-animated"
                                                    role="progressbar" style="width: 0%"></div>
                                            </div>
                                            <div id="batchUploadFeedbackMsg" class="alert d-none"></div>
                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary" id="batchUploadSubmitBtn">Upload</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Add parts (inventory.php) -->
                        <div class="modal fade" id="addSpareparts" tabindex="-1" aria-labelledby="addSparepartsLabel" aria-hidden="true">
                            <div class="modal-dialog modal-xl">
                                <div class="modal-content">
                                    <div class="modal-header bg-success">
                                        <h5 class="modal-title fw-bold text-white" id="addSparepartsLabel">+ Add Parts</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="table-responsive">
                                            <table class="table" id="partsTable">
                                                <thead>
                                                    <tr>
                                                        <th>Equip ID</th>
                                                        <th>Name</th>
                                                        <th>Brand</th>
                                                        <th>Part No.</th>
                                                        <th>Qty.</th>
                                                        <th>Specs.</th>
                                                        <th>Remarks</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>
                                                            <select name="EquipmentIdInput" id="EquipmentIdInput" class="form-select" required>
                                                                <option value="">Select Equipment</option>
                                                                <?php
                                                                $equipmentQuery = "SELECT custom_equip_id FROM equip_tbl";
                                                                $equipmentResult = mysqli_query($connection, $equipmentQuery);

                                                                while ($row = mysqli_fetch_assoc($equipmentResult)) {
                                                                    $custom_equip_id = htmlspecialchars($row['custom_equip_id']);
                                                                    echo "<option value='$custom_equip_id'>$custom_equip_id</option>";
                                                                }
                                                                ?>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <textarea class="form-control part-input" name="parts[]" rows="1" style="resize: vertical;" required></textarea>
                                                        </td>
                                                        <td>
                                                            <textarea class="form-control brand-input" name="brands[]" rows="1" style="resize: vertical;" required></textarea>
                                                        </td>
                                                        <td>
                                                            <textarea class="form-control num-input" name="nums[]" rows="1" style="resize: vertical;" required></textarea>
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control quantity-input" name="quantities[]" min="1" required>
                                                        </td>
                                                        <td>
                                                            <textarea class="form-control specs-input" name="specs[]" rows="1" style="resize: vertical;"></textarea>
                                                        </td>
                                                        <td>
                                                            <textarea class="form-control remarks-input" name="remarks[]" rows="1" style="resize: vertical;"></textarea>
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-danger btn-sm remove-part">
                                                                <i class="fas fa-trash-alt me-2"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <button type="button" class="btn btn-success" id="addPartRow">
                                                <i class="fas fa-plus"></i> Add Another Part
                                            </button>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-success" id="savePartsBtn">Save Parts</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Add new Operator -->
                        <div class="modal fade" id="addOperatorModal" tabindex="-1" aria-labelledby="addOperatorModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <form id="addOperatorForm">
                                    <div class="modal-content">
                                        <div class="modal-header bg-success">
                                            <h5 class="modal-title fw-bold text-white" id="addOperatorModalLabel">+ Add New Operator</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="operatorEmpId" class="form-label fw-bold">Employee ID: <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="operatorEmpId" name="company_emp_id" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="operatorFirstName" class="form-label fw-bold">First Name: <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="operatorFirstName" name="first_name" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="operatorLastName" class="form-label fw-bold">Last Name: <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="operatorLastName" name="last_name" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="operatorContactNum" class="form-label fw-bold">Contact Number: <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="operatorContactNum" name="emp_contact_num"
                                                    placeholder="0000-000-0000" maxlength="13" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-success">Save</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <!-- Add another Equipment -->
                        <div class="modal fade" id="addEquipmentTypeModal" data-bs-backdrop="static" tabindex="-1" aria-labelledby="addEquipmentTypeLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header bg-success">
                                        <h5 class="modal-title fw-bold text-white" id="addEquipmentTypeLabel">+ Add Another Equipment</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form id="addEquipmentTypeForm">
                                            <div class="mb-3">
                                                <label for="newEquipmentType" class="form-label fw-bold">Equipment Name: <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="newEquipmentType" name="equipmentTypeName" required>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="button" class="btn btn-success" id="saveEquipmentTypeBtn">Save</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Add profiling -->
                        <form class="create-main" action="createProfiling.php" method="post">
                            <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false"
                                tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                                <div class="modal-dialog modal-xl">
                                    <div class="modal-content">
                                        <div class="modal-header bg-success">
                                            <h5 class="modal-title fw-bold text-white" id="addEquipmentModalLabel">+ Add New Equipment</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="createProfiling.php" method="POST" enctype="multipart/form-data">
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="equipmentTypeInput" class="form-label fw-bold">Type of Equipment: <span class="text-danger">*</span></label>
                                                            <select name="equipmentType" class="form-select shadow-sm" id="equipmentTypeInput" required>
                                                                <option value="" selected disabled>Choose equipment type</option>
                                                                <option value="" style="color: green;">+ Add another equipment</option>
                                                                <?php
                                                                $equipTypeQuery = "SELECT equip_type_id, equip_type_name FROM equip_type_tbl ORDER BY equip_type_name";
                                                                $equipTypeResult = mysqli_query($connection, $equipTypeQuery);

                                                                while ($equipType = mysqli_fetch_assoc($equipTypeResult)) {
                                                                    echo '<option value="' . htmlspecialchars($equipType['equip_type_id']) . '">'
                                                                        . htmlspecialchars($equipType['equip_type_name']) . '</option>';
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="ModelInput" class="form-label fw-bold">Model: <span class="text-danger">*</span></label>
                                                            <input type="text" name="model" class="form-control" id="ModelInput" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="YearInput" class="form-label fw-bold">Year: <span class="text-danger">*</span></label>
                                                            <input type="text" name="year" class="form-control" id="YearInput" maxlength="4" pattern="\d{4}" title="Please enter a year between 1900 and 2100" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="serialNo" class="form-label fw-bold">Serial No.: <span class="text-danger">*</span></label>
                                                            <input type="text" name="serial" id="serialNo" class="form-control">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="fuelTankCapacityInput" class="form-label fw-bold">Fuel Tank Capacity: <span class="text-danger">*</span></label>
                                                            <div class="input-group">
                                                                <input type="text" name="fuelTankCapacity" class="form-control" id="fuelTankCapacityInput" required>
                                                                <span class="input-group-text shadow-sm bg-white">Liters</span>
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="lastOperatingHoursInput" class="form-label fw-bold">Last Operating Hours: <span class="text-danger">*</span></label>
                                                            <input type="text" name="lastOperatingHours" class="form-control" id="lastOperatingHoursInput" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="maintenanceIntervalInput" class="form-label fw-bold">Maintenance Interval: <span class="text-danger">*</span></label>
                                                            <input type="text" name="maintenanceInterval" class="form-control" id="maintenanceIntervalInput" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="transmissionTypeInput" class="form-label fw-bold">Transmission Type: <span class="text-danger">*</span></label>
                                                            <input type="text" name="transmissionType" class="form-control" id="transmissionTypeInput" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="capacityInput" class="form-label fw-bold">Capacity: <span class="text-danger">*</span></label>
                                                            <div class="input-group">
                                                                <input type="text" name="capacityValue" class="form-control" id="capacityInput" oninput="validateCapacityInput()" required>
                                                                <select class="form-select shadow-sm rounded-end" style="max-width: 120px;" id="capacityUnit" name="capacityUnit" onchange="updateCapacity()" required>
                                                                    <option value="cu.m.">cu.m.</option>
                                                                    <option value="mtrs">mtrs</option>
                                                                    <option value="hp">hp</option>
                                                                    <option value="ton">ton</option>
                                                                </select>
                                                                <input type="hidden" name="capacity" id="combinedCapacity">
                                                            </div>
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="operatorSelect" class="form-label fw-bold">Operator: <span class="text-danger">*</span></label>
                                                            <select class="form-select shadow-sm" id="operatorSelect" name="operator" required>
                                                                <option value="" selected disabled>Select Operator</option>
                                                                <option value="new_operator" style="color: green;"> + Add Operator</option>
                                                                <?php
                                                                $operators = getOperators($connection);
                                                                if ($operators) {
                                                                    while ($operator = mysqli_fetch_assoc($operators)) {
                                                                        echo "<option value='" . htmlspecialchars($operator['employee_id']) . "'>" .
                                                                            htmlspecialchars($operator['company_emp_id'] . ' - ' . $operator['full_name']) .
                                                                            "</option>";
                                                                    }
                                                                }
                                                                ?>
                                                            </select>
                                                            <div class="invalid-feedback">Please select an operator</div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="engineTypeInput" class="form-label fw-bold">Engine Type: <span class="text-danger">*</span></label>
                                                            <input type="text" name="engineType" class="form-control" id="engineTypeInput" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="bodyIdInput" class="form-label fw-bold">Body ID: <span class="text-danger">*</span></label>
                                                            <input type="text" name="bodyId" class="form-control" id="bodyIdInput" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="fuelTypeInput" class="form-label fw-bold">Fuel Type: <span class="text-danger">*</span></label>
                                                            <select class="form-select shadow-sm" id="fuelTypeInput" name="fuelType" required>
                                                                <option value="">Select Fuel Type</option>
                                                                <option value="Diesel">Diesel</option>
                                                                <option value="Gasoline">Gasoline</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="lastPMSInput" class="form-label fw-bold">Last PMS Date: <span class="text-danger">*</span></label>
                                                            <input type="date" name="lastPMS" class="form-control" id="lastPMSInput" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="remarksInput" class="form-label fw-bold">Remarks: (Optional)</label>
                                                            <textarea name="remarks" class="form-control" id="remarksInput" rows="1"></textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="allRequiredFields"><span class="text-danger">*</span> Required Fields</label>
                                                        </div>
                                                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSpareparts">
                                                            + Add Parts
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-success me-auto" data-bs-toggle="modal" data-bs-target="#batchUploadModal">
                                                    <i class="fas fa-file-upload"></i>Batch Upload
                                                </button>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" name="create" class="btn btn-success">Add</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $sqlEquipment = mysqli_query($connection, "
            SELECT e.*, 
                   et.equip_type_name,
                   CONCAT(emp.first_name, ' ', emp.last_name) as operator_name,
                   p.project_location
            FROM equip_tbl e
            LEFT JOIN equip_type_tbl et ON e.equip_type_id = et.equip_type_id
            LEFT JOIN employee_tbl emp ON e.operator_id = emp.employee_id
            LEFT JOIN proj_sched_tbl p ON e.assigned_proj_id = p.project_id
            WHERE e.equip_status != 'Archived'
        ");
        ?>
        <div class="row mb-4 mt-4">
            <?php while ($equipmentResults = mysqli_fetch_array($sqlEquipment)) { ?>
                <div class="col-md-3 mt-2">
                    <div class="card card-stats border-success d-flex" data-date-added="<?php echo htmlspecialchars($equipmentResults['date_added']); ?>">
                        <div class="card-body">
                            <div class="row">
                                <div class="d-flex justify-content-between">
                                    <input type="checkbox" class="equipment-checkbox" name="equipment_ids[]" value="<?php echo htmlspecialchars($equipmentResults['custom_equip_id']); ?>">
                                    <form>
                                        <input type="hidden" name="edit_id" value="<?php echo htmlspecialchars($equipmentResults['equipment_id']); ?>">
                                        <button type="button" class="dropdown-item edit-equipment-btn" data-equipment-id="<?php echo htmlspecialchars($equipmentResults['equipment_id']); ?>" data-bs-toggle="modal" data-bs-target="#edit">Edit</button>
                                    </form>
                                </div>
                                <div class="modal fade" id="edit" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="edit" aria-hidden="true">
                                    <div class="modal-dialog modal-xl">
                                        <div class="modal-content">
                                            <div class="modal-header bg-success">
                                                <h5 class="modal-title fw-bold" id="staticBackdropLabel">Edit Equipment</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="editEquipmentTypeInput" class="form-label fw-bold">Type of Equipment: <span class="text-danger">*</span></label>
                                                            <select name="equipmentType" class="form-select shadow-sm" id="editEquipmentTypeInput" required>
                                                                <option value=""> + Add another equipment</option>
                                                                <option value="" selected disabled>Choose equipment type</option>
                                                                <?php
                                                                $equipTypeQuery = "SELECT equip_type_id, equip_type_name FROM equip_type_tbl ORDER BY equip_type_name";
                                                                $equipTypeResult = mysqli_query($connection, $equipTypeQuery);

                                                                while ($equipType = mysqli_fetch_assoc($equipTypeResult)) {
                                                                    echo '<option value="' . htmlspecialchars($equipType['equip_type_id']) . '">'
                                                                        . htmlspecialchars($equipType['equip_type_name']) . '</option>';
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="ModelInput" class="form-label fw-bold">Model: <span class="text-danger">*</span></label>
                                                            <input type="text" name="model" class="form-control" id="ModelInput" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="YearInput" class="form-label fw-bold">Year: <span class="text-danger">*</span></label>
                                                            <input type="text" name="year" class="form-control" id="YearInput" min="1900" max="2100" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="serialNo" class="form-label fw-bold">Serial No.: <span class="text-danger">*</span></label>
                                                            <input type="text" name="serial" id="serialNo" class="form-control">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="fuelTankCapacityInput" class="form-label fw-bold">Fuel Tank Capacity: <span class="text-danger">*</span></label>
                                                            <input type="text" name="fuelTankCapacity" class="form-control" id="fuelTankCapacityInput" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="lastOperatingHoursInput" class="form-label fw-bold">Last Operating Hours: <span class="text-danger">*</span></label>
                                                            <input type="text" name="lastOperatingHours" class="form-control" id="lastOperatingHoursInput" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="maintenanceIntervalInput" class="form-label fw-bold">Maintenance Interval: <span class="text-danger">*</span></label>
                                                            <input type="text" name="maintenanceInterval" class="form-control" id="maintenanceIntervalInput" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="transmissionTypeInput" class="form-label fw-bold">Transmission Type: <span class="text-danger">*</span></label>
                                                            <input type="text" name="transmissionType" class="form-control" id="transmissionTypeInput" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="editCapacityInput" class="form-label fw-bold">Capacity: <span class="text-danger">*</span></label>
                                                            <div class="input-group">
                                                                <input type="number" name="capacity" class="form-control shadow-sm" id="editCapacityInput" required>
                                                                <select class="form-select" style="max-width: 120px;" name="capacityUnit" required>
                                                                    <option value="cu.m.">cu.m.</option>
                                                                    <option value="mtrs">mtrs</option>
                                                                    <option value="hp">hp</option>
                                                                    <option value="ton">ton</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="operatorSelect" class="form-label fw-bold">Operator: <span class="text-danger">*</span></label>
                                                            <select class="form-select shadow-sm" id="operatorSelect" name="operator" required>
                                                                <option value="">Select Operator</option>
                                                                <?php
                                                                $operators = getOperators($connection);
                                                                if ($operators) {
                                                                    while ($operator = mysqli_fetch_assoc($operators)) {
                                                                        echo "<option value='" . htmlspecialchars($operator['employee_id']) . "'>" .
                                                                            htmlspecialchars($operator['company_emp_id'] . ' - ' . $operator['full_name']) .
                                                                            "</option>";
                                                                    }
                                                                }
                                                                ?>
                                                            </select>
                                                            <div class="invalid-feedback">Please select an operator</div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="engineTypeInput" class="form-label fw-bold">Engine Type: <span class="text-danger">*</span></label>
                                                            <input type="text" name="engineType" class="form-control" id="engineTypeInput" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="bodyIdInput" class="form-label fw-bold">Body ID: <span class="text-danger">*</span></label>
                                                            <input type="text" name="bodyId" class="form-control" id="bodyIdInput" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="editFuelTypeInput" class="form-label fw-bold">Fuel Type: <span class="text-danger">*</span></label>
                                                            <select class="form-select shadow-sm" id="editFuelTypeInput" name="fuelType" required>
                                                                <option value="">Select Fuel Type</option>
                                                                <option value="Diesel">Diesel</option>
                                                                <option value="Gasoline">Gasoline</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="lastPMSInput" class="form-label fw-bold">Last PMS Date: <span class="text-danger">*</span></label>
                                                            <input type="date" name="lastPMS" class="form-control" id="lastPMSInput" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="remarksInput" class="form-label fw-bold">Remarks: (Optional)</label>
                                                            <textarea name="remarks" class="form-control" id="remarksInput" rows="2"></textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="allRequiredFields"><span class="text-danger">*</span> Required Fields</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="button" class="btn btn-success">Save Changes</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="content text-center">
                                <div>
                                    <?php
                                    $imagePath = "../Pictures/default.png"; // Default fallback image
                                    if (isset($equipmentResults['equip_type_id'])) {
                                        $equipType = strtolower($equipmentResults['equip_type_name']);

                                        // Updated image mapping with consistent naming
                                        $imageMap = [
                                            // Loaders
                                            'payloader' => 'payloader-removebg-preview.png',
                                            'wheel loader' => 'payloader-removebg-preview.png',

                                            // Excavators
                                            'backhoe' => 'backhoe-removebg-preview.png',
                                            'excavator' => 'backhoe-removebg-preview.png',

                                            // Heavy Equipment
                                            'bulldozer' => 'bulldozer-removebg-preview.png',
                                            'road grader' => 'road_grader-removebg-preview.png',
                                            'motor grader' => 'road_grader-removebg-preview.png',

                                            // Cranes
                                            'truck crane' => 'crane-removebg-preview.png',
                                            'truck mounted crane' => 'crane-removebg-preview.png',
                                            'crawler crane' => 'crane-removebg-preview.png',
                                            'rough terrain crane' => 'crane-removebg-preview.png',

                                            // Specialized Equipment
                                            'forklift' => 'forklift-removebg-preview.png',
                                            'asphalt paver' => 'asphalt_paver-removebg-preview.png',

                                            // Drilling Equipment
                                            'bored pile rig' => 'bored_pile_rig-removebg-preview.png',
                                            'hydraulic drilling rig' => 'hydraulic_drilling_rig-removebg-preview.png',
                                            'horizontal directional drilling machine' => 'Directional_Drilling_Machine-removebg-preview.png',

                                            // Hammers
                                            'vibro hammer' => 'vibro_hammer-removebg-preview.png',
                                            'vibro sheet pile hammer' => 'vibro_sheet-removebg-preview.png',
                                            'diesel hammer' => 'diesel_hammer-removebg-preview.png',
                                            'hydraulic breaker' => 'hydraulic_breaker-removebg-preview.png',

                                            // Rollers
                                            'road roller' => 'Road_roller-removebg-preview.png',
                                            'vibratory roller' => 'Road_roller-removebg-preview.png',
                                            'vibratory roller double drum hydraulic type' => 'Road_roller-removebg-preview.png',
                                            'tire roller' => 'Road_roller-removebg-preview.png',
                                            'double drum roller' => 'Road_roller-removebg-preview.png',
                                            'tandem roller' => 'Road_roller-removebg-preview.png',
                                            'pneumatic tire roller' => 'Road_roller-removebg-preview.png',

                                            // Power Equipment
                                            'generator set' => 'Generator_set-removebg-preview.png',
                                            'welding generator' => 'Welding_Generator-removebg-preview.png',
                                            'air compressor' => 'Aircompressor-removebg-preview.png'
                                        ];

                                        // Find matching equipment type in the map
                                        foreach ($imageMap as $key => $image) {
                                            if (strpos($equipType, $key) !== false) {
                                                $imagePath = "../Pictures/" . $image;
                                                break;
                                            }
                                        }
                                    }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Equipment Image" class="equipment-image">
                                    <p class="fw-bold mt-3">
                                        <?php echo $equipmentResults['custom_equip_id'] ?>
                                    </p>
                                    <p class="text-danger fw-bold">
                                        <?php echo $equipmentResults['equip_type_name'] ?>
                                    </p>
                                    <p class="text-muted fw-bold">
                                        Model: <?php echo $equipmentResults['model'] ?>
                                    </p>
                                    <div class="btn-group">
                                        <button type="button" class="btn dropdown-toggle
                                        <?php
                                        $status = strtolower($equipmentResults['equip_status'] ?? 'idle');
                                        switch ($status) {
                                            case 'active':
                                                echo 'btn-status-active';
                                                break;
                                            case 'idle':
                                                echo 'btn-status-idle';
                                                break;
                                            case 'under maintenance':
                                            case 'for maintenance':
                                                echo 'btn-status-maintenance';
                                                break;
                                            case 'for repair':
                                            case 'breakdown':
                                            case 'for disposal':
                                                echo 'btn-status-repair';
                                                break;
                                            default:
                                                echo 'btn-status-idle';
                                        }
                                        ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                            <?php echo $equipmentResults['equip_status'] ?? 'Idle'; ?>
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                            <div class="overflow-auto" style="max-height: 200px;">
                                                <li><a class="dropdown-item status-option" href="#" data-status="Active">
                                                        <span class="badge bg-success">Active</span></a></li>
                                                <li><a class="dropdown-item status-option" href="#" data-status="Idle">
                                                        <span class="badge bg-secondary">Idle</span></a></li>
                                                <li><a class="dropdown-item status-option" href="#" data-status="Under Maintenance">
                                                        <span class="badge bg-warning text-dark">Under Maintenance</span></a></li>
                                                <li><a class="dropdown-item status-option" href="#" data-status="For Maintenance">
                                                        <span class="badge bg-warning text-dark">For Maintenance</span></a></li>
                                                <li><a class="dropdown-item status-option" href="#" data-status="For Repair">
                                                        <span class="badge bg-danger">For Repair</span></a></li>
                                                <li><a class="dropdown-item status-option" href="#" data-status="Breakdown">
                                                        <span class="badge bg-danger">Breakdown</span></a></li>
                                                <li><a class="dropdown-item status-option" href="#" data-status="For Disposal">
                                                        <span class="badge bg-danger">For Disposal</span></a></li>
                                            </div>
                                        </ul>
                                    </div>

                                    <button class="btn btn-secondary more-details"
                                        data-equipment="<?php echo htmlspecialchars($equipmentResults['custom_equip_id'] ?? ''); ?>"
                                        data-type="<?php echo htmlspecialchars($equipmentResults['equip_type_name'] ?? ''); ?>"
                                        data-model="<?php echo htmlspecialchars($equipmentResults['model'] ?? ''); ?>"
                                        data-engine-serial="<?php echo htmlspecialchars($equipmentResults['engine_serial_num'] ?? ''); ?>"
                                        data-year="<?php echo htmlspecialchars($equipmentResults['equip_year'] ?? ''); ?>"
                                        data-capacity="<?php echo htmlspecialchars($equipmentResults['capacity'] ?? ''); ?>"
                                        data-body="<?php echo htmlspecialchars($equipmentResults['body_id'] ?? ''); ?>"
                                        data-location="<?php echo htmlspecialchars($equipmentResults['project_location'] ?? 'Not Assigned'); ?>"
                                        data-last-pms-date="<?php echo htmlspecialchars($equipmentResults['last_pms_date'] ? date('M d, Y', strtotime($equipmentResults['last_pms_date'])) : ''); ?>"
                                        data-maintenance-interval="<?php echo htmlspecialchars($equipmentResults['maintenance_interval'] ?? ''); ?>"
                                        data-status="<?php echo htmlspecialchars($equipmentResults['equip_status'] ?? ''); ?>"
                                        data-deployment="<?php echo htmlspecialchars($equipmentResults['deployment_status'] ?? ''); ?>"
                                        data-operator-id="<?php echo htmlspecialchars($equipmentResults['operator_id'] ?? ''); ?>"
                                        data-operator-name="<?php echo htmlspecialchars(
                                                                $equipmentResults['operator_name'] ??
                                                                    (!empty($equipmentResults['operator_id']) ?
                                                                        mysqli_fetch_assoc(mysqli_query(
                                                                            $connection,
                                                                            "SELECT CONCAT(first_name, ' ', last_name) as name 
                                                     FROM employee_tbl 
                                                     WHERE employee_id = " . $equipmentResults['operator_id']
                                                                        ))['name'] : '')
                                                            ); ?>"
                                        data-remarks="<?php echo htmlspecialchars($equipmentResults['equip_remarks'] ?? ''); ?>">
                                        More Details
                                    </button>
                                    <button class="btn btn-primary equipment-specification mt-3" type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#equipmentSpecification"
                                        data-engine-type="<?php echo htmlspecialchars($equipmentResults['engine_type'] ?? ''); ?>"
                                        data-body-id="<?php echo htmlspecialchars($equipmentResults['body_id'] ?? ''); ?>"
                                        data-fuel-type="<?php echo htmlspecialchars($equipmentResults['fuel_type'] ?? ''); ?>"
                                        data-capacity="<?php echo htmlspecialchars($equipmentResults['capacity'] ?? ''); ?>"
                                        data-last-pms-date="<?php echo htmlspecialchars($equipmentResults['last_pms_date'] ?? ''); ?>"
                                        data-fuel-tank-capacity="<?php echo htmlspecialchars($equipmentResults['fuel_tank_capacity'] ?? ''); ?>"
                                        data-last-operating-hours="<?php echo htmlspecialchars($equipmentResults['last_operating_hours'] ?? ''); ?>"
                                        data-maintenance-interval="<?php echo htmlspecialchars($equipmentResults['maintenance_interval'] ?? ''); ?>"
                                        data-transmission-type="<?php echo htmlspecialchars($equipmentResults['transmission_type'] ?? ''); ?>"
                                        data-serial-no="<?php echo htmlspecialchars($equipmentResults['engine_serial_num'] ?? ''); ?>">
                                        Technical Specifications
                                    </button>
                                    <?php if ($equipmentResults['date_added']): ?>
                                        <small class="text-muted mt-2">
                                            Added: <?php echo date('M d, Y', strtotime($equipmentResults['date_added'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="modal fade" id="equipmentSpecification" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="equipmentSpecification" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-success">
                                            <h5 class="modal-title fw-bold" id="staticBackdropLabel">Technical Specifications</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <table class="table table-borderless">
                                                        <tr>
                                                            <td class="fw-bold">Engine Type:</td>
                                                            <td id="modal-engine-type">-</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="fw-bold">Body ID:</td>
                                                            <td id="modal-body-id">-</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="fw-bold">Fuel Type:</td>
                                                            <td id="modal-fuel-type">-</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="fw-bold">Capacity:</td>
                                                            <td id="modal-capacity">-</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="fw-bold">Last PMS date:</td>
                                                            <td id="modal-last-pms-date">-</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <div class="col-md-6">
                                                    <table class="table table-borderless">
                                                        <tr>
                                                            <td class="fw-bold">Fuel tank capacity:</td>
                                                            <td id="modal-fuel-tank-capacity">-</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="fw-bold">Last operating hours:</td>
                                                            <td id="modal-last-operating-hours">-</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="fw-bold">Maintenance interval:</td>
                                                            <td id="modal-maintenance-interval">-</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="fw-bold">Transmission Type:</td>
                                                            <td id="modal-transmission-type">-</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
        <div id="equipmentDetailsModal" class="details-modal">
            <div class="modal-content">
                <div class="modal-header mb-2" style="background-color: #198754; color: #fff; border-top-left-radius: 15px; border-top-right-radius: 15px; padding: 1.5rem; margin: -10px -10px 0 -10px;">
                    <h5 class="modal-title fw-bold" id="modalTitle">Equipment Details</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="document.getElementById('equipmentDetailsModal').style.display='none'" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="equipment-details">
                    <div class="equipment-image">
                        <img src="" alt="Equipment Image" id="modalEquipmentImage">
                    </div>
                    <div class="equipment-info">
                        <h4 id="equipmentId" class="fw-bold"></h4>
                        <p id="equipmentType" class="text-danger fw-bold"></p>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Model:</strong> <span id="equipmentModel"></span></p>
                                <p><strong>Serial No.:</strong> <span id="equipmentSerial"></span></p>
                                <p><strong>Year:</strong> <span id="equipmentYear"></span></p>
                                <p><strong>Capacity:</strong> <span id="equipmentCapacity"></span></p>
                                <p><strong>Body ID:</strong> <span id="equipmentBodyID"></span></p>
                                <p><strong>Location:</strong> <span id="equipmentLocation"><?php echo $equipmentResults['project_location'] ?? 'Not Assigned'; ?></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Last Maintenance:</strong> <span id="lastMaintenance"></span></p>
                                <p><strong>Next Maintenance:</strong> <span id="nextMaintenance"></span></p>
                                <p><strong>Equipment Status:</strong> <span id="equipmentStatus"></span></p>
                                <p><strong>Operator:</strong>
                                    <span id="equipmentOperator">
                                        <?php echo isset($equipmentResults['operator_id']) ?
                                            htmlspecialchars($equipmentResults['operator_id']) : 'Not Assigned';
                                        ?>
                                    </span>
                                </p>
                                <p><strong>Remarks:</strong> <span id="equipmentRemarks"></span></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="function-buttons">
                    <div class="function-btn">
                        <i class="fas fa-calendar-check"></i>
                        <div>
                            <h5 class="mb-0">Schedule Maintenance</h5>
                            <p class="mb-0 text-muted">Plan next service</p>
                        </div>
                    </div>
                    <div class="function-btn">
                        <i class="fas fa-tools"></i>
                        <div>
                            <h5 class="mb-0">Add Spare Parts</h5>
                            <p class="mb-0 text-muted">Manage inventory</p>
                        </div>
                    </div>
                    <div class="function-btn">
                        <i class="fas fa-history"></i>
                        <div>
                            <h5 class="mb-0">Maintenance History</h5>
                            <p class="mb-0 text-muted">View past services</p>
                        </div>
                    </div>
                    <div class="function-btn">
                        <i class="fas fa-file-alt"></i>
                        <div>
                            <h5 class="mb-0">Related Reports</h5>
                            <p class="mb-0 text-muted">View documentation</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                updateArchiveCount();

                // 1) Equipment Details Modal
                const detailsModal = document.getElementById('equipmentDetailsModal');
                const detailButtons = document.querySelectorAll('.more-details');
                const specButtons = document.querySelectorAll('.equipment-specification');

                // Handle More Details button clicks
                detailButtons.forEach(btn => {
                    btn.addEventListener('click', () => {
                        // Get all data attributes from the button
                        const data = btn.dataset;
                        const modal = document.getElementById('equipmentDetailsModal');
                        const operatorId = btn.dataset.operatorId;

                        // Update equipment image based on type
                        const equipType = (data.type || '').toLowerCase();
                        const imagePath = getEquipmentImagePath(equipType);
                        document.getElementById('modalEquipmentImage').src = imagePath;

                        // Update all equipment details
                        const details = {
                            'equipmentId': data.equipment || 'N/A',
                            'equipmentType': data.type || 'N/A',
                            'equipmentModel': data.model || 'N/A',
                            'equipmentSerial': data.engineSerial || 'N/A',
                            'equipmentYear': data.year || 'N/A',
                            'equipmentCapacity': data.capacity || 'N/A',
                            'equipmentBodyID': data.body || 'N/A',
                            'equipmentLocation': data.location || 'N/A',
                            'lastMaintenance': data.lastPmsDate || 'N/A',
                            'nextMaintenance': calculateNextMaintenance(data.lastPmsDate, data.maintenanceInterval),
                            'equipmentStatus': data.status || 'N/A',
                            'deploymentStatus': data.deployment || 'Undeployed',
                            'assignedProjectID': data.projectId || 'N/A',
                            'equipmentFuelTankCapacity': data.fuelTankCapacity || 'N/A',
                            'equipmentRemarks': data.remarks || 'N/A'
                        };

                        const operatorSpan = document.getElementById('equipmentOperator');
                        if (operatorSpan) {
                            if (data.operatorId && data.operatorName) {
                                operatorSpan.textContent = `${data.operatorName}`;
                            } else {
                                operatorSpan.textContent = 'Not Assigned';
                            }
                        }

                        // Update each field in the modal
                        Object.entries(details).forEach(([id, value]) => {
                            const element = document.getElementById(id);
                            if (element) {
                                if (id === 'equipmentStatus') {
                                    element.className = `badge ${getStatusClass(value)}`;
                                    element.textContent = value;
                                } else {
                                    element.textContent = value;
                                }
                            }
                        });

                        // Show the modal
                        modal.style.display = 'block';

                        // Add click handlers for function buttons
                        document.querySelectorAll('.function-btn').forEach(btn => {
                            btn.addEventListener('click', () => {
                                const action = btn.querySelector('h5').textContent;
                                switch (action) {
                                    case 'Schedule Maintenance':
                                        // Handle maintenance scheduling
                                        window.location.href = `MaintenanceScheduling.php?equipment=${data.equipment}`;
                                        break;
                                    case 'Add Spare Parts':
                                        // Handle spare parts management
                                        window.location.href = `Inventory.php?equipment=${data.equipment}`;
                                        break;
                                    case 'Maintenance History':
                                        // Handle maintenance history
                                        window.location.href = `MaintenanceScheduling.php?equipment=${data.equipment}`;
                                        break;
                                    case 'Related Reports':
                                        // Handle reports
                                        window.location.href = `Reports.php?equipment=${data.equipment}`;
                                        break;
                                }
                            });
                        });
                    });
                });

                // --- Automatic status-based sorting on page load ---
                const statusOrder = [
                    'idle',
                    'for maintenance',
                    'under maintenance',
                    'for repair',
                    'for disposal',
                    'breakdown',
                    'active'
                ];

                function getStatusIndex(status) {
                    const normalized = (status || '').toLowerCase();
                    const idx = statusOrder.indexOf(normalized);
                    return idx === -1 ? statusOrder.length : idx;
                }

                function sortEquipmentCardsByStatus() {
                    const container = document.querySelector('.row.mb-4.mt-4');
                    if (!container) return;
                    const cards = Array.from(container.querySelectorAll('.card.card-stats'));
                    const sortedCards = cards.sort((a, b) => {
                        const statusA = a.querySelector('.btn.dropdown-toggle')?.textContent.trim().toLowerCase() || '';
                        const statusB = b.querySelector('.btn.dropdown-toggle')?.textContent.trim().toLowerCase() || '';
                        const idxA = getStatusIndex(statusA);
                        const idxB = getStatusIndex(statusB);
                        if (idxA !== idxB) return idxA - idxB;
                        // fallback: sort by equipment ID
                        const textA = a.querySelector('.fw-bold.mt-3')?.textContent.toLowerCase() || '';
                        const textB = b.querySelector('.fw-bold.mt-3')?.textContent.toLowerCase() || '';
                        return textA.localeCompare(textB);
                    });
                    // Clear and repopulate container
                    container.innerHTML = '';
                    sortedCards.forEach(card => {
                        const cardWrapper = document.createElement('div');
                        cardWrapper.className = 'col-md-3 mt-2';
                        cardWrapper.appendChild(card);
                        container.appendChild(cardWrapper);
                    });
                }

                // Call the sorting function immediately after DOM is ready
                sortEquipmentCardsByStatus();

                // Year input validation
                const yearInput = document.getElementById('YearInput');
                if (yearInput) {
                    yearInput.addEventListener('input', function() {
                        // Remove non-digits
                        this.value = this.value.replace(/[^0-9]/g, '');

                        // Limit to 4 digits
                        if (this.value.length > 4) {
                            this.value = this.value.slice(0, 4);
                        }

                        // Get current year
                        const currentYear = new Date().getFullYear();

                        // Validate year range when 4 digits are entered
                        if (this.value.length === 4) {
                            const year = parseInt(this.value);
                            if (year < 1900 || year > 2100) {
                                this.setCustomValidity(`Please enter a year between 1900 and 2100`);
                                this.classList.add('is-invalid');
                            } else {
                                this.setCustomValidity('');
                                this.classList.remove('is-invalid');
                            }
                        }
                    });

                    // Prevent non-numeric key presses and show message for "!"
                    yearInput.addEventListener('keypress', function(e) {
                        if (e.key === '!') {
                            e.preventDefault();
                            alert('Please enter a year between 1900 and 2100');
                            return;
                        }

                        if (!/^\d$/.test(e.key)) {
                            e.preventDefault();
                        }
                    });

                    // Handle paste events
                    yearInput.addEventListener('paste', function(e) {
                        e.preventDefault();
                        const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                        if (/^\d+$/.test(pastedText)) {
                            this.value = pastedText.slice(0, 4);
                            this.dispatchEvent(new Event('input'));
                        }
                    });
                }

                // Helper function to update archive count
                function resetAutoIncrement(tableData) {
                    const maxId = Math.max(...tableData.map(item => parseInt(item.equipment_id))) || 0;
                    return maxId + 1;
                }

                // Function to update capacity input
                function updateCapacity() {
                    const capacityValue = document.getElementById('capacityInput').value;
                    const capacityUnit = document.getElementById('capacityUnit').value;
                    const combinedCapacityInput = document.getElementById('combinedCapacity');
                    const invalidFeedback = document.querySelector('.invalid-feedback');

                    // Validate numeric input
                    if (!capacityValue || isNaN(capacityValue) || parseFloat(capacityValue) <= 0) {
                        invalidFeedback.style.display = 'block';
                        invalidFeedback.textContent = 'Please enter a valid positive number';
                        combinedCapacityInput.value = '';
                        return false;
                    }

                    try {
                        // Format the number to 2 decimal places
                        const formattedValue = parseFloat(capacityValue).toFixed(2);
                        const combinedCapacity = `${formattedValue} ${capacityUnit}`;

                        combinedCapacityInput.value = combinedCapacity;
                        invalidFeedback.style.display = 'none';
                        console.log('Combined capacity updated:', combinedCapacity);
                        return true;
                    } catch (error) {
                        console.error('Error updating capacity:', error);
                        invalidFeedback.style.display = 'block';
                        invalidFeedback.textContent = 'Invalid capacity format';
                        return false;
                    }
                }

                // Input validation for capacity
                function validateCapacityInput() {
                    const input = document.getElementById('capacityInput');
                    let value = input.value;

                    // Remove any non-numeric characters except decimal point
                    value = value.replace(/[^\d.]/g, '');

                    // Ensure only one decimal point
                    const decimalPoints = value.match(/\./g) || [];
                    if (decimalPoints.length > 1) {
                        value = value.replace(/\.(?=.*\.)/g, '');
                    }

                    input.value = value;
                    return updateCapacity();
                }

                // Add event listeners when document is ready
                document.addEventListener('DOMContentLoaded', () => {
                    const form = document.querySelector('form.create-main');
                    const capacityInput = document.getElementById('capacityInput');
                    const capacityUnit = document.getElementById('capacityUnit');

                    if (form && capacityInput && capacityUnit) {
                        // Form submission handler
                        form.addEventListener('submit', function(e) {
                            if (!updateCapacity()) {
                                e.preventDefault();
                                alert('Please enter a valid capacity value');
                                return false;
                            }
                            return true;
                        });

                        capacityInput.addEventListener('input', validateCapacityInput);
                        capacityUnit.addEventListener('change', updateCapacity);

                        updateCapacity();
                    }
                });

                // Helper function to calculate next maintenance date
                function calculateNextMaintenance(lastPmsDate, interval) {
                    if (!lastPmsDate || !interval) return 'Not scheduled';
                    const lastDate = new Date(lastPmsDate);
                    const nextDate = new Date(lastDate);
                    nextDate.setDate(lastDate.getDate() + parseInt(interval));
                    return nextDate.toLocaleDateString();
                }

                // Select All Button functionality
                const selectAllBtn = document.getElementById('select-all-btn');
                const equipmentCheckboxes = document.querySelectorAll('.equipment-checkbox');
                let isAllSelected = false;

                if (selectAllBtn) {
                    selectAllBtn.addEventListener('click', function() {
                        isAllSelected = !isAllSelected; // Toggle the state

                        // Update all checkboxes
                        equipmentCheckboxes.forEach(checkbox => {
                            checkbox.checked = isAllSelected;
                        });

                        // Update button text and visibility
                        if (isAllSelected) {
                            selectAllBtn.innerHTML = '<i class="fa-solid fa-square-xmark"></i> Cancel';
                            selectAllBtn.classList.add('active');
                        } else {
                            selectAllBtn.innerHTML = '<i class="fa-solid fa-square-check"></i> Select All';
                            selectAllBtn.classList.remove('active');
                            selectAllBtn.style.display = 'none'; // Hide when canceled
                        }

                        // Enable/disable archive button based on selection
                        const archiveAllBtn = document.getElementById('archive-all-btn');
                        if (archiveAllBtn) {
                            archiveAllBtn.disabled = !isAllSelected && !Array.from(equipmentCheckboxes).some(cb => cb.checked);
                        }
                    });
                }

                // Function to update operator display
                function updateOperatorDisplay(operatorId, element) {
                    if (!operatorId) {
                        element.textContent = 'Not Assigned';
                        return;
                    }

                    fetch(`getOperator.php?operator_id=${operatorId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                element.textContent = data.full_name;
                            } else {
                                element.textContent = 'Not Assigned';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching operator:', error);
                            element.textContent = 'Not Assigned';
                        });
                }

                // Add Operator Modal functionality
                const operatorSelect = document.getElementById('operatorSelect');
                const addOperatorModalEl = document.getElementById('addOperatorModal');
                const addEquipmentModalEl = document.getElementById('staticBackdrop');
                let addOperatorModal, addEquipmentModal;

                if (operatorSelect && addOperatorModalEl && addEquipmentModalEl) {
                    addOperatorModal = new bootstrap.Modal(addOperatorModalEl);
                    addEquipmentModal = new bootstrap.Modal(addEquipmentModalEl);

                    operatorSelect.addEventListener('change', function() {
                        if (this.value === 'new_operator') {
                            bootstrap.Modal.getInstance(addEquipmentModalEl)?.hide();
                            addOperatorModal.show();
                        }
                    });

                    addOperatorModalEl.addEventListener('hidden.bs.modal', function() {
                        if (!addEquipmentModalEl.classList.contains('show')) {
                            addEquipmentModal.show();
                        }
                    });
                }

                // Handle Add Operator form submission
                if (addOperatorForm) {
                    addOperatorForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        const formData = new FormData(addOperatorForm);

                        fetch('getOperator.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    // Add new operator to select
                                    const option = document.createElement('option');
                                    option.value = data.employee_id;
                                    option.textContent = data.display_name;
                                    operatorSelect.appendChild(option);
                                    operatorSelect.value = data.employee_id;
                                    addOperatorModal.hide();

                                    // Show success alert
                                    const alertDiv = document.createElement('div');
                                    alertDiv.className = 'alert alert-success position-fixed top-50 start-50 translate-middle';
                                    alertDiv.style.zIndex = '1060';
                                    alertDiv.textContent = 'Operator added successfully!';
                                    document.body.appendChild(alertDiv);
                                    setTimeout(() => alertDiv.remove(), 2000);
                                } else {
                                    alert(data.message || 'Failed to add operator');
                                }
                            })
                            .catch(() => alert('Error adding operator'));
                    });
                }

                // Handle contact number input formatting
                document.addEventListener('DOMContentLoaded', function() {
                    const contactInput = document.getElementById('operatorContactNum');
                    if (contactInput) {
                        contactInput.addEventListener('input', function(e) {
                            let value = this.value.replace(/\D/g, '').slice(0, 11);
                            if (value.length > 4 && value.length <= 7)
                                value = value.slice(0, 4) + '-' + value.slice(4);
                            else if (value.length > 7)
                                value = value.slice(0, 4) + '-' + value.slice(4, 7) + '-' + value.slice(7);
                            this.value = value;
                        });
                    }
                });

                // Update individual checkbox handlers
                equipmentCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        const allChecked = Array.from(equipmentCheckboxes).every(cb => cb.checked);
                        const anyChecked = Array.from(equipmentCheckboxes).some(cb => cb.checked);

                        if (selectAllBtn) {
                            // Show or hide the "Select All" button
                            selectAllBtn.style.display = anyChecked ? 'inline-block' : 'none';

                            // Update select all button state
                            isAllSelected = allChecked;
                            selectAllBtn.innerHTML = allChecked ?
                                '<i class="fa-solid fa-square-xmark"></i> Cancel' :
                                '<i class="fa-solid fa-square-check"></i> Select All';
                            selectAllBtn.classList.toggle('active', allChecked);
                        }

                        // Enable/disable archive button
                        const archiveAllBtn = document.getElementById('archive-all-btn');
                        if (archiveAllBtn) {
                            archiveAllBtn.disabled = !anyChecked;
                        }
                    });
                });

                // Helper function to get image path
                function getEquipmentImagePath(equipType) {
                    const imageMap = {
                        'payloader': '../Pictures/payloader-removebg-preview.png',
                        'wheel loader': '../Pictures/payloader-removebg-preview.png',
                        'backhoe': '../Pictures/backhoe-removebg-preview.png',
                        'excavator': '../Pictures/backhoe-removebg-preview.png',
                        'bulldozer': '../Pictures/bulldozer-removebg-preview.png',
                        'grader': '../Pictures/road_grader-removebg-preview.png',
                        'crane': '../Pictures/crane-removebg-preview.png',
                        'forklift': '../Pictures/forklift-removebg-preview.png',
                        // Add other equipment types as needed
                    };

                    return imageMap[equipType] || '../Pictures/default.png';
                }

                // Helper function for status badge classes
                function getStatusClass(status) {
                    const statusMap = {
                        'active': 'bg-success',
                        'idle': 'bg-secondary',
                        'under maintenance': 'bg-warning text-dark',
                        'for maintenance': 'bg-warning text-dark',
                        'for repair': 'bg-danger',
                        'breakdown': 'bg-danger',
                        'for disposal': 'bg-danger'
                    };
                    const normalizedStatus = status?.toLowerCase() || '';
                    return statusMap[status?.toLowerCase()] || 'bg-secondary';
                }

                // Helper function to calculate next maintenance date
                function calculateNextMaintenance(lastPmsDate, interval) {
                    if (!lastPmsDate || !interval) return 'Not scheduled';
                    const lastDate = new Date(lastPmsDate);
                    const nextDate = new Date(lastDate);
                    nextDate.setDate(lastDate.getDate() + parseInt(interval));
                    return nextDate.toLocaleDateString();
                }

                // Handle Technical Specifications button clicks
                specButtons.forEach(btn => {
                    btn.addEventListener('click', () => {
                        // Get data directly from the button's data attributes
                        const data = btn.dataset;

                        // Update modal fields
                        document.getElementById('modal-engine-type').textContent = data.engineType || '-';
                        document.getElementById('modal-body-id').textContent = data.bodyId || '-';
                        document.getElementById('modal-fuel-type').textContent = data.fuelType || '-';
                        document.getElementById('modal-capacity').textContent = data.capacity || '-';
                        document.getElementById('modal-last-pms-date').textContent = data.lastPmsDate || '-';
                        document.getElementById('modal-fuel-tank-capacity').textContent = data.fuelTankCapacity || '-';
                        document.getElementById('modal-last-operating-hours').textContent = data.lastOperatingHours || '-';
                        document.getElementById('modal-maintenance-interval').textContent = data.maintenanceInterval || '-';
                        document.getElementById('modal-transmission-type').textContent = data.transmissionType || '-';
                        document.getElementById('modal-serial-no').textContent = data.serialNo || '-';
                    });
                });

                // Handle opening the specifications modal from the main details modal
                const viewSpecsBtn = document.getElementById('viewSpecificationsBtn');
                if (viewSpecsBtn) {
                    viewSpecsBtn.addEventListener('click', () => {
                        // Hide the details modal
                        detailsModal.style.display = 'none';

                        console.log("View Specs Button Data:", {
                            engineType: viewSpecsBtn.dataset.engineType,
                            bodyId: viewSpecsBtn.dataset.body,
                            fuelType: viewSpecsBtn.dataset.fuelType,
                            capacity: viewSpecsBtn.dataset.capacity,
                            lastPmsDate: viewSpecsBtn.dataset.lastPmsDate,
                            fuelTankCapacity: viewSpecsBtn.dataset.fuelTankCapacity,
                            lastOperatingHours: viewSpecsBtn.dataset.lastOperatingHours,
                            maintenanceInterval: viewSpecsBtn.dataset.maintenanceInterval,
                            transmissionType: viewSpecsBtn.dataset.transmissionType,
                            engineSerial: viewSpecsBtn.dataset.engineSerial
                        });

                        // Populate the specs modal with data from the viewSpecificationsBtn
                        document.getElementById('modal-engine-type').textContent = viewSpecsBtn.dataset.engineType || '-';
                        document.getElementById('modal-body-id').textContent = viewSpecsBtn.dataset.body || '-';
                        document.getElementById('modal-fuel-type').textContent = viewSpecsBtn.dataset.fuelType || '-';
                        document.getElementById('modal-capacity').textContent = viewSpecsBtn.dataset.capacity || '-';
                        document.getElementById('modal-last-pms-date').textContent = viewSpecsBtn.dataset.lastPmsDate || '-';
                        document.getElementById('modal-fuel-tank-capacity').textContent = viewSpecsBtn.dataset.fuelTankCapacity || '-';
                        document.getElementById('modal-last-operating-hours').textContent = viewSpecsBtn.dataset.lastOperatingHours || '-';
                        document.getElementById('modal-maintenance-interval').textContent = viewSpecsBtn.dataset.maintenanceInterval || '-';
                        document.getElementById('modal-transmission-type').textContent = viewSpecsBtn.dataset.transmissionType || '-';
                        document.getElementById('modal-engine-serial').textContent = viewSpecsBtn.dataset.engineSerial || '-';

                        // Show the specifications modal using Bootstrap's modal method
                        var specModal = new bootstrap.Modal(document.getElementById('equipmentSpecification'));
                        specModal.show();
                    });
                }

                // Close buttons for the main details modal
                const closeBtns = document.querySelectorAll('.close-modal, .btn-close');
                closeBtns.forEach(x => x.addEventListener('click', () => {
                    detailsModal.style.display = 'none';
                }));

                // Close the main details modal when clicking outside
                window.addEventListener('click', e => {
                    if (e.target === detailsModal) detailsModal.style.display = 'none';
                });

                // 2) Plate-No Auto-Fill on Type Change
                const select = document.getElementById('equipmentTypeInput');
                const plate = document.getElementById('PlateNo');
                if (select && plate) {
                    async function fetchPlate() {
                        try {
                            const res = await fetch('createProfiling.php?action=getNextPlate');
                            const json = await res.json();
                            plate.value = json.plate || '';
                        } catch {
                            plate.value = '';
                        }
                    }
                    select.addEventListener('change', fetchPlate);
                    if (select.value) fetchPlate();
                }

                // 3) Search functionality
                const searchInput = document.querySelector('.search-input');
                const equipmentCards = document.querySelectorAll('.card.card-stats');
                if (searchInput) {
                    searchInput.addEventListener('input', () => {
                        const searchTerm = searchInput.value.toLowerCase();
                        equipmentCards.forEach(card => {
                            const equipmentId = card.querySelector('.fw-bold.mt-3')?.textContent.toLowerCase() || '';
                            const equipmentType = card.querySelector('.text-danger.fw-bold')?.textContent.toLowerCase() || '';
                            if (equipmentId.includes(searchTerm) || equipmentType.includes(searchTerm)) {
                                card.parentElement.style.display = 'block';
                            } else {
                                card.parentElement.style.display = 'none';
                            }
                        });
                    });
                }

                // Reset search
                const resetSearch = () => {
                    if (searchInput) {
                        searchInput.value = '';
                        equipmentCards.forEach(card => {
                            card.parentElement.style.display = 'block';
                        });
                    }
                };

                // 4) Sort functionality
                const sortButtons = document.querySelectorAll('.sort-option');
                sortButtons.forEach(button => {
                    button.addEventListener('click', () => {
                        const sortOrder = button.dataset.sort;
                        const container = document.querySelector('.row.mb-4.mt-4');
                        if (!container) return;

                        // If the sort order is 'unsort', reload the page to reset to the original order.
                        if (sortOrder === 'unsort') {
                            window.location.reload();
                            return;
                        }

                        const sortedCards = Array.from(document.querySelectorAll('.card.card-stats')).sort((a, b) => {
                            const textA = a.querySelector('.fw-bold.mt-3')?.textContent.toLowerCase() || '';
                            const textB = b.querySelector('.fw-bold.mt-3')?.textContent.toLowerCase() || '';
                            const dateA = a.dataset.dateAdded ? new Date(a.dataset.dateAdded) : new Date(0);
                            const dateB = b.dataset.dateAdded ? new Date(b.dataset.dateAdded) : new Date(0);

                            switch (sortOrder) {
                                case 'asc':
                                    return textA.localeCompare(textB);
                                case 'desc':
                                    return textB.localeCompare(textA);
                                case 'newest':
                                    return dateB - dateA;
                                case 'oldest':
                                    return dateA - dateB;
                                default:
                                    return 0;
                            }
                        });

                        // Clear and repopulate container
                        container.innerHTML = '';
                        sortedCards.forEach(card => {
                            const cardWrapper = document.createElement('div');
                            cardWrapper.className = 'col-md-3 mt-2';
                            cardWrapper.appendChild(card);
                            container.appendChild(cardWrapper);
                        });

                        // Update dropdown button text
                        const sortDropdown = document.getElementById('sortDropdown');
                        if (sortDropdown) {
                            const sortText = button.textContent;
                            sortDropdown.innerHTML = `<i class="fa-solid fa-arrow-down-wide-short"></i> ${sortText}`;
                        }
                    });
                });

                // 5) Filter functionality
                const categorySubmenu = document.getElementById('categorySubmenu');

                // Get unique equipment types from cards
                function getUniqueEquipmentTypes() {
                    const types = new Set();
                    equipmentCards.forEach(card => {
                        const categoryElement = card.querySelector('.text-danger.fw-bold');
                        if (categoryElement) {
                            types.add(categoryElement.textContent.trim());
                        }
                    });
                    return Array.from(types).sort();
                }

                // Populate category submenu dynamically
                function populateCategorySubmenu() {
                    const equipmentTypes = getUniqueEquipmentTypes();
                    categorySubmenu.innerHTML = '';

                    equipmentTypes.forEach(type => {
                        const li = document.createElement('li');
                        const a = document.createElement('a');
                        a.href = '#';
                        a.className = 'dropdown-item category-filter';
                        a.textContent = type;
                        a.dataset.category = type;
                        li.appendChild(a);
                        categorySubmenu.appendChild(li);
                    });
                }

                // Filter equipment by category
                function filterByCategory(selectedCategory) {
                    equipmentCards.forEach(card => {
                        const categoryElement = card.querySelector('.text-danger.fw-bold');
                        const cardCategory = categoryElement ? categoryElement.textContent.trim() : '';
                        const cardContainer = card.parentElement;

                        if (cardCategory === selectedCategory) {
                            cardContainer.style.display = 'block';
                            // Add a subtle highlight effect
                            cardContainer.style.transform = 'scale(1.02)';
                            setTimeout(() => {
                                cardContainer.style.transform = 'scale(1)';
                            }, 200);
                        } else {
                            cardContainer.style.display = 'none';
                        }
                    });
                }

                // Show all equipment
                function showAllEquipment() {
                    equipmentCards.forEach(card => {
                        card.parentElement.style.display = 'block';
                        card.parentElement.style.transform = 'scale(1)';
                    });
                }

                // Initialize submenu
                populateCategorySubmenu();

                // Handle filter options
                document.addEventListener('click', function(e) {
                    if (e.target.classList.contains('filter-option')) {
                        e.preventDefault();
                        const filterType = e.target.dataset.filter;

                        if (filterType === 'all') {
                            showAllEquipment();
                        }
                    }

                    if (e.target.classList.contains('category-filter')) {
                        e.preventDefault();
                        const selectedCategory = e.target.dataset.category;
                        filterByCategory(selectedCategory);

                        // Close the dropdown
                        const dropdownButton = document.getElementById('filterDropdown');
                        const dropdown = bootstrap.Dropdown.getInstance(dropdownButton);
                        if (dropdown) {
                            dropdown.hide();
                        }
                    }
                });

                // Handle dropdown submenu hover (for better UX)
                const submenuItem = document.querySelector('.dropdown-submenu');
                let submenuTimer;

                submenuItem.addEventListener('mouseenter', function() {
                    clearTimeout(submenuTimer);
                    this.querySelector('.dropdown-menu').style.display = 'block';
                });

                submenuItem.addEventListener('mouseleave', function() {
                    const submenu = this.querySelector('.dropdown-menu');
                    submenuTimer = setTimeout(() => {
                        submenu.style.display = 'none';
                    }, 100);
                });

                // Keep submenu open when hovering over it
                categorySubmenu.addEventListener('mouseenter', function() {
                    clearTimeout(submenuTimer);
                });

                categorySubmenu.addEventListener('mouseleave', function() {
                    submenuTimer = setTimeout(() => {
                        this.style.display = 'none';
                    }, 100);
                });

                // 6) Archive Functionality
                // Handle archive all button
                const archiveAllBtn = document.getElementById('archive-all-btn');
                if (archiveAllBtn) {
                    archiveAllBtn.addEventListener('click', function() {
                        const checkboxes = document.querySelectorAll('.equipment-checkbox:checked');

                        if (checkboxes.length === 0) {
                            alert('Please select at least one equipment to archive.');
                            return;
                        }

                        const equipmentIds = Array.from(checkboxes)
                            .map(checkbox => checkbox.value)
                            .filter(id => id && id !== 'undefined' && id !== 'null');

                        if (equipmentIds.length === 0) {
                            alert('No valid equipment IDs selected.');
                            return;
                        }

                        if (confirm(`Are you sure you want to archive ${equipmentIds.length} equipment item(s)?`)) {
                            archiveEquipment(equipmentIds);
                        }
                    });
                }

                // Handle archive modal
                const archiveModal = document.getElementById('archiveModal');
                if (archiveModal) {
                    archiveModal.addEventListener('show.bs.modal', function() {
                        loadArchivedEquipment();
                    });
                }

                // Handle restore button
                const restoreArchivedBtn = document.getElementById('restore-archived-btn');
                if (restoreArchivedBtn) {
                    restoreArchivedBtn.addEventListener('click', function() {
                        const checkboxes = document.querySelectorAll('#archive-modal-body .equipment-checkbox:checked');
                        const equipmentIds = Array.from(checkboxes).map(checkbox => checkbox.value);

                        if (equipmentIds.length === 0) {
                            alert('Please select at least one equipment to restore.');
                            return;
                        }

                        if (confirm(`Are you sure you want to restore ${equipmentIds.length} equipment item(s)?`)) {
                            restoreArchivedEquipment(equipmentIds);
                        }
                    });
                }

                // Handle delete button
                const deleteArchivedBtn = document.getElementById('delete-archived-btn');
                if (deleteArchivedBtn) {
                    deleteArchivedBtn.addEventListener('click', function() {
                        const checkboxes = document.querySelectorAll('#archive-modal-body .equipment-checkbox:checked');
                        const equipmentIds = Array.from(checkboxes).map(checkbox => checkbox.value);
                        if (equipmentIds.length === 0) {
                            alert('Please select at least one equipment to delete.');
                            return;
                        }
                        if (confirm(`Are you sure you want to permanently delete ${equipmentIds.length} equipment item(s)? This action cannot be undone.`)) {
                            deleteArchivedEquipment(equipmentIds);
                        }
                    });
                }

                // Handle select all archived button
                const selectAllArchivedBtn = document.getElementById('select-all-archived-btn');
                if (selectAllArchivedBtn) {
                    selectAllArchivedBtn.addEventListener('click', function() {
                        const checkboxes = document.querySelectorAll('#archive-modal-body .equipment-checkbox');
                        const isSelectAll = this.textContent.trim() === 'Select All';

                        checkboxes.forEach(checkbox => {
                            checkbox.checked = isSelectAll;
                        });

                        // Update button text and state
                        this.textContent = isSelectAll ? 'Cancel' : 'Select All';
                        this.classList.toggle('active', isSelectAll);
                    });
                }

                // 7) Edit Equipment Functionality
                const editButtons = document.querySelectorAll('.edit-equipment-btn');
                // Add click event to each edit button
                editButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const equipmentId = this.getAttribute('data-equipment-id');
                        console.log('Fetching equipment ID:', equipmentId);

                        // Fetch equipment details
                        fetch(`editProfiling.php?id=${equipmentId}`)
                            .then(response => {
                                console.log('Response status:', response.status);
                                if (!response.ok) {
                                    throw new Error(`HTTP error! Status: ${response.status}`);
                                }
                                return response.text();
                            })
                            .then(text => {
                                console.log('Raw response:', text);
                                try {
                                    return JSON.parse(text);
                                } catch (e) {
                                    console.error('JSON parse error:', e);
                                    throw new Error('Invalid JSON response: ' + text);
                                }
                            })
                            .then(data => {
                                console.log('Parsed data:', data);
                                if (data.status === 'success') {
                                    populateEditForm(data.data);
                                } else {
                                    alert('Error: ' + data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error fetching equipment details:', error);
                                alert('Failed to load equipment details. Please try again. Error: ' + error.message);
                            });
                    });
                });

                // 8) Function to populate the edit form
                function populateEditForm(equipment) {
                    console.log('Populating form with:', equipment);

                    const equipmentTypeSelect = document.querySelector('#edit select[name="equipmentType"]');
                    const modelInput = document.querySelector('#edit input[name="model"]');
                    const yearInput = document.querySelector('#edit input[name="year"]');
                    const capacityInput = document.querySelector('#edit input[name="capacity"]');
                    const bodyIdInput = document.querySelector('#edit input[name="bodyId"]');
                    const operatorSelect = document.querySelector('#edit select[name="operator"]');
                    const remarksTextarea = document.querySelector('#edit textarea[name="remarks"]');

                    const lastOperatingHoursInput = document.querySelector('#edit input[name="lastOperatingHours"]');
                    const maintenanceIntervalInput = document.querySelector('#edit input[name="maintenanceInterval"]');
                    const transmissionTypeInput = document.querySelector('#edit input[name="transmissionType"]');
                    const engineTypeInput = document.querySelector('#edit input[name="engineType"]');
                    const lastPMSInput = document.querySelector('#edit input[name="lastPMS"]');
                    const fuelTypeSelect = document.getElementById('editFuelTypeInput');


                    let serialInput = document.querySelector('#edit input[name="serial"]');
                    if (!serialInput) {
                        serialInput = document.querySelector('#serialNo');
                    }
                    if (!serialInput) {
                        serialInput = document.querySelector('input[name="serial"]');
                    }

                    let fuelTankCapacityInput = document.querySelector('#edit input[name="fuelTankCapacity"]');
                    if (!fuelTankCapacityInput) {
                        fuelTankCapacityInput = document.querySelector('#fuelTankCapacityInput');
                    }
                    if (!fuelTankCapacityInput) {
                        fuelTankCapacityInput = document.querySelector('input[name="fuelTankCapacity"]');
                    }

                    console.log('Serial input found:', !!serialInput, serialInput ? serialInput.id : 'N/A');
                    console.log('Fuel tank capacity input found:', !!fuelTankCapacityInput, fuelTankCapacityInput ? fuelTankCapacityInput.id : 'N/A');

                    console.log('Engine serial number from data:', equipment.engine_serial_num);
                    console.log('Fuel tank capacity from data:', equipment.fuel_tank_capacity);

                    // Add hidden input for equipment ID
                    let equipmentIdInput = document.querySelector('#edit input[name="equipment_id"]');
                    if (!equipmentIdInput) {
                        equipmentIdInput = document.createElement('input');
                        equipmentIdInput.type = 'hidden';
                        equipmentIdInput.name = 'equipment_id';
                        document.querySelector('#edit .modal-content').appendChild(equipmentIdInput);
                    }

                    // Set equipment ID value
                    equipmentIdInput.value = equipment.equipment_id;

                    // Set equipment type
                    if (equipmentTypeSelect) {
                        for (let i = 0; i < equipmentTypeSelect.options.length; i++) {
                            if (equipmentTypeSelect.options[i].value == equipment.equip_type_id) {
                                equipmentTypeSelect.selectedIndex = i;
                                break;
                            }
                        }
                    }

                    // Set existing form values
                    if (modelInput) modelInput.value = equipment.model || '';
                    if (yearInput) yearInput.value = equipment.equip_year || '';

                    // Set serial number with more detailed debugging
                    if (serialInput) {
                        serialInput.value = equipment.engine_serial_num || '';
                        console.log('Set serial input value to:', serialInput.value);
                        console.log('Serial input element:', serialInput);

                        setTimeout(() => {
                            serialInput.value = equipment.engine_serial_num || '';
                            console.log('Forced serial input value after timeout:', serialInput.value);
                        }, 100);
                    } else {
                        console.error('Serial input element not found in the DOM');
                    }

                    if (capacityInput) {
                        // Split capacity into value and unit
                        const capacityMatch = (equipment.capacity || '').match(/^([\d.]+)\s*(.+)$/);
                        if (capacityMatch) {
                            const [_, value, unit] = capacityMatch;

                            // Set the numeric value
                            capacityInput.value = parseFloat(value);

                            // Find and set the unit in the dropdown
                            const unitSelect = document.querySelector('#edit select[name="capacityUnit"]');
                            if (unitSelect) {
                                Array.from(unitSelect.options).forEach((option, index) => {
                                    if (option.value.toLowerCase() === unit.toLowerCase()) {
                                        unitSelect.selectedIndex = index;
                                    }
                                });
                            }

                            console.log('Set capacity value:', value, 'and unit:', unit);
                        } else {
                            capacityInput.value = '';
                            console.log('No valid capacity format found');
                        }
                    }

                    if (bodyIdInput) bodyIdInput.value = equipment.body_id || '';
                    if (remarksTextarea) remarksTextarea.value = equipment.equip_remarks || '';

                    // Set fuel tank capacity with more detailed debugging
                    if (fuelTankCapacityInput) {
                        fuelTankCapacityInput.value = equipment.fuel_tank_capacity || '';
                        console.log('Set fuel tank capacity value to:', fuelTankCapacityInput.value);
                        console.log('Fuel tank capacity element:', fuelTankCapacityInput);

                        // Force the value to be visible
                        setTimeout(() => {
                            fuelTankCapacityInput.value = equipment.fuel_tank_capacity || '';
                            console.log('Forced fuel tank capacity value after timeout:', fuelTankCapacityInput.value);
                        }, 100);
                    } else {
                        console.error('Fuel tank capacity input element not found in the DOM');
                    }

                    if (lastOperatingHoursInput) lastOperatingHoursInput.value = equipment.last_operating_hours || '';
                    if (maintenanceIntervalInput) maintenanceIntervalInput.value = equipment.maintenance_interval || '';
                    if (transmissionTypeInput) transmissionTypeInput.value = equipment.transmission_type || '';
                    if (engineTypeInput) engineTypeInput.value = equipment.engine_type || '';
                    if (fuelTypeInput) fuelTypeInput.value = equipment.fuel_type || '';

                    // Format and set the last PMS date if it exists
                    if (lastPMSInput && equipment.last_pms_date) {
                        lastPMSInput.value = equipment.last_pms_date;
                    }

                    // Set operator if exists
                    if (operatorSelect && equipment.operator_id) {
                        Array.from(operatorSelect.options).forEach(option => {
                            if (option.value == equipment.operator_id) {
                                option.selected = true;
                            }
                        });
                    }

                    // Add a check to see if the values are actually set after a short delay
                    setTimeout(() => {
                        if (serialInput) {
                            console.log('Serial input value after delay:', serialInput.value);
                        }
                        if (fuelTankCapacityInput) {
                            console.log('Fuel tank capacity value after delay:', fuelTankCapacityInput.value);
                        }
                    }, 500);
                }

                // Handle form submission for edit
                const editForm = document.querySelector('#edit .modal-content');
                if (editForm) {
                    const saveButton = editForm.querySelector('button.btn-success');
                    if (saveButton) {
                        saveButton.addEventListener('click', function() {
                            const formData = new FormData();

                            // Add equipment ID
                            const equipmentIdInput = document.querySelector('#edit input[name="equipment_id"]');
                            if (equipmentIdInput) {
                                formData.append('equipment_id', equipmentIdInput.value);
                                console.log('Adding equipment_id:', equipmentIdInput.value);
                            } else {
                                console.error('Equipment ID input not found');
                                alert('Error: Equipment ID not found');
                                return;
                            }

                            // Add existing form fields
                            const equipmentTypeSelect = document.querySelector('#edit select[name="equipmentType"]');
                            if (equipmentTypeSelect && equipmentTypeSelect.selectedIndex >= 0) {
                                formData.append('equipmentType', equipmentTypeSelect.options[equipmentTypeSelect.selectedIndex].value);
                                console.log('Adding equipmentType:', equipmentTypeSelect.options[equipmentTypeSelect.selectedIndex].value);
                            }

                            const modelInput = document.querySelector('#edit input[name="model"]');
                            if (modelInput) {
                                formData.append('model', modelInput.value);
                                console.log('Adding model:', modelInput.value);
                            }

                            const yearInput = document.querySelector('#edit input[name="year"]');
                            if (yearInput) {
                                formData.append('year', yearInput.value);
                                console.log('Adding year:', yearInput.value);
                            }

                            let serialInput = document.querySelector('#edit input[name="serial"]');
                            if (!serialInput) {
                                serialInput = document.querySelector('#serialNo');
                            }
                            if (!serialInput) {
                                serialInput = document.querySelector('input[name="serial"]');
                            }

                            if (serialInput) {
                                formData.append('serial', serialInput.value);
                                console.log('Adding serial:', serialInput.value);
                            } else {
                                console.warn('Serial input not found');
                            }

                            const capacityInput = document.querySelector('#edit input[name="capacity"]');
                            const capacityUnitSelect = document.querySelector('#edit select[name="capacityUnit"]');
                            if (capacityInput && capacityUnitSelect) {
                                const value = parseFloat(capacityInput.value);
                                const unit = capacityUnitSelect.value;
                                if (!isNaN(value)) {
                                    const combinedCapacity = `${value.toFixed(2)} ${unit}`;
                                    formData.append('capacity', combinedCapacity);
                                    console.log('Adding capacity:', combinedCapacity);
                                }
                            }

                            const bodyIdInput = document.querySelector('#edit input[name="bodyId"]');
                            if (bodyIdInput) {
                                formData.append('bodyId', bodyIdInput.value);
                                console.log('Adding bodyId:', bodyIdInput.value);
                            }

                            const operatorSelect = document.querySelector('#edit select[name="operator"]');
                            if (operatorSelect && operatorSelect.selectedIndex >= 0) {
                                formData.append('operator', operatorSelect.options[operatorSelect.selectedIndex].value);
                                console.log('Adding operator:', operatorSelect.options[operatorSelect.selectedIndex].value);
                            }

                            const remarksTextarea = document.querySelector('#edit textarea[name="remarks"]');
                            if (remarksTextarea) {
                                formData.append('remarks', remarksTextarea.value);
                                console.log('Adding remarks:', remarksTextarea.value);
                            }

                            let fuelTankCapacityInput = document.querySelector('#edit input[name="fuelTankCapacity"]');
                            if (!fuelTankCapacityInput) {
                                fuelTankCapacityInput = document.querySelector('#fuelTankCapacityInput');
                            }
                            if (!fuelTankCapacityInput) {
                                fuelTankCapacityInput = document.querySelector('input[name="fuelTankCapacity"]');
                            }

                            if (fuelTankCapacityInput) {
                                formData.append('fuelTankCapacity', fuelTankCapacityInput.value);
                                console.log('Adding fuelTankCapacity:', fuelTankCapacityInput.value);
                            } else {
                                console.warn('Fuel tank capacity input not found');
                            }

                            const lastOperatingHoursInput = document.querySelector('#edit input[name="lastOperatingHours"]');
                            if (lastOperatingHoursInput) {
                                formData.append('lastOperatingHours', lastOperatingHoursInput.value);
                                console.log('Adding lastOperatingHours:', lastOperatingHoursInput.value);
                            }

                            const maintenanceIntervalInput = document.querySelector('#edit input[name="maintenanceInterval"]');
                            if (maintenanceIntervalInput) {
                                formData.append('maintenanceInterval', maintenanceIntervalInput.value);
                                console.log('Adding maintenanceInterval:', maintenanceIntervalInput.value);
                            }

                            const transmissionTypeInput = document.querySelector('#edit input[name="transmissionType"]');
                            if (transmissionTypeInput) {
                                formData.append('transmissionType', transmissionTypeInput.value);
                                console.log('Adding transmissionType:', transmissionTypeInput.value);
                            }

                            const engineTypeInput = document.querySelector('#edit input[name="engineType"]');
                            if (engineTypeInput) {
                                formData.append('engineType', engineTypeInput.value);
                                console.log('Adding engineType:', engineTypeInput.value);
                            }

                            const fuelTypeInput = document.querySelector('#edit input[name="fuelType"]');
                            if (fuelTypeInput) {
                                formData.append('fuelType', fuelTypeInput.value);
                                console.log('Adding fuelType:', fuelTypeInput.value);
                            }

                            const lastPMSInput = document.querySelector('#edit input[name="lastPMS"]');
                            if (lastPMSInput) {
                                formData.append('lastPMS', lastPMSInput.value);
                                console.log('Adding lastPMS:', lastPMSInput.value);
                            }

                            console.log('Submitting form data...');

                            // Submit form data
                            fetch('editProfiling.php', {
                                    method: 'POST',
                                    body: formData,
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                })
                                .then(response => {
                                    console.log('Response status:', response.status);
                                    return response.text();
                                })
                                .then(text => {
                                    console.log('Raw response:', text);
                                    try {
                                        return JSON.parse(text);
                                    } catch (e) {
                                        console.error('JSON parse error:', e);
                                        throw new Error('Invalid JSON response: ' + text);
                                    }
                                })
                                .then(data => {
                                    console.log('Parsed data:', data);
                                    if (data.status === 'success') {
                                        alert('Equipment updated successfully!');
                                        const editModal = bootstrap.Modal.getInstance(document.getElementById('edit'));
                                        if (editModal) {
                                            editModal.hide();
                                        }
                                        window.location.reload();
                                    } else {
                                        alert('Error: ' + data.message);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error updating equipment:', error);
                                    alert('Failed to update equipment. Please try again. Error: ' + error.message);
                                });
                        });
                    }
                }

            });

            // Function to archive equipment
            function archiveEquipment(equipmentIds) {
                fetch('./archiveProfiling.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            equipment_ids: equipmentIds
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Update archive count immediately
                            updateArchiveCount();

                            const toast = document.createElement('div');
                            toast.className = 'alert alert-success';
                            toast.textContent = 'Equipment archived successfully!';
                            document.body.appendChild(toast);

                            setTimeout(() => {
                                toast.style.opacity = '0';
                                toast.style.transition = 'opacity 0.5s ease';
                                setTimeout(() => {
                                    toast.remove();
                                    window.location.reload();
                                }, 500);
                            }, 1500);
                        } else {
                            alert('Error archiving equipment: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while archiving equipment.');
                    });
            }

            function updateArchiveCount() {
                fetch('updateStatusProfiling.php?action=getCount')
                    .then(response => response.json())
                    .then(data => {
                        const archiveCount = document.getElementById('archiveCount');
                        if (archiveCount) {
                            archiveCount.textContent = data.count;
                            // Always show the badge, even for zero count
                            archiveCount.style.display = 'inline-block';
                            console.log('Archive count updated:', data.count);
                        }
                    })
                    .catch(error => console.error('Error updating archive count:', error));
            }

            // Function to load archived equipment
            function loadArchivedEquipment() {
                const modalBody = document.getElementById('archive-modal-body');
                if (!modalBody) return;
                modalBody.innerHTML = `
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p>Loading archived equipment...</p>
                    </div>
                `;
                fetch('./getArchivedEquipment.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.length === 0) {
                            modalBody.innerHTML = '<div class="col-12 text-center"><p>No archived equipment found.</p></div>';
                            return;
                        }
                        modalBody.innerHTML = '';
                        data.forEach(equipment => {
                            const equipmentCard = document.createElement('div');
                            equipmentCard.className = 'col-md-3 mt-2';
                            equipmentCard.innerHTML = `
                                <div class="card archived-card-stats border-success">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="d-flex justify-content-between">
                                                <input type="checkbox" class="equipment-checkbox" value="${equipment.equipment_id}">
                                            </div>
                                        </div>
                                        <div class="content text-center">
                                            <p class="fw-bold mt-3">${equipment.custom_equip_id || equipment.equipment_id}</p>
                                            <p class="text-danger fw-bold">${equipment.equip_type_name || 'N/A'}</p>
                                            <p class="text-muted fw-bold">Model: ${equipment.model || 'N/A'}</p>
                                            <p class="text-muted fw-bold">Status: ${equipment.equip_status || 'N/A'}</p>
                                        </div>
                                    </div>
                                </div>
                            `;
                            modalBody.appendChild(equipmentCard);
                        });
                        const archiveCountBadge = document.getElementById('archiveCount');
                        if (archiveCountBadge) {
                            archiveCountBadge.textContent = data.length;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        modalBody.innerHTML = '<div class="col-12 text-center"><p>Error loading archived equipment.</p></div>';
                    });
            }

            // Function to restore equipment
            function restoreArchivedEquipment(equipmentIds) {
                fetch('./restoreProfiling.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            equipment_ids: equipmentIds,
                            preserve_date: true
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'success' || data.status === 'partial') {
                            const archiveModal = bootstrap.Modal.getInstance(document.getElementById('archiveModal'));
                            if (archiveModal) {
                                archiveModal.hide();
                            }

                            const toast = document.createElement('div');
                            toast.className = 'alert alert-success';
                            toast.textContent = 'Equipment restored successfully!';
                            document.body.appendChild(toast);

                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            alert('Error restoring equipment: ' + (data.messages ? data.messages.join('\n') : 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Fetch Error:', error);
                        alert('An error occurred while restoring equipment: ' + error.message);
                    });
            }

            // Function to delete archived equipment
            function deleteArchivedEquipment(equipmentIds) {
                fetch('./deleteProfiling.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            equipment_ids: equipmentIds
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert('Equipment deleted successfully!');
                            // Reload the entire page to reflect the changes
                            window.location.reload();
                        } else {
                            alert('Error deleting equipment: ' + data.messages.join('\n'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting equipment.');
                    });
            }

            // 9) Handle status updates
            document.querySelectorAll('.status-option').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const status = this.dataset.status;
                    const card = this.closest('.card');
                    const equipmentId = card.querySelector('.fw-bold.mt-3').textContent.trim();

                    updateEquipmentStatus(equipmentId, status);
                });
            });

            // Function to update equipment status
            function updateEquipmentStatus(equipmentId, status) {
                // Set deployment status based on whether status is "Deployed"
                const deploymentStatus = status.toLowerCase() === 'active' ? 'Deployed' : 'Undeployed';

                fetch('updateStatusProfiling.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            equipment_id: equipmentId,
                            status: status,
                            deployment_status: deploymentStatus
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            const toast = document.createElement('div');
                            toast.className = 'alert alert-success';
                            toast.textContent = `Status updated to ${status}`;
                            document.body.appendChild(toast);

                            setTimeout(() => {
                                toast.style.opacity = '0';
                                toast.style.transition = 'opacity 0.5s ease';
                                setTimeout(() => {
                                    toast.remove();
                                    window.location.reload();
                                }, 500);
                            }, 1500);
                        } else {
                            alert('Error updating status: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to update status: ' + error.message);
                    });
            }

            // Update the event listeners for status options
            document.querySelectorAll('.status-option').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const status = this.dataset.status;
                    const card = this.closest('.card');
                    const equipmentId = card.querySelector('.fw-bold.mt-3').textContent.trim();

                    // Show loading indicator (optional)
                    const statusButton = card.querySelector('.btn.dropdown-toggle');
                    if (statusButton) {
                        statusButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
                    }

                    updateEquipmentStatus(equipmentId, status);
                });
            });

            // 10) Batch upload functionality
            document.addEventListener('DOMContentLoaded', function() {
                const batchFileInput = document.getElementById('batchExcelFileInput');
                const selectedFilesPreview = document.getElementById('selectedFilesPreview');
                const filesList = document.getElementById('filesList');
                const batchUploadSubmitBtn = document.getElementById('batchUploadSubmitBtn');
                const batchUploadForm = document.getElementById('batchUploadForm');
                const progressBar = document.getElementById('batchUploadProgressBar');
                const feedbackMsg = document.getElementById('batchUploadFeedbackMsg');

                // Handle file selection change
                batchFileInput.addEventListener('change', function(e) {
                    const files = e.target.files;

                    if (files.length > 0) {
                        displaySelectedFiles(files);
                        selectedFilesPreview.classList.remove('d-none');
                        batchUploadSubmitBtn.disabled = false;
                    } else {
                        selectedFilesPreview.classList.add('d-none');
                        batchUploadSubmitBtn.disabled = true;
                    }
                });

                // Display selected files
                function displaySelectedFiles(files) {
                    filesList.innerHTML = '';

                    for (let i = 0; i < files.length; i++) {
                        const file = files[i];
                        const fileItem = document.createElement('div');
                        fileItem.className = 'border-bottom py-2 d-flex justify-content-between align-items-center';

                        const fileInfo = document.createElement('div');
                        fileInfo.innerHTML = `
                            <i class="fas fa-file-excel text-success me-2"></i>
                            <span class="fw-medium">${file.name}</span>
                            <small class="text-muted ms-2">(${formatFileSize(file.size)})</small>
                        `;

                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'btn btn-sm btn-outline-danger';
                        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                        removeBtn.onclick = function() {
                            removeFile(i);
                        };

                        fileItem.appendChild(fileInfo);
                        fileItem.appendChild(removeBtn);
                        filesList.appendChild(fileItem);
                    }
                }

                // Remove file from selection
                function removeFile(index) {
                    const dt = new DataTransfer();
                    const files = batchFileInput.files;

                    for (let i = 0; i < files.length; i++) {
                        if (i !== index) {
                            dt.items.add(files[i]);
                        }
                    }

                    batchFileInput.files = dt.files;

                    if (dt.files.length > 0) {
                        displaySelectedFiles(dt.files);
                    } else {
                        selectedFilesPreview.classList.add('d-none');
                        batchUploadSubmitBtn.disabled = true;
                    }
                }

                // Format file size
                function formatFileSize(bytes) {
                    if (bytes === 0) return '0 Bytes';
                    const k = 1024;
                    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                }

                // Handle form submission
                batchUploadSubmitBtn.addEventListener('click', function() {
                    const files = batchFileInput.files;

                    if (files.length === 0) {
                        showFeedback('Please select at least one file to upload.', 'danger');
                        return;
                    }

                    // Create FormData object
                    const formData = new FormData();

                    // Append all selected files
                    for (let i = 0; i < files.length; i++) {
                        formData.append('excelFiles[]', files[i]);
                    }

                    // Show progress bar
                    progressBar.classList.remove('d-none');
                    batchUploadSubmitBtn.disabled = true;
                    batchUploadSubmitBtn.textContent = 'Uploading...';

                    // Upload files
                    fetch('batchUploadProfiling.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            progressBar.classList.add('d-none');

                            if (data.status === 'success') {
                                showFeedback(data.message, 'success');

                                // Show processed files summary
                                if (data.processed_files && data.processed_files.length > 0) {
                                    let summaryHtml = '<div class="mt-2"><strong>Files Processed:</strong><ul class="mb-0">';
                                    data.processed_files.forEach(file => {
                                        summaryHtml += `<li>${file.name}: ${file.rows} records</li>`;
                                    });
                                    summaryHtml += '</ul></div>';
                                    feedbackMsg.innerHTML += summaryHtml;
                                }

                                // Reset form after successful upload
                                setTimeout(() => {
                                    batchUploadForm.reset();
                                    selectedFilesPreview.classList.add('d-none');
                                    feedbackMsg.classList.add('d-none');

                                    // Close modal and refresh page/table
                                    const modal = bootstrap.Modal.getInstance(document.getElementById('batchUploadModal'));
                                    modal.hide();

                                    // Refresh the page or update the equipment table
                                    location.reload();
                                }, 3000);

                            } else {
                                showFeedback(data.message, 'danger');

                                // Show detailed errors if available
                                if (data.errors && data.errors.length > 0) {
                                    let errorHtml = '<div class="mt-2"><strong>Errors:</strong><ul class="mb-0">';
                                    data.errors.forEach(error => {
                                        errorHtml += `<li class="text-danger">${error}</li>`;
                                    });
                                    errorHtml += '</ul></div>';
                                    feedbackMsg.innerHTML += errorHtml;
                                }

                                // Show processed files summary even if there were errors
                                if (data.processed_files && data.processed_files.length > 0) {
                                    let summaryHtml = '<div class="mt-2"><strong>Successfully Processed Files:</strong><ul class="mb-0">';
                                    data.processed_files.forEach(file => {
                                        summaryHtml += `<li class="text-success">${file.name}: ${file.rows} records</li>`;
                                    });
                                    summaryHtml += '</ul></div>';
                                    feedbackMsg.innerHTML += summaryHtml;
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            progressBar.classList.add('d-none');
                            showFeedback('An error occurred while uploading files. Please try again.', 'danger');
                        })
                        .finally(() => {
                            batchUploadSubmitBtn.disabled = false;
                            batchUploadSubmitBtn.textContent = 'Upload';
                        });
                });

                // Show feedback message
                function showFeedback(message, type) {
                    feedbackMsg.className = `alert alert-${type}`;
                    feedbackMsg.textContent = message;
                    feedbackMsg.classList.remove('d-none');
                }

                // Reset form when modal is closed
                document.getElementById('batchUploadModal').addEventListener('hidden.bs.modal', function() {
                    batchUploadForm.reset();
                    selectedFilesPreview.classList.add('d-none');
                    feedbackMsg.classList.add('d-none');
                    progressBar.classList.add('d-none');
                    batchUploadSubmitBtn.disabled = false;
                    batchUploadSubmitBtn.textContent = 'Upload';
                });
            });

            // Equipment Type Modal Functionality
            document.addEventListener('DOMContentLoaded', function() {
                const equipmentTypeSelect = document.getElementById('equipmentTypeInput');
                const addEquipmentTypeModal = new bootstrap.Modal(document.getElementById('addEquipmentTypeModal'));
                const addEquipmentModal = document.getElementById('staticBackdrop');
                const saveEquipmentTypeBtn = document.getElementById('saveEquipmentTypeBtn');
                const editEquipmentTypeSelect = document.getElementById('editEquipmentTypeInput');
                const editEquipmentModal = document.getElementById('edit');

                if (editEquipmentTypeSelect) {
                    editEquipmentTypeSelect.addEventListener('change', function() {
                        if (this.value === '') {
                            // Hide the edit equipment modal
                            const editModalInstance = bootstrap.Modal.getInstance(editEquipmentModal);
                            if (editModalInstance) {
                                editModalInstance.hide();
                            }
                            // Show the add equipment type modal
                            addEquipmentTypeModal.show();
                        }
                    });
                }

                // When add equipment type modal closes, show the correct modal again
                document.getElementById('addEquipmentTypeModal').addEventListener('hidden.bs.modal', function() {
                    // If the edit modal was last open, show it again
                    if (editEquipmentTypeSelect && editEquipmentTypeSelect.closest('.modal.show')) {
                        const editModalInstance = new bootstrap.Modal(editEquipmentModal);
                        editModalInstance.show();
                    } else {
                        const addModalInstance = new bootstrap.Modal(addEquipmentModal);
                        addModalInstance.show();
                    }
                });

                if (equipmentTypeSelect) {
                    equipmentTypeSelect.addEventListener('change', function() {
                        if (this.value === '') {
                            // Store the bootstrap modal instance
                            const equipmentModal = bootstrap.Modal.getInstance(addEquipmentModal);
                            if (equipmentModal) {
                                // Hide the add equipment modal
                                equipmentModal.hide();
                                // Show the add equipment type modal
                                addEquipmentTypeModal.show();
                            }
                        }
                    });
                }

                if (saveEquipmentTypeBtn) {
                    saveEquipmentTypeBtn.addEventListener('click', function() {
                        const form = document.getElementById('addEquipmentTypeForm');
                        const formData = new FormData(form);

                        // Validate form
                        if (!form.checkValidity()) {
                            form.reportValidity();
                            return;
                        }

                        // Send request to add new equipment type
                        fetch('createProfiling.php', {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    // Add the new option to the equipment type select
                                    const equipmentTypeSelect = document.getElementById('equipmentTypeInput');
                                    const newOption = new Option(formData.get('equipmentTypeName'), data.equipment_type_id);

                                    // Insert the new option after the "Add another equipment" and "Choose equipment type" options
                                    equipmentTypeSelect.insertBefore(newOption, equipmentTypeSelect.options[2]);

                                    // Select the new option
                                    equipmentTypeSelect.value = data.equipment_type_id;

                                    // Show success message
                                    const toast = document.createElement('div');
                                    toast.className = 'alert alert-success position-fixed top-50 start-50 translate-middle';
                                    toast.style.zIndex = '1060';
                                    toast.textContent = 'New equipment type added successfully!';
                                    document.body.appendChild(toast);

                                    setTimeout(() => {
                                        toast.remove();
                                    }, 3000);

                                    // Close the modal
                                    const modal = bootstrap.Modal.getInstance(document.getElementById('addEquipmentTypeModal'));
                                    if (modal) {
                                        modal.hide();
                                    }

                                    // Show the main add equipment modal again
                                    const equipmentModal = new bootstrap.Modal(document.getElementById('staticBackdrop'));
                                    equipmentModal.show();
                                } else {
                                    alert('Error adding new equipment type: ' + data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred while adding the new equipment type.');
                            });
                    });
                }

                // Handle modal closing
                document.getElementById('addEquipmentTypeModal').addEventListener('hidden.bs.modal', function() {});
            });

            // 8) Add Spare Parts Modal Functionality
            document.addEventListener('DOMContentLoaded', function() {
                const addPartRow = document.getElementById('addPartRow');
                const partsTable = document.getElementById('partsTable').getElementsByTagName('tbody')[0];
                const savePartsBtn = document.getElementById('savePartsBtn');

                // Function to create new row
                function createNewRow() {
                    const newRow = partsTable.insertRow();
                    newRow.innerHTML = `
                        <td>
                            <select name="EquipmentIdInput" class="form-select" required>
                                <option value="">Select Equipment</option>
                                ${document.querySelector('#EquipmentIdInput').innerHTML}
                            </select>
                        </td>
                        <td><textarea class="form-control part-input" name="parts[]" rows="1" style="resize: vertical;" required></textarea></td>
                        <td><textarea class="form-control brand-input" name="brands[]" rows="1" style="resize: vertical;" required></textarea></td>
                        <td><textarea class="form-control num-input" name="nums[]" rows="1" style="resize: vertical;" required></textarea></td>
                        <td><input type="number" class="form-control quantity-input" name="quantities[]" min="1" required></td>
                        <td><textarea class="form-control specs-input" name="specs[]" rows="1" style="resize: vertical;"></textarea></td>
                        <td><textarea class="form-control remarks-input" name="remarks[]" rows="1" style="resize: vertical;"></textarea></td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm remove-part">
                                <i class="fas fa-trash-alt me-2"></i>
                            </button>
                        </td>
                    `;
                    return newRow;
                }

                // Add new row button
                if (addPartRow) {
                    addPartRow.addEventListener('click', () => {
                        createNewRow();
                    });
                }

                // Remove row functionality
                document.addEventListener('click', function(e) {
                    if (e.target.classList.contains('remove-part') || e.target.closest('.remove-part')) {
                        const row = e.target.closest('tr');
                        if (partsTable.rows.length > 1) {
                            row.remove();
                        } else {
                            // Clear fields instead of removing last row
                            row.querySelectorAll('input, textarea').forEach(input => input.value = '');
                            row.querySelector('select').selectedIndex = 0;
                        }
                    }
                });

                // Save parts functionality
                if (savePartsBtn) {
                    savePartsBtn.addEventListener('click', function() {
                        const rows = partsTable.getElementsByTagName('tr');
                        const parts = [];
                        let isValid = true;

                        // Validate and collect data from each row
                        Array.from(rows).forEach(row => {
                            const inputs = {
                                equipment_id: row.querySelector('select[name="EquipmentIdInput"]').value,
                                name: row.querySelector('.part-input').value.trim(),
                                brand: row.querySelector('.brand-input').value.trim(),
                                part_number: row.querySelector('.num-input').value.trim(),
                                quantity: row.querySelector('.quantity-input').value,
                                specs: row.querySelector('.specs-input').value.trim(),
                                remarks: row.querySelector('.remarks-input').value.trim()
                            };

                            // Basic validation
                            if (!inputs.equipment_id || !inputs.name || !inputs.brand ||
                                !inputs.part_number || !inputs.quantity) {
                                isValid = false;
                                return;
                            }

                            parts.push(inputs);
                        });

                        if (!isValid) {
                            alert('Please fill in all required fields');
                            return;
                        }

                        // Send data to server
                        fetch('addPartsInventory.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify(parts)
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    // Show success message
                                    const alert = document.createElement('div');
                                    alert.className = 'alert alert-success';
                                    alert.innerHTML = 'Parts added successfully!';
                                    document.body.appendChild(alert);

                                    // Close modal and reset form
                                    const modal = bootstrap.Modal.getInstance(document.getElementById('addSpareparts'));
                                    modal.hide();

                                    // Clear table except first row
                                    while (partsTable.rows.length > 1) {
                                        partsTable.deleteRow(1);
                                    }
                                    // Clear first row inputs
                                    const firstRow = partsTable.rows[0];
                                    firstRow.querySelectorAll('input, textarea').forEach(input => input.value = '');
                                    firstRow.querySelector('select').selectedIndex = 0;

                                    // Remove success message after 3 seconds
                                    setTimeout(() => alert.remove(), 3000);
                                } else {
                                    throw new Error(data.message || 'Error adding parts');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('Error adding parts: ' + error.message);
                            });
                    });
                }
            });

            // 11) Fuel Tank Capacity Input Handling
            document.addEventListener('shown.bs.modal', function(event) {
                const modal = event.target;
                const fuelInput = modal.querySelector('#fuelTankCapacityInput');
                if (fuelInput) {
                    fuelInput.addEventListener('input', function() {
                        this.value = this.value.replace(/\s*liters?$/i, '').trim();
                    });
                }
            });
        </script>
</body>

</html>