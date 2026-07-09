<?php
// File: /super-admin/agency-delete.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

checkAuth(['Super Admin']);

$pdo = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (verifyCsrfToken($csrf_token)) {
        if (isset($_POST['id']) && is_numeric($_POST['id'])) {
            $agency_id = (int)$_POST['id'];

            // Check if exists
            $stmt = $pdo->prepare("SELECT id FROM agencies WHERE id = :id AND deleted_at IS NULL");
            $stmt->execute(['id' => $agency_id]);

            if ($stmt->fetch()) {
                // Soft delete
                $stmt = $pdo->prepare("UPDATE agencies SET deleted_at = NOW() WHERE id = :id");
                $stmt->execute(['id' => $agency_id]);
            }
        }
    }
}

redirect('/super-admin/agencies.php');
