<?php
include('./database.php');

if (isset($_POST['addBrand'])) {
    $brandName = $_POST['addBrandInput'];

    // Check if the brand already exists
    $checkQuery = "INSERT INTO brand_tbl (brand_name) VALUES (?)";
    $stmt = $connection->prepare($checkQuery);
    $stmt->bind_param("s", $brandName);

    if ($stmt->execute()) {
        // Brand added successfully
        echo "<script> Swal.fire({ icon: 'success', title: 'Brand Added Successfully', text: 'The brand has been added to the database.', confirmButtonText: 'OK' }).then((result) => { if (result.isConfirmed) { window.location.href = 'Inventory.php'; } }); </script>";
    } else {
        // Error adding brand
        echo "<script> Swal.fire({ icon: 'error', title: 'Error Adding Brand', text: 'There was an error adding the brand. Please try again.', confirmButtonText: 'OK' }); </script>";
    }
    header("Location: Inventory.php");
    exit();
} else {
    // If the form is not submitted, redirect to Inventory.php
    header("Location: Inventory.php");
    exit();
}
