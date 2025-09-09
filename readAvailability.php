<?php
require('./database.php');

// Get all equipment details with related information
$queryavailability = "
    SELECT
        e.*,
        et.equip_type_name,
        p.project_location as location,
        p.project_name
    FROM equip_tbl e
    LEFT JOIN equip_type_tbl et ON e.equip_type_id = et.equip_type_id
    LEFT JOIN proj_sched_tbl p ON e.assigned_proj_id = p.project_id
    WHERE e.equip_status != 'archived'";

$sqlavailability = mysqli_query($connection, $queryavailability);

if (!$sqlavailability) {
    die("Query failed: " . mysqli_error($connection));
}

// Get summary counts for statistics
$stats_query = "
    SELECT
        COUNT(*) as total_equipment,
        SUM(CASE WHEN equip_status = 'Active' THEN 1 ELSE 0 END) as deployed_count,
        SUM(CASE WHEN equip_status = 'Idle' THEN 1 ELSE 0 END) as undeployed_count,
        SUM(CASE WHEN equip_status IN ('Under Maintenance', 'For Maintenance', 'For Repair', 'For Reconditioning', 'Assemble Process', 'Breakdown', 'For Demolization', 'For Disposal', 'Condemned', 'For Scrap') THEN 1 ELSE 0 END) as serviced_count
    FROM equip_tbl
    WHERE equip_status != 'archived'";

$stats_result = mysqli_query($connection, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>
