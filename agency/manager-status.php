<?php
// File: /agency/manager-status.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

checkAuth(['Agency Owner']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (verifyCsrfToken($csrf_token)) {
        if (isset($_POST['id']) && is_numeric($_POST['id']) && isset($_POST['status'])) {
            $user_id = (int)$_POST['id'];
            $new_status = $_POST['status'];

            // Allow only valid statuses to be toggled this way
            if (in_array($new_status, ['Active', 'Temporary Suspend'])) {
                // Ensure the user belongs to this agency and is a manager
                $stmt = $pdo->prepare("UPDATE users SET account_status = :status WHERE id = :id AND agency_id = :agency_id AND role_id = :role_id AND deleted_at IS NULL");
                $stmt->execute([
                    'status' => $new_status,
                    'id' => $user_id,
                    'agency_id' => $agency_id,
                    'role_id' => ROLE_MANAGER
                ]);
            }
        }
    }
}

redirect('/agency/managers.php');
