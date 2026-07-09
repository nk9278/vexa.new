<?php
// File: /config/functions.php
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../includes/notification_functions.php';
require_once __DIR__ . '/../includes/google_drive_functions.php';
require_once __DIR__ . '/../includes/performance_functions.php';

function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit;
}

function redirectUserByRole($role_name) {
    switch ($role_name) {
        case 'Super Admin':
            redirect('/super-admin/dashboard.php');
            break;
        case 'Agency Owner':
            redirect('/agency/dashboard.php');
            break;
        case 'Manager':
            redirect('/manager/dashboard.php');
            break;
        case 'CRM':
            redirect('/crm/dashboard.php');
            break;
        default:
            redirect('/employee/dashboard.php');
            break;
    }
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}
