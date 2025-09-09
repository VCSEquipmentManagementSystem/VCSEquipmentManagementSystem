<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require('./database.php');
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get the part ID to edit
        $part_id = !empty($_POST['part_id']) ? (int)$_POST['part_id'] : null;

        if (!$part_id) {
            throw new Exception("Part ID is required for editing");
        }

        // Get form data with proper default values
        $part_name = mysqli_real_escape_string($connection, $_POST['PartNameInput'] ?? '');
        $part_number = mysqli_real_escape_string($connection, $_POST['PartNumberInput'] ?? '');

        // Changed: Brand is now a text input instead of select
        $brand_name = !empty($_POST['BrandInput']) ? mysqli_real_escape_string($connection, trim($_POST['BrandInput'])) : null;

        // Validate stock input
        if (!isset($_POST['StockInput']) || $_POST['StockInput'] === '') {
            throw new Exception("Stock quantity is required");
        }

        // Check if stock is a valid non-negative integer
        if (!is_numeric($_POST['StockInput']) || (float)$_POST['StockInput'] < 0 || (int)$_POST['StockInput'] != (float)$_POST['StockInput']) {
            throw new Exception("Stock quantity must be a non-negative whole number");
        }

        $stock_quantity = (int)$_POST['StockInput'];

        // Changed: Supplier is now a text input instead of select
        $supplier_comp_name = !empty($_POST['SupplierInput']) ? mysqli_real_escape_string($connection, trim($_POST['SupplierInput'])) : null;

        // Validate unit price input
        if (!isset($_POST['UnitPriceInput']) || $_POST['UnitPriceInput'] === '') {
            throw new Exception("Unit price is required");
        }

        // Check if unit price is a valid non-negative number
        if (!is_numeric($_POST['UnitPriceInput']) || (float)$_POST['UnitPriceInput'] < 0) {
            throw new Exception("Unit price must be a non-negative number");
        }

        $unit_price = (float)$_POST['UnitPriceInput'];
        $equipment_id = !empty($_POST['EquipmentIdInput']) ? (int)$_POST['EquipmentIdInput'] : null;
        $custom_equip_id = !empty($_POST['EquipmentIdInput']) ? $_POST['EquipmentIdInput'] : null;

        error_log("Received POST data for edit: " . print_r($_POST, true));

        // Validate required fields
        if (empty($part_name) || empty($part_number) || $unit_price === 0) {
            throw new Exception("Part name, part number, and unit price are required");
        }

        // Start transaction
        mysqli_begin_transaction($connection);

        // Handle brand - create if doesn't exist
        $brand_id = null;
        if ($brand_name) {
            // Check if brand already exists (case-insensitive)
            $checkBrandQuery = "SELECT brand_id FROM brand_tbl WHERE LOWER(TRIM(brand_name)) = LOWER(TRIM(?))";
            $brandStmt = mysqli_prepare($connection, $checkBrandQuery);
            mysqli_stmt_bind_param($brandStmt, "s", $brand_name);

            if (!mysqli_stmt_execute($brandStmt)) {
                throw new Exception("Error checking brand: " . mysqli_stmt_error($brandStmt));
            }

            $brandResult = mysqli_stmt_get_result($brandStmt);

            if (mysqli_num_rows($brandResult) > 0) {
                // Brand exists, get its ID
                $brandRow = mysqli_fetch_assoc($brandResult);
                $brand_id = $brandRow['brand_id'];
            } else {
                // Brand doesn't exist, create new one
                $insertBrandQuery = "INSERT INTO brand_tbl (brand_name) VALUES (?)";
                $insertBrandStmt = mysqli_prepare($connection, $insertBrandQuery);
                mysqli_stmt_bind_param($insertBrandStmt, "s", $brand_name);

                if (!mysqli_stmt_execute($insertBrandStmt)) {
                    throw new Exception("Error creating brand: " . mysqli_stmt_error($insertBrandStmt));
                }

                $brand_id = mysqli_insert_id($connection);
            }
        }

        // Handle supplier - create if doesn't exist (removed sup_email)
        $supplier_id = null;
        if ($supplier_comp_name) {
            // Check if supplier already exists (case-insensitive)
            $checkSupplierQuery = "SELECT supplier_id FROM supplier_tbl WHERE LOWER(TRIM(supplier_comp_name)) = LOWER(TRIM(?))";
            $supplierStmt = mysqli_prepare($connection, $checkSupplierQuery);
            mysqli_stmt_bind_param($supplierStmt, "s", $supplier_comp_name);

            if (!mysqli_stmt_execute($supplierStmt)) {
                throw new Exception("Error checking supplier: " . mysqli_stmt_error($supplierStmt));
            }

            $supplierResult = mysqli_stmt_get_result($supplierStmt);

            if (mysqli_num_rows($supplierResult) > 0) {
                // Supplier exists, get its ID
                $supplierRow = mysqli_fetch_assoc($supplierResult);
                $supplier_id = $supplierRow['supplier_id'];
            } else {
                // Supplier doesn't exist, create new one with unique email
                $unique_email = 'noemail_' . uniqid() . '@placeholder.com';
                $insertSupplierQuery = "INSERT INTO supplier_tbl (supplier_comp_name, sup_email) VALUES (?, ?)";
                $insertSupplierStmt = mysqli_prepare($connection, $insertSupplierQuery);
                mysqli_stmt_bind_param($insertSupplierStmt, "ss", $supplier_comp_name, $unique_email);

                if (!mysqli_stmt_execute($insertSupplierStmt)) {
                    throw new Exception("Error creating supplier: " . mysqli_stmt_error($insertSupplierStmt));
                }

                $supplier_id = mysqli_insert_id($connection);
            }
        }

        // Enhanced duplication checking (exclude current part from check)

        // 1. Check for duplicate part number (exclude current part)
        $checkPartNumberQuery = "SELECT ip.part_id, ip.part_name, ip.part_number, b.brand_name
                                FROM inventory_parts_tbl ip
                                LEFT JOIN brand_tbl b ON ip.brand_id = b.brand_id
                                WHERE ip.part_number = ?
                                 AND ip.part_id != ?
                                AND ip.part_id NOT IN (SELECT part_id FROM archived_inventory_tbl)";

        $checkStmt = mysqli_prepare($connection, $checkPartNumberQuery);
        mysqli_stmt_bind_param($checkStmt, "si", $part_number, $part_id);

        if (!mysqli_stmt_execute($checkStmt)) {
            throw new Exception("Error checking for duplicate part numbers: " . mysqli_stmt_error($checkStmt));
        }

        $duplicateResult = mysqli_stmt_get_result($checkStmt);

        if (mysqli_num_rows($duplicateResult) > 0) {
            $existingPart = mysqli_fetch_assoc($duplicateResult);
            $brand_info = $existingPart['brand_name'] ? " from brand '{$existingPart['brand_name']}'" : "";

            throw new Exception("Part number '{$part_number}' already exists! Found in part '{$existingPart['part_name']}'{$brand_info} (ID: {$existingPart['part_id']})");
        }

        // 2. Check for duplicate part name + brand combination (exclude current part)
        if ($brand_id) {
            $checkNameBrandQuery = "SELECT ip.part_id, ip.part_name, ip.part_number, b.brand_name
                                   FROM inventory_parts_tbl ip
                                   LEFT JOIN brand_tbl b ON ip.brand_id = b.brand_id
                                   WHERE LOWER(TRIM(ip.part_name)) = LOWER(TRIM(?))
                                   AND ip.brand_id = ?
                                   AND ip.part_id != ?
                                   AND ip.part_id NOT IN (SELECT part_id FROM archived_inventory_tbl)";

            $checkStmt2 = mysqli_prepare($connection, $checkNameBrandQuery);
            mysqli_stmt_bind_param($checkStmt2, "sii", $part_name, $brand_id, $part_id);

            if (!mysqli_stmt_execute($checkStmt2)) {
                throw new Exception("Error checking for duplicate part name and brand: " . mysqli_stmt_error($checkStmt2));
            }

            $duplicateResult2 = mysqli_stmt_get_result($checkStmt2);

            if (mysqli_num_rows($duplicateResult2) > 0) {
                $existingPart = mysqli_fetch_assoc($duplicateResult2);

                throw new Exception("A part with the name '{$part_name}' from brand '{$existingPart['brand_name']}' already exists! (Part Number: {$existingPart['part_number']}, ID: {$existingPart['part_id']})");
            }
        }

        // Update part details
        $updatePartQuery = "UPDATE inventory_parts_tbl SET
                            part_name = ?,
                            brand_id = ?,
                           part_number = ?
                           WHERE part_id = ?";

        $updatePartStmt = mysqli_prepare($connection, $updatePartQuery);
        if (!$updatePartStmt) {
            throw new Exception("Database error: " . mysqli_error($connection));
        }

        mysqli_stmt_bind_param(
            $updatePartStmt,
            "sisi",
            $part_name,
            $brand_id,
            $part_number,
            $part_id
        );

        if (!mysqli_stmt_execute($updatePartStmt)) {
            throw new Exception("Error updating part details: " . mysqli_stmt_error($updatePartStmt));
        }

        // Update inventory details
        $updateInventoryQuery = "UPDATE spareparts_inventory_tbl SET
                                 equipment_id = ?,
                                custom_equip_id = ?,
                                stock_quantity = ?,
                                supplier_id = ?,
                                unit_price = ?
                                WHERE part_id = ?";

        $updateInventoryStmt = mysqli_prepare($connection, $updateInventoryQuery);
        if (!$updateInventoryStmt) {
            throw new Exception("Database error: " . mysqli_error($connection));
        }

        mysqli_stmt_bind_param(
            $updateInventoryStmt,
            "isidsi",
            $equipment_id,
            $custom_equip_id,
            $stock_quantity,
            $supplier_id,
            $unit_price,
            $part_id
        );

        if (!mysqli_stmt_execute($updateInventoryStmt)) {
            throw new Exception("Error updating inventory details: " . mysqli_stmt_error($updateInventoryStmt));
        }

        mysqli_commit($connection);

        $response_message = 'Part updated successfully';
        if ($brand_name && $supplier_comp_name) {
            $response_message .= " (Brand: {$brand_name}, Supplier: {$supplier_comp_name})";
        } elseif ($brand_name) {
            $response_message .= " (Brand: {$brand_name})";
        } elseif ($supplier_comp_name) {
            $response_message .= " (Supplier: {$supplier_comp_name})";
        }

        echo json_encode([
            'success' => true,
            'message' => $response_message
        ]);
    } else {
        throw new Exception("Invalid request method");
    }
} catch (Exception $e) {
    if (isset($connection) && mysqli_ping($connection)) {
        mysqli_rollback($connection);
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($connection)) {
        mysqli_close($connection);
    }
}
