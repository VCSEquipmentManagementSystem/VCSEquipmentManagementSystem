<?php
// archiveInventory.php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);
require './database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['selected_spare_ids'])) {
        throw new Exception('Invalid request or missing selected IDs.');
    }

    $selectedSpareIds = $_POST['selected_spare_ids'];

    if (!is_array($selectedSpareIds) || empty($selectedSpareIds)) {
        throw new Exception('No items selected for archiving.');
    }

    mysqli_begin_transaction($connection);

    foreach ($selectedSpareIds as $spare_id) {
        $spare_id = intval($spare_id);
        if ($spare_id <= 0) {
            continue; // Skip invalid IDs
        }

        // 1. Select data from spareparts_inventory_tbl, inventory_parts_tbl, brand_tbl, and equip_tbl
        //    Ensure all fields needed for archived_inventory_tbl are selected.
        $select_sql = "
            SELECT
                si.spare_id,
                si.part_id,
                ip.part_name,
                ip.part_number,
                bt.brand_name,
                si.equipment_id,
                ep.custom_equip_id,
                si.stock_quantity,
                si.supplier_id,
                si.unit_price,
                si.last_update
            FROM
                spareparts_inventory_tbl si
            LEFT JOIN
                inventory_parts_tbl ip ON si.part_id = ip.part_id
            LEFT JOIN
                brand_tbl bt ON ip.brand_id = bt.brand_id
            LEFT JOIN
                equip_tbl ep ON si.equipment_id = ep.equipment_id
            WHERE
                si.spare_id = ?
        ";
        $select_stmt = mysqli_prepare($connection, $select_sql);
        if (!$select_stmt) {
            throw new Exception('Failed to prepare select statement: ' . mysqli_error($connection));
        }
        mysqli_stmt_bind_param($select_stmt, 'i', $spare_id);
        mysqli_stmt_execute($select_stmt);
        $result = mysqli_stmt_get_result($select_stmt);
        $row_to_archive = mysqli_fetch_assoc($result);
        mysqli_stmt_close($select_stmt);

        if (!$row_to_archive) {
            // Item not found in main inventory, skip or log
            error_log("Attempted to archive non-existent spare_id: " . $spare_id);
            continue;
        }

        // 2. Insert into archived_inventory_tbl with all necessary fields
        $insert_archive_sql = "
            INSERT INTO archived_inventory_tbl (
                spare_id, part_id, part_name, part_number, brand_name,
                equipment_id, custom_equip_id,
                stock_quantity, supplier_id, unit_price, last_update
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $insert_archive_stmt = mysqli_prepare($connection, $insert_archive_sql);
        if (!$insert_archive_stmt) {
            throw new Exception('Failed to prepare archive insert statement: ' . mysqli_error($connection));
        }
        mysqli_stmt_bind_param(
            $insert_archive_stmt,
            'iisssisidis', // Types: int, int, string, string, string, int, string, int, int, decimal, timestamp
            $row_to_archive['spare_id'],
            $row_to_archive['part_id'],
            $row_to_archive['part_name'],
            $row_to_archive['part_number'],
            $row_to_archive['brand_name'],
            $row_to_archive['equipment_id'],
            $row_to_archive['custom_equip_id'],
            $row_to_archive['stock_quantity'],
            $row_to_archive['supplier_id'],
            $row_to_archive['unit_price'],
            $row_to_archive['last_update']
        );
        if (!mysqli_stmt_execute($insert_archive_stmt)) {
            throw new Exception('Failed to insert into archive: ' . mysqli_stmt_error($insert_archive_stmt));
        }
        mysqli_stmt_close($insert_archive_stmt);

        // 3. Delete from spareparts_inventory_tbl
        $delete_spareparts_sql = "DELETE FROM spareparts_inventory_tbl WHERE spare_id = ?";
        $delete_spareparts_stmt = mysqli_prepare($connection, $delete_spareparts_sql);
        if (!$delete_spareparts_stmt) {
            throw new Exception('Failed to prepare spareparts delete statement: ' . mysqli_error($connection));
        }
        mysqli_stmt_bind_param($delete_spareparts_stmt, 'i', $spare_id);
        if (!mysqli_stmt_execute($delete_spareparts_stmt)) {
            throw new Exception('Failed to delete from spareparts: ' . mysqli_stmt_error($delete_spareparts_stmt));
        }
        mysqli_stmt_close($delete_spareparts_stmt);

        // 4. Delete from inventory_parts_tbl (if part_id is no longer referenced anywhere else)
        $part_id_for_delete = $row_to_archive['part_id'];
        $check_part_usage_sql = "SELECT COUNT(*) FROM spareparts_inventory_tbl WHERE part_id = ?";
        $check_part_usage_stmt = mysqli_prepare($connection, $check_part_usage_sql);
        if (!$check_part_usage_stmt) {
            throw new Exception('Failed to prepare part usage check statement: ' . mysqli_error($connection));
        }
        mysqli_stmt_bind_param($check_part_usage_stmt, 'i', $part_id_for_delete);
        mysqli_stmt_execute($check_part_usage_stmt);
        $part_usage_result = mysqli_stmt_get_result($check_part_usage_stmt);
        $part_usage_row = mysqli_fetch_row($part_usage_result);
        $part_references = $part_usage_row[0];
        mysqli_stmt_close($check_part_usage_stmt);

        if ($part_references == 0) { // Only delete from inventory_parts_tbl if no other spareparts reference this part_id
            $delete_parts_sql = "DELETE FROM inventory_parts_tbl WHERE part_id = ?";
            $delete_parts_stmt = mysqli_prepare($connection, $delete_parts_sql);
            if (!$delete_parts_stmt) {
                throw new Exception('Failed to prepare parts delete statement: ' . mysqli_error($connection));
            }
            mysqli_stmt_bind_param($delete_parts_stmt, 'i', $part_id_for_delete);
            if (!mysqli_stmt_execute($delete_parts_stmt)) {
                throw new Exception('Failed to delete from inventory parts: ' . mysqli_stmt_error($delete_parts_stmt));
            }
            mysqli_stmt_close($delete_parts_stmt);
        }
    }

    mysqli_commit($connection);
    echo json_encode(['success' => true, 'message' => 'Selected items archived successfully.']);
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
