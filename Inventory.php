<?php
require('./database.php');
require('./readInventory.php');
require('./readArchivedInventory.php');
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Filter $inventoryRows based on search
$filteredInventoryRows = array_filter($inventoryRows, function ($row) use ($searchTerm) {
    if ($searchTerm === '') return true;
    return stripos($row['part_name'], $searchTerm) !== false ||
        stripos($row['brand_name'], $searchTerm) !== false ||
        stripos($row['part_number'], $searchTerm) !== false;
});
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Inventory</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap");

        body {
            background-color: #f0f2f5;
            font-family: "Poppins", sans-serif;
        }

        .offcanvas {
            --bs-offcanvas-width: 280px;
        }

        .navbar-toggler:focus {
            outline: none;
            box-shadow: none;
            border-style: none;
        }

        table {
            margin-top: 20px;
        }

        .table-container {
            background-color: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .table thead th {
            background-color: #f8f9fa;
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

        .form-control {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.10);
        }

        .archived-table-container {
            display: none;
            background-color: #f8f9fa;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-top: 20px;
            border: 1px solid #e9ecef;
        }

        .archived-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .archive-badge {
            position: relative;
            top: -8px;
            right: -5px;
            font-size: 9px;
        }

        .archive-indicator {
            color: #6c757d;
            padding: 4px 8px;
            border-radius: 4px;
            margin-right: 5px;
            background-color: #e9ecef;
            font-size: 0.8rem;
        }

        .restore-btn {
            cursor: pointer;
        }

        .restore-btn:hover {
            color: #0d6efd;
        }

        /* Custom Alert Styling */
        .custom-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
        }

        .custom-alert.show {
            opacity: 1;
            transform: translateY(0);
        }

        .custom-alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .custom-alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .custom-alert .close-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            color: inherit;
            cursor: pointer;
            margin-left: auto;
        }
    </style>
</head>

