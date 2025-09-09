<?php
session_start();
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'ian_db');
define('DB_NAME', 'emsdb');
$uploadDir = 'uploads/';

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {

        error_log("Error: Failed to create upload directory. Check permissions.");
        $_SESSION['upload_messages'] = ["Error: Server configuration issue. Cannot create upload directory."];
        header("Location: SupplierInformation.php");
        exit();
    }
}

$detailedMessages = [];
$successCount = 0;
$errorCount = 0;


if (isset($_POST['submit']) && isset($_FILES['csvFile'])) {
    $file = $_FILES['csvFile'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $detailedMessages[] = "Error: The uploaded file is too large.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $detailedMessages[] = "Error: The uploaded file was only partially uploaded.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $detailedMessages[] = "Error: No file was uploaded. Please select a CSV file.";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $detailedMessages[] = "Error: Missing a temporary folder for uploads.";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $detailedMessages[] = "Error: Failed to write file to disk.";
                break;
            case UPLOAD_ERR_EXTENSION:
                $detailedMessages[] = "Error: A PHP extension stopped the file upload.";
                break;
            default:
                $detailedMessages[] = "Error: An unknown file upload error occurred.";
                break;
        }
    } else {

        $fileMimeType = mime_content_type($file['tmp_name']);
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        $allowedMimeTypes = ['text/csv', 'application/vnd.ms-excel'];
        $allowedExtensions = ['csv'];

        if (!in_array($fileMimeType, $allowedMimeTypes) || !in_array($fileExtension, $allowedExtensions)) {
            $detailedMessages[] = "Error: Invalid file type. Only CSV files are allowed.";
        } else {

            $newFileName = uniqid('csv_upload_', true) . '.' . $fileExtension;
            $destinationPath = $uploadDir . $newFileName;

            if (move_uploaded_file($file['tmp_name'], $destinationPath)) {
                $detailedMessages[] = "File uploaded successfully. Processing data...";


                $conn = null;
                try {
                    $conn = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);

                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                } catch (PDOException $e) {
                    $detailedMessages[] = "Database connection failed: " . $e->getMessage();
                    error_log("Database connection error: " . $e->getMessage());
                    $conn = null;
                }

                if ($conn) {

                    if (($handle = fopen($destinationPath, "r")) !== FALSE) {

                        fgetcsv($handle);

                        $rowNum = 1;
                        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                            $rowNum++;
                            if (count($data) !== 5) {
                                $detailedMessages[] = "Row $rowNum: Skipping due to incorrect number of columns. Expected 5, got " . count($data) . ".";
                                $errorCount++;
                                continue;
                            }
                            $supplier_comp_name = trim($data[0]);
                            $contact_person = trim($data[1]);
                            $sup_contact_num = trim($data[2]);
                            $sup_email = trim($data[3]);
                            $product_service = trim($data[4]);
                            $rowErrors = [];
                            if (empty($supplier_comp_name)) {
                                $rowErrors[] = "Company Name is empty.";
                            }
                            if (empty($contact_person)) {
                                $rowErrors[] = "Contact Person is empty.";
                            }
                            if (empty($sup_contact_num)) {
                                $rowErrors[] = "Contact Number is empty.";
                            }
                            if (empty($product_service)) {
                                $rowErrors[] = "Product/Service is empty.";
                            }
                            $sup_email_decoded = html_entity_decode($sup_email, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                            $sup_email_cleaned = preg_replace('/\s+/', '', $sup_email_decoded);
                            $sup_email_cleaned = preg_replace('/[[:cntrl:]]/', '', $sup_email_cleaned);
                            $sup_email_cleaned = strtolower($sup_email_cleaned);
                            $emptyPlaceholders = [
                                '',
                                '-',
                                'n/a',
                                'na',
                                'none',
                                'null',
                                'noemail',
                                'notapplicable',
                                'notavailable',
                                'tba',
                                'tbd',
                                'unknown',
                                'x',
                                'xx',
                                'xxx',
                                'notprovided',
                                'noemailaddress',
                                'nil',

                                'slm',
                                'cs',
                                'olb',
                                'cba',
                                'cs&cba',
                                'sls&cs',
                                'ofs&ps',
                                'sp',
                                'rsf',
                                'tb'
                            ];
                            if (empty($sup_email_cleaned) || in_array($sup_email_cleaned, $emptyPlaceholders)) {
                                $sup_email_for_db = '';
                            } else {
                                if (!filter_var($sup_email_decoded, FILTER_VALIDATE_EMAIL)) {
                                    $rowErrors[] = "Invalid Email format.";

                                    $detailedMessages[] = "DEBUG (Row $rowNum): Problematic email: '" . htmlspecialchars($sup_email) . "' (decoded: '" . htmlspecialchars($sup_email_decoded) . "')";
                                }
                                $sup_email_for_db = $sup_email_decoded;
                            }
                            if (!empty($rowErrors)) {
                                $detailedMessages[] = "Row $rowNum: Data validation failed: " . implode(", ", $rowErrors);
                                $errorCount++;
                                continue;
                            }
                            try {
                                $stmt = $conn->prepare("INSERT INTO supplier_tbl (supplier_comp_name, contact_person, sup_contact_num, product_service, sup_email) VALUES (?, ?, ?, ?, ?)");
                                $stmt->execute([
                                    $supplier_comp_name,
                                    $contact_person,
                                    $sup_contact_num,
                                    $product_service,
                                    $sup_email_for_db
                                ]);
                                $successCount++;
                            } catch (PDOException $e) {
                                $detailedMessages[] = "Row $rowNum: Database error during insertion: " . $e->getMessage();
                                $errorCount++;
                            }
                        }
                        fclose($handle);
                        $detailedMessages[] = "CSV processing complete. Successfully inserted: $successCount, Failed: $errorCount.";
                        if ($errorCount === 0 && $successCount > 0) {
                            $_SESSION['success'] = "Batch upload successful! " . $successCount . " supplier(s) added.";
                        }
                    } else {
                        $detailedMessages[] = "Error: Could not open the uploaded CSV file.";
                    }
                }
                if (file_exists($destinationPath)) {
                    unlink($destinationPath);
                }
            } else {
                $detailedMessages[] = "Error: Failed to move the uploaded file.";
            }
        }
    }
} else {
    $detailedMessages[] = "No file submitted or invalid request.";
}
if (!empty($detailedMessages) && ($errorCount > 0 || $successCount === 0)) {
    $_SESSION['upload_messages'] = $detailedMessages;
    $_SESSION['upload_success_count'] = $successCount;
    $_SESSION['upload_error_count'] = $errorCount;
} else {
    unset($_SESSION['upload_messages']);
    unset($_SESSION['upload_success_count']);
    unset($_SESSION['upload_error_count']);
}
header("Location: SupplierInformation.php");
exit();
