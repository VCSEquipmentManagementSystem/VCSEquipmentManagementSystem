<?php
include './database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1) Grab supplier_id from POST
    $supplier_id = intval($_POST['supplier_id'] ?? 0);

    // 2) Grab and trim your other fields
    $companyName     = trim($_POST['editCompanyName'] ?? '');
    $productService  = trim($_POST['editProductService'] ?? '');
    $contactPerson   = trim($_POST['editContactPerson'] ?? '');
    $contactNo       = trim($_POST['editSupplierContactNum'] ?? '');
    $email           = trim($_POST['editEmail'] ?? '');

    // 3) Validate
    if (
        $supplier_id <= 0
        || $companyName === ''
        || $contactPerson === ''
        || $contactNo === ''
        || $email === ''
    ) {
        $_SESSION['error'] = "All required fields must be filled out.";
        header("Location: SupplierInformation.php");
        exit();
    }

    // 4) Prepare & execute
    $stmt = $connection->prepare(" UPDATE supplier_tbl SET supplier_comp_name = ?, product_service = ?, contact_person = ?, sup_contact_num = ?, sup_email = ? WHERE supplier_id = ?");
    $stmt->bind_param(
        "sssssi",
        $companyName,
        $productService,
        $contactPerson,
        $contactNo,
        $email,
        $supplier_id
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = "Supplier updated successfully.";
    } else {
        $_SESSION['error'] = "Error updating supplier: " . $stmt->error;
    }

    $stmt->close();
    $connection->close();
    header("Location: SupplierInformation.php");
    exit();
}
