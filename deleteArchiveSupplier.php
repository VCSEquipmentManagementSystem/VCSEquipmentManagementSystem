<<<<<<< HEAD
    <?php
    include './database.php';
    session_start();

    if (!isset($_POST['selectedIds']) || !is_array($_POST['selectedIds'])) {
        $_SESSION['error'] = "No suppliers selected.";
        header("Location: SupplierInformation.php");
        exit();
    }

    $action = $_POST['action'];
    $ids = $_POST['selectedIds'];

    foreach ($ids as $id) {
        $id = intval($id);

        if ($action === "restore") {
            // Restore to supplier_tbl
            $insertQuery = "INSERT INTO supplier_tbl (supplier_comp_name, product_service, contact_person, sup_contact_num, sup_email)
                        SELECT supplier_comp_name, product_service, contact_person, sup_contact_num, sup_email
                        FROM archived_supplier_tbl WHERE supplier_id = $id";
            $insertResult = mysqli_query($connection, $insertQuery);

            if ($insertResult) {
                $deleteQuery = "DELETE FROM archived_supplier_tbl WHERE supplier_id = $id";
                mysqli_query($connection, $deleteQuery);
            }
        } elseif ($action === "delete") {
            // Permanently delete from archive
            $deleteQuery = "DELETE FROM archived_supplier_tbl WHERE supplier_id = $id";
            mysqli_query($connection, $deleteQuery);
        }
    }

    $_SESSION['success'] = $action === "restore" ? "Suppliers restored." : "Suppliers deleted.";
    header("Location: SupplierInformation.php");
    exit();
