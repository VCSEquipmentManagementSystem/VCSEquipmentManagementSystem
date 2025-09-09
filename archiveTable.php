<?php
function archiveEquipment($equipmentID, $connection)
{
    // Check for duplicate
    $checkQuery = "SELECT * FROM archivedequipment_tbl WHERE equipmentID = '$equipmentID'";
    $checkResult = mysqli_query($connection, $checkQuery);

    if (mysqli_num_rows($checkResult) == 0) {
        // No duplicate, proceed with archiving
        $insertQuery = "INSERT INTO archivedequipment_tbl SELECT * FROM equipment_tbl WHERE equipmentID = '$equipmentID'";
        mysqli_query($connection, $insertQuery);

        // Delete from original table if needed
        $deleteQuery = "DELETE FROM archivedequipment_tbl WHERE equipmentID = '$equipmentID'";
        mysqli_query($connection, $deleteQuery);
    } else {
        echo "Record already archived.";
    }
}
