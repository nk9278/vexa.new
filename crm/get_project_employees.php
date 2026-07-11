<?php
// File: /crm/get_project_employees.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

checkAuth(['CRM']);

header('Content-Type: application/json');

$project_id = isset($_GET['project_id']) ? filter_var($_GET['project_id'], FILTER_VALIDATE_INT) : 0;
$user_id = $_SESSION['user_id'];

if (!$project_id) {
    echo json_encode([]);
    exit;
}

$pdo = getDbConnection();

// Verify CRM owns this project
$stmt = $pdo->prepare("SELECT id FROM projects WHERE id = :id AND crm_id = :crm_id AND deleted_at IS NULL");
$stmt->execute(['id' => $project_id, 'crm_id' => $user_id]);
if (!$stmt->fetch()) {
    echo json_encode([]);
    exit;
}

// Fetch assigned employees
$stmt = $pdo->prepare("
    SELECT pa.employee_id, pa.role, u.full_name
    FROM project_assignments pa
    JOIN users u ON pa.employee_id = u.id
    WHERE pa.project_id = :project_id
");
$stmt->execute(['project_id' => $project_id]);
$employees = $stmt->fetchAll();

echo json_encode($employees);
exit;
