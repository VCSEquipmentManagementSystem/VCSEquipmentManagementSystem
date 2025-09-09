<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Manage Users</title>
</head>
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
                        <img src="LOGO.png" data-bs-toggle="tooltip" data-bs-placement="top" title="Logo" alt="Viking Logo" style="width: 30px; height: 30px;">
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
                    Manage User
                </h3>
            </div>
        </div>
        <div class="d-flex justify-content-between mb-3">
            <div class="search-container me-2">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control search-input" placeholder="Search">
            </div>
            <div class="justify-content-end mb-3">
                <button class="btn btn-outline-secondary me-2">
                    <i class="fa-solid fa-arrow-down-wide-short"></i>
                </button>
                <button class="btn btn-outline-secondary me-2">
                    <i class="fa-solid fa-box-archive"></i>
                </button>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                    <i class="fas fa-plus me-1"></i> Add User
                </button>
                <div class="modal fade" id="staticBackdrop" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdrop" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title fw-bold" id="staticBackdropLabel">Add user</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                                </button>
                            </div>
                            <div class="modal-body d-flex flex-column">
                                <!-- Add Name -->
                                <label for="AddNameInput" class="form-label fw-bold">Name:</label>
                                <input type="text" class="form-control" id="AddNameInput">
                                <!-- Add Employee ID -->
                                <label for="AddEmployeeIdInput" class="form-label fw-bold">Employee ID</label>
                                <input type="text" class="form-control" id="AddEmployeeIdInput">
                                <!-- Add Role -->
                                <label for="AddRoleInput" class="form-label fw-bold">Role:</label>
                                <input type="text" class="form-control" id="AddRoleInput">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-success" data-bs-dismiss="modal">Add</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-container">
            <div class="table-header">
                <div class="table-responsive">
                    <table class="table text-center" id="SupplierInformationTable">
                        <thead>
                            <tr>
                                <th>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                        Name
                                    </div>
                                </th>
                                <th>Employee ID</th>
                                <th>Role</th>
                                <th>Last Login</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <img src="dummy_pic.jpg" alt="logo" class="rounded-circle">
                                    <span>Aliya A. Baay</span>
                                </td>
                                <td>-VCS-ML0-007-2027</td>
                                <td>Bossing</td>
                                <td>2024-12-05 10:45 AM</td>
                                <td>
                                    <button class="action-button" id="ellipsisDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="ellipsisDropdown">
                                        <!-- Edit Equipment Modal -->
                                        <li><a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#staticBackdrop2">Edit</a></li>
                                        <li><a href="#" class="dropdown-item">Archive</a></li>
                                    </ul>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>