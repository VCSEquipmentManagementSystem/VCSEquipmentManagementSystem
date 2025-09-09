<?php
require('../database.php');
require('./readInventory.php');
require_once('./inventory_func.php');

// Fetch available equipment_id values from the database
$equipmentOptions = "";
$equipmentQuery = "SELECT custom_equip_id FROM equip_tbl";
$equipmentResult = mysqli_query($connection, $equipmentQuery);

while ($row = mysqli_fetch_assoc($equipmentResult)) {
    $custom_equip_id = $row['custom_equip_id'];
    $equipmentOptions .= "<option value='$custom_equip_id'>$custom_equip_id</option>";
}

// Define the stock status function
function getStockStatus($stock_quantity)
{
    // Define thresholds
    $out_of_stock_threshold = 0;
    $low_stock_threshold = 10;

    if ($stock_quantity == 0) {  
        return [
            'text' => 'out of stock',
            'class' => 'bg-danger',
            'action' => 'none'
        ];
    } else if ($stock_quantity <= $low_stock_threshold) {
        return [
            'text' => 'low stock',
            'class' => 'bg-warning text-dark',
            'action' => 'warn'
        ];
    } else {
        return [
            'text' => 'in stock',
            'class' => 'bg-success',
            'action' => 'none'
        ];
    }
}

// Fetch archived items from archived_inventory_tbl instead
$archivedItems = array();
$archivedQuery = "SELECT ai.*, ip.part_name, b.brand_name as brand 
                 FROM archived_inventory_tbl ai
                 LEFT JOIN inventory_parts_tbl ip ON ai.part_id = ip.part_id
                 LEFT JOIN brand_tbl b ON ip.brand_id = b.brand_id";
$archivedResult = mysqli_query($connection, $archivedQuery);

