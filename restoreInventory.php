<?php
// restoreInventory.php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);
require './database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['selectedArchiveIds']) || !isset($_POST['action'])) {
        throw new Exception('Invalid request or missing selected IDs/action.');
    }
    $selectedIds = $_POST['selectedArchiveIds'];
    $action = $_POST['action'];

    // CORRECTED LINE 15: Use $selectedIds instead of $selectedArchiveIds
    if (!is_array($selectedIds) || empty($selectedIds)) {
        throw new Exception('No items selected for ' . $action . '.');
    }

    mysqli_begin_transaction($connection);

    if ($action === 'restore') {
        // CORRECTED LINE 35: Use $selectedIds instead of $selectedArchiveIds
        foreach ($selectedIds as $archive_id) {
            $archive_id = intval($archive_id);
            if ($archive_id <= 0) {
                error_log("Skipping invalid archive_id during restore: " . $archive_id);
                continue; // Skip invalid IDs
            }

            // 1. Select data from archived_inventory_tbl, including part_name, part_number, brand_name
            $select_archive_sql = "
                SELECT
                    spare_id, part_id, part_name, part_number, brand_name,
                    equipment_id, custom_equip_id, stock_quantity, supplier_id, unit_price, last_update
                FROM
                    archived_inventory_tbl
                WHERE
                    archive_id = ?
            ";
            $select_archive_stmt = mysqli_prepare($connection, $select_archive_sql);
            if (!$select_archive_stmt) {
                throw new Exception('Failed to prepare select archive statement: ' . mysqli_error($connection));
            }
            mysqli_stmt_bind_param($select_archive_stmt, 'i', $archive_id);
            mysqli_stmt_execute($select_archive_stmt);
            $result = mysqli_stmt_get_result($select_archive_stmt);
            $row_to_restore = mysqli_fetch_assoc($result);
            mysqli_stmt_close($select_archive_stmt);

            if (!$row_to_restore) {
                error_log("Attempted to restore non-existent archive_id: " . $archive_id);
                continue;
            }

            $restored_part_id = $row_to_restore['part_id'];
            $restored_part_name = $row_to_restore['part_name'];
            $restored_part_number = $row_to_restore['part_number'];
            $restored_brand_name = $row_to_restore['brand_name'];

            // Get brand_id from brand_tbl using brand_name
            $brand_id_for_restore = 0;
            $brand_id_query = mysqli_prepare($connection, "SELECT brand_id FROM brand_tbl WHERE brand_name = ?");
            if ($brand_id_query) {
                mysqli_stmt_bind_param($brand_id_query, 's', $restored_brand_name);
                mysqli_stmt_execute($brand_id_query);
                $result_brand = mysqli_stmt_get_result($brand_id_query);
                $brand_row = mysqli_fetch_assoc($result_brand);
                $brand_id_for_restore = $brand_row['brand_id'] ?? 0;
                mysqli_stmt_close($brand_id_query);
            }

            if ($brand_id_for_restore === 0) {
                // If brand doesn't exist, you might want to insert it or throw a more specific error
                // For now, let's keep the existing logic to throw an exception.
                throw new Exception("Brand '{$restored_brand_name}' not found in brand_tbl during restore. Please add the brand first or ensure data consistency.");
            }

            // 2. Check if part_id already exists in inventory_parts_tbl
            $check_part_sql = "SELECT COUNT(*) FROM inventory_parts_tbl WHERE part_id = ?";
            $check_part_stmt = mysqli_prepare($connection, $check_part_sql);
            if (!$check_part_stmt) {
                throw new Exception('Failed to prepare part check statement: ' . mysqli_error($connection));
            }
            mysqli_stmt_bind_param($check_part_stmt, 'i', $restored_part_id);
            mysqli_stmt_execute($check_part_stmt);
            $part_exists_result = mysqli_stmt_get_result($check_part_stmt);
            $part_exists = mysqli_fetch_row($part_exists_result)[0];
            mysqli_stmt_close($check_part_stmt);

            if ($part_exists == 0) {
                // If part_id does not exist, re-insert into inventory_parts_tbl
                $insert_parts_sql = "
                    INSERT INTO inventory_parts_tbl (part_id, part_name, part_number, brand_id)
                    VALUES (?, ?, ?, ?)
                ";
                $insert_parts_stmt = mysqli_prepare($connection, $insert_parts_sql);
                if (!$insert_parts_stmt) {
                    throw new Exception('Failed to prepare parts insert statement: ' . mysqli_error($connection));
                }
                // CORRECTED TYPE BINDING: 'issi' for (int, string, string, int)
                mysqli_stmt_bind_param(
                    $insert_parts_stmt,
                    'issi',
                    $restored_part_id,
                    $restored_part_name,
                    $restored_part_number,
                    $brand_id_for_restore
                );
                if (!mysqli_stmt_execute($insert_parts_stmt)) {
                    throw new Exception('Failed to insert into inventory_parts_tbl: ' . mysqli_stmt_error($insert_parts_stmt));
                }
                mysqli_stmt_close($insert_parts_stmt);
            } else {
                // If part_id exists, update it to ensure consistency (e.g., if name/number changed)
                $update_parts_sql = "
                    UPDATE inventory_parts_tbl
                    SET part_name = ?, part_number = ?, brand_id = ?
                    WHERE part_id = ?
                ";
                $update_parts_stmt = mysqli_prepare($connection, $update_parts_sql);
                if (!$update_parts_stmt) {
                    throw new Exception('Failed to prepare parts update statement: ' . mysqli_error($connection));
                }
                mysqli_stmt_bind_param(
                    $update_parts_stmt,
                    'ssii', // string, string, int, int
                    $restored_part_name,
                    $restored_part_number,
                    $brand_id_for_restore,
                    $restored_part_id
                );
                if (!mysqli_stmt_execute($update_parts_stmt)) {
                    throw new Exception('Failed to update inventory_parts_tbl: ' . mysqli_stmt_error($update_parts_stmt));
                }
                mysqli_stmt_close($update_parts_stmt);
            }


            // 3. Re-insert into spareparts_inventory_tbl
            $insert_spareparts_sql = "
                INSERT INTO spareparts_inventory_tbl (
                    spare_id, part_id, equipment_id, stock_quantity, supplier_id, unit_price, custom_equip_id, last_update
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    part_id = VALUES(part_id),
                    equipment_id = VALUES(equipment_id),
                    stock_quantity = VALUES(stock_quantity),
                    supplier_id = VALUES(supplier_id),
                    unit_price = VALUES(unit_price),
                    custom_equip_id = VALUES(custom_equip_id),
                    last_update = VALUES(last_update)
            ";
            $insert_spareparts_stmt = mysqli_prepare($connection, $insert_spareparts_sql);
            if (!$insert_spareparts_stmt) {
                throw new Exception('Failed to prepare spareparts insert statement: ' . mysqli_error($connection));
            }

            // Assuming: spare_id (int), part_id (int), equipment_id (int),
            // stock_quantity (int), supplier_id (int), unit_price (double),
            // custom_equip_id (string, assuming it's VARCHAR), last_update (string, for DATETIME)
            mysqli_stmt_bind_param(
                $insert_spareparts_stmt,
                'iiiisdss', // Adjusted types based on typical usage for your columns
                $row_to_restore['spare_id'],
                $row_to_restore['part_id'],
                $row_to_restore['equipment_id'],
                $row_to_restore['stock_quantity'],
                $row_to_restore['supplier_id'],
                $row_to_restore['unit_price'],
                $row_to_restore['custom_equip_id'], // Added custom_equip_id
                $row_to_restore['last_update']
            );
            if (!mysqli_stmt_execute($insert_spareparts_stmt)) {
                throw new Exception('Failed to insert/update into spareparts: ' . mysqli_stmt_error($insert_spareparts_stmt));
            }
            mysqli_stmt_close($insert_spareparts_stmt);

            // 4. Delete from archived_inventory_tbl
            $delete_archive_sql = "DELETE FROM archived_inventory_tbl WHERE archive_id = ?";
            $delete_archive_stmt = mysqli_prepare($connection, $delete_archive_sql);
            if (!$delete_archive_stmt) {
                throw new Exception('Failed to prepare archive delete statement: ' . mysqli_error($connection));
            }
            mysqli_stmt_bind_param($delete_archive_stmt, 'i', $archive_id);
            if (!mysqli_stmt_execute($delete_archive_stmt)) {
                throw new Exception('Failed to delete from archive: ' . mysqli_stmt_error($delete_archive_stmt));
            }
            mysqli_stmt_close($delete_archive_stmt);
        }
        mysqli_commit($connection);
        echo json_encode(['success' => true, 'message' => 'Selected items restored successfully.']);
    } elseif ($action === 'delete') {
        $delete_archive_sql = "DELETE FROM archived_inventory_tbl WHERE archive_id = ?";
        $delete_archive_stmt = mysqli_prepare($connection, $delete_archive_sql);
        if (!$delete_archive_stmt) {
            throw new Exception('Failed to prepare archive delete statement: ' . mysqli_error($connection));
        }
        // CORRECTED LINE 131: Use $selectedIds instead of $selectedArchiveIds
        foreach ($selectedIds as $archive_id) {
            $archive_id = intval($archive_id);
            if ($archive_id <= 0) {
                error_log("Skipping invalid archive_id during permanent delete: " . $archive_id);
                continue; // Skip invalid IDs
            }
            mysqli_stmt_bind_param($delete_archive_stmt, 'i', $archive_id);
            if (!mysqli_stmt_execute($delete_archive_stmt)) {
                throw new Exception('Failed to delete from archive: ' . mysqli_stmt_error($delete_archive_stmt));
            }
        }
        mysqli_stmt_close($delete_archive_stmt);
        mysqli_commit($connection);
        echo json_encode(['success' => true, 'message' => 'Selected archived items permanently deleted.']);
    } else {
        throw new Exception('Unknown action requested.');
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
