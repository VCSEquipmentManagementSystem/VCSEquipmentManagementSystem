<?php
include './database.php';

$querySupplier = "SELECT * FROM supplier_tbl";
$sqlSupplier = mysqli_query($connection, $querySupplier);

$queryArchivedSupplier = "SELECT * FROM archived_supplier_tbl";
$sqlArchivedSupplier = mysqli_query($connection, $queryArchivedSupplier);