if ($archivedResult) {
    while ($row = mysqli_fetch_assoc($archivedResult)) {
        $archivedItems[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <title>Inventory</title>
</head>
<style>
    @import url("https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap");

    body {
        background-color: #f0f2f5;
        font-family: "Poppins", sans-serif;
    }

    .offcanvas {
        --bs-offcanvas-width: 280px;
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

    /* .archive-badge {
        position: relative;
        top: -8px;
        right: -5px;
        font-size: 9px;
    } */

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
        margin-right: 8px !important;
    }

    .restore-btn:hover {
        color: #0d6efd;
    }

    .delete-archived-item {
        color: #dc3545 !important; 
        background-color: transparent !important;
        border: none;
        cursor: pointer;
    }

    .delete-archived-item:hover {
        color: #bb2d3b !important;
        background-color: rgba(220, 53, 69, 0.1) !important;
    }



</style>

<body>
    <div class="container mt-4">
        <!-- Navbar -->
        <nav class="navbar bg-success fixed-top">
            <div class="container-fluid">
                <div>
                    <button class="navbar-toggler bg-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarNav" aria-controls="sidebarNav">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <a class="navbar-brand fw-bold text-white" href="#">
                        <img src="../LOGO.png" data-bs-toggle="tooltip" data-bs-placement="top" title="Logo" alt="Viking Logo" style="width: 30px; height: 30px;">
                        Viking Construction & Supplies
                    </a>
                </div>
                <!-- Sidebar Navigation -->
                <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarNav" aria-labelledby="sidebarNavLabel">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title fw-bold" id="sidebarNavLabel">
                            <img src="../LOGO.png" alt="Viking Logo" style="width: 50px; height: 50px;">
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
        <div class="row mb-4 mt-5">
            <div class="title-header d-flex justify-content-between">
                <h3 class="mb-4 fw-bold mt-3">Equipment Inventory</h3>
            </div>
        </div>
        <div class="d-flex justify-content-between mb-3">
            <div class="search-container me-2">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control search-input" id="searchInput" placeholder="Search">
            </div>
            <div class="justify-content-end mb-3">
                <!-- Archive Item -->
                <button class="btn btn-outline-secondary me-2" id="archiveAllBtn">
                    <i class="fa-solid fa-box-archive"></i>Archive  
                </button>
                <!-- Filter -->
                <button class="btn btn-outline-secondary me-2" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                    <li><a href="#" class="dropdown-item filter-option" data-filter="all">All</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a href="#" class="dropdown-item filter-option" data-filter="partId">Filter by Part ID</a></li>
                    <li><a href="#" class="dropdown-item filter-option" data-filter="partName">Filter by Part Name</a></li>
                    <li><a href="#" class="dropdown-item filter-option" data-filter="brand">Filter by Brand</a></li>
                    <li><a href="#" class="dropdown-item filter-option" data-filter="stock">Filter by Stock</a></li>
                </ul>
                <!-- Sort -->
                <button class="btn btn-outline-secondary me-2" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-arrow-down-wide-short"></i> Sort
                </button>
                <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                    <li><a href="#" class="dropdown-item sort-option" data-sort="asc">Sort by A-Z</a></li>
                    <li><a href="#" class="dropdown-item sort-option" data-sort="desc">Sort by Z-A</a></li>
                </ul>
                <!-- Archived Item -->
                <button class="btn btn-outline-secondary me-2 toggle-archive-btn" id="toggleArchiveBtn">
                    <i class="fa-solid fa-box-archive"></i>View Archived
                    <span class="badge bg-danger rounded-pill archive-badge" id="archiveCount"><?php echo count($archivedItems); ?></span>
                </button>
                <!-- Button trigger modal -->
                <button class="btn btn-add btn-success" type="button" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                    <i class="fas fa-plus me-1"></i> Add Parts
                </button>
                <!-- Modal -->
                <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" 
                    aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title fw-bold" id="staticBackdropLabel">Add Parts</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                                </button>
                            </div>
                            <!-- Modal form fields -->
                            <form class="createInventory-main" action="createInventory.php" method="post">
                                <div class="modal-body">
                                    <!-- Part Name -->
                                    <label for="PartName" class="form-label fw-bold">Part Name:</label>
                                    <input type="text" name="PartNameInput" class="form-control" id="PartName" required>

                                    <!-- Part Number -->
                                    <label for="PartNumber" class="form-label fw-bold">Part No.:</label>
                                    <input type="text" name="PartNumberInput" class="form-control" id="PartNumber" required>
                                    
                                    <!-- Stock Quantity -->
                                    <label for="Stock" class="form-label fw-bold">Stock Quantity:</label>
                                    <input type="number" name="StockInput" class="form-control" id="Stock" required>

                                    <!-- Unit Price -->
                                    <label for="UnitPrice" class="form-label fw-bold">Unit Price:</label>
                                    <input type="number" name="UnitPriceInput" class="form-control" id="UnitPrice" step="0.01" required>

                                    <!-- Brand -->
                                    <div class="form-group">
                                        <label for="BrandInput" class="form-label fw-bold">Brand:</label>
                                        <input type="text" id="BrandInput" name="BrandInput" class="form-control" autocomplete="off" required>
                                    </div>
                                    
                                    <!-- Supplier -->
                                    <div class="form-group">
                                        <label for="SupplierInput" class="form-label fw-bold">Supplier:</label>
                                        <input type="text" id="SupplierInput" name="SupplierInput" class="form-control" autocomplete="off" required>
                                    </div>
                                    
                                    <!-- Equipment ID -->
                                    <label for="EquipmentIdInput" class="form-label fw-bold">Equipment:</label>
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
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" name="add" class="btn btn-success">Add</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Output Inv Table -->
        <div class="table-container">
            <div class="table-header">
                <div class="table-responsive">
                    <table class="table text-center" id="equipmentTable">
                        <thead>
                            <tr>
                                <th scope="col"><input type="checkbox" id="selectAll"></th>
                                <th scope="col">Part ID</th>
                                <th scope="col">Part Name</th>
                                <th scope="col">Brand</th>
                                <th scope="col">Part No.</th>
                                <th scope="col">Stock Quantity</th>
                                <th scope="col">Status</th>
                                <th scope="col">Supplier</th>
                                <th scope="col">Unit Price</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($inventoryResults = mysqli_fetch_array($sqlInventory)) {
                                $part_id = htmlspecialchars($inventoryResults['part_id'] ?? '', ENT_QUOTES, 'UTF-8');
                                $part_name = htmlspecialchars($inventoryResults['part_name'] ?? '', ENT_QUOTES, 'UTF-8');
                                $part_number = htmlspecialchars($inventoryResults['part_number'] ?? '', ENT_QUOTES, 'UTF-8');
                                $brand_name = htmlspecialchars($inventoryResults['brand_name'] ?? '', ENT_QUOTES, 'UTF-8');
                                $stock_quantity = htmlspecialchars($inventoryResults['stock_quantity'] ?? '0', ENT_QUOTES, 'UTF-8');
                                $supplier_id = htmlspecialchars($inventoryResults['supplier_id'] ?? '', ENT_QUOTES, 'UTF-8');
                            ?>
                                <tr>
                                    <td><input type="checkbox" class="row-checkbox"></td>
                                    <td><?php echo $part_id; ?></td>
                                    <td><?php echo $part_name; ?></td>
                                    <td><?php echo $brand_name; ?></td>
                                    <td><?php echo $part_number; ?></td>
                                    <td><?php echo $stock_quantity; ?></td>
                                    <td>
                                        <?php
                                        $status = getStockStatus($stock_quantity);
                                        ?>
                                        <span class="badge <?php echo $status['class']; ?>">
                                            <?php echo $status['text']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $supplierCompName = '';
                                        if ($supplier_id) {
                                            $suppQuery = "SELECT supplier_comp_name FROM supplier_tbl WHERE supplier_id = ?";
                                            $stmt = mysqli_prepare($connection, $suppQuery);
                                            if ($stmt) {
                                                mysqli_stmt_bind_param($stmt, "i", $supplier_id);
                                                mysqli_stmt_execute($stmt);
                                                $suppResult = mysqli_stmt_get_result($stmt);
                                                if ($suppRow = mysqli_fetch_assoc($suppResult)) {
                                                    $supplierCompName = htmlspecialchars($suppRow['supplier_comp_name']);
                                                }
                                                mysqli_stmt_close($stmt);
                                            }
                                        }
                                        echo $supplierCompName ?: 'N/A';
                                        ?>
                                    </td>
                                    <td>₱<?php echo number_format($inventoryResults['unit_price'] ?? 0, 2); ?></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn" type="button" data-bs-toggle="dropdown">
                                                <i class="fa-solid fa-ellipsis-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a href="#" class="dropdown-item view-description" 
                                                       data-bs-toggle="modal"
                                                       data-bs-target="#descriptionModal"
                                                       data-part-id="<?php echo $part_id; ?>"
                                                       data-part-name="<?php echo htmlspecialchars($part_name); ?>"
                                                       data-part-number="<?php echo htmlspecialchars($part_number); ?>"
                                                       data-brand="<?php echo htmlspecialchars($brand_name); ?>"
                                                       data-supplier-id="<?php echo $supplier_id; ?>"
                                                       data-stock="<?php echo $stock_quantity; ?>"
                                                       data-unit-price="<?php echo htmlspecialchars($inventoryResults['unit_price']); ?>"
                                                       data-equipment-id="<?php echo htmlspecialchars($inventoryResults['custom_equip_id']); ?>">
                                                        <i class="fas fa-eye me-2"></i>View
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="#" class="dropdown-item edit-btn"
                                                       data-bs-toggle="modal"
                                                       data-bs-target="#editModal"
                                                       data-part-id="<?php echo $part_id; ?>"
                                                       data-part-name="<?php echo htmlspecialchars($part_name); ?>"
                                                       data-part-number="<?php echo htmlspecialchars($part_number); ?>"
                                                       data-brand="<?php echo htmlspecialchars($brand_name); ?>"
                                                       data-supplier="<?php echo htmlspecialchars($supplier_comp_name ?? ''); ?>"
                                                       data-stock="<?php echo $stock_quantity; ?>"
                                                       data-unit-price="<?php echo htmlspecialchars($inventoryResults['unit_price']); ?>"
                                                       data-equipment-id="<?php echo htmlspecialchars($inventoryResults['custom_equip_id']); ?>">
                                                        <i class="fas fa-edit me-2"></i>Edit
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Archived Items Table -->
        <div class="archived-table-container" id="archivedTableContainer" style="display: none;">
            <div class="archived-header">
                <h4 class="mb-0"><i class="fa-solid fa-box-archive me-2"></i>Archived Items</h4>
                <button type="button" class="btn-close" id="closeArchivedTable"></button>
            </div>
            <div class="table-responsive">
                <table class="table text-center" id="archivedItemsTable">
                    <thead>
                        <tr>
                            <th scope="col">Part ID</th>
                            <th scope="col">Part Name</th>
                            <th scope="col">Brand</th>
                            <th scope="col">Part No.</th>
                            <th scope="col">Stock Quantity</th>
                            <th scope="col">Supplier</th>
                            <th scope="col">Unit Price</th>
                            <th scope="col">Archive Date</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($archivedItems as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['part_id']); ?></td>
                                <td><?php echo htmlspecialchars($item['part_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['brand_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['part_number']); ?></td>
                                <td><?php echo htmlspecialchars($item['stock_quantity']); ?></td>
                                <td><?php echo htmlspecialchars($item['supplier_comp_name']); ?></td>
                                <td>₱<?php echo number_format($item['unit_price'] ?? 0, 2); ?></td>
                                <td><?php echo htmlspecialchars($item['archive_date']); ?></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn" type="button" id="ellipsisDropdown_<?php echo $item['part_id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="ellipsisDropdown_<?php echo $item['part_id']; ?>">
                                            <li>
                                                <button class="dropdown-item restore-btn" data-part-id="<?php echo $item['part_id']; ?>">
                                                    <i class="fas fa-undo-alt me-2"></i>Restore
                                                </button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item text-danger delete-archived-item" data-part-id="<?php echo $item['part_id']; ?>">
                                                    <i class="fas fa-trash-alt me-2"></i>Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($archivedItems)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-3">No archived items found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
                        
        
        <!-- Description Modal -->
        <div class="modal fade" id="descriptionModal" tabindex="-1" aria-labelledby="descriptionModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="descriptionModalLabel">Description</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p id="modalPartID" class="fw-bold">Part ID:
                            <span class="fw-normal text-center modal-content-value"></span>
                        </p>
                        <p id="modalPartName" class="fw-bold">Part Name:
                            <span class="fw-normal modal-content-value"></span>
                        </p>
                        <p id="modalPartNumber" class="fw-bold">Part No.:
                            <span class="fw-normal modal-content-value"></span>
                        </p>
                        <p id="modalBrand" class="fw-bold">Brand:
                            <span class="fw-normal modal-content-value"></span>
                        </p>
                        <p id="modalStockQuantity" class="fw-bold">Stock Quantity:
                            <span class="fw-normal modal-content-value"></span>
                        </p>
                        <p id="modalUnitPrice" class="fw-bold">Unit Price:
                            <span class="fw-normal modal-content-value"></span>
                        </p>
                        <p id="modalEquipmentID" class="fw-bold">Equipment:
                            <span class="fw-normal modal-content-value"></span>
                        </p>
                        <p id="modalSupplier" class="fw-bold">Supplier:
                            <span class="fw-normal modal-content-value"></span>
                        </p>
                        <p id="modalStatus" class="fw-bold">Status:
                            <span class="fw-normal modal-content-value"></span>
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="editModalLabel">Edit Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="updateInventory.php" method="post">
                        <div class="modal-body">
                            <!-- Hidden ID field for the primary key -->
                            <input type="hidden" id="originalPartId" name="original_part_id">

                            <!-- Edit Part ID -->
                            <!-- <label for="editPartId" class="form-label fw-bold">Part ID:</label>
                            <input type="text" name="PartId" class="form-control" id="editPartId"> -->

                            <!-- Edit Part Name -->
                            <label for="editPartName" class="form-label fw-bold">Part Name:</label>
                            <input type="text" name="PartName" class="form-control" id="editPartName">

                            <!-- Edit Part Number -->
                            <div class="mb-3">
                                <label for="editPartNumber" class="form-label fw-bold">Part No.:</label>
                                <input type="text" class="form-control" id="editPartNumber" name="PartNumber">
                            </div>

                            <!-- Edit Brand -->
                            <div class="form-group">
                                <label for="editBrand" class="form-label fw-bold">Brand:</label>
                                <input type="text" id="editBrand" name="BrandInput" class="form-control" autocomplete="off">
                            </div>

                            <!-- Edit Stock -->
                            <label for="editStock" class="form-label fw-bold">Stock:</label>
                            <input type="number" name="Stock" class="form-control" id="editStock" min="0" step="1" 
                                onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                                required>

                            <!-- Edit Supplier ID -->
                            <div class="form-group">
                                <label for="editSupplier" class="form-label fw-bold">Supplier:</label>
                                <input type="text" id="editSupplier" name="SupplierInput" class="form-control" autocomplete="off">
                            </div>

                            <!-- Unit Price -->
                            <label for="editUnitPrice" class="form-label fw-bold">Unit Price:</label>
                            <input type="text" name="UnitPrice" class="form-control" id="editUnitPrice">

                            <!-- Equipment ID -->
                            <label for="editEquipmentId" class="form-label fw-bold">Equipment:</label>
                            <select name="EquipmentId" id="editEquipmentId" class="form-select">
                                <option value="">Select Equipment</option>
                                <?php
                                mysqli_data_seek($equipmentResult, 0); // Reset result pointer
                                while ($row = mysqli_fetch_assoc($equipmentResult)) {
                                    $custom_equip_id = htmlspecialchars($row['custom_equip_id']);
                                    echo "<option value='$custom_equip_id'>$custom_equip_id</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="update" class="btn btn-success">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
    // Form elements
    const addPartsForm = document.querySelector('.createInventory-main');
    const editForm = document.querySelector('#editModal form');
    const selectAllCheckbox = document.getElementById('selectAll');
    const rowCheckboxes = document.getElementsByClassName('row-checkbox');
    const archiveAllBtn = document.getElementById('archiveAllBtn');
    const toggleArchiveBtn = document.getElementById('toggleArchiveBtn');
    const archivedTableContainer = document.getElementById('archivedTableContainer');
    const closeArchivedTable = document.getElementById('closeArchivedTable');
    const searchInput = document.getElementById('searchInput');
    const editButtons = document.querySelectorAll('.edit-btn');

    // Add Parts Form Submission
    if (addPartsForm) {
        addPartsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate required fields - UPDATED: Brand is no longer required
            const requiredFields = [
                { field: 'PartNameInput', label: 'Part Name' },
                { field: 'PartNumberInput', label: 'Part Number' },
                { field: 'StockInput', label: 'Stock Quantity' },
                { field: 'UnitPriceInput', label: 'Unit Price' },
                { field: 'EquipmentIdInput', label: 'Equipment' }
            ];
            
            // Check each required field
            for (const field of requiredFields) {
                const input = this.querySelector(`[name="${field.field}"]`);
                if (!input || !input.value.trim()) {
                    alert(`${field.label} is required`);
                    if (input) input.focus();
                    return;
                }
            }
            
            // Validate stock quantity
            const stockInput = this.querySelector('[name="StockInput"]');
            const stockValue = stockInput.value;
            // Check if it's a valid integer
            if (!Number.isInteger(Number(stockValue)) || stockValue.includes('.') || stockValue.includes(',')) {
                alert('Please enter a valid whole number (0 or greater) for Stock Quantity');
                stockInput.focus();
                return;
            }
            
            // Check for negative numbers only
            if (Number(stockValue) < 0) {
                alert('Stock quantity cannot be negative. Please enter 0 or a greater number.');
                stockInput.focus();
                return;
            }
            
            // Validate unit price
            const priceInput = this.querySelector('[name="UnitPriceInput"]');
            const priceValue = priceInput.value;
            if (isNaN(priceValue) || Number(priceValue) <= 0) {
                alert('Please enter a valid price greater than 0');
                priceInput.focus();
                return;
            }
            
            const formData = new FormData(this);
            fetch('createInventory.php', {
                method: 'POST',
                body: formData
            })
            .then(async response => {
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Server response:', text);
                    throw new Error('Invalid server response');
                }
            })
            .then(data => {
                if (data.success) {
                    alert('Part added successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to add part'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the part. Please check the console for details.');
            });
        });
    }

    // Search Functionality
    if (searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            const searchValue = e.target.value.toLowerCase();
            const tableRows = document.querySelectorAll('#equipmentTable tbody tr');
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            });
        });
    }

    // Show Description Modal
    document.querySelectorAll('.view-description').forEach(button => {
        button.addEventListener('click', function() {
            // Get data from button attributes
            const partId = this.dataset.partId;
            const partName = this.dataset.partName;
            const partNumber = this.dataset.partNumber;
            const brand = this.dataset.brand;
            const stock = this.dataset.stock;
            const unitPrice = this.dataset.unitPrice;
            const equipmentId = this.dataset.equipmentId;

            // Update modal content
            document.querySelector('#modalPartID .modal-content-value').textContent = partId;
            document.querySelector('#modalPartName .modal-content-value').textContent = partName;
            document.querySelector('#modalPartNumber .modal-content-value').textContent = partNumber;
            document.querySelector('#modalBrand .modal-content-value').textContent = brand;
            document.querySelector('#modalStockQuantity .modal-content-value').textContent = stock;
            document.querySelector('#modalUnitPrice .modal-content-value').textContent =
             `₱${parseFloat(unitPrice).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            document.querySelector('#modalEquipmentID .modal-content-value').textContent = equipmentId;

            // Get supplier name from the table row
            const row = this.closest('tr');
            const supplierName = row.querySelector('td:nth-child(8)').textContent.trim();
            document.querySelector('#modalSupplier .modal-content-value').textContent = supplierName;

            // Get and set status badge
            const statusBadge = row.querySelector('.badge').cloneNode(true);
            const statusValueElement = document.querySelector('#modalStatus .modal-content-value');
            statusValueElement.innerHTML = '';
            statusValueElement.appendChild(statusBadge);
            statusBadge.classList.remove('dropdown-toggle');
            statusBadge.removeAttribute('data-bs-toggle');
            statusBadge.removeAttribute('aria-expanded');
            statusValueElement.innerHTML = '';
            statusValueElement.appendChild(statusBadge);
            
            // Clear unit field as it's not needed
            document.querySelector('#modalUnit .modal-content-value').textContent = 'pcs';
        });
    });

    // Filter functionality
    const filterDropdownItems = document.querySelectorAll('.filter-option');
    const tableRows = document.querySelectorAll('#equipmentTable tbody tr');
    let currentFilter = 'all';
        
    filterDropdownItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const filterType = this.getAttribute('data-filter');
            currentFilter = filterType;
            
            // Update dropdown button text
            const filterDropdown = document.getElementById('filterDropdown');
            filterDropdown.innerHTML = `<i class="fa-solid fa-filter"></i> ${this.textContent}`;
            
            applyFilter();
        });
    });
        
    // Function to apply both filter and search
    function applyFilter() {
        const searchValue = document.getElementById('searchInput').value.toLowerCase();
        
        tableRows.forEach(row => {
            let showRow = true;
            const cells = row.getElementsByTagName('td');
            
            if (currentFilter !== 'all') {
                const cellValue = getFilterValue(cells, currentFilter).toLowerCase();
                showRow = cellValue.includes(searchValue);
            } else {
                // When "All" is selected, search in all columns
                showRow = Array.from(cells).some(cell =>
                     cell.textContent.toLowerCase().includes(searchValue)
                );
            }
            
            row.style.display = showRow ? '' : 'none';
        });
    }
        
    // Function to get the value based on filter type
    function getFilterValue(cells, filterType) {
        switch(filterType) {
            case 'partId':
                return cells[1].textContent;
            case 'partName':
                return cells[2].textContent;
            case 'brand':
                return cells[3].textContent;
            case 'stock':
                return cells[5].textContent;
            default:
                return '';
        }
    }
        
    // Modify the existing search input event listener
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            applyFilter();
        });
    }

    // Sort functionality
    const sortDropdownItems = document.querySelectorAll('.sort-option');
    let currentSortOrder = 'asc';
    let currentSortColumn = 'partName'; // Default sort by Part Name

    sortDropdownItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            currentSortOrder = this.getAttribute('data-sort');

            // Update dropdown button text
            const sortDropdown = document.getElementById('sortDropdown');
            sortDropdown.innerHTML = `<i class="fa-solid fa-arrow-down-wide-short"></i> ${this.textContent}`;

            sortTable();
        });
    });

    function sortTable() {
        const table = document.getElementById('equipmentTable');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        rows.sort((a, b) => {
            let aValue = '';
            let bValue = '';

            // Get values based on current filter
            switch(currentFilter) {
                case 'partId':
                    aValue = a.cells[1].textContent.trim();
                    bValue = b.cells[1].textContent.trim();
                    break;
                case 'partName':
                    aValue = a.cells[2].textContent.trim();
                    bValue = b.cells[2].textContent.trim();
                    break;
                case 'brand':
                    aValue = a.cells[3].textContent.trim();
                    bValue = b.cells[3].textContent.trim();
                    break;
                case 'stock':
                    aValue = parseInt(a.cells[5].textContent.trim()) || 0;
                    bValue = parseInt(b.cells[5].textContent.trim()) || 0;
                    return currentSortOrder === 'asc' ? aValue - bValue : bValue - aValue;
                default:
                    // Default sort by Part Name
                    aValue = a.cells[2].textContent.trim();
                    bValue = b.cells[2].textContent.trim();
            }

            // Compare values
            if (currentSortOrder === 'asc') {
                return aValue.localeCompare(bValue, undefined, {numeric: true});
            } else {
                return bValue.localeCompare(aValue, undefined, {numeric: true});
            }
        });

        // Clear and append sorted rows
        rows.forEach(row => tbody.appendChild(row));
    }

    // Modify the applyFilter function to maintain sort order after filtering
    function applyFilter() {
        const searchValue = document.getElementById('searchInput').value.toLowerCase();
        tableRows.forEach(row => {
            let showRow = true;
            const cells = row.getElementsByTagName('td');
            if (currentFilter !== 'all') {
                const cellValue = getFilterValue(cells, currentFilter).toLowerCase();
                showRow = cellValue.includes(searchValue);
            } else {
                showRow = Array.from(cells).some(cell =>
                     cell.textContent.toLowerCase().includes(searchValue)
                );
            }
            row.style.display = showRow ? '' : 'none';
        });
        // Apply current sort after filtering
        sortTable();
    }

    // Add column header click sorting
    const tableHeaders = document.querySelectorAll('#equipmentTable thead th');
    tableHeaders.forEach((header, index) => {
        if (index === 0) return; // Skip checkbox column
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            // Toggle sort order if clicking the same column
            if (currentSortColumn === this.textContent.toLowerCase().replace(/\s+/g, '')) {
                currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                currentSortOrder = 'asc';
            }
            currentSortColumn = this.textContent.toLowerCase().replace(/\s+/g, '');
            currentFilter = getSortFilterType(currentSortColumn);

            // Update sort dropdown text
            const sortDropdown = document.getElementById('sortDropdown');
            sortDropdown.innerHTML = `<i class="fa-solid fa-arrow-down-wide-short"></i> Sort ${currentSortOrder === 'asc' ? 'A-Z' : 'Z-A'}`;

            sortTable();

            // Update visual indication of sort
            tableHeaders.forEach(h => h.classList.remove('sorted-asc', 'sorted-desc'));
            this.classList.add(currentSortOrder === 'asc' ? 'sorted-asc' : 'sorted-desc');
        });
    });

    function getSortFilterType(column) {
        const columnMap = {
            'partid': 'partId',
            'partname': 'partName',
            'brand': 'brand',
            'stockquantity': 'stock'
        };
        return columnMap[column] || 'partName';
    }

    // Archive Table Toggle
    if (toggleArchiveBtn) {
        toggleArchiveBtn.addEventListener('click', function() {
            fetch('getArchivedItems.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (archivedTableContainer) {
                            archivedTableContainer.style.display =
                                 archivedTableContainer.style.display === 'none' ? 'block' : 'none';

                            // Update the table content
                            const tableBody = document.querySelector('#archivedItemsTable tbody');
                            if (data.items.length > 0) {
                                tableBody.innerHTML = data.items.map(item => `
                                    <tr>
                                        <td>${item.part_id || ''}</td>
                                        <td>${item.part_name || 'Unknown'}</td>
                                        <td>${item.brand_name || 'Unknown'}</td>
                                        <td>${item.part_number || ''}</td>
                                        <td>${item.stock_quantity || 0}</td>
                                        <td>${item.supplier_name || 'N/A'}</td>
                                        <td>₱${parseFloat(item.unit_price || 0).toFixed(2)}</td>
                                        <td>${item.archive_date || ''}</td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn" type="button" id="ellipsisDropdown_${item.part_id}" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="ellipsisDropdown_${item.part_id}">
                                                    <li>
                                                        <button class="dropdown-item restore-btn" data-part-id="${item.part_id}">
                                                                                                                        <i class="fas fa-undo-alt me-2"></i>Restore
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item text-danger delete-archived-item" data-part-id="${item.part_id}">
                                                            <i class="fas fa-trash-alt me-2"></i>Delete
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                `).join('');

                                // Update the restore and delete button event listeners
                                const restoreButtons = document.querySelectorAll('.restore-btn');
                                const deleteButtons = document.querySelectorAll('.delete-archived-item');

                                restoreButtons.forEach(button => {
                                    button.addEventListener('click', function() {
                                        const partId = this.getAttribute('data-part-id');
                                        if (confirm('Are you sure you want to restore this item?')) {
                                            fetch('restoreItemInv.php', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json'
                                                },
                                                body: JSON.stringify({ part_id: partId })
                                            })
                                            .then(response => response.json())
                                            .then(data => {
                                                if (data.success) {
                                                    // Remove the row from archived table
                                                    this.closest('tr').remove();
                                                    
                                                    // Update archive count badge
                                                    const archiveCount = document.getElementById('archiveCount');
                                                    if (archiveCount) {
                                                        const currentCount = parseInt(archiveCount.textContent || '0');
                                                        archiveCount.textContent = Math.max(0, currentCount - 1);
                                                    }
                                                    
                                                    alert(data.message);
                                                    location.reload();
                                                } else {
                                                    throw new Error(data.message || 'Failed to restore item');
                                                }
                                            })
                                            .catch(error => {
                                                console.error('Error:', error);
                                                alert('An error occurred while restoring the item: ' + error.message);
                                            });
                                        }
                                    });
                                });

                                deleteButtons.forEach(button => {
                                    button.addEventListener('click', function() {
                                        const partId = this.getAttribute('data-part-id');
                                        if (confirm('Are you sure you want to permanently delete this item? This action cannot be undone.')) {
                                            fetch('deleteInventory.php', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json'
                                                },
                                                body: JSON.stringify({ part_id: partId })
                                            })
                                            .then(response => response.json())
                                            .then(data => {
                                                if (data.success) {
                                                    // Remove the row from archived table
                                                    this.closest('tr').remove();
                                                    
                                                    // Update archive count badge
                                                    const archiveCount = document.getElementById('archiveCount');
                                                    if (archiveCount) {
                                                        const currentCount = parseInt(archiveCount.textContent || '0');
                                                        archiveCount.textContent = Math.max(0, currentCount - 1);
                                                    }
                                                    
                                                    alert(data.message);
                                                } else {
                                                    throw new Error(data.message || 'Failed to delete item');
                                                }
                                            })
                                            .catch(error => {
                                                console.error('Error:', error);
                                                alert('An error occurred while deleting the item: ' + error.message);
                                            });
                                        }
                                    });
                                });
                            } else {
                                tableBody.innerHTML = `
                                    <tr>
                                        <td colspan="7" class="text-center py-3">No archived items found</td>
                                    </tr>
                                `;
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching archived items:', error);
                    alert('Failed to load archived items');
                });
        });

        // Close archived table when clicking the close button
        if (closeArchivedTable) {
            closeArchivedTable.addEventListener('click', function() {
                archivedTableContainer.style.display = 'none';
            });
        }
    }

    // Edit Functionality - UPDATED FOR TEXT INPUTS
    // Handle form submission for editing
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const stockInput = document.getElementById('editStock');
            const stockValue = stockInput.value;
            
            // Check if input is a valid non-negative integer
            if (!Number.isInteger(Number(stockValue)) || stockValue.includes('.') || stockValue.includes(',')) {
                alert('Please enter a whole number for stock quantity (no decimals or special characters)');
                stockInput.focus();
                return;
            }
            
            // Convert to number for comparison
            const numericStock = parseInt(stockValue);
            // Check for negative numbers
            if (numericStock < 0) {
                alert('Stock quantity cannot be negative. Please enter a number from 0 and up.');
                stockInput.focus();
                return;
            }
            
            const formData = new FormData(this);
            
            // UPDATED: Use editInventory.php instead of updateInventory.php
            fetch('editInventory.php', {
                method: 'POST',
                body: formData
            })
            .then(async response => {
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Server response:', text);
                    throw new Error('Invalid server response');
                }
            })
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    throw new Error(data.message || 'Failed to update part');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the part: ' + error.message);
            });
        });
    }

    // UPDATED: Edit button click handler for text inputs instead of dropdowns
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Get data from button attributes
            const partId = this.dataset.partId;
            const partName = this.dataset.partName;
            const partNumber = this.dataset.partNumber;
            const brandName = this.dataset.brand;
            const supplierName = this.dataset.supplier;
            const stock = this.dataset.stock;
            const unitPrice = this.dataset.unitPrice;
            const equipmentId = this.dataset.equipmentId;

            // Populate edit modal fields - FIXED FIELD IDs AND ADDED MISSING FIELDS
            document.getElementById('originalPartId').value = partId; 
            document.getElementById('editPartName').value = partName;
            document.getElementById('editPartNumber').value = partNumber;

            // Set brand text input
            document.getElementById('editBrand').value = brandName || '';

            // Set supplier text input
            document.getElementById('editSupplier').value = supplierName || '';

            // Set other fields
            document.getElementById('editStock').value = stock;
            document.getElementById('editUnitPrice').value = unitPrice;
            document.getElementById('editEquipmentId').value = equipmentId || '';
        });
    });

    // Archive Functionality
    // Handle "Select All" checkbox
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            Array.from(rowCheckboxes).forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateArchiveButton();
        });
    }

    // Handle individual checkboxes
    Array.from(rowCheckboxes).forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateArchiveButton();
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = Array.from(rowCheckboxes)
                    .every(cb => cb.checked);
            }
        });
    });

    // Update archive button state
    function updateArchiveButton() {
        const checkedBoxes = Array.from(rowCheckboxes)
            .filter(cb => cb.checked).length;
        const selectedCountSpan = document.getElementById('selectedCount');
        
        if (selectedCountSpan) {
            selectedCountSpan.textContent = checkedBoxes;
        }
        
        if (archiveAllBtn) {
            archiveAllBtn.disabled = checkedBoxes === 0;
        }
    }

    // Handle archive button click
    if (archiveAllBtn) {
        archiveAllBtn.addEventListener('click', function() {
            const selectedRows = Array.from(rowCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.closest('tr'));

            if (selectedRows.length === 0) {
                alert('Please select items to archive');
                return;
            }

            if (confirm(`Are you sure you want to archive ${selectedRows.length} item(s)?`)) {
                const partIds = selectedRows.map(row => {
                    const partId = row.querySelector('td:nth-child(2)').textContent.trim();
                    return parseInt(partId, 10);
                }).filter(id => !isNaN(id));

                fetch('archiveItem.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ part_ids: partIds })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                     } else {
                        throw new Error(data.message || 'Failed to archive items');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while archiving items: ' + error.message);
                });
            }
        });
    }

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
