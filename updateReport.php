<?php
session_start();
// updateReport
include('./database.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    function getEmployeeIdFromName($connection, $name)
    {
        if (empty($name)) {
            return null;
        }

        $stmt = $connection->prepare("SELECT employee_id FROM user_tbl WHERE name = ? AND employee_id IS NOT NULL");
        if (!$stmt) {
            error_log("Failed to prepare getEmployeeIdFromName query: " . $connection->error);
            return null;
        }
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stmt->close();
            return $row['employee_id'];
        }
        $stmt->close();
        return null;
    }

    function getIdFromName($connection, $tableName, $idColumn, $nameColumn, $nameValue)
    {
        if (empty($nameValue)) {
            return null;
        }
        $query = "SELECT $idColumn FROM $tableName WHERE $nameColumn = ?";
        $stmt = $connection->prepare($query);
        if (!$stmt) {
            error_log("Failed to prepare getIdFromName query for $tableName: " . $connection->error);
            return null;
        }
        $stmt->bind_param("s", $nameValue);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stmt->close();
            return $row[$idColumn];
        }
        $stmt->close();
        return null;
    }

    $reportType = $_POST['reportType'] ?? '';

    if ($reportType === 'Maintenance' || $reportType === 'Repair' || $reportType === 'Breakdown') {
        $report_id = $_POST['report_id'] ?? null;
        if (is_null($report_id)) {
            $_SESSION['message'] = "Error: Report ID is missing for update.";
            header('Location: Reports.php');
            exit();
        }
        $report_id = (int)$report_id;

        $reportDate_post = $_POST['date'] ?? null;
        $equipmentID_custom = $_POST['equipmentID'] ?? '';
        $operatorName = $_POST['operatorName'] ?? '';
        $inspectedBy = $_POST['inspectedBy'] ?? '';
        $problemEncounter = $_POST['problemEncounter'] ?? '';
        $finalDiagnosis = $_POST['finalDiagnosis'] ?? '';
        $detailsOfWork = $_POST['detailsOfWork'] ?? '';
        $diagnosisRemarks = $_POST['diagnosisRemarks'] ?? '';
        $partId = $_POST['partId'] ?? null;
        $description = $_POST['description'] ?? '';
        $quantity = $_POST['quantity'] ?? null;
        $unit = $_POST['unit'] ?? '';
        $lastReplacement_post = $_POST['lastReplacement'] ?? null;
        $conductedByName = $_POST['conductedBy'] ?? '';
        $dateStarted_post = $_POST['dateStarted'] ?? null;
        $dateCompleted_post = $_POST['dateCompleted'] ?? null;
        $timeStarted_post = $_POST['timeStarted'] ?? null;
        $timeCompleted_post = $_POST['timeCompleted'] ?? null;
        $acceptedBy = $_POST['acceptedBy'] ?? '';
        $jobCompletionVerifiedBy = $_POST['jobCompletionVerifiedBy'] ?? '';
        $conductedByChief = $_POST['conductedByChief'] ?? '';
        $workDetailsRemarks = $_POST['workDetailsRemarks'] ?? '';
        $reportStatus = $_POST['reportStatus'] ?? 'Open';
        $repairedByName = $_POST['repairedBy'] ?? '';

        $equipment_id_fk = getIdFromName($connection, 'equip_tbl', 'equipment_id', 'custom_equip_id', $equipmentID_custom);
        $operator_id_fk = getEmployeeIdFromName($connection, $operatorName);
        $inspected_by_employee_id = getEmployeeIdFromName($connection, $inspectedBy);
        $conducted_by_employee_id = getEmployeeIdFromName($connection, $conductedByName);
        $repaired_by_employee_id = getEmployeeIdFromName($connection, $repairedByName);

        $report_by_employee_id = null;
        if (isset($_SESSION['userID'])) {
            $report_by_employee_id = getIdFromName($connection, 'user_tbl', 'employee_id', 'user_id', $_SESSION['userID']);
        }

        $missing_ids = [];
        if (is_null($equipment_id_fk) && !empty($equipmentID_custom)) $missing_ids[] = 'Equipment (ID: ' . htmlspecialchars($equipmentID_custom) . ')';
        if (is_null($operator_id_fk) && !empty($operatorName)) $missing_ids[] = 'Operator (Name: ' . htmlspecialchars($operatorName) . ')';
        if (!empty($inspectedBy) && is_null($inspected_by_employee_id)) $missing_ids[] = 'Inspected By (Name: ' . htmlspecialchars($inspectedBy) . ')';
        if (!empty($conductedByName) && is_null($conducted_by_employee_id)) $missing_ids[] = 'Conducted By (Name: ' . htmlspecialchars($conductedByName) . ')';
        if (!empty($repairedByName) && is_null($repaired_by_employee_id)) $missing_ids[] = 'Repaired By (Name: ' . htmlspecialchars($repairedByName) . ')';

        if (!empty($missing_ids)) {
            $_SESSION['message'] = "Error: One or more required fields could not be found or mapped to an ID: " . implode(', ', $missing_ids) . ". Please check spelling or database entries.";
            header('Location: Reports.php');
            exit();
        }

        $partId_bind = ($partId !== null && $partId !== '') ? (int)$partId : null;
        $quantity_bind = ($quantity !== null && $quantity !== '') ? (int)$quantity : null;
        $acceptedBy_for_bind = $acceptedBy;
        $jobCompletionVerifiedBy_for_bind = $jobCompletionVerifiedBy;

        $reportDate = null;
        if (!empty($reportDate_post)) {
            $dt = DateTime::createFromFormat('Y-m-d', $reportDate_post);
            if ($dt && $dt->format('Y-m-d') === $reportDate_post) {
                $reportDate = $dt->format('Y-m-d');
            } else {
                error_log("Invalid reportDate format received: '" . $reportDate_post . "'. Expected YYYY-MM-DD.");
            }
        }

        $lastReplacement = null;
        if (!empty($lastReplacement_post)) {
            $dt = DateTime::createFromFormat('Y-m-d', $lastReplacement_post);
            if ($dt && $dt->format('Y-m-d') === $lastReplacement_post) {
                $lastReplacement = $dt->format('Y-m-d');
            } else {
                error_log("Invalid lastReplacement format received: '" . $lastReplacement_post . "'. Expected YYYY-MM-DD.");
            }
        }

        $dateStarted = null;
        if (!empty($dateStarted_post)) {
            $dt = DateTime::createFromFormat('Y-m-d', $dateStarted_post);
            if ($dt && $dt->format('Y-m-d') === $dateStarted_post) {
                $dateStarted = $dt->format('Y-m-d');
            } else {
                error_log("Invalid dateStarted format received: '" . $dateStarted_post . "'. Expected YYYY-MM-DD.");
            }
        }

        $dateCompleted = null;
        if (!empty($dateCompleted_post)) {
            $dt = DateTime::createFromFormat('Y-m-d', $dateCompleted_post);
            if ($dt && $dt->format('Y-m-d') === $dateCompleted_post) {
                $dateCompleted = $dt->format('Y-m-d');
            } else {
                error_log("Invalid dateCompleted format received: '" . $dateCompleted_post . "'. Expected YYYY-MM-DD.");
            }
        }

        $timeStarted = null;
        if (!empty($timeStarted_post)) {
            $dt = DateTime::createFromFormat('H:i', $timeStarted_post);
            if ($dt && $dt->format('H:i') === $timeStarted_post) {
                $timeStarted = $dt->format('H:i:s');
            } else {
                $dt = DateTime::createFromFormat('H:i:s', $timeStarted_post);
                if ($dt && $dt->format('H:i:s') === $timeStarted_post) {
                    $timeStarted = $dt->format('H:i:s');
                } else {
                    error_log("Invalid timeStarted format received: '" . $timeStarted_post . "'. Expected HH:MM or HH:MM:SS.");
                }
            }
        }

        $timeCompleted = null;
        if (!empty($timeCompleted_post)) {
            $dt = DateTime::createFromFormat('H:i', $timeCompleted_post);
            if ($dt && $dt->format('H:i') === $timeCompleted_post) {
                $timeCompleted = $dt->format('H:i:s');
            } else {
                $dt = DateTime::createFromFormat('H:i:s', $timeCompleted_post);
                if ($dt && $dt->format('H:i:s') === $timeCompleted_post) {
                    $timeCompleted = $dt->format('H:i:s');
                } else {
                    error_log("Invalid timeCompleted format received: '" . $timeCompleted_post . "'. Expected HH:MM or HH:MM:SS.");
                }
            }
        }

        $sql = "UPDATE report_tbl SET
    equipment_id = ?, report_type = ?, report_date = ?, operator_id = ?, report_by = ?,
    inspected_by = ?, problem_encountered = ?, final_diagnosis = ?,
    details_of_work_done = ?, remarks_report = ?, part_id = ?, part_description = ?,
    quantity = ?, unit = ?, last_replacement_date = ?, conducted_by = ?,
    date_started = ?, time_started = ?, date_completed = ?, time_completed = ?,
    accepted_by = ?, job_completion_verified_by = ?, remarks_job_completion = ?,
    report_status = ?, repaired_by = ?
    WHERE report_id = ?";

        $stmt = $connection->prepare($sql);
        if (!$stmt) {
            $_SESSION['message'] = "Database error preparing report update: " . $connection->error;
            header('Location: Reports.php');
            exit();
        }

        $stmt->bind_param(
            "issiiissssisississssssssii",
            $equipment_id_fk,
            $reportType,
            $reportDate,
            $operator_id_fk,
            $report_by_employee_id,
            $inspected_by_employee_id,
            $problemEncounter,
            $finalDiagnosis,
            $detailsOfWork,
            $diagnosisRemarks,
            $partId_bind,
            $description,
            $quantity_bind,
            $unit,
            $lastReplacement,
            $conducted_by_employee_id,
            $dateStarted,
            $timeStarted,
            $dateCompleted,
            $timeCompleted,
            $acceptedBy_for_bind,
            $jobCompletionVerifiedBy_for_bind,
            $workDetailsRemarks,
            $reportStatus,
            $repaired_by_employee_id,
            $report_id
        );

        if ($stmt->execute()) {
            $_SESSION['message'] = "Report ID " . $report_id . " updated successfully!";
        } else {
            $_SESSION['message'] = "Error updating report ID " . $report_id . ": " . $stmt->error;
        }
        $stmt->close();
        header('Location: Reports.php');
        exit();
    } elseif ($reportType === 'Equipment Usage') {
        $usage_id = $_POST['usage_id'] ?? null;
        if (is_null($usage_id)) {
            $_SESSION['message'] = "Error: Usage ID is missing for update.";
            header('Location: Reports.php');
            exit();
        }

        $equipmentUsageId_custom = $_POST['equipmentUsageId'] ?? '';
        $projectName = $_POST['projectName'] ?? '';
        $timeIn_post = $_POST['timeIn'] ?? null;
        $timeOut_post = $_POST['timeOut'] ?? null;
        $operatorUsageName = $_POST['operatorName'] ?? '';
        $operatingHours = $_POST['operatingHours'] ?? null;
        $natureOfWork = $_POST['natureOfWork'] ?? '';
        $logRemarks = $_POST['logRemarks'] ?? '';

        $equipment_id_fk = getIdFromName($connection, 'equip_tbl', 'equipment_id', 'custom_equip_id', $equipmentUsageId_custom);
        $project_id_fk = getIdFromName($connection, 'proj_sched_tbl', 'project_id', 'project_name', $projectName);
        $operator_id_fk = getEmployeeIdFromName($connection, $operatorUsageName);

        $missing_ids_usage = [];
        if (is_null($equipment_id_fk) && !empty($equipmentUsageId_custom)) $missing_ids_usage[] = 'Equipment (ID: ' . htmlspecialchars($equipmentUsageId_custom) . ')';
        if (is_null($project_id_fk) && !empty($projectName)) $missing_ids_usage[] = 'Project (Name: ' . htmlspecialchars($projectName) . ')';
        if (is_null($operator_id_fk) && !empty($operatorUsageName)) $missing_ids_usage[] = 'Operator (Name: ' . htmlspecialchars($operatorUsageName) . ')';

        if (!empty($missing_ids_usage)) {
            $_SESSION['message'] = "Error: One or more required IDs (" . implode(', ', $missing_ids_usage) . ") could not be found for usage report update. Please check spelling or database entries.";
            header('Location: Reports.php');
            exit();
        }

        $timeIn = null;
        if (!empty($timeIn_post)) {
            $dt = DateTime::createFromFormat('H:i', $timeIn_post);
            if ($dt && $dt->format('H:i') === $timeIn_post) {
                $timeIn = $dt->format('H:i:s');
            } else {
                $dt = DateTime::createFromFormat('H:i:s', $timeIn_post);
                if ($dt && $dt->format('H:i:s') === $timeIn_post) {
                    $timeIn = $dt->format('H:i:s');
                } else {
                    error_log("Invalid timeIn format received: '" . $timeIn_post . "'. Expected HH:MM or HH:MM:SS.");
                }
            }
        }

        $timeOut = null;
        if (!empty($timeOut_post)) {
            $dt = DateTime::createFromFormat('H:i', $timeOut_post);
            if ($dt && $dt->format('H:i') === $timeOut_post) {
                $timeOut = $dt->format('H:i:s');
            } else {
                $dt = DateTime::createFromFormat('H:i:s', $timeOut_post);
                if ($dt && $dt->format('H:i:s') === $timeOut_post) {
                    $timeOut = $dt->format('H:i:s');
                } else {
                    error_log("Invalid timeOut format received: '" . $timeOut_post . "'. Expected HH:MM or HH:MM:SS.");
                }
            }
        }

        $sql = "UPDATE equip_usage_tbl SET
            equipment_id = ?, project_id = ?, time_in = ?, time_out = ?,
            operating_hours = ?, nature_of_work = ?, log_remarks = ?, operator_id = ?
        WHERE usage_id = ?";

        $stmt = $connection->prepare($sql);
        if (!$stmt) {
            $_SESSION['message'] = "Database error preparing usage report update: " . $connection->error;
            header('Location: Reports.php');
            exit();
        }

        $stmt->bind_param(
            "iissssisi",
            $equipment_id_fk,
            $project_id_fk,
            $timeIn,
            $timeOut,
            $operatingHours,
            $natureOfWork,
            $logRemarks,
            $operator_id_fk,
            $usage_id
        );

        if ($stmt->execute()) {
            $_SESSION['message'] = "Equipment usage report ID " . $usage_id . " updated successfully!";
        } else {
            $_SESSION['message'] = "Error updating equipment usage report ID " . $usage_id . ": " . $stmt->error;
        }
        $stmt->close();
        header('Location: Reports.php');
        exit();
    } else {
        $_SESSION['message'] = "Error: Unknown report type provided for update.";
        header('Location: Reports.php');
        exit();
    }
} else {
    $_SESSION['message'] = "Invalid request method.";
    header('Location: Reports.php');
    exit();
}
