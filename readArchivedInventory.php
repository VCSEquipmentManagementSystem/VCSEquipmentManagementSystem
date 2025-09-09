<?php
require('./database.php');

$archivedInventoryRows = [];

try {
    $sql = "
        SELECT
            ai.archive_id,
            ai.spare_id,
            ai.part_id,
            ai.part_name,      -- Directly select from archived_inventory_tbl
            ai.part_number,    -- Directly select from archived_inventory_tbl
            ai.brand_name,     -- Directly select from archived_inventory_tbl
            ai.stock_quantity,
            ai.unit_price,
            ai.last_update,
            ai.archive_date,
            ai.custom_equip_id,
            st.supplier_comp_name AS supplier_name
        FROM
            archived_inventory_tbl ai
        LEFT JOIN
            supplier_tbl st ON ai.supplier_id = st.supplier_id
        ORDER BY
            ai.archive_date DESC
    ";

    $result = mysqli_query($connection, $sql);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $archivedInventoryRows[] = $row;
        }
        mysqli_free_result($result);
    } else {
        error_log("Error fetching archived inventory: " . mysqli_error($connection));
    }
} catch (Exception $e) {
    error_log("Exception in readArchivedInventory.php: " . $e->getMessage());
} finally {
}
