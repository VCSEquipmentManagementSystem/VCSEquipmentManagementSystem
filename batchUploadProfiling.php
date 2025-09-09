<?php
error_reporting(E_ALL);
ini_set('display_errors', 1); 
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

require './database.php';
require '../Thesis-Equipment-Management-System/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;

header('Content-Type: application/json');
ob_start();

// Error logger
function logError($message, $context = []) {
    $logFile = '../logs/batch_upload_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? json_encode($context) : '';
    $logMessage = "[$timestamp] $message $contextStr\n";
    error_log($logMessage, 3, $logFile);
}

function validateOperatorId($connection, $operatorId) {
    if (empty($operatorId) || strtolower(trim($operatorId)) === 'none') {
        return null;
    }
    $operatorId = trim($operatorId);

    $stmt = mysqli_prepare($connection, 
        "SELECT employee_id FROM employee_tbl 
         WHERE (employee_id = ? OR company_emp_id = ?) 
         AND emp_status = 'Active'"
    );
    mysqli_stmt_bind_param($stmt, 'ss', $operatorId, $operatorId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($row) {
        return $row['employee_id'];
    }

    $stmt = mysqli_prepare($connection, 
        "SELECT employee_id FROM employee_tbl 
         WHERE CONCAT(first_name, ' ', last_name) = ? 
         AND emp_status = 'Active'"
    );
    mysqli_stmt_bind_param($stmt, 's', $operatorId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $row ? $row['employee_id'] : null;
}

function validateRowData($rowData, $rowNum, $fileName) {
    $errors = [];
    // FIX #1: Removed 'equip_year' and 'transmission_type' from the required fields list.
    $requiredFields = ['equip_type', 'model'];
    
    foreach ($requiredFields as $field) {
        $isMissing = false;

        if (!isset($rowData[$field]) || trim($rowData[$field]) === '') {
             $isMissing = true;
        }

        if ($isMissing) {
            $errors[] = "File '$fileName' - Row $rowNum: " . ucfirst(str_replace('_', ' ', $field)) . " is required";
        }
    }

    if (!empty($rowData['equip_year'])) {
        $year = (int)$rowData['equip_year'];
        if ($year < 1900 || $year > date('Y')) {
            $errors[] = "File '$fileName' - Row $rowNum: Year must be between 1900 and " . date('Y');
        }
    }
    return $errors;
}

function processFile($fileInfo, $connection) {
    $fileName = $fileInfo['name'];
    $tempDir = sys_get_temp_dir();
    $tempFile = tempnam($tempDir, 'upload_');
    
    if ($tempFile === false) {
        throw new Exception("Failed to create temporary file for '$fileName'");
    }
    
    if (!copy($fileInfo['tmp_name'], $tempFile)) {
        unlink($tempFile);
        throw new Exception("Failed to process uploaded file '$fileName'");
    }

    try {
        $fileType = IOFactory::identify($tempFile);
        $reader = IOFactory::createReader($fileType);
        $reader->setReadDataOnly(true);

        if ($fileType === 'Csv') {
            $content = file_get_contents($tempFile);
            $content = str_replace(["\r\n", "\r"], "\n", $content);
            file_put_contents($tempFile, $content);
            if ($reader instanceof \PhpOffice\PhpSpreadsheet\Reader\Csv) {
                $reader->setInputEncoding('UTF-8');
                $reader->setDelimiter(',');
                $reader->setEnclosure('"');
            }
        }

        $spreadsheet = $reader->load($tempFile);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray(null, true, true, true);
        
        return ['rows' => $rows, 'fileName' => $fileName];
        
    } finally {
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }
}

// Only these columns are required in the upload
// FIX #2: Updated this array to reflect the new validation rules.
$requiredColumns = [
    'equip_type',
    'model'
];

try {
    // Check if files were uploaded
    if (!isset($_FILES['excelFiles'])) {
        throw new Exception('No files uploaded');
    }

    $uploadedFiles = $_FILES['excelFiles'];
    
    // Handle single file or multiple files
    $files = [];
    if (is_array($uploadedFiles['name'])) {
        // Multiple files
        for ($i = 0; $i < count($uploadedFiles['name']); $i++) {
            if ($uploadedFiles['error'][$i] === UPLOAD_ERR_OK) {
                $files[] = [
                    'name' => $uploadedFiles['name'][$i],
                    'tmp_name' => $uploadedFiles['tmp_name'][$i],
                    'error' => $uploadedFiles['error'][$i],
                    'size' => $uploadedFiles['size'][$i]
                ];
            } else if ($uploadedFiles['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
                ];
                $errorMessage = $uploadErrors[$uploadedFiles['error'][$i]] ?? 'Unknown upload error';
                throw new Exception("File '{$uploadedFiles['name'][$i]}' upload failed: " . $errorMessage);
            }
        }
    } else {
        // Single file
        if ($uploadedFiles['error'] === UPLOAD_ERR_OK) {
            $files[] = $uploadedFiles;
        } else {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
            ];
            $errorMessage = $uploadErrors[$uploadedFiles['error']] ?? 'Unknown upload error';
            throw new Exception('File upload failed: ' . $errorMessage);
        }
    }

    if (empty($files)) {
        throw new Exception('No valid files to process');
    }

    $allValidRows = [];
    $allErrors = [];
    $processedFiles = [];
    $totalRowsProcessed = 0;

    // Process each file
    foreach ($files as $fileInfo) {
        try {
            $fileData = processFile($fileInfo, $connection);
            $rows = $fileData['rows'];
            $fileName = $fileData['fileName'];
            
            $headers = array_shift($rows);
            $headers = array_map('trim', array_map('strtolower', $headers));

            // Check only required columns for this file
            $missingColumns = [];
            foreach ($requiredColumns as $col) {
                if (!in_array($col, $headers)) {
                    $missingColumns[] = $col;
                }
            }
            
            if (!empty($missingColumns)) {
                $allErrors[] = "File '$fileName': Missing required columns: " . implode(', ', $missingColumns);
                continue;
            }

            $validRows = [];
            $rowNum = 2;

            foreach ($rows as $row) {
                $rowData = array_combine($headers, $row);

                // FIX #3: Clean up row data. Trim all values and convert standalone "-" to null.
                foreach ($rowData as $key => &$value) {
                    $trimmedValue = trim((string) $value);
                    if ($trimmedValue === '-') {
                        $value = null;
                    } else {
                        $value = $trimmedValue;
                    }
                }
                unset($value);

                // Skip empty rows
                if (empty(array_filter($rowData))) {
                    $rowNum++;
                    continue;
                }

                $rowErrors = validateRowData($rowData, $rowNum, $fileName);
                if (!empty($rowErrors)) {
                    $allErrors = array_merge($allErrors, $rowErrors);
                    $rowNum++;
                    continue;
                }

                $operatorId = null;
                if (!empty($rowData['operator_id'])) {
                    $operatorId = validateOperatorId($connection, $rowData['operator_id']);
                    if ($operatorId === null && strtolower(trim($rowData['operator_id'])) !== 'none') {
                        $allErrors[] = "File '$fileName' - Row $rowNum: Invalid operator ID/Name: " . $rowData['operator_id'];
                        $rowNum++;
                        continue;
                    }
                }

                $stmt = mysqli_prepare($connection, 
                    "SELECT equip_type_id, prefix_code FROM equip_type_tbl WHERE equip_type_name = ?"
                );
                mysqli_stmt_bind_param($stmt, 's', $rowData['equip_type']);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $typeData = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);

                if (!$typeData) {
                    $allErrors[] = "File '$fileName' - Row $rowNum: Invalid equipment type: " . $rowData['equip_type'];
                    $rowNum++;
                    continue;
                }

                $prefix = $typeData['prefix_code'];
                $seqQuery = "SELECT MAX(CAST(SUBSTRING_INDEX(custom_equip_id, '-', -1) AS UNSIGNED)) 
                             FROM equip_tbl WHERE custom_equip_id LIKE '$prefix-%'";
                $seqResult = mysqli_query($connection, $seqQuery);
                $seqRow = mysqli_fetch_array($seqResult);
                $nextSeq = ($seqRow[0] ?? 0) + 1;
                
                $currentBatchCount = count($allValidRows);
                $adjustedSeq = $nextSeq + $currentBatchCount;
                $customEquipId = $prefix . '-' . str_pad($adjustedSeq, 3, '0', STR_PAD_LEFT);

                // FIX #4: Provided default values for 'equip_year' and 'transmission_type' if they are empty.
                $validRows[] = [
                    'custom_equip_id'      => $customEquipId,
                    'equip_type_id'        => $typeData['equip_type_id'],
                    'equip_status'         => $rowData['equip_status'] ?? 'Idle',
                    'deployment_status'    => $rowData['deployment_stat'] ?? 'Undeployed',
                    'model'                => $rowData['model'],
                    'equip_year'           => empty($rowData['equip_year']) ? 0 : intval($rowData['equip_year']),
                    'brand_id'             => null,
                    'operator_id'          => $operatorId,
                    'images'               => null,
                    'engine_type'          => $rowData['engine_type'] ?? null,
                    'engine_serial_num'    => $rowData['engine_serial_num'] ?? null,
                    'fuel_type'            => $rowData['fuel_type'] ?? 'Diesel',
                    'fuel_tank_capacity'   => null,
                    'transmission_type'    => empty($rowData['transmission_type']) ? 'N/A' : $rowData['transmission_type'],
                    'last_operating_hours' => empty($rowData['last_operating_hours']) ? 0 : $rowData['last_operating_hours'],
                    'capacity'             => $rowData['capacity'] ?? null,
                    'maintenance_interval' => null,
                    'body_id'              => $rowData['body_id'] ?? null,
                    'last_pms_date'        => (!empty($rowData['last_pms_date']) && strtolower($rowData['last_pms_date']) !== 'n/a') ? date('Y-m-d', strtotime($rowData['last_pms_date'])) : null,
                    'equip_remarks'        => ($rowData['remarks'] ?? null),
                    'assigned_proj_id'     => null,
                    'source_file'          => $fileName
                ];

                $rowNum++;
            }

            $allValidRows = array_merge($allValidRows, $validRows);
            $processedFiles[] = [
                'name' => $fileName,
                'rows' => count($validRows)
            ];
            $totalRowsProcessed += count($validRows);

        } catch (Exception $e) {
            $allErrors[] = "File '{$fileInfo['name']}': " . $e->getMessage();
            logError("Error processing file {$fileInfo['name']}", ['error' => $e->getMessage()]);
        }
    }

    if (!empty($allErrors)) {
        ob_clean();
        echo json_encode([
            'status' => 'error',
            'message' => 'Validation errors found in uploaded files',
            'errors' => $allErrors,
            'processed_files' => $processedFiles
        ]);
        exit;
    }

    if (empty($allValidRows)) {
        ob_clean();
        echo json_encode([
            'status' => 'error',
            'message' => 'No valid data found in any of the uploaded files'
        ]);
        exit;
    }

    mysqli_begin_transaction($connection);

    try {
        foreach ($allValidRows as $data) {
            $sql = "INSERT INTO equip_tbl (
                custom_equip_id, equip_type_id, equip_status, deployment_status, model, equip_year, brand_id,
                operator_id, images, engine_type, engine_serial_num, fuel_type, fuel_tank_capacity, transmission_type,
                last_operating_hours, capacity, maintenance_interval, body_id, last_pms_date, equip_remarks, assigned_proj_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($connection, $sql);
            if (!$stmt) {
                throw new Exception("Error preparing statement: " . mysqli_error($connection));
            }

            mysqli_stmt_bind_param($stmt, 
                'sisssissssssssdsssssi',
                $data['custom_equip_id'],
                $data['equip_type_id'],
                $data['equip_status'],
                $data['deployment_status'],
                $data['model'],
                $data['equip_year'],
                $data['brand_id'],
                $data['operator_id'],
                $data['images'],
                $data['engine_type'],
                $data['engine_serial_num'],
                $data['fuel_type'],
                $data['fuel_tank_capacity'],
                $data['transmission_type'],
                $data['last_operating_hours'],
                $data['capacity'],
                $data['maintenance_interval'],
                $data['body_id'],
                $data['last_pms_date'],
                $data['equip_remarks'],
                $data['assigned_proj_id']
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error inserting row from file '{$data['source_file']}': " . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        }

        mysqli_commit($connection);
        ob_clean();
        echo json_encode([
            'status' => 'success',
            'message' => $totalRowsProcessed . ' equipment records imported successfully from ' . count($processedFiles) . ' file(s)',
            'processed_files' => $processedFiles,
            'total_records' => $totalRowsProcessed
        ]);

    } catch (Exception $e) {
        mysqli_rollback($connection);
        throw $e;
    }

} catch (Exception | ReaderException $e) {
    if (isset($connection) && mysqli_connect_errno() === 0) {
        mysqli_rollback($connection);
    }

    logError('Multi-file upload process failed', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);

    ob_clean();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
} finally {
    if (isset($connection)) {
        mysqli_close($connection);
    }
    ob_end_flush();
}