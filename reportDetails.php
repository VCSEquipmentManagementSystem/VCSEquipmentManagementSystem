<?php
include('./database.php');
// display report details through AJAX
header('Content-Type: application/json');

$response = ['success' => false, 'data' => null, 'message' => ''];

if (isset($_GET['report_id'])) {
    $report_id = mysqli_real_escape_string($connection, $_GET['report_id']);

    $query = "
        SELECT
            r.*,
            e.custom_equip_id,
            p.project_name,
            emp_op.first_name AS operator_first_name,
            emp_op.last_name AS operator_last_name,
            emp_rep.first_name AS reported_by_first_name,
            emp_rep.last_name AS reported_by_last_name,
            emp_insp.first_name AS inspected_by_first_name,
            emp_insp.last_name AS inspected_by_last_name,
            emp_cond.first_name AS conducted_by_first_name,
            emp_cond.last_name AS conducted_by_last_name
            -- accepted_by and job_completion_verified_by are VARCHAR in report_tbl, so no JOIN needed
        FROM
            report_tbl r
        LEFT JOIN
            equip_tbl e ON r.equipment_id = e.equipment_id
        LEFT JOIN
            proj_sched_tbl p ON r.project_id = p.project_id
        LEFT JOIN
            employee_tbl emp_op ON r.operator_id = emp_op.employee_id
        LEFT JOIN
            employee_tbl emp_rep ON r.report_by = emp_rep.employee_id
        LEFT JOIN
            employee_tbl emp_insp ON r.inspected_by = emp_insp.employee_id
        LEFT JOIN
            employee_tbl emp_cond ON r.conducted_by = emp_cond.employee_id
        WHERE
            r.report_id = '$report_id'";

    $result = mysqli_query($connection, $query);

    if ($result) {
        $data = mysqli_fetch_assoc($result);
        if ($data) {
            // Concatenate names for display in JavaScript (for INT fields)
            $data['operator_name'] = ($data['operator_first_name'] ?? '') . ' ' . ($data['operator_last_name'] ?? '');
            $data['reported_by_name'] = ($data['reported_by_first_name'] ?? '') . ' ' . ($data['reported_by_last_name'] ?? '');
            $data['inspected_by_name'] = ($data['inspected_by_first_name'] ?? '') . ' ' . ($data['inspected_by_last_name'] ?? '');
            $data['conducted_by_name'] = ($data['conducted_by_first_name'] ?? '') . ' ' . ($data['conducted_by_last_name'] ?? '');
            $response['success'] = true;
            $response['data'] = $data;
        } else {
            $response['message'] = "No report found with ID: " . $report_id;
        }
    } else {
        $response['message'] = "Database query error: " . mysqli_error($connection);
    }
} else {
    $response['message'] = "No report ID provided.";
}

echo json_encode($response);

mysqli_close($connection);
