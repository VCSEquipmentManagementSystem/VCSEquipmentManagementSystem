<?php
include './database.php';
session_start();

if (isset($_POST['delete'])) {
    $deleteId = intval($_POST['deleteId']);

    $queryArchive = "INSERT INTO archived_supplier_tbl (supplier_id, supplier_comp_name, product_service, contact_person, sup_contact_num, sup_email)
                    SELECT supplier_id, supplier_comp_name, product_service, contact_person, sup_contact_num, sup_email
                    FROM supplier_tbl WHERE supplier_id = $deleteId";
    $sqlArchive = mysqli_query($connection, $queryArchive);
    if (!$sqlArchive) {
        echo '<script>alert("Error archiving supplier: ' . mysqli_error($connection) . '")</script>';
        exit();
    }

    $queryArchive = "DELETE FROM supplier_tbl WHERE supplier_id = $deleteId";
    $sqlArchive = mysqli_query($connection, $queryArchive);

    if ($sqlArchive) {
        echo '<script>alert("Successfully archived")</script>';
    } else {
        echo '<script>alert("Error archiving supplier: ' . mysqli_error($connection) . '")</script>';
    }
} else {
    echo '<script>alert("No delete parameter received.")</script>';
}

echo '<script>window.location.href = "SupplierInformation.php";</script>';
// Close the database connection
$connection->close();
