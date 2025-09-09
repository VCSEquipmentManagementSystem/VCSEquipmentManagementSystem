<?php
session_start();
require('./readPurchaseRequest.php');
if (empty($_SESSION['userID'])) {
    header('Location: loginPage.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <title>Purchase Request</title>
</head>
<style>
    @import url("https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap");

    body {
        font-family: "Poppins", sans-serif;
        background-color: #e8edf2;
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
        --bs-offcanvas-width: 260px;
    }

    table {
        margin-top: 20px;
    }

    .table-container {
        background-color: white;
        border-radius: 20px;
        padding: 20px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
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

    /* Make table rows clickable */
    #purchaserequesttable tbody tr {
        cursor: pointer;
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

    #archivedRequestsTable {
        width: 100%;
        margin-bottom: 0;
    }

    #archivedRequestsTable thead th {
        background-color: #f8f9fa;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
        padding: 0.75rem;
        vertical-align: bottom;
    }

    #archivedRequestsTable tbody td {
        vertical-align: middle;
        padding: 0.75rem;
    }

    #selectAllArchived,
    .archived-checkbox {
        transform: scale(1.2);
        margin-right: 5px;
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
                        Purchase Request
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
        <div class="row container-header mt-5">
            <!-- <div class="title-header d-flex justify-content-between">
                <h3 class="mb-4 fw-bold mt-3">
                    Purchase Request
                </h3>
            </div> -->
            <div class="d-flex justify-content-end mt-3">
                <!--Search  -->
                <div class="search-container me-2">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control search-input" id="searchInput" name="search" placeholder="Search">
                </div>
                <div class="d-flex justify-content-end mb-3">
                    <!--Export  -->
                    <button class="btn btn-outline-secondary me-2" id="exportPdfBtn">
                        <i class="fas fa-file-pdf"></i>
                    </button>
                    <!--Sort  -->
                    <button class="btn btn-outline-secondary me-2" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-arrow-down-wide-short"></i>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                        <li><a href="#" class="dropdown-item sort-option" data-sort="desc">Sort by Date</a></li>
                    </ul>
                    <div class="dropdown me-2">
                        <button class="btn btn-outline-success dropdown-toggle" type="button" id="bulkActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cogs me-1"></i>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="bulkActionsDropdown">
                            <li><a class="dropdown-item" href="#" id="bulkApproveBtn"><i class="fa-solid fa-circle-check me-2"></i>Approve Selected</a></li>
                            <li><a class="dropdown-item" href="#" id="bulkDeclineBtn"><i class="fa-solid fa-times-circle me-2"></i>Decline Selected</a></li>
                            <li><a class="dropdown-item" href="#" id="bulkArchiveBtn"><i class="fas fa-archive me-2"></i>Archive Selected</a></li>
                        </ul>
                    </div>
                    <!--View Archive  -->
                    <button type="button" class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#archiveModal" id="viewArchivedBtn">
                        <i class="fas fa-archive"></i>
                        <span class="badge rounded-pill bg-danger ms-1" id="archivedCountBadge">0</span>
                    </button>
                    <!--Add Purchase Request  -->
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                        <i class="fas fa-plus me-1"></i>
                    </button>
                    <form action="createPurchaseRequest.php" method="POST" enctype="multipart/form-data">
                        <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false"
                            tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header bg-success">
                                        <h5 class="modal-title fw-bold text-white" id="exampleModalLabel">+ Create purchase request</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close">
                                        </button>
                                    </div>
                                    <!--Create PR Modal  -->
                                    <div class="modal-body text-center">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label for="DateAutofill" class="form-label fw-bold">Date:</label>
                                                <p><?php
                                                    $currentDateTime = new DateTime('now');
                                                    $currentDate = $currentDateTime->format('m-d-Y');
                                                    echo $currentDate
                                                    ?>
                                                </p>
                                                <label for="DateNeeded" class="form-label fw-bold">Date Needed:</label>
                                                <input type="date" name="date_needed" class="form-control" id="DateNeeded">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="PurchaseRquestIdAutofill" class="form-label fw-bold"></label>
                                                <label for="RequestedByAutofill" class="form-label fw-bold">Requested by:</label>
                                                <p>Ivan Baltar</p>
                                                <label for="Location" class="form-label fw-bold mx-1">Location: <span class="text-danger">*</span></label>
                                                <select class="form-select" name='location' style="max-width: 400px;" id="location" onchange="updateLocation()" requried>
                                                    <option selected disabled value="placeholder">-- Select Hub --</option>
                                                    <option value="Manila">Manila</option>
                                                    <option value="Cagayan">Cagayan</option>
                                                    <option value="Ilocos Sur">Ilocos Sur</option>
                                                    <option value="Ilocos Norte">Ilocos Norte</option>
                                                    <option value="Laoag">Laoag</option>
                                                    <option value="Siquijor">Siquijor</option>
                                                </select>
                                            </div>
                                            <div class="d-flex align-text-center mt-4">
                                                <label for="PurposeInput" class="form-label fw-bold mt-2 me-1">Purpose:</label>
                                                <input type="text" name='purpose' class="form-control" style="width: 700px;" id="PurposeInput">
                                            </div>
                                            <p></p>
                                            <div class="row">
                                                <div class="col-sm-2"><label class="form-label fw-bold text-nowrap">Qty:<span class="text-danger">*</span></label></div>
                                                <div class="col-sm-2"><label class="form-label fw-bold text-nowrap">Unit:<span class="text-danger">*</span></label></div>
                                                <div class="col-sm-3"><label class="form-label fw-bold">Item Description:<span class="text-danger">*</span></label></div>
                                                <div class="col-sm-4"><label class="form-label fw-bold text-nowrap">Remarks:<span class="text-danger">*</span></label></div>
                                                <div class="col-sm-1"></div>
                                            </div>

                                            <div id="itemContainer">
                                                <div class="row mt-2 item-entry align-items-end">
                                                    <div class="col-sm-2">
                                                        <input type="number" name="qty[]" class="form-control" min="1" required>
                                                    </div>
                                                    <div class="col-sm-2">
                                                        <input type="text" name="unit[]" class="form-control" required>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <input type="text" name="item_description[]" class="form-control" required>
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <input type="text" name="remarks[]" class="form-control" required>
                                                    </div>
                                                    <div class="col-sm-1"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-4 text-start">
                                            <button type="button" id="addItemBtn" class="btn btn-primary mt-2"><i class="fa-solid fa-square-plus"></i></button>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" name="createPurchaseRequest" class="btn btn-success" data-bs-dismiss="modal" id="AddPr">Submit</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!--PR Main Table  -->
            <div class="table-container">
                <div class="table-header">
                    <div class="table-responsive">
                        <table class="table text-center" id="purchaserequesttable">
                            <thead>
                                <tr>
                                    <th>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAll">
                                        </div>
                                    </th>
                                    <th>Purchase ID</th>
                                    <th>Purpose</th>
                                    <th>Requested by</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                mysqli_data_seek($sqlPurchaseRequest, 0);

                                while ($PurchaseRequestResults = mysqli_fetch_array($sqlPurchaseRequest)) {
                                ?>
                                    <tr data-purchase-id="<?php echo $PurchaseRequestResults['id']; ?>"
                                        data-pr-number="<?php echo htmlspecialchars($PurchaseRequestResults['pr_number']); ?>"
                                        data-request-date="<?php echo $PurchaseRequestResults['date_prepared']; ?>"
                                        data-purpose="<?php echo htmlspecialchars($PurchaseRequestResults['purpose']); ?>"
                                        data-date-needed="<?php echo $PurchaseRequestResults['date_needed']; ?>"
                                        data-location="<?php echo htmlspecialchars($PurchaseRequestResults['location']); ?>"
                                        data-requested-by="<?php echo htmlspecialchars($PurchaseRequestResults['requested_by']); ?>">
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input approve-checkbox" type="checkbox">
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($PurchaseRequestResults['pr_number']); ?></td>
                                        <td><?php echo $PurchaseRequestResults['purpose'] ?></td>
                                        <td><?php echo $PurchaseRequestResults['requested_by'] ?></td>
                                        <td>
                                            <div>
                                                <?php
                                                $status = $PurchaseRequestResults['status'];
                                                $badgeClass = '';
                                                switch ($status) {
                                                    case 'approved':
                                                        $badgeClass = 'bg-success';
                                                        break;
                                                    case 'declined':
                                                        $badgeClass = 'bg-danger';
                                                        break;
                                                    default:
                                                        $badgeClass = 'bg-warning';
                                                        break;
                                                }
                                                ?>
                                                <button class="badge <?php echo $badgeClass; ?> text-white border-0">
                                                    <?php echo $status; ?>
                                                </button>
                                            </div>
                                        </td>
                                        <td><?php echo $PurchaseRequestResults['date_prepared'] ?></td>
                                        <td class="d-flex justify-content-center">
                                            <button class="action-button" data-bs-toggle="modal" data-bs-target="#editPurchaseRequestModal">
                                                <i class="fa-solid fa-pen-to-square"></i> Edit
                                            </button>
                                            <button class="action-button archive-item-btn">
                                                <i class="fa-solid fa-box-archive"></i> Archive
                                            </button>
                                        </td>
                                    </tr>
                                <?php } //end of while loop 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!--Edit PR Modal  -->
    <form action="editPurchaseRequest.php" method="POST" enctype="multipart/form-data">
        <div class="modal fade" id="editPurchaseRequestModal" data-bs-backdrop="static" data-bs-keyboard="false"
            tabindex="-1" aria-labelledby="editPurchaseRequestModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="editPurchaseRequestModalLabel">Edit Purchase Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close">
                        </button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="row">
                            <div class="col-md-6">
                                <input type="hidden" name="purchase_request_id" id="editPurchaseRequestIdHidden">

                                <label for="editDateAutofill" class="form-label fw-bold">Date:</label>
                                <p id="editDateAutofill"></p>
                                <label for="editDateNeeded" class="form-label fw-bold">Date Needed:</label>
                                <input type="date" name="date_needed" class="form-control" id="editDateNeeded">
                            </div>
                            <div class="col-md-6">
                                <label for="editRequestedByAutofill" class="form-label fw-bold">Requested by:</label>
                                <p id="editRequestedByAutofill"></p>
                                <label for="editLocation" class="form-label fw-bold mx-1">Location: <span class="text-danger">*</span></label>
                                <select class="form-select" name='location' style="max-width: 400px;" id="editLocation" required>
                                    <option value="Manila">Manila</option>
                                    <option value="Cagayan">Cagayan</option>
                                    <option value="Ilocos Sur">Ilocos Sur</option>
                                    <option value="Ilocos Norte">Ilocos Norte</option>
                                    <option value="Laoag">Laoag</option>
                                    <option value="Siquijor">Siquijor</option>
                                </select>
                            </div>
                            <div class="d-flex align-text-center mt-4">
                                <label for="editPurposeInput" class="form-label fw-bold mt-2 me-1">Purpose:</label>
                                <input type="text" name='purpose' class="form-control" style="width: 700px;" id="editPurposeInput">
                            </div>
                            <p></p>
                            <div class="row">
                                <div class="col-sm-2"><label class="form-label fw-bold text-nowrap">Qty:<span class="text-danger">*</span></label></div>
                                <div class="col-sm-2"><label class="form-label fw-bold text-nowrap">Unit:<span class="text-danger">*</span></label></div>
                                <div class="col-sm-3"><label class="form-label fw-bold">Item Description:<span class="text-danger">*</span></label></div>
                                <div class="col-sm-4"><label class="form-label fw-bold text-nowrap">Remarks:<span class="text-danger">*</span></label></div>
                            </div>

                            <div id="editItemContainer">
                            </div>
                        </div>
                        <div class="mt-4 text-start">
                            <button type="button" id="editAddItemBtn" class="btn btn-primary mt-2"><i class="fa-solid fa-square-plus"></i></button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="editPurchaseRequest" class="btn btn-success">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- PR Description Modal  -->
    <div class="modal fade" id="purchaseRequestModal" tabindex="-1" aria-labelledby="purchaseRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-3">
                <!-- Header -->
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold" id="purchaseRequestModalLabel">Purchase Request Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <!-- Request Info -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="mb-1 fw-bold">Request Date:</p>
                            <span id="modalRequestDate" class="text-muted"></span>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 fw-bold">Purpose:</p>
                            <span id="modalPurpose" class="text-muted"></span>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 fw-bold">Purchase ID:</p>
                            <span id="modalPurchaseId" class="text-muted"></span>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 fw-bold">Date Needed:</p>
                            <span id="modalDateNeeded" class="text-muted"></span>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 fw-bold">Location:</p>
                            <span id="modalLocation" class="text-muted"></span>
                        </div>
                    </div>

                    <!-- Item List -->
                    <div class="table-responsive mt-4">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="text-center">QTY</th>
                                    <th scope="col" class="text-center">UNIT</th>
                                    <th scope="col">Item Description</th>
                                    <th scope="col">Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="text-center" id="modalQTY"></td>
                                    <td class="text-center" id="modalUNIT"></td>
                                    <td id="modalItemDescription"></td>
                                    <td id="modalRemarks"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer Info -->
                    <div class="mt-4">
                        <p class="mb-1"><strong>Requested By:</strong> <span id="modalRequestedBy" class="text-muted"></span></p>
                        <p class="mb-0"><strong>Approved By:</strong> <span id="modalApprovedBy" class="text-muted"></span></p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>



    <!--Archive Table Modal  -->
    <div class="modal fade" id="archiveModal" tabindex="-1" aria-labelledby="archiveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="archiveModalLabel">Archived Purchase Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="archiveMessage" class="text-danger text-center"></p>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered" id="archivedRequestsTable">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAllArchived"></th>
                                    <th>PR ID</th>
                                    <th>Purpose</th>
                                    <th>Requested By</th>
                                    <th>Date Prepared</th>
                                </tr>
                            </thead>
                            <tbody id="archivedItemsBody">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" id="delete-archived-btn">Delete Selected</button>
                    <button type="button" class="btn btn-primary" id="restore-archived-btn">Restore Selected</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll("#purchaserequesttable tbody tr").forEach(row => {
                row.addEventListener("click", function(event) {
                    if (event.target.closest('button, input, a, .dropdown-toggle, .form-check, .dropdown')) {
                        return;
                    }

                    const purchaseId = this.dataset.purchaseId;
                    const prNumber = this.dataset.prNumber;
                    const requestDate = this.dataset.requestDate;
                    const purpose = this.dataset.purpose;
                    const dateNeeded = this.dataset.dateNeeded;
                    const location = this.dataset.location;
                    const requestedBy = this.dataset.requestedBy;

                    // Populate the main purchase request details in the view modal
                    document.getElementById('modalRequestDate').textContent = requestDate;
                    document.getElementById('modalPurpose').textContent = purpose;
                    document.getElementById('modalPurchaseId').textContent = prNumber;
                    document.getElementById('modalDateNeeded').textContent = dateNeeded;
                    document.getElementById('modalLocation').textContent = location;
                    document.getElementById('modalRequestedBy').textContent = requestedBy;

                    // Fetch the item details for this specific purchaseId via AJAX
                    fetch(`./getPurchaseRequestItems.php?purchase_id=${purchaseId}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(items => {
                            const qtyDiv = document.getElementById('modalQTY');
                            const unitDiv = document.getElementById('modalUNIT');
                            const itemDescDiv = document.getElementById('modalItemDescription');
                            const remarksDiv = document.getElementById('modalRemarks');

                            // Clear previous item data
                            qtyDiv.innerHTML = '';
                            unitDiv.innerHTML = '';
                            itemDescDiv.innerHTML = '';
                            remarksDiv.innerHTML = '';

                            // Append new item data
                            if (items.length > 0) {
                                items.forEach(item => {
                                    qtyDiv.innerHTML += `<p>${item.qty}</p>`;
                                    unitDiv.innerHTML += `<p>${item.unit}</p>`;
                                    itemDescDiv.innerHTML += `<p>${item.item_description}</p>`;
                                    remarksDiv.innerHTML += `<p>${item.remarks}</p>`;
                                });
                            } else {
                                // Display a message if no items are found
                                qtyDiv.innerHTML = '<p>N/A</p>';
                                unitDiv.innerHTML = '<p>N/A</p>';
                                itemDescDiv.innerHTML = '<p>No items found.</p>';
                                remarksDiv.innerHTML = '<p>N/A</p>';
                            }

                            // Show the modal
                            const modal = new bootstrap.Modal(document.getElementById("purchaseRequestModal"));
                            modal.show();
                        })
                        .catch(error => console.error('Error fetching purchase request items:', error));
                });
            });

            // --- For Edit Modal ---
            document.querySelectorAll(".dropdown-item[data-bs-target='#editPurchaseRequestModal']").forEach(editLink => {
                editLink.addEventListener("click", function(event) {
                    event.preventDefault();
                    event.stopPropagation();

                    // Find the closest table row to get the purchase ID
                    const row = this.closest('tr');
                    const purchaseId = row.dataset.purchaseId;

                    // Fetch all details for this purchase ID
                    fetch(`./getPurchaseRequestDetails.php?purchase_id=${purchaseId}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success && data.data) {
                                const main = data.data.main;
                                const items = data.data.items;

                                // Populate main details in the edit modal
                                document.getElementById('editPurchaseRequestIdHidden').value = main.id; // Hidden ID
                                document.getElementById('editDateAutofill').textContent = main.date_prepared;
                                document.getElementById('editDateNeeded').value = main.date_needed;
                                document.getElementById('editRequestedByAutofill').textContent = main.requested_by;
                                document.getElementById('editLocation').value = main.location; // Select dropdown value
                                document.getElementById('editPurposeInput').value = main.purpose;

                                // Populate items in the edit modal
                                const editItemContainer = document.getElementById('editItemContainer');
                                editItemContainer.innerHTML = '';

                                if (items.length > 0) {
                                    items.forEach(item => {
                                        const newItemRow = document.createElement('div');
                                        newItemRow.className = 'row mt-2 item-entry align-items-end';

                                        // Hidden input for item_id - CRUCIAL for updating/deleting specific items
                                        const itemIdInput = document.createElement('input');
                                        itemIdInput.type = 'hidden';
                                        itemIdInput.name = 'item_id[]';
                                        itemIdInput.value = item.item_id; // Use item.item_id from PHP response
                                        newItemRow.appendChild(itemIdInput);

                                        newItemRow.innerHTML += `
                                            <div class="col-sm-2">
                                                <input type="number" name="qty[]" class="form-control" min="1" value="${item.qty}" required>
                                            </div>
                                            <div class="col-sm-2">
                                                <input type="text" name="unit[]" class="form-control" value="${item.unit}" required>
                                            </div>
                                            <div class="col-sm-3">
                                                <input type="text" name="item_description[]" class="form-control" value="${item.item_description}" required>
                                            </div>
                                            <div class="col-sm-4">
                                                <input type="text" name="remarks[]" class="form-control" value="${item.remarks}" required>
                                            </div>
                                            <div class="col-sm-1 text-center">
                                                <button type="button" class="btn btn-danger btn-sm remove-part"><i class="fas fa-trash-alt"></i></button>
                                            </div>
                                        `;
                                        // Add event listener to the remove button
                                        newItemRow.querySelector('.remove-part').addEventListener('click', function() {
                                            newItemRow.remove();
                                        });

                                        editItemContainer.appendChild(newItemRow);
                                    });
                                } else {
                                    // If no items, add a single empty row for new input
                                    addEditItemRow();
                                }

                                // Show the edit modal
                                const editModal = new bootstrap.Modal(document.getElementById("editPurchaseRequestModal"));
                                editModal.show();
                            } else {
                                console.error('Failed to fetch purchase request details:', data.message);
                                alert('Could not load purchase request details. Please try again.');
                            }
                        })
                        .catch(error => console.error('Error fetching purchase request details:', error));
                });
            });

            // --- Event Delegation for "Archive" dropdown item ---
            document.querySelectorAll('.dropdown-menu').forEach(dropdownMenu => {
                dropdownMenu.addEventListener('click', function(event) {
                    // Check if the clicked element (or its closest parent) is an "Archive" button
                    const archiveLink = event.target.closest('.archive-item-btn');

                    if (archiveLink) {
                        event.preventDefault();
                        event.stopPropagation();

                        const row = archiveLink.closest('tr');
                        if (!row) {
                            console.error("Error: Could not find parent table row for archive link.");
                            alert("Error: Could not find purchase request data.");
                            return;
                        }

                        const purchaseId = row.dataset.purchaseId;
                        const prNumber = row.dataset.prNumber;
                        console.log("Retrieved Purchase ID:", purchaseId);

                        if (!purchaseId || isNaN(purchaseId)) {
                            console.error("Error: Invalid Purchase ID retrieved:", purchaseId);
                            alert("Error: Invalid purchase request ID. Cannot archive.");
                            return;
                        }

                        if (confirm(`Are you sure you want to archive purchase request ID: ${prNumber}?`)) {
                            console.log("User confirmed archive for ID:", prNumber);

                            fetch('archivePurchaseRequest.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify({
                                        id: purchaseId
                                    })
                                })
                                .then(response => {
                                    console.log("Fetch response received. Status:", response.status);
                                    if (!response.ok) {
                                        throw new Error('Network response was not ok ' + response.statusText);
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    console.log("JSON data received:", data);
                                    if (data.success) {
                                        alert(data.message);
                                        window.location.reload();
                                    } else {
                                        alert('Error archiving: ' + data.message);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error archiving purchase request (catch block):', error);
                                    alert('An error occurred while trying to archive the request.');
                                });
                        } else {
                            console.log("User cancelled archive.");
                        }

                        // Manually hide the Bootstrap dropdown after clicking an item
                        const dropdown = bootstrap.Dropdown.getInstance(archiveLink.closest('.dropdown').querySelector('[data-bs-toggle="dropdown"]'));
                        if (dropdown) {
                            dropdown.hide();
                        }
                    }
                });
            });

            // --- "Add Item" button for Edit Modal ---
            document.getElementById("editAddItemBtn").addEventListener("click", function() {
                addEditItemRow();
            });

            function addEditItemRow() {
                const container = document.getElementById("editItemContainer");
                const newItem = document.createElement('div');
                newItem.className = 'row mt-2 item-entry align-items-end';

                // IMPORTANT: For new items, item_id is empty/null, so backend knows to INSERT
                newItem.innerHTML = `
                    <input type="hidden" name="item_id[]" value="">
                    <div class="col-sm-2">
                        <input type="number" name="qty[]" class="form-control" min="1" required>
                    </div>
                    <div class="col-sm-2">
                        <input type="text" name="unit[]" class="form-control" required>
                    </div>
                    <div class="col-sm-5">
                        <input type="text" name="item_description[]" class="form-control" required>
                    </div>
                    <div class="col-sm-2">
                        <input type="text" name="remarks[]" class="form-control" required>
                    </div>
                    <div class="col-sm-1 text-center">
                        <button type="button" class="btn btn-danger btn-sm remove-part"><i class="fas fa-trash-alt"></i></button>
                    </div>
                `;
                // Add event listener to the newly created remove button
                newItem.querySelector('.remove-part').addEventListener('click', function() {
                    newItem.remove();
                });

                container.appendChild(newItem);
            }

            // --- Existing JavaScript for Create Modal (Add Item button) ---
            document.getElementById("addItemBtn").addEventListener("click", function() {
                const container = document.getElementById("itemContainer");
                const firstItem = container.querySelector(".item-entry");
                const newItem = firstItem.cloneNode(true);

                // Clear all inputs in cloned row
                newItem.querySelectorAll("input").forEach(input => input.value = "");

                // Remove any existing remove button from the clone and add a new one
                let removeCol = newItem.querySelector(".col-sm-1");
                if (removeCol) {
                    removeCol.remove(); // Remove existing if it was cloned
                }

                removeCol = document.createElement("div");
                removeCol.className = "col-sm-1 text-center";

                const removeBtn = document.createElement("button");
                removeBtn.setAttribute("type", "button");
                removeBtn.className = "btn btn-danger btn-sm remove-part";
                removeBtn.innerHTML = '<i class="fas fa-trash-alt"></i>';

                // Button event: remove the row
                removeBtn.addEventListener("click", function() {
                    newItem.remove();
                });

                removeCol.appendChild(removeBtn);
                newItem.appendChild(removeCol);

                // Append the new item
                container.appendChild(newItem);
            });

            // --- Existing "Select All" checkbox functionality ---
            document.getElementById("selectAll").addEventListener("change", function() {
                const isChecked = this.checked;
                document.querySelectorAll("#purchaserequesttable tbody input[type='checkbox']").forEach(checkbox => {
                    checkbox.checked = isChecked;
                });
            });

            // Ensure the dropdowns for ellipsis menu work correctly by stopping propagation
            document.querySelectorAll('.action-button').forEach(button => {
                button.addEventListener('click', function(event) {
                    event.stopPropagation();
                });
            });
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.addEventListener('click', function(event) {
                    event.stopPropagation();
                });
            });

            //  SORTING JS (TOGGLE ASC/DESC & PRESERVE SEARCH) 
            document.querySelector(".sort-option[data-sort='desc']").addEventListener("click", function(event) {
                event.preventDefault();

                const currentUrl = new URL(window.location.href);
                let currentSort = currentUrl.searchParams.get('sort');

                let newSort;
                if (currentSort === 'date_desc') {
                    newSort = 'date_asc';
                } else {
                    newSort = 'date_desc';
                }

                currentUrl.searchParams.set('sort', newSort);

                window.location.href = currentUrl.toString();
            });

            // --- Bulk Actions Dropdown Functionality ---

            // Helper function to get IDs of selected checkboxes
            function getSelectedPurchaseRequestIds() {
                const selectedIds = [];
                document.querySelectorAll("#purchaserequesttable tbody .approve-checkbox:checked").forEach(checkbox => {
                    const row = checkbox.closest('tr');
                    if (row) {
                        selectedIds.push(row.dataset.purchaseId);
                    }
                });
                return selectedIds;
            }

            // Reusable function to perform AJAX actions (Approve, Decline, Archive)
            function performBulkAction(actionType, phpScriptUrl, confirmationMessage) {
                const selectedIds = getSelectedPurchaseRequestIds();

                if (selectedIds.length === 0) {
                    alert("Please select at least one purchase request to " + actionType + ".");
                    return;
                }

                if (!confirm(confirmationMessage.replace('{count}', selectedIds.length))) {
                    return;
                }

                fetch(phpScriptUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            ids: selectedIds
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            window.location.reload();
                        } else {
                            alert("Error: " + data.message);
                        }
                    })
                    .catch(error => {
                        console.error(`Error ${actionType} purchase requests:`, error);
                        alert(`An error occurred while trying to ${actionType} the requests.`);
                    });
            }

            // Event listener for Approve Selected
            document.getElementById("bulkApproveBtn").addEventListener("click", function(event) {
                event.preventDefault();
                performBulkAction(
                    'approve',
                    './approvePurchaseRequest.php',
                    'Are you sure you want to approve {count} selected purchase request(s)?'
                );
            });

            // Event listener for Decline Selected
            document.getElementById("bulkDeclineBtn").addEventListener("click", function(event) {
                event.preventDefault();
                performBulkAction(
                    'decline',
                    './declinePurchaseRequest.php',
                    'Are you sure you want to decline {count} selected purchase request(s)?'
                );
            });

            // Event listener for Archive Selected
            document.getElementById("bulkArchiveBtn").addEventListener("click", function(event) {
                event.preventDefault();
                performBulkAction(
                    'archive',
                    './archiveSelectedPurchaseRequests.php',
                    'Are you sure you want to archive {count} selected purchase request(s)?'
                );
            });

            // --- Search Functionality (on Enter Key) ---
            document.getElementById("searchInput").addEventListener("keydown", function(event) {
                // Check if the pressed key is 'Enter'
                if (event.key === 'Enter') {
                    event.preventDefault();

                    const searchTerm = this.value;
                    const currentUrl = new URL(window.location.href);

                    // Set or update the 'search' parameter in the URL
                    if (searchTerm) {
                        currentUrl.searchParams.set('search', searchTerm);
                    } else {
                        currentUrl.searchParams.delete('search');
                    }

                    // Preserve the existing 'sort' parameter if it's present
                    const sortParam = new URLSearchParams(window.location.search).get('sort');
                    if (sortParam) {
                        currentUrl.searchParams.set('sort', sortParam);
                    }

                    // Only redirect if the URL actually changes to avoid unnecessary reloads
                    if (window.location.href !== currentUrl.toString()) {
                        window.location.href = currentUrl.toString();
                    }
                }
            });

            // --- Export Selected to PDF Functionality ---
            document.getElementById("exportPdfBtn").addEventListener("click", function() {
                const selectedIds = [];
                // Use the same checkbox selector as for bulk actions
                document.querySelectorAll("#purchaserequesttable tbody .approve-checkbox:checked").forEach(checkbox => {
                    const row = checkbox.closest('tr');
                    if (row) {
                        selectedIds.push(row.dataset.purchaseId);
                    }
                });

                if (selectedIds.length === 0) {
                    alert("Please select at least one purchase request to export.");
                    return;
                }

                // Create a hidden form to send data via POST
                const form = document.createElement('form');
                form.action = 'exportToPdf.php';
                form.method = 'POST';
                form.target = '_blank';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids';
                input.value = JSON.stringify(selectedIds);

                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            });

            // --- Archive Modal Functionality ---
            const archiveModalElement = document.getElementById('archiveModal');
            if (archiveModalElement) {
                archiveModalElement.addEventListener('show.bs.modal', function() {
                    // When the modal is about to be shown, load archived items
                    loadArchivedItems();
                });
            }

            // --- Function to update the archived items count badge ---
            function updateArchivedCountBadge() {
                fetch('fetchArchivedPurchaseRequests.php')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        const archivedCountBadge = document.getElementById('archivedCountBadge');
                        if (archivedCountBadge) {
                            if (data.success && data.archivedRequests) {
                                const count = data.archivedRequests.length;
                                archivedCountBadge.textContent = count;
                                // Optionally, hide the badge if count is 0
                                if (count === 0) {
                                    archivedCountBadge.closest('.badge').style.display = 'none';
                                } else {
                                    archivedCountBadge.closest('.badge').style.display = '';
                                }
                            } else {
                                console.error('Failed to get archived count:', data.message);
                                archivedCountBadge.textContent = '0';
                                archivedCountBadge.closest('.badge').style.display = 'none';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching archived count for badge:', error);
                        const archivedCountBadge = document.getElementById('archivedCountBadge');
                        if (archivedCountBadge) {
                            archivedCountBadge.textContent = '0';
                            archivedCountBadge.closest('.badge').style.display = 'none';
                        }
                    });
            }

            // Function to load archived purchase requests into the modal
            function loadArchivedItems() {
                const archivedItemsBody = document.getElementById('archivedItemsBody');
                const archiveMessage = document.getElementById('archiveMessage');

                // Display loading spinner inside the table while fetching
                archivedItemsBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-4">
                            <div class="spinner-border text-success" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading archived equipment...</p>
                        </td>
                    </tr>
                `;
                archiveMessage.textContent = '';

                fetch('fetchArchivedPurchaseRequests.php')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            if (data.archivedRequests.length > 0) {
                                archivedItemsBody.innerHTML = '';

                                data.archivedRequests.forEach(pr => {
                                    const row = document.createElement('tr');
                                    row.setAttribute('data-id', pr.id);
                                    row.innerHTML = `
                                        <td><input type="checkbox" class="archived-checkbox" value="${pr.id}"></td>
                                        <td>${pr.pr_number}</td>
                                        <td>${pr.purpose}</td>
                                        <td>${pr.requested_by}</td>
                                        <td>${pr.date_prepared}</td>
                                    `;
                                    archivedItemsBody.appendChild(row);
                                });
                            } else {
                                archivedItemsBody.innerHTML = '<tr><td colspan="5" class="text-center py-4">No archived purchase requests found.</td></tr>';
                            }
                        } else {
                            archivedItemsBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">Error: ${data.message}</td></tr>`;
                            console.error('Error fetching archived requests:', data.message);
                        }
                    })
                    .catch(error => {
                        archivedItemsBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">Error loading archived requests. Please try again.</td></tr>`;
                        console.error('Fetch error:', error);
                    });
            }

            // --- Select All Archived Checkboxes (for table header) ---
            const selectAllArchivedCheckbox = document.getElementById('selectAllArchived');
            if (selectAllArchivedCheckbox) {
                selectAllArchivedCheckbox.addEventListener('change', function() {
                    const archivedCheckboxes = document.querySelectorAll('#archivedRequestsTable .archived-checkbox');
                    archivedCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                });
            }

            // --- Restore Archived Items ---
            document.getElementById('restore-archived-btn').addEventListener('click', function() {
                const selectedArchivedIds = Array.from(document.querySelectorAll('#archiveModal .archived-checkbox:checked')).map(cb => cb.value);

                if (selectedArchivedIds.length === 0) {
                    alert('Please select at least one item to restore.');
                    return;
                }

                if (confirm(`Are you sure you want to restore ${selectedArchivedIds.length} selected item(s)?`)) {
                    fetch('restoreArchivedPurchaseRequests.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                ids: selectedArchivedIds
                            })
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok ' + response.statusText);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                alert(data.message);
                                loadArchivedItems();
                                window.location.reload();
                            } else {
                                alert('Error: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error restoring items:', error);
                            alert('An error occurred while trying to restore the items.');
                        });
                }
            });

            // --- Delete Archived Items ---
            document.getElementById('delete-archived-btn').addEventListener('click', function() {
                const selectedArchivedIds = Array.from(document.querySelectorAll('#archiveModal .archived-checkbox:checked')).map(cb => cb.value);

                if (selectedArchivedIds.length === 0) {
                    alert('Please select at least one item to delete permanently.');
                    return;
                }

                if (confirm(`Are you sure you want to PERMANENTLY DELETE ${selectedArchivedIds.length} selected item(s)? This action cannot be undone.`)) {
                    fetch('deleteArchivedPurchaseRequests.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                ids: selectedArchivedIds
                            })
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok ' + response.statusText);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                alert(data.message);
                                loadArchivedItems();
                            } else {
                                alert('Error: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error deleting items:', error);
                            alert('An error occurred while trying to delete the items.');
                        });
                }
            });

            // Calling the function to update the badge count
            updateArchivedCountBadge();

        }); // CLOSING TAG FOR document.addEventListener("DOMContentLoaded", function()

        // This event listener should be OUTSIDE the DOMContentLoaded for modals that might be shown multiple times
        // Add this new event listener
        const editPurchaseRequestModalElement = document.getElementById('editPurchaseRequestModal');
        if (editPurchaseRequestModalElement) {
            editPurchaseRequestModalElement.addEventListener('hidden.bs.modal', function() {
                // Ensure the modal-backdrop is fully removed
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
                // Ensure the modal-open class is removed from the body
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
            });
        }
    </script>
</body>

</html>