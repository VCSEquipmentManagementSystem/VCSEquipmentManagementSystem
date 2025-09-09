<?php
//s SupplierInformation.php
include './database.php';
include './readSupplier.php';
session_start();
if (isset($_SESSION['success'])) {
    echo "<script>alert('" . addslashes($_SESSION['success']) . "');</script>";
    unset($_SESSION['success']);
}
// counting for view archives
$archiveCount = 0;

$query = "SELECT COUNT(*) as total FROM archived_supplier_tbl";
$result = mysqli_query($connection, $query);

if ($row = mysqli_fetch_assoc($result)) {
    $archiveCount = $row['total'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Supplier Information</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap");

        body {
            background-color: #f0f2f5;
            font-family: "Poppins", sans-serif;
        }

        .navbar-toggler:focus {
            outline: none;
            box-shadow: none;
            border-style: none;
        }

        img {
            height: 50px;
            width: 50px;
        }

        .offcanvas {
            --bs-offcanvas-width: 280px;
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

        .form-control {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.10);
        }
    </style>
</head>

<body>
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
                        Supplier Information
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
        <!-- Header -->
        <div class="row container-header mt-5">
            <!-- <div class="title-header d-flex justify-content-between">
                <h3 class="mb-4 fw-bold mt-3">
                    Supplier Information
                </h3>
            </div> -->
            <?php
            if (isset($_SESSION['upload_messages']) && !empty($_SESSION['upload_messages'])) {
                $totalSuccess = $_SESSION['upload_success_count'] ?? 0;
                $totalErrors = $_SESSION['upload_error_count'] ?? 0;

                $alertClass = 'alert-danger';
                $alertHeading = 'Upload Failed!';

                if ($totalErrors === 0 && $totalSuccess > 0) {
                    $alertClass = 'alert-success';
                    $alertHeading = 'Upload Complete!';
                } elseif ($totalSuccess > 0 && $totalErrors > 0) {
                    $alertClass = 'alert-warning';
                    $alertHeading = 'Upload Finished with Issues!';
                } elseif ($totalSuccess === 0 && $totalErrors > 0) {
                    $alertClass = 'alert-danger';
                    $alertHeading = 'Upload Failed!';
                }

                echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
                echo '<strong>' . $alertHeading . '</strong><br>';
                echo '<ul>';
                foreach ($_SESSION['upload_messages'] as $msg) {
                    echo '<li>' . htmlspecialchars($msg) . '</li>';
                }
                echo '</ul>';
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                echo '</div>';

                // Clear the session messages after displaying them
                unset($_SESSION['upload_messages']);
                unset($_SESSION['upload_success_count']);
                unset($_SESSION['upload_error_count']);
            }
            ?>
            <div class="d-flex justify-content-end mt-3">
                <div class="search-container me-2">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control search-input" id="supplierSearchInput" placeholder="Search">
                </div>
                <div class="justify-content-end mb-3">
                    <!-- Sorting -->
                    <button class="btn btn-outline-secondary me-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-arrow-down-wide-short"></i>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                    </ul>
                    <button name="view_archived" class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#ViewArchivedModal">
                        <i class="fa-solid fa-box-archive"></i>
                        <span class="badge bg-danger rounded-pill archive-badge" id="archiveCount">
                            <?php echo $archiveCount; ?>
                        </span>
                    </button>
                    <!-- View Archive -->
                    <div class="modal fade" id="ViewArchivedModal" data-bs-backdrop="static" name="view_archived" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <form action="deleteArchiveSupplier.php" method="post">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Archived</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="table-responsive overflow-auto" style="max-height: 400px;">
                                            <table class="table text-center" id="ArchivedSupplierTable">
                                                <thead>
                                                    <tr>
                                                        <th><input type="checkbox" id="selectAllArchived" class="form-check-input"></th>
                                                        <th>Company Name</th>
                                                        <th>Product/Service</th>
                                                        <th>Contact Person</th>
                                                        <th>Contact No.</th>
                                                        <th>Email</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($archiveResults = mysqli_fetch_array($sqlArchivedSupplier)) { ?>
                                                        <tr>
                                                            <td>
                                                                <input type="checkbox" class="form-check-input" name="selectedIds[]" value="<?php echo $archiveResults['supplier_id']; ?>">
                                                            </td>
                                                            <td><?php echo htmlspecialchars($archiveResults['supplier_comp_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($archiveResults['product_service']); ?></td>
                                                            <td><?php echo htmlspecialchars($archiveResults['contact_person']); ?></td>
                                                            <td><?php echo htmlspecialchars($archiveResults['sup_contact_num']); ?></td>
                                                            <td><?php echo htmlspecialchars($archiveResults['sup_email']); ?></td>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
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

                    <!-- Button trigger Add Supplier Modal -->
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#staticBackdrop" data-bs-toggle="tooltip" data-bs-placement="top" title="Add Supplier">
                        <i class="fas fa-plus"></i>
                    </button>
                    <!-- Modal -->
                    <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false"
                        tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-success">
                                    <h5 class="modal-title fw-bold text-white" id="exampleModalLabel">+ Add new Supplier</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                                    </button>
                                </div>
                                <!-- Inputs -->
                                <form action="createSupplier.php" method="post">
                                    <div class="modal-body">
                                        <div class="row">
                                            <!-- Company Name -->
                                            <label for="companyNameInput" class="form-label fw-bold">Company Name:<span class="text-danger">*</span></label>
                                            <input type="text" name="companyNameInput" class="form-control" id="companyNameInput" required>
                                            <!-- Product/Service -->
                                            <label for="productServiceInput" class="form-label fw-bold">Product/Service:<span class="text-danger">*</span></label>
                                            <input type="text" name="productServiceInput" class="form-control" id="productServiceInput" required>
                                            <!-- Contact Person -->
                                            <label for="contactPersonInput" class="form-label fw-bold">Contact Person:<span class="text-danger">*</span></label>
                                            <input type="text" name="contactPersonInput" class="form-control" id="contactPersonInput" required>
                                            <!-- Supplier Contact Number -->
                                            <label for="contactNoInput" class="form-label fw-bold">Contact No.:<span class="text-danger">*</span></label>
                                            <input type="text" name="contactNoInput" class="form-control" id="contactNoInput" required>
                                            <!-- Email -->
                                            <label for="emailInput" class="form-label fw-bold">Email:<span class="text-danger">*</span></label>
                                            <input type="email" name="emailInput" class="form-control" id="emailInput" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer justify-content-between">
                                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#batchUploadModal">
                                            <i class="fas fa-file-upload"></i> Upload CSV
                                        </button>
                                        <div class="">
                                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-success">Add</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- Batch upload CSV -->
                    <div class="modal fade" id="batchUploadModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="batchUploadModal" aria-hidden="true">
                        <div class="modal-dialog modal-md">
                            <div class="modal-content">
                                <div class="modal-header bg-success">
                                    <h5 class="modal-title fw-bold text-white" id="batchUploadModal">Batch Upload Supplier</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form action="batchUploadSupplier.php" method="post" enctype="multipart/form-data">
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="csvFileInput" class="form-label
                                                fw-bold">Select CSV File:<span class="text-danger">*</span></label>
                                            <input type="file" name="csvFile" class="form-control" id="csvFileInput" accept=".csv" required>
                                            <div class="form-text">Upload Excel (.xlsx, .xls) or CSV file</div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" name="submit" class="btn btn-success">Upload CSV</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Supplier Information Table -->
        <div class="table-container">
            <div class="table-header">
                <div class="table-responsive overflow-auto" style="max-height: 450px;">
                    <table class="table text-center" id="SupplierInformationTable">
                        <thead>
                            <tr>
                                <th>Company Name</th>
                                <th>Contact Person</th>
                                <th>Contact No.</th>
                                <th>Email</th>
                                <th>Product/Service</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Reset pointer if needed
                            mysqli_data_seek($sqlSupplier, 0);
                            while ($supplierResults = mysqli_fetch_array($sqlSupplier)) {
                                $modalId = 'editSupplier' . $supplierResults['supplier_id'];
                            ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($supplierResults['supplier_comp_name']); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($supplierResults['sup_contact_num']); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($supplierResults['contact_person']); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($supplierResults['sup_email']); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($supplierResults['product_service']); ?>
                                    </td>
                                    <td class="d-flex justify-content-center">
                                        <!-- Edit Supplier Button -->
                                        <button type="button" class="action-button" data-bs-toggle="modal" data-bs-target="#<?php echo $modalId; ?>">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </button>
                                        <form action="archiveSupplier.php" method="post">
                                            <input type="hidden" name="deleteId" value="<?php echo $supplierResults['supplier_id']; ?>">
                                            <button type="submit" class="action-button" name="delete">
                                                <i class="fa-solid fa-box-archive"></i> Archive
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Edit Supplier Modal -->
                                <div class="modal fade" id="<?php echo $modalId; ?>" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="<?php echo $modalId; ?>Label" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold" id="<?php echo $modalId; ?>Label">Edit Supplier Information</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="updateSupplier.php" method="post">
                                                <div class="modal-body">
                                                    <input type="hidden" name="supplier_id" value="<?php echo $supplierResults['supplier_id']; ?>">
                                                    <div class="row">
                                                        <!-- Company Name -->
                                                        <label for="editCompanyNameInput<?php echo $supplierResults['supplier_id']; ?>" class="form-label fw-bold">Company Name:<span class="text-danger">*</span></label>
                                                        <input type="text" name="editCompanyName" value="<?php echo htmlspecialchars($supplierResults['supplier_comp_name']); ?>" class="form-control" id="editCompanyNameInput<?php echo $supplierResults['supplier_id']; ?>" required>
                                                        <!-- Product/Service -->
                                                        <label for="editProductServiceInput<?php echo $supplierResults['supplier_id']; ?>" class="form-label fw-bold">Product/Service:<span class="text-danger">*</span></label>
                                                        <input type="text" name="editProductService" value="<?php echo htmlspecialchars($supplierResults['product_service']); ?>" class="form-control" id="editProductServiceInput<?php echo $supplierResults['supplier_id']; ?>" required>
                                                        <!-- Contact Person -->
                                                        <label for="editContactPersonInput<?php echo $supplierResults['supplier_id']; ?>" class="form-label fw-bold">Contact Person:<span class="text-danger">*</span></label>
                                                        <input type="text" name="editContactPerson" value="<?php echo htmlspecialchars($supplierResults['contact_person']); ?>" class="form-control" id="editContactPersonInput<?php echo $supplierResults['supplier_id']; ?>" required>
                                                        <!-- Supplier Contact Number -->
                                                        <label for="editContactNoInput<?php echo $supplierResults['supplier_id']; ?>" class="form-label fw-bold">Contact No.:<span class="text-danger">*</span></label>
                                                        <input type="text" name="editSupplierContactNum" value="<?php echo htmlspecialchars($supplierResults['sup_contact_num']); ?>" class="form-control" id="editContactNoInput<?php echo $supplierResults['supplier_id']; ?>" required>
                                                        <!-- Email -->
                                                        <label for="editEmailInput<?php echo $supplierResults['supplier_id']; ?>" class="form-label fw-bold">Email:</label>
                                                        <input type="email" name="editEmail" value="<?php echo htmlspecialchars($supplierResults['sup_email']); ?>" class="form-control" id="editEmailInput<?php echo $supplierResults['supplier_id']; ?>" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <form action="updateSupplier.php" method="post">
                                                        <input type="hidden" name="supplier_id" value="<?= $supplierResults['supplier_id'] ?>">
                                                        <button type="submit" class="btn btn-success">Save</button>
                                                    </form>
                                                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('selectAllArchived').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="selectedIds[]"]');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        document.getElementById('supplierSearchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#SupplierInformationTable tbody tr');

            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                let matchFound = false;

                // Loop through each cell in the row
                cells.forEach(cell => {
                    if (cell.textContent.toLowerCase().includes(searchValue)) {
                        matchFound = true;
                    }
                });

                // Toggle row visibility based on match
                row.style.display = matchFound ? '' : 'none';
            });
        });
    </script>
</body>

</html>