<?php
require('./database.php');
// junreh eto
// Archive projects
if (isset($_POST['archive_project']) && isset($_POST['project_ids'])) {
    $project_ids = $_POST['project_ids'];

    if (!empty($project_ids)) {
        mysqli_autocommit($connection, FALSE);

        try {
            foreach ($project_ids as $project_id) {
                // Get project data before archiving
                $getProjectQuery = "SELECT * FROM proj_sched_tbl WHERE project_id = ?";
                $getStmt = mysqli_prepare($connection, $getProjectQuery);
                mysqli_stmt_bind_param($getStmt, "i", $project_id);
                mysqli_stmt_execute($getStmt);
                $result = mysqli_stmt_get_result($getStmt);
                $project = mysqli_fetch_assoc($result);

                if ($project) {
                    // --- Get assigned equipments ---
                    $getEquipQuery = "SELECT equipment_id FROM proj_eqp_assign_tbl WHERE project_id = ?";
                    $getEquipStmt = mysqli_prepare($connection, $getEquipQuery);
                    mysqli_stmt_bind_param($getEquipStmt, "i", $project_id);
                    mysqli_stmt_execute($getEquipStmt);
                    $equipResult = mysqli_stmt_get_result($getEquipStmt);
                    $assignedEquipments = [];
                    while ($equipRow = mysqli_fetch_assoc($equipResult)) {
                        $assignedEquipments[] = $equipRow['equipment_id'];
                    }
                    mysqli_stmt_close($getEquipStmt);

                    $assignedEquipmentsJson = json_encode($assignedEquipments);

                    // Insert into archived_project_tbl (add assigned_equipments)
                    $archiveQuery = "INSERT INTO archived_project_tbl 
                        (project_id, project_name, project_location, client, project_engineer, start_date, end_date, proj_status, archived_by, assigned_equipments) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $archiveStmt = mysqli_prepare($connection, $archiveQuery);

                    $archived_by = 'System';

                    mysqli_stmt_bind_param(
                        $archiveStmt,
                        "isssssssss",
                        $project['project_id'],
                        $project['project_name'],
                        $project['project_location'],
                        $project['client'],
                        $project['project_engineer'],
                        $project['start_date'],
                        $project['end_date'],
                        $project['proj_status'],
                        $archived_by,
                        $assignedEquipmentsJson
                    );

                    if (!mysqli_stmt_execute($archiveStmt)) {
                        throw new Exception("Failed to archive project: " . mysqli_error($connection));
                    }

                    // Delete assigned employees for this project
                    $deleteAssignedQuery = "DELETE FROM assigned_employee_tbl WHERE project_id = ?";
                    $deleteAssignedStmt = mysqli_prepare($connection, $deleteAssignedQuery);
                    mysqli_stmt_bind_param($deleteAssignedStmt, "i", $project_id);
                    mysqli_stmt_execute($deleteAssignedStmt);
                    mysqli_stmt_close($deleteAssignedStmt);

                    // --- Update equipment status to 'Idle' ---
                    // Get equipment IDs assigned to the project
                    $getEquipmentsQuery = "SELECT equipment_id FROM proj_eqp_assign_tbl WHERE project_id = ?";
                    $getEquipmentsStmt = mysqli_prepare($connection, $getEquipmentsQuery);
                    mysqli_stmt_bind_param($getEquipmentsStmt, "i", $project_id);
                    mysqli_stmt_execute($getEquipmentsStmt);
                    $equipmentsResult = mysqli_stmt_get_result($getEquipmentsStmt);
                    $equipment_ids = [];
                    while ($row = mysqli_fetch_assoc($equipmentsResult)) {
                        $equipment_ids[] = $row['equipment_id'];
                    }
                    mysqli_stmt_close($getEquipmentsStmt);

                    if (!empty($equipment_ids)) {
                        $placeholders = implode(',', array_fill(0, count($equipment_ids), '?'));
                        $updateEquipStatusQuery = "UPDATE equip_tbl SET equip_status = 'Idle', deployment_status = 'Undeployed' WHERE equipment_id IN ($placeholders)";
                        $updateEquipStatusStmt = mysqli_prepare($connection, $updateEquipStatusQuery);

                        // Bind parameters dynamically
                        $types = str_repeat('i', count($equipment_ids));
                        mysqli_stmt_bind_param($updateEquipStatusStmt, $types, ...$equipment_ids);
                        mysqli_stmt_execute($updateEquipStatusStmt);
                        mysqli_stmt_close($updateEquipStatusStmt);
                    }

                    // --- Update equipment status to 'Idle' for deleted projects ---
                    // Get equipment IDs assigned to the project
                    $getEquipmentsQuery = "SELECT equipment_id FROM proj_eqp_assign_tbl WHERE project_id = ?";
                    $getEquipmentsStmt = mysqli_prepare($connection, $getEquipmentsQuery);
                    mysqli_stmt_bind_param($getEquipmentsStmt, "i", $project_id);
                    mysqli_stmt_execute($getEquipmentsStmt);
                    $equipmentsResult = mysqli_stmt_get_result($getEquipmentsStmt);
                    $equipment_ids = [];
                    while ($row = mysqli_fetch_assoc($equipmentsResult)) {
                        $equipment_ids[] = $row['equipment_id'];
                    }
                    mysqli_stmt_close($getEquipmentsStmt);

                    if (!empty($equipment_ids)) {
                        $placeholders = implode(',', array_fill(0, count($equipment_ids), '?'));
                        $updateEquipStatusQuery = "UPDATE equip_tbl SET equip_status = 'Idle', deployment_status = 'Undeployed' WHERE equipment_id IN ($placeholders)";
                        $updateEquipStatusStmt = mysqli_prepare($connection, $updateEquipStatusQuery);

                        $types = str_repeat('i', count($equipment_ids));
                        mysqli_stmt_bind_param($updateEquipStatusStmt, $types, ...$equipment_ids);
                        mysqli_stmt_execute($updateEquipStatusStmt);
                        mysqli_stmt_close($updateEquipStatusStmt);
                    }

                    // Delete assigned employees and equipment for this project
                    $deleteAssignedQuery = "DELETE FROM assigned_employee_tbl WHERE project_id = ?";
                    $deleteAssignedStmt = mysqli_prepare($connection, $deleteAssignedQuery);
                    mysqli_stmt_bind_param($deleteAssignedStmt, "i", $project_id);
                    mysqli_stmt_execute($deleteAssignedStmt);
                    mysqli_stmt_close($deleteAssignedStmt);

                    $deleteEquipQuery = "DELETE FROM proj_eqp_assign_tbl WHERE project_id = ?";
                    $deleteEquipStmt = mysqli_prepare($connection, $deleteEquipQuery);
                    mysqli_stmt_bind_param($deleteEquipStmt, "i", $project_id);
                    mysqli_stmt_execute($deleteEquipStmt);
                    mysqli_stmt_close($deleteEquipStmt);

                    // Delete assigned equipments for this project
                    $deleteEquipQuery = "DELETE FROM proj_eqp_assign_tbl WHERE project_id = ?";
                    $deleteEquipStmt = mysqli_prepare($connection, $deleteEquipQuery);
                    mysqli_stmt_bind_param($deleteEquipStmt, "i", $project_id);
                    mysqli_stmt_execute($deleteEquipStmt);
                    mysqli_stmt_close($deleteEquipStmt);

                    // Delete from original table
                    $deleteQuery = "DELETE FROM proj_sched_tbl WHERE project_id = ?";
                    $deleteStmt = mysqli_prepare($connection, $deleteQuery);
                    mysqli_stmt_bind_param($deleteStmt, "i", $project_id);

                    if (!mysqli_stmt_execute($deleteStmt)) {
                        throw new Exception("Failed to delete project from main table: " . mysqli_error($connection));
                    }

                    mysqli_stmt_close($archiveStmt);
                    mysqli_stmt_close($deleteStmt);
                }
                mysqli_stmt_close($getStmt);
            }

            // Check if proj_sched_tbl is empty and reset AUTO_INCREMENT
            $checkEmpty = mysqli_query($connection, "SELECT COUNT(*) as cnt FROM proj_sched_tbl");
            $row = mysqli_fetch_assoc($checkEmpty);
            if ($row && $row['cnt'] == 0) {
                mysqli_query($connection, "ALTER TABLE proj_sched_tbl AUTO_INCREMENT = 1");
            }

            mysqli_commit($connection);
            header("Location: ProjectList.php?success=archived&count=" . count($project_ids));
        } catch (Exception $e) {
            mysqli_rollback($connection);
            header("Location: ProjectList.php?error=archive_failed&details=" . urlencode($e->getMessage()));
        } finally {
            mysqli_autocommit($connection, TRUE);
        }
    } else {
        header("Location: ProjectList.php?error=no_selection");
    }
    exit();
}

