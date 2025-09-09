<?php
session_start();
include('./database.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Helper function to get employee_id from user_tbl based on name
    function getEmployeeIdFromName($connection, $name)
    {
        // Handle empty or null names gracefully
        if (empty($name)) {
            return 'NULL';
        }

        $name = mysqli_real_escape_string($connection, $name);
        $query = "SELECT employee_id FROM user_tbl WHERE name = '$name' AND employee_id IS NOT NULL";
        $result = mysqli_query($connection, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            return $row['employee_id'];
        }
        return 'NULL'; // Return NULL for a non-existent name
    }

    // Handle Report Table Submission
    if (isset($_POST['submit_main'])) {
        // --- Fetching data from POST and sanitizing ---
        $reportedByName = mysqli_real_escape_string($connection, $_POST['reportedBy'] ?? '');
        $reportType = mysqli_real_escape_string($connection, $_POST['reportType'] ?? 'Maintenance'); // Default to 'Maintenance' if not provided
        $equipmentID = mysqli_real_escape_string($connection, $_POST['equipmentID'] ?? '');
        $operatorName = mysqli_real_escape_string($connection, $_POST['operatorName'] ?? '');
        $inspectedByName = mysqli_real_escape_string($connection, $_POST['inspectedBy'] ?? '');
        $repairedByName = mysqli_real_escape_string($connection, $_POST['repairedBy'] ?? '');
        $problemEncounter = mysqli_real_escape_string($connection, $_POST['problemEncounter'] ?? '');
        $finalDiagnosis = mysqli_real_escape_string($connection, $_POST['finalDiagnosis'] ?? '');
        $detailsOfWorkDone = mysqli_real_escape_string($connection, $_POST['detailsOfWorkDone'] ?? '');
        $remarksReport = mysqli_real_escape_string($connection, $_POST['remarksReport'] ?? '');
        $conductedByName = mysqli_real_escape_string($connection, $_POST['conductedBy'] ?? '');
        $reportDate = mysqli_real_escape_string($connection, $_POST['reportDate'] ?? date('Y-m-d'));
        $dateStarted = mysqli_real_escape_string($connection, $_POST['dateStarted'] ?? date('Y-m-d'));
        $timeStarted = mysqli_real_escape_string($connection, $_POST['timeStarted'] ?? date('H:i:s'));

        // --- Setting fields that are not part of initial creation to NULL ---
        $date_completed = 'NULL';
        $time_completed = 'NULL';
        $accepted_by = 'NULL';
        $job_completion_verified_by = 'NULL';
        $remarks_job_completion = 'NULL';
        $report_description = 'NULL'; // Not collected in the form

        // --- Fetching Foreign Keys using helper function ---
        $equipment_id_fk = 'NULL';
        if (!empty($equipmentID)) {
            $equipment_query = "SELECT equipment_id FROM equip_tbl WHERE custom_equip_id = '$equipmentID'";
            $equip_result = mysqli_query($connection, $equipment_query);
            if ($equip_result && mysqli_num_rows($equip_result) > 0) {
                $row = mysqli_fetch_assoc($equip_result);
                $equipment_id_fk = $row['equipment_id'];
            }
        }
        $reported_by_id = getEmployeeIdFromName($connection, $reportedByName);
        $operator_id_fk = getEmployeeIdFromName($connection, $operatorName);
        $inspected_by_id = getEmployeeIdFromName($connection, $inspectedByName);
        $repaired_by_id = getEmployeeIdFromName($connection, $repairedByName);
        $conducted_by_id = getEmployeeIdFromName($connection, $conductedByName);

        // --- Handle Spare Parts details ---
        $partDescription = 'NULL';
        $quantity = 'NULL';
        $unit = 'NULL';
        $lastReplacementDate = 'NULL';
        $purchase_req_id = 'NULL';
        $part_id = 'NULL';

        if (isset($_POST['needPartRequirement']) && $_POST['needPartRequirement'] === 'on') {
            $partDescription = "'" . mysqli_real_escape_string($connection, $_POST['part_description'][0] ?? '') . "'";
            $quantity = "'" . mysqli_real_escape_string($connection, $_POST['part_qty'][0] ?? '') . "'";
            $unit = "'" . mysqli_real_escape_string($connection, $_POST['part_unit'][0] ?? '') . "'";
            $lastReplacementDate = "'" . mysqli_real_escape_string($connection, $_POST['lastReplacement'] ?? '') . "'";
        }

        $hasSpareParts = !empty($_POST['part_description'][0]);
        $report_status_for_db = $hasSpareParts ? "'Pending'" : "'In Progress'";
        if ($reportType === 'Maintenance' || $reportType === 'Repair' || $reportType === 'Breakdown') {
            $report_type_for_db = "'" . $reportType . "'";
        } else {
            $report_type_for_db = 'NULL';
        }

        // --- Insert into report_tbl ---
        $insert_query = "INSERT INTO report_tbl (
            equipment_id, report_type, report_date, operator_id, report_by,
            inspected_by, repaired_by, report_description, report_status, problem_encountered,
            final_diagnosis, details_of_work_done, remarks_report, purchase_req_id, part_id,
            part_description, quantity, unit, last_replacement_date, conducted_by,
            date_started, time_started, date_completed, time_completed, accepted_by,
            job_completion_verified_by, remarks_job_completion
        ) VALUES (
            $equipment_id_fk, $report_type_for_db, '$reportDate', $operator_id_fk, $reported_by_id,
            $inspected_by_id, $repaired_by_id, $report_description, $report_status_for_db, '$problemEncounter',
            '$finalDiagnosis', '$detailsOfWorkDone', '$remarksReport', $purchase_req_id, $part_id,
            $partDescription, $quantity, $unit, $lastReplacementDate, $conducted_by_id,
            '$dateStarted', '$timeStarted', $date_completed, $time_completed, $accepted_by,
            $job_completion_verified_by, $remarks_job_completion
        )";

        if (mysqli_query($connection, $insert_query)) {
            $_SESSION['message'] = "Report created successfully!";
        } else {
            $_SESSION['message'] = "Error creating report: " . mysqli_error($connection);
        }

        header('Location: Reports.php');
        exit;
    }
}
?>