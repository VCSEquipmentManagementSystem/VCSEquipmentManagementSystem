<?php
require('./database.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvFile'])) {
    $csvFile = $_FILES['csvFile'];

    if ($csvFile['error'] !== UPLOAD_ERR_OK) {
        header('Location: Inventory.php?status=batch_error&message=File upload error: ' . $csvFile['error']);
        exit();
    }

    $filePath = $csvFile['tmp_name'];

    if (($handle = fopen($filePath, 'r')) !== FALSE) {
        $headers = fgetcsv($handle);

        $expectedHeaders = [
            'part_name',
            'brand_id',
            'part_number',
            'part_specs',
            'part_remarks'
        ];

        $normalizedHeaders = array_map('strtolower', array_map('trim', $headers));
        $normalizedExpected = array_map('strtolower', $expectedHeaders);

        if (array_diff($normalizedExpected, $normalizedHeaders)) {
            fclose($handle);
            header('Location: Inventory.php?status=batch_error&message=Invalid CSV format. Ensure headers match: ' . implode(', ', $expectedHeaders));
            exit();
        }

        $headerMap = array_flip($normalizedExpected);
        $dataIndices = [];
        foreach ($normalizedHeaders as $index => $header) {
            if (isset($headerMap[$header])) {
                $dataIndices[$headerMap[$header]] = $index;
            }
        }

        $stmtCheckBrand = null;
        $stmtCheckPart = null;
        $stmtInsertPart = null;
        $stmtInsertSpare = null;

        try {
            $stmtCheckBrand = mysqli_prepare($connection, "SELECT brand_id FROM brand_tbl WHERE brand_name = ?");
            if (!$stmtCheckBrand) {
                throw new Exception("Failed to prepare brand check statement: " . mysqli_error($connection));
            }

            $stmtCheckPart = mysqli_prepare($connection, "SELECT part_id FROM inventory_parts_tbl WHERE part_number = ?");
            if (!$stmtCheckPart) {
                throw new Exception("Failed to prepare part check statement: " . mysqli_error($connection));
            }

            $stmtInsertPart = mysqli_prepare($connection, "INSERT INTO inventory_parts_tbl (part_name, brand_id, part_number, part_specs, part_remarks) VALUES (?, ?, ?, ?, ?)");
            if (!$stmtInsertPart) {
                throw new Exception("Failed to prepare inventory_parts_tbl insert statement: " . mysqli_error($connection));
            }

            $stmtInsertSpare = mysqli_prepare($connection, "INSERT INTO spareparts_inventory_tbl (part_id, stock_quantity, unit_price, equipment_id, custom_equip_id, supplier_id) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmtInsertSpare) {
                throw new Exception("Failed to prepare spareparts_inventory_tbl insert statement: " . mysqli_error($connection));
            }

            mysqli_begin_transaction($connection);

            $importedCount = 0;
            while (($row = fgetcsv($handle)) !== FALSE) {
                $data = [];
                foreach ($dataIndices as $expectedIndex => $csvIndex) {
                    $data[$expectedHeaders[$expectedIndex]] = isset($row[$csvIndex]) ? trim($row[$csvIndex]) : '';
                }

                $part_name = $data['part_name'];
                $brand_name = $data['brand_id'];
                $part_number = $data['part_number'];
                $part_specs = $data['part_specs'];
                $part_remarks = $data['part_remarks'];

                if (empty($part_name) || empty($part_number)) {
                    continue;
                }

                $brand_id = null;
                if (!empty($brand_name) && $brand_name !== '-' && $brand_name !== '') {
                    mysqli_stmt_bind_param($stmtCheckBrand, "s", $brand_name);
                    mysqli_stmt_execute($stmtCheckBrand);
                    $result = mysqli_stmt_get_result($stmtCheckBrand);
                    if ($row = mysqli_fetch_assoc($result)) {
                        $brand_id = $row['brand_id'];
                    } else {
                        $brand_id = null;
                    }
                } else {
                    $brand_id = null;
                }

                mysqli_stmt_bind_param($stmtCheckPart, "s", $part_number);
                mysqli_stmt_execute($stmtCheckPart);
                $result = mysqli_stmt_get_result($stmtCheckPart);
                $part_id = null;

                if ($row = mysqli_fetch_assoc($result)) {
                    $part_id = $row['part_id'];
                } else {
                    mysqli_stmt_bind_param(
                        $stmtInsertPart,
                        "sisss",
                        $part_name,
                        $brand_id,
                        $part_number,
                        $part_specs,
                        $part_remarks
                    );

                    if (!mysqli_stmt_execute($stmtInsertPart)) {
                        throw new Exception("Error inserting row into inventory_parts_tbl for part_number '$part_number': " . mysqli_stmt_error($stmtInsertPart));
                    }
                    $part_id = mysqli_insert_id($connection);
                }

                $unit_price = 0.00;
                $priceIndex = array_search('price', array_map('strtolower', $headers));
                if ($priceIndex !== false && isset($row[$priceIndex])) {
                    $priceStr = trim($row[$priceIndex]);
                    if (preg_match('/^₱?(\d+(?:\.\d{2})?)$/', $priceStr, $matches)) {
                        $unit_price = floatval($matches[1]);
                    }
                }

                $stock_quantity = 0;
                $equipment_id = null;
                $custom_equip_id = null;
                $supplier_id = null;

                mysqli_stmt_bind_param(
                    $stmtInsertSpare,
                    "iiddis",
                    $part_id,
                    $stock_quantity,
                    $unit_price,
                    $equipment_id,
                    $custom_equip_id,
                    $supplier_id
                );

                if (!mysqli_stmt_execute($stmtInsertSpare)) {
                    throw new Exception("Error inserting row into spareparts_inventory_tbl for part_id '$part_id': " . mysqli_stmt_error($stmtInsertSpare));
                }

                $importedCount++;
            }

            mysqli_commit($connection);
            header('Location: Inventory.php?status=batch_success&message=Successfully imported ' . $importedCount . ' items into spareparts_inventory_tbl.');
            exit();
        } catch (Exception $e) {
            mysqli_rollback($connection);
            header('Location: Inventory.php?status=batch_error&message=' . urlencode($e->getMessage()));
            exit();
        } finally {
            if ($stmtCheckBrand) mysqli_stmt_close($stmtCheckBrand);
            if ($stmtCheckPart) mysqli_stmt_close($stmtCheckPart);
            if ($stmtInsertPart) mysqli_stmt_close($stmtInsertPart);
            if ($stmtInsertSpare) mysqli_stmt_close($stmtInsertSpare);
            fclose($handle);
            mysqli_close($connection);
        }
    } else {
        header('Location: Inventory.php?status=batch_error&message=Could not open CSV file.');
        exit();
    }
} else {
    header('Location: Inventory.php?status=batch_error&message=Invalid request.');
    exit();
}
