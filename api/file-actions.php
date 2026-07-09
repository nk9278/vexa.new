<?php
// File: /api/file-actions.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/notification_functions.php';

// Requires auth for everyone
if (!isset($_SESSION['user_id'])) {
    redirect('/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/files/index.php');
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrf_token)) {
    $_SESSION['error_msg'] = "Invalid CSRF token.";
    redirect('/files/index.php');
}

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];
$user_id = $_SESSION['user_id'];
$role_name = $_SESSION['role_name'];

$action = $_POST['action'] ?? '';
$file_id = filter_var($_POST['file_id'] ?? '', FILTER_VALIDATE_INT);

if (!$file_id || !$action) {
    $_SESSION['error_msg'] = "Invalid request parameters.";
    redirect('/files/index.php');
}

// Ensure the user actually has access to the project this file belongs to
$query = "
    SELECT f.*, p.crm_id
    FROM google_drive_files f
    JOIN projects p ON f.project_id = p.id
    WHERE f.id = :file_id AND f.agency_id = :agency_id AND f.status = 'Active'
";
$params = ['file_id' => $file_id, 'agency_id' => $agency_id];

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$file = $stmt->fetch();

if (!$file) {
    $_SESSION['error_msg'] = "File not found or access denied.";
    redirect('/files/index.php');
}

// Scoping checks based on role
$has_access = false;
if (in_array($role_name, ['Super Admin', 'Agency Owner', 'Manager'])) {
    $has_access = true;
} elseif ($role_name === 'CRM' && $file['crm_id'] == $user_id) {
    $has_access = true;
} elseif ($role_name === 'Employee') {
    $chk_stmt = $pdo->prepare("SELECT id FROM project_assignments WHERE project_id = :pid AND employee_id = :eid");
    $chk_stmt->execute(['pid' => $file['project_id'], 'eid' => $user_id]);
    if ($chk_stmt->fetch()) {
        $has_access = true;
    }
}

if (!$has_access) {
    $_SESSION['error_msg'] = "Access denied.";
    redirect('/files/index.php');
}

// Actions
if ($action === 'delete') {
    $stmt = $pdo->prepare("UPDATE google_drive_files SET status = 'Deleted', updated_at = NOW() WHERE id = :id");
    if ($stmt->execute(['id' => $file_id])) {
        // Mock Google Drive API delete
        logActivity($agency_id, $user_id, 'File Deleted', "File '{$file['file_name']}' was soft deleted.", $file['project_id']);
        logAudit($agency_id, $user_id, $role_name, "Deleted File: {$file['file_name']} ($file_id)", $file['project_id']);
        $_SESSION['success_msg'] = "File deleted successfully.";
    } else {
        $_SESSION['error_msg'] = "Failed to delete file.";
    }
} elseif ($action === 'rename') {
    $new_name = sanitizeInput($_POST['new_name'] ?? '');
    if ($new_name) {
        // Mock Google Drive API Rename
        $stmt = $pdo->prepare("UPDATE google_drive_files SET file_name = :name, updated_at = NOW() WHERE id = :id");
        if ($stmt->execute(['name' => $new_name, 'id' => $file_id])) {
            logActivity($agency_id, $user_id, 'File Renamed', "File renamed from '{$file['file_name']}' to '$new_name'.", $file['project_id']);
            logAudit($agency_id, $user_id, $role_name, "Renamed File: {$file['file_name']} -> $new_name ($file_id)", $file['project_id']);
            $_SESSION['success_msg'] = "File renamed successfully.";
        } else {
            $_SESSION['error_msg'] = "Failed to rename file.";
        }
    } else {
        $_SESSION['error_msg'] = "File name cannot be empty.";
    }
}

redirect('/files/index.php');
