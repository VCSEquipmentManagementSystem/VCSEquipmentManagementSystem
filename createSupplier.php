<?php
// Connect database
include './database.php';
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form data
    $companyName = trim($_POST['companyNameInput'] ?? '');
    $productService = trim($_POST['productServiceInput'] ?? '');
    $contactPerson = trim($_POST['contactPersonInput'] ?? '');
    $supplierContact = trim($_POST['contactNoInput'] ?? '');
    $email = trim($_POST['emailInput'] ?? '');

    // Validate inputs
    if (empty($companyName) || empty($contactPerson) || empty($supplierContact) || empty($email)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: SupplierInformation.php");
        exit();
    }

    // Prepare and execute the SQL statement
    $stmt = $connection->prepare("INSERT INTO supplier_tbl (supplier_comp_name, product_service, contact_person, sup_contact_num, sup_email) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $companyName, $productService, $contactPerson, $supplierContact, $email);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Supplier created successfully.";
        header("Location: SupplierInformation.php");
        exit();
    } else {
        $_SESSION['error'] = "Error creating supplier: " . $stmt->error;
        header("Location: createSupplier.php");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    if (isset($_SESSION['error'])) {
        echo "<div style='color: red;'>" . htmlspecialchars($_SESSION['error']) . "</div>";
        unset($_SESSION['error']);
    } else {
        echo "Invalid request.";
    }
    exit();
}
