<?php
// File: /includes/performance_functions.php

/**
 * Calculates employee productivity score.
 * (Completed Tasks / Total Assigned Tasks) * 100
 */
function calculateEmployeeProductivity($employee_id) {
    global $pdo;
    if (!isset($pdo)) {
        $pdo = getDbConnection();
    }

    // Total assigned tasks
    $stmt = $pdo->prepare("SELECT COUNT(t.id) FROM tasks t JOIN task_assignments ta ON t.id = ta.task_id WHERE ta.employee_id = :employee_id AND t.deleted_at IS NULL");
    $stmt->execute(['employee_id' => $employee_id]);
    $total = $stmt->fetchColumn();

    if ($total == 0) return 0;

    // Completed assigned tasks
    $stmt = $pdo->prepare("SELECT COUNT(t.id) FROM tasks t JOIN task_assignments ta ON t.id = ta.task_id WHERE ta.employee_id = :employee_id AND t.status = 'Completed' AND t.deleted_at IS NULL");
    $stmt->execute(['employee_id' => $employee_id]);
    $completed = $stmt->fetchColumn();

    return round(($completed / $total) * 100);
}

/**
 * Calculates CRM productivity score.
 * (Completed Projects / Total Assigned Projects) * 100
 */
function calculateCRMProductivity($crm_id) {
    global $pdo;
    if (!isset($pdo)) {
        $pdo = getDbConnection();
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE crm_id = :crm_id AND deleted_at IS NULL");
    $stmt->execute(['crm_id' => $crm_id]);
    $total = $stmt->fetchColumn();

    if ($total == 0) return 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE crm_id = :crm_id AND status = 'Completed' AND deleted_at IS NULL");
    $stmt->execute(['crm_id' => $crm_id]);
    $completed = $stmt->fetchColumn();

    return round(($completed / $total) * 100);
}

/**
 * Calculates Manager productivity score.
 * Currently uses same logic as CRM but scoped to agency.
 * Alternatively: (Completed Projects / Total Projects in Agency) * 100
 */
function calculateManagerProductivity($agency_id) {
    global $pdo;
    if (!isset($pdo)) {
        $pdo = getDbConnection();
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE agency_id = :agency_id AND deleted_at IS NULL");
    $stmt->execute(['agency_id' => $agency_id]);
    $total = $stmt->fetchColumn();

    if ($total == 0) return 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE agency_id = :agency_id AND status = 'Completed' AND deleted_at IS NULL");
    $stmt->execute(['agency_id' => $agency_id]);
    $completed = $stmt->fetchColumn();

    return round(($completed / $total) * 100);
}

/**
 * Calculates overall Agency productivity score.
 * Average of project completion rates and task completion rates.
 */
function calculateAgencyProductivity($agency_id) {
    global $pdo;
    if (!isset($pdo)) {
        $pdo = getDbConnection();
    }

    $manager_score = calculateManagerProductivity($agency_id);

    // Total tasks in agency
    $stmt = $pdo->prepare("SELECT COUNT(t.id) FROM tasks t JOIN projects p ON t.project_id = p.id WHERE p.agency_id = :agency_id AND t.deleted_at IS NULL");
    $stmt->execute(['agency_id' => $agency_id]);
    $total_tasks = $stmt->fetchColumn();

    if ($total_tasks == 0) return $manager_score;

    $stmt = $pdo->prepare("SELECT COUNT(t.id) FROM tasks t JOIN projects p ON t.project_id = p.id WHERE p.agency_id = :agency_id AND t.status = 'Completed' AND t.deleted_at IS NULL");
    $stmt->execute(['agency_id' => $agency_id]);
    $completed_tasks = $stmt->fetchColumn();

    $task_score = round(($completed_tasks / $total_tasks) * 100);

    return round(($manager_score + $task_score) / 2);
}
