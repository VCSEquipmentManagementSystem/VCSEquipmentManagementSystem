<<<<<<< HEAD
    <?php
    include './database.php';
    session_start();

    if (isset($_POST['restoreIds']) && is_array($_POST['restoreIds'])) {
        $restoreIds = $_POST['restoreIds'];

        foreach ($restoreIds as $id) {
            $id = intval($id);

            // Insert into supplier_tbl
            $insertQuery = "INSERT INTO supplier_tbl (supplier_comp_name, product_service, contact_person, sup_contact_num, sup_email)
                        SELECT supplier_comp_name, product_service, contact_person, sup_contact_num, sup_email
                        FROM archived_supplier_tbl WHERE supplier_id = $id";
            $insertResult = mysqli_query($connection, $insertQuery);

            if (!$insertResult) {
                $_SESSION['error'] = "Restore failed for ID $id: " . mysqli_error($connection);
                header("Location: SupplierInformation.php");
                exit();
            }

            // Delete from archived
            $deleteQuery = "DELETE FROM archived_supplier_tbl WHERE supplier_id = $id";
            mysqli_query($connection, $deleteQuery);
        }

        $_SESSION['success'] = "Selected suppliers restored successfully.";
    } else {
        $_SESSION['error'] = "No suppliers selected for restoration.";
    }

    header("Location: SupplierInformation.php");
    exit();