<body>
    <div class="container-fluid" style="margin-top: 70px;">
        <div id="customAlert" class="custom-alert">
            <span id="alertMessage"></span>
            <button class="close-btn" onclick="document.getElementById('customAlert').classList.remove('show')">&times;</button>
        </div>
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
                        Inventory
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
                                </li>
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
            <form class="search-container me-2" method="get" action="Inventory.php" style="display:inline-block;">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control search-input" name="search" placeholder="Search" value="<?= htmlspecialchars($searchTerm) ?>">
            </form>
            <div class="justify-content-end mb-3">
                <form id="archiveForm" action="archiveInventory.php" method="post" style="display: inline-block;">
                    <button class="btn btn-outline-secondary me-2" type="submit" id="archive-selected">
                        <i class="fa-solid fa-box-archive"></i>
                    </button>
                </form>
                <button class="btn btn-outline-secondary me-2" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-filter"></i>
                </button>
                <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                    <li><a href="#" class="dropdown-item filter-option" name="brand">Brand</a></li>
                    <li><a href="#" class="dropdown-item filter-option" name="status">Status</a></li>
                </ul>
                <button class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#archiveModal">
                    <i class="fa-solid fa-box-archive"></i>
                    <span class="badge bg-danger rounded-pill archive-badge" id="archiveCount">
                        <?= count($archivedInventoryRows) ?>
                    </span>
                </button>
                <!-- Add Inventory -->
                <button class="btn btn-add btn-success" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                    <i class="fas fa-plus"></i>
                </button>
                <div class="modal fade" id="archiveModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="archiveModal" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title fw-bold" id="archiveModal">Archived Products</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                                </button>
                            </div>
                            <form id="archiveActionsForm" action="restoreInventory.php" method="post">
                                <div class="modal-body">
                                    <div class="table-container">
                                        <div class="table-header">
                                            <div class="table-responsive overflow-auto" style="max-height: 400px;">
                                                <table class="table text-center">
                                                    <thead>
                                                        <tr>
                                                            <th><input type="checkbox" id="selectAllArchived" class="form-check-input"></th>
                                                            <th>Part Name</th>
                                                            <th>Brand</th>
                                                            <th>Part No.</th>
                                                            <th>Stock Quantity</th>
                                                            <th>Unit Price</th>
                                                            <th>Archived Date</th>
                                                            <th>Original Last Update</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (empty($archivedInventoryRows)): ?>
                                                            <tr>
                                                                <td colspan="8">No archived items found.</td>
                                                            </tr>
                                                        <?php else: ?>
                                                            <?php foreach ($archivedInventoryRows as $ar): ?>
                                                                <tr>
                                                                    <td>
                                                                        <input type="checkbox" class="form-check-input archive-checkbox" name="selectedArchiveIds[]" value="<?= htmlspecialchars($ar['archive_id']) ?>">
                                                                    </td>
                                                                    <td><?= htmlspecialchars($ar['part_id'] ?? '') ?></td>
                                                                    <td><?= htmlspecialchars($ar['brand_name'] ?? '') ?></td>
                                                                    <td><?= htmlspecialchars($ar['part_number'] ?? '') ?></td>
                                                                    <td><?= htmlspecialchars($ar['stock_quantity']) ?></td>
                                                                    <td>₱ <?= number_format($ar['unit_price'], 2) ?></td>
                                                                    <td><?= date('Y-m-d - h:i A', strtotime($ar['archive_date'])) ?></td>
                                                                    <td><?= date('Y-m-d - h:i A', strtotime($ar['last_update'])) ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-success me-2" name="action" value="restore">
                                        <i class="fa-solid fa-rotate-right"></i> Restore Selected
                                    </button>
                                    <button type="submit" class="btn btn-danger" name="action" value="delete">
                                        <i class="fa-solid fa-trash"></i> Delete Selected
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-success">
                                <h5 class="modal-title fw-bold text-white" id="staticBackdropLabel"> +Add Product</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                                </button>
                            </div>
                            <form action="createInventory.php" method="post">
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="PartName" class="form-label fw-bold">Part Name: <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="PartNameInput" id="PartName">

                                            <label for="PartNumber" class="form-label fw-bold">Part No.: <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="PartNumInput" id="PartNumber">

                                            <label for="Brand" class="form-label fw-bold">Brand: <span class="text-danger">*</span></label>
                                            <select name="BrandInput" id="BrandInput" class="form-select">
                                                <option value="" selected disabled>-- Select Brand --</option>
                                                <?php
                                                if (isset($sqlBrand) && is_object($sqlBrand)) {
                                                    mysqli_data_seek($sqlBrand, 0);
                                                }
                                                while ($brandResults = mysqli_fetch_array($sqlBrand)): ?>
                                                    <option>
                                                        <?php echo htmlspecialchars($brandResults['brand_name']) ?>
                                                    </option>
                                                <?php endwhile; ?>
                                                <option value="addbrand" class="text-success">+ Add Brand</option>
                                            </select>

                                            <label for="Stock" class="form-label fw-bold">Stock: <span class="text-danger">*</span></label>
                                            <input type="number" name="StockInput" class="form-control" id="Stock">
                                        </div>

                                        <div class="col-md-6">
                                            <label for="EquipmentIdInput" class="form-label fw-bold">Equipment ID: <span class="text-danger">*</span></label>
                                            <select name="EquipmentIdInput" id="EquipmentIdInput" class="form-select" required>
                                                <option selected disabled>-- Select Equipment ID --</option>
                                                <?php
                                                if (isset($sqlEquipment) && is_object($sqlEquipment)) {
                                                    mysqli_data_seek($sqlEquipment, 0);
                                                }
                                                while ($equipmentResults = mysqli_fetch_array($sqlEquipment)): ?>
                                                    <option>
                                                        <?php echo htmlspecialchars($equipmentResults['custom_equip_id']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                            <label for="SupplierId" class="form-label fw-bold">Supplier Name: <span class="text-danger">*</span></label>
                                            <select name="SupplierIdInput" id="SupplierIdInput" class="form-select" required>
                                                <option selected disabled>-- Select Supplier --</option>
                                                <?php
                                                if (isset($sqlSupplier) && is_object($sqlSupplier)) {
                                                    mysqli_data_seek($sqlSupplier, 0);
                                                }
                                                while ($supplierResults =  mysqli_fetch_array($sqlSupplier)): ?>
                                                    <option>
                                                        <?php echo htmlspecialchars($supplierResults['supplier_comp_name']) ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>

                                            <label for="LastUpdate" class="form-label fw-bold">Last Update: <span class="text-danger">*</span></label>
                                            <input type="datetime-local" name="LastUpdateInput" class="form-control" id="LastUpdate">

                                            <label for="UnitPrice" class="form-label fw-bold">Unit Price: <span class="text-danger">*</span></label>
                                            <input type="text" name="UnitPriceInput" class="form-control" id="UnitPrice">
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-footer justify-content-between">
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#batchUploadInventory">
                                        <i class="fas fa-file-upload"></i> Upload CSV
                                    </button>
                                    <div class="">
                                        <button type="submit" name="add" class="btn btn-success">Add</button>
                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="batchUploadInventory" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="batchUploadInventoryLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-success">
                                <h5 class="modal-title fw-bold text-white" id="batchUploadInventoryLabel">Batch Upload Inventory</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form id="batchUploadForm" action="batchUploadInventory.php" method="post" enctype="multipart/form-data">
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="csvFile" class="form-label fw-bold">Upload CSV File: <span class="text-danger">*</span></label>
                                        <input type="file" class="form-control" id="csvFile" name="csvFile" accept=".csv" required>
                                        <small class="form-text text-muted">Ensure the CSV file has columns: part_name, part_number, brand_name, stock_quantity, equipment_id, supplier_name, unit_price, last_update (YYYY-MM-DD HH:MM:SS).</small>
                                    </div>
                                    <div class="mb-3">
                                        <a href="template_inventory.csv" download class="btn btn-outline-secondary">
                                            <i class="fas fa-download"></i> Download CSV Template
                                        </a>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-success">Upload</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="  modal fade" id="addBrand" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="addBrandLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-success">
                        <h5 class="modal-title fw-bold text-white" id="addBrandLabel">Add Brand</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <form action="createBrand.php" method="post">
                        <div class="modal-body">
                            <label for="addBrand" class="form-label fw-bold">Add new Brand: <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="addBrandInput" required>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-success" name="addBrand">Add</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="table-container">
            <div class="table-header">
                <div class="table-responsive" style="max-height: 450px;">
                    <table class="table text-center" id="equipmentTable">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll" class="form-check-input"></th>
                                <th>Part Name</th>
                                <th>Brand</th>
                                <th>Part No.</th>
                                <th>Stock Quantity</th>
                                <th>Status</th>
                                <th>Unit Price</th>
                                <th>Last Update</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filteredInventoryRows as $r): ?>
                                <?php
                                $q = (int)$r['stock_quantity'];
                                if ($q < 10) {
                                    $statusText  = 'Out of stock';
                                    $statusClass = 'bg-danger';
                                } elseif ($q < 20) {
                                    $statusText  = 'Low stock';
                                    $statusClass = 'bg-warning';
                                } else {
                                    $statusText  = 'In stock';
                                    $statusClass = 'bg-success';
                                }
                                ?>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input inventory-checkbox" name="selectedSpareIds[]" value="<?= htmlspecialchars($r['spare_id']) ?>"></td>
                                    <td><?= htmlspecialchars($r['part_name']) ?></td>
                                    <td><?= htmlspecialchars($r['brand_name'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($r['part_number']) ?></td>
                                    <td><?= $q ?></td>
                                    <td><span class="badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                                    <td>₱ <?= number_format($r['unit_price'], 2) ?></td>
                                    <td><?= date('Y-m-d - h:i A', strtotime($r['last_update'])) ?></td>
                                    <td class="d-flex justify-content-center">
                                        <button type="button" class="action-button" data-bs-toggle="modal" data-bs-target="#editModal<?= $r['part_id'] ?>">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </button>
                                        <button type="button" class="action-button view-button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewModal"
                                            data-part-name="<?= htmlspecialchars($r['part_name']) ?>"
                                            data-brand-name="<?= htmlspecialchars($r['brand_name'] ?? '—') ?>"
                                            data-part-number="<?= htmlspecialchars($r['part_number']) ?>"
                                            data-stock-quantity="<?= htmlspecialchars($q) ?>"
                                            data-unit-price="<?= number_format($r['unit_price'], 2) ?>"
                                            data-last-update="<?= date('Y-m-d - h:i A', strtotime($r['last_update'])) ?>"
                                            data-equipment-id="<?= htmlspecialchars($r['custom_equip_id'] ?? '—') ?>"
                                            data-supplier-name="<?= htmlspecialchars($r['supplier_name'] ?? '—') ?>"
                                            data-part-specs="<?= htmlspecialchars($r['part_specs'] ?? '—') ?>"
                                            data-part-remarks="<?= htmlspecialchars($r['part_remarks'] ?? '—') ?>">
                                            <i class="fa-solid fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <!-- View modal -->
                <div class="modal fade" id="viewModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdrop" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-primary ">
                                <h5 class="modal-title fw-bold text-white" id="staticBackdropLabel">
                                    <i class="fas fa-file-alt me-2"></i>Inventory Description
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold">Part Name:</label>
                                                <div class="border-bottom pb-1" id="viewPartName"></div>

                                                <label class="form-label fw-bold mt-3">Brand:</label>
                                                <div class="border-bottom pb-1" id="viewBrandName"></div>

                                                <label class="form-label fw-bold mt-3">Part No.:</label>
                                                <div class="border-bottom pb-1" id="viewPartNumber"></div>

                                                <label class="form-label fw-bold mt-3">Stock Quantity:</label>
                                                <div class="border-bottom pb-1" id="viewStockQuantity"></div>

                                                <label class="form-label fw-bold mt-3">Unit Price:</label>
                                                <div class="border-bottom pb-1" id="viewUnitPrice"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold">Equipment ID:</label>
                                                <div class="border-bottom pb-1" id="viewEquipmentId"></div>

                                                <label class="form-label fw-bold mt-3">Supplier Name:</label>
                                                <div class="border-bottom pb-1" id="viewSupplierName"></div>

                                                <label class="form-label fw-bold mt-3">Last Update:</label>
                                                <div class="border-bottom pb-1" id="viewLastUpdate"></div>

                                                <label class="form-label fw-bold mt-3">Part Specs:</label>
                                                <div class="border-bottom pb-1" id="viewPartSpecs"></div>

                                                <label class="form-label fw-bold mt-3">Part Remarks:</label>
                                                <div class="border-bottom pb-1" id="viewPartRemarks"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Edit Modal -->
                <?php foreach ($inventoryRows as $r): ?>
                    <div class="modal fade" id="editModal<?= $r['part_id'] ?>" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="editModalLabel<?= $r['part_id'] ?>" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-secondary">
                                    <h5 class="modal-title text-white fw-bold" id="editModalLabel<?= $r['part_id'] ?>"> Edit Part #<?= $r['part_id'] ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form action="updateInventory.php" method="post">
                                    <input type="hidden" name="part_id" value="<?= $r['part_id'] ?>">
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold">Part Name <span class="text-danger">*</span></label>
                                                <input type="text" name="part_name" class="form-control" value="<?= htmlspecialchars($r['part_name']) ?>" required>

                                                <label class="form-label fw-bold mt-3">Part No. <span class="text-danger">*</span></label>
                                                <input type="text" name="part_number" class="form-control" value="<?= htmlspecialchars($r['part_number']) ?>" required>

                                                <label class="form-label fw-bold mt-3">Brand <span class="text-danger">*</span></label>
                                                <input type="text" name="brand_name" class="form-control" value="<?= htmlspecialchars($r['brand_name'] ?? '') ?>" required>

                                                <label class="form-label fw-bold mt-3">Stock <span class="text-danger">*</span></label>
                                                <input type="number" name="stock_quantity" class="form-control" value="<?= (int)$r['stock_quantity'] ?>" required>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-bold">Equipment ID <span class="text-danger">*</span></label>
                                                <input type="text" name="equipment_id" class="form-control" value="<?= htmlspecialchars($r['custom_equip_id'] ?? '') ?>" required>

                                                <label class="form-label fw-bold mt-3">Supplier Name <span class="text-danger">*</span></label>
                                                <input type="text" name="supplier_name" class="form-control" value="<?= htmlspecialchars($r['supplier_name'] ?? '') ?>" required>

                                                <label class="form-label fw-bold mt-3">Last Update <span class="text-danger">*</span></label>
                                                <input type="datetime-local" name="last_update" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($r['last_update'])) ?>" required>

                                                <label class="form-label fw-bold mt-3">Unit Price <span class="text-danger">*</span></label>
                                                <input type="text" name="unit_price" class="form-control" value="<?= htmlspecialchars($r['unit_price']) ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal"> Close </button>
                                        <button type="submit" name="update" class="btn btn-success"> Update </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to show custom alert
        function showAlert(message, type = 'success') {
            const alertDiv = document.getElementById('customAlert');
            const alertMessage = document.getElementById('alertMessage');

            alertMessage.textContent = message;
            alertDiv.className = 'custom-alert show ' + type;

            setTimeout(() => {
                alertDiv.classList.remove('show');
            }, 5000);
        }

        // Check for success message in URL on page load
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('status')) {
                const status = urlParams.get('status');
                if (status === 'success') {
                    showAlert('Successfully updated or added inventory!', 'success');
                } else if (status === 'batch_success') {
                    showAlert('Successfully uploaded inventory from CSV!', 'success');
                } else if (status === 'batch_error') {
                    showAlert('Error uploading CSV: ' + urlParams.get('message'), 'error');
                }
                urlParams.delete('status');
                urlParams.delete('message');
                window.history.replaceState({}, document.title, "?" + urlParams.toString());
            }

            // Populate View Modal when it's shown
            const viewModal = document.getElementById('viewModal');
            viewModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget; // Button that triggered the modal
                document.getElementById('viewPartName').textContent = button.getAttribute('data-part-name');
                document.getElementById('viewBrandName').textContent = button.getAttribute('data-brand-name');
                document.getElementById('viewPartNumber').textContent = button.getAttribute('data-part-number');
                document.getElementById('viewStockQuantity').textContent = button.getAttribute('data-stock-quantity');
                document.getElementById('viewUnitPrice').textContent = '₱ ' + button.getAttribute('data-unit-price');
                document.getElementById('viewLastUpdate').textContent = button.getAttribute('data-last-update');
                document.getElementById('viewEquipmentId').textContent = button.getAttribute('data-equipment-id');
                document.getElementById('viewSupplierName').textContent = button.getAttribute('data-supplier-name');
                document.getElementById('viewPartSpecs').textContent = button.getAttribute('data-part-specs');
                document.getElementById('viewPartRemarks').textContent = button.getAttribute('data-part-remarks');
            });
        });

        document.getElementById('BrandInput').addEventListener('change', function() {
            const selected = this.value;

            if (selected === 'addbrand') {
                new bootstrap.Modal(document.getElementById('addBrand')).show();
                this.selectedIndex = 0;
            }
        });

        document.getElementById('selectAllArchived').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('#archiveModal input[name="selectedArchiveIds[]"]');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="selectedSpareIds[]"]');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        document.getElementById('archiveForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const selectedCheckboxes = document.querySelectorAll('input[name="selectedSpareIds[]"]:checked');
            const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);

            if (selectedIds.length === 0) {
                showAlert('Please select at least one item to archive.', 'error');
                return;
            }

            if (!confirm('Are you sure you want to archive the selected items?')) {
                return;
            }

            const formData = new FormData();
            selectedIds.forEach(id => formData.append('selected_spare_ids[]', id));

            fetch('archiveInventory.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        location.reload();
                    } else {
                        showAlert('Error archiving items: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An unexpected error occurred during archiving.', 'error');
                });
        });

        document.getElementById('archiveActionsForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const action = event.submitter.value;
            const selectedCheckboxes = document.querySelectorAll('#archiveModal input[name="selectedArchiveIds[]"]:checked');
            const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);

            if (selectedIds.length === 0) {
                showAlert('Please select at least one item to ' + action + '.', 'error');
                return;
            }

            let confirmMessage = '';
            let phpScript = '';

            if (action === 'restore') {
                confirmMessage = 'Are you sure you want to restore the selected items?';
                phpScript = 'restoreInventory.php';
            } else if (action === 'delete') {
                confirmMessage = 'Are you sure you want to PERMANENTLY DELETE the selected items? This action cannot be undone.';
                phpScript = 'deleteArchived.php';
            }

            if (!confirm(confirmMessage)) {
                return;
            }

            const formData = new FormData();
            selectedIds.forEach(id => formData.append('selectedArchiveIds[]', id));
            formData.append('action', action);

            fetch(phpScript, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        const archiveModal = bootstrap.Modal.getInstance(document.getElementById('archiveModal'));
                        archiveModal.hide();
                        location.reload();
                    } else {
                        showAlert('Error ' + action + 'ing items: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An unexpected error occurred during ' + action + 'ing.', 'error');
                });
        });

        archiveModalElement.addEventListener('show.bs.modal', function() {
            const currentArchivedCount = document.querySelectorAll('#archiveModal tbody .archive-checkbox').length;
            document.getElementById('archiveCount').textContent = currentArchivedCount;
        });
    </script>
</body>

</html>