// Restore projects
if (isset($_POST['restore_project']) && isset($_POST['archive_ids'])) {
    $archive_ids = $_POST['archive_ids'];
    $success = true;

    if (!empty($archive_ids)) {
        mysqli_autocommit($connection, FALSE);

        try {
            foreach ($archive_ids as $archive_id) {
                // Get archived project data
                $getArchivedQuery = "SELECT * FROM archived_project_tbl WHERE archive_id = ?";
                $getStmt = mysqli_prepare($connection, $getArchivedQuery);
                mysqli_stmt_bind_param($getStmt, "i", $archive_id);

                if (!mysqli_stmt_execute($getStmt)) {
                    throw new Exception("Failed to fetch archived project");
                }

                $result = mysqli_stmt_get_result($getStmt);
                $archivedProject = mysqli_fetch_assoc($result);

                if ($archivedProject) {
                    // Insert into proj_sched_tbl
                    $restoreQuery = "INSERT INTO proj_sched_tbl 
                        (project_name, project_location, client, project_engineer, start_date, end_date, proj_status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $restoreStmt = mysqli_prepare($connection, $restoreQuery);

                    if (!$restoreStmt) {
                        throw new Exception("Failed to prepare restore statement");
                    }

                    mysqli_stmt_bind_param(
                        $restoreStmt,
                        "sssssss",
                        $archivedProject['project_name'],
                        $archivedProject['project_location'],
                        $archivedProject['client'],
                        $archivedProject['project_engineer'],
                        $archivedProject['start_date'],
                        $archivedProject['end_date'],
                        $archivedProject['proj_status']
                    );

                    if (!mysqli_stmt_execute($restoreStmt)) {
                        throw new Exception("Failed to restore project");
                    }

                    // Get the new project_id
                    $newProjectId = mysqli_insert_id($connection);

                    // Restore assigned equipments
                    if (!empty($archivedProject['assigned_equipments'])) {
                        $assignedEquipments = json_decode($archivedProject['assigned_equipments'], true);
                        if (is_array($assignedEquipments)) {
                            foreach ($assignedEquipments as $equipment_id) {
                                $insertEquipQuery = "INSERT INTO proj_eqp_assign_tbl (project_id, equipment_id) VALUES (?, ?)";
                                $insertEquipStmt = mysqli_prepare($connection, $insertEquipQuery);
                                mysqli_stmt_bind_param($insertEquipStmt, "ii", $newProjectId, $equipment_id);
                                mysqli_stmt_execute($insertEquipStmt);
                                mysqli_stmt_close($insertEquipStmt);
                                // Update the equipment status and assigned project ID in the equip_tbl
                                $updateEquipStatusQuery = "
                                    UPDATE equip_tbl
                                    SET equip_status = 'Active', deployment_status = 'Deployed', assigned_proj_id = ?
                                    WHERE equipment_id = ?
                                ";
                                $updateEquipStatusStmt = mysqli_prepare($connection, $updateEquipStatusQuery);
                                mysqli_stmt_bind_param($updateEquipStatusStmt, "ii", $newProjectId, $equipment_id);
                                mysqli_stmt_execute($updateEquipStatusStmt);
                                mysqli_stmt_close($updateEquipStatusStmt);
                            }
                        }
                    }

                    // Delete from archived table
                    $deleteQuery = "DELETE FROM archived_project_tbl WHERE archive_id = ?";
                    $deleteStmt = mysqli_prepare($connection, $deleteQuery);
                    mysqli_stmt_bind_param($deleteStmt, "i", $archive_id);

                    if (!mysqli_stmt_execute($deleteStmt)) {
                        throw new Exception("Failed to remove from archive");
                    }

                    mysqli_stmt_close($deleteStmt);
                    mysqli_stmt_close($restoreStmt);
                }
                mysqli_stmt_close($getStmt);
            }

            mysqli_commit($connection);
            header("Location: ProjectList.php?success=restored");
            exit();
        } catch (Exception $e) {
            mysqli_rollback($connection);
            header("Location: ProjectList.php?error=restore_failed");
            exit();
        }
    }
}

// Delete archived projects permanently
if (isset($_POST['delete_archived']) && isset($_POST['archive_ids'])) {
    $archive_ids = $_POST['archive_ids'];

    if (!empty($archive_ids)) {
        mysqli_autocommit($connection, FALSE);

        try {
            $placeholders = str_repeat('?,', count($archive_ids) - 1) . '?';
            $deleteQuery = "DELETE FROM archived_project_tbl WHERE archive_id IN ($placeholders)";
            $stmt = mysqli_prepare($connection, $deleteQuery);
            $types = str_repeat('i', count($archive_ids));
            mysqli_stmt_bind_param($stmt, $types, ...$archive_ids);

            if (mysqli_stmt_execute($stmt)) {
                $deleted_count = mysqli_stmt_affected_rows($stmt);

                // Check if archived_project_tbl is empty and reset AUTO_INCREMENT
                $checkEmpty = mysqli_query($connection, "SELECT COUNT(*) as cnt FROM archived_project_tbl");
                $row = mysqli_fetch_assoc($checkEmpty);
                if ($row && $row['cnt'] == 0) {
                    mysqli_query($connection, "ALTER TABLE archived_project_tbl AUTO_INCREMENT = 1");
                }

                mysqli_commit($connection);
                header("Location: ProjectList.php?success=deleted&count=" . $deleted_count);
            } else {
                throw new Exception("Failed to delete archived projects: " . mysqli_error($connection));
            }

            mysqli_stmt_close($stmt);
        } catch (Exception $e) {
            mysqli_rollback($connection);
            header("Location: ProjectList.php?error=delete_failed&details=" . urlencode($e->getMessage()));
        } finally {
            mysqli_autocommit($connection, TRUE);
        }
    } else {
        header("Location: ProjectList.php?error=no_selection");
    }
    exit();
}

// If no valid action, redirect back
header("Location: ProjectList.php");
exit();