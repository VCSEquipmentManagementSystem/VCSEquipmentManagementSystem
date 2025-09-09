<?php
require 'database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Get only equipment types that have maintenance records
    $typeQuery = "
        SELECT DISTINCT et.equip_type_id, et.equip_type_name 
        FROM equip_type_tbl et
        INNER JOIN equip_tbl e ON et.equip_type_id = e.equip_type_id
        WHERE EXISTS (
            SELECT 1 FROM report_tbl r 
            WHERE r.equipment_id = e.equipment_id 
            AND r.report_type = 'Maintenance'
        )
        OR EXISTS (
            SELECT 1 FROM maintenance_sched_tbl m 
            WHERE m.equipment_id = e.equipment_id
        )
        ORDER BY et.equip_type_name";
    
    $typeResult = $connection->query($typeQuery);
    
    if (!$typeResult) {
        throw new Exception("Error fetching equipment types: " . $connection->error);
    }

    $equipmentCategories = [];
    $goodCondition = [];
    $maintenanceNeeded = [];
    $poorCondition = [];

    while ($typeRow = $typeResult->fetch_assoc()) {
        $typeId = $typeRow['equip_type_id'];
        $equipmentCategories[] = $typeRow['equip_type_name'];

        // Count equipment in good condition (with completed maintenance)
        $goodQuery = "
            SELECT COUNT(DISTINCT e.equipment_id) as count
            FROM equip_tbl e
            LEFT JOIN report_tbl r ON e.equipment_id = r.equipment_id
            WHERE e.equip_type_id = ?
            AND EXISTS (
                SELECT 1 FROM report_tbl r2 
                WHERE r2.equipment_id = e.equipment_id
                AND r2.report_type = 'Maintenance'
                AND r2.report_status = 'Completed'
                AND r2.report_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
            )";
        
        $stmt = $connection->prepare($goodQuery);
        $stmt->bind_param("i", $typeId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $goodCondition[] = intval($row['count']);
        $stmt->close();

        // Count equipment needing maintenance
        $maintenanceQuery = "
            SELECT COUNT(DISTINCT e.equipment_id) as count
            FROM equip_tbl e
            WHERE e.equip_type_id = ?
            AND (
                EXISTS (
                    SELECT 1 FROM maintenance_sched_tbl m 
                    WHERE m.equipment_id = e.equipment_id
                    AND m.pms_status = 'Scheduled'
                )
                OR EXISTS (
                    SELECT 1 FROM report_tbl r 
                    WHERE r.equipment_id = e.equipment_id
                    AND r.report_type = 'Maintenance'
                    AND r.report_status IN ('Not yet started', 'In Progress')
                )
            )";
        
        $stmt = $connection->prepare($maintenanceQuery);
        $stmt->bind_param("i", $typeId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $maintenanceNeeded[] = intval($row['count']);
        $stmt->close();

        // Count equipment in poor condition
        $poorQuery = "
            SELECT COUNT(DISTINCT e.equipment_id) as count
            FROM equip_tbl e
            WHERE e.equip_type_id = ?
            AND (
                EXISTS (
                    SELECT 1 FROM maintenance_sched_tbl m 
                    WHERE m.equipment_id = e.equipment_id
                    AND m.pms_status = 'Overdue'
                )
                OR EXISTS (
                    SELECT 1 FROM report_tbl r 
                    WHERE r.equipment_id = e.equipment_id
                    AND r.report_type = 'Maintenance'
                    AND r.report_status = 'Rescheduled'
                )
            )";
        
        $stmt = $connection->prepare($poorQuery);
        $stmt->bind_param("i", $typeId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $poorCondition[] = intval($row['count']);
        $stmt->close();
    }

    $response = [
        'categories' => $equipmentCategories,
        'data' => [
            'good' => $goodCondition,
            'maintenance' => $maintenanceNeeded,
            'poor' => $poorCondition
        ]
    ];

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in fetchMachineLifespan.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}

$connection->close();