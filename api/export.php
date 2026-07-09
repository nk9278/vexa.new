<?php
// File: /api/export.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];
$role_name = $_SESSION['role_name'];

$report_type = $_GET['report'] ?? '';
$date_filter = $_GET['date'] ?? 'All';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="vexa_' . $report_type . '_report_' . date('Ymd') . '.csv"');

$output = fopen('php://output', 'w');

if ($report_type === 'clients' && in_array($role_name, ['Super Admin', 'Agency Owner', 'Manager'])) {
    $date_sql = "";
    if ($date_filter === 'Today') {
        $date_sql = " AND DATE(p.created_at) = CURDATE() ";
    } elseif ($date_filter === 'This Week') {
        $date_sql = " AND YEARWEEK(p.created_at, 1) = YEARWEEK(CURDATE(), 1) ";
    } elseif ($date_filter === 'This Month') {
        $date_sql = " AND MONTH(p.created_at) = MONTH(CURDATE()) AND YEAR(p.created_at) = YEAR(CURDATE()) ";
    } elseif ($date_filter === 'This Year') {
        $date_sql = " AND YEAR(p.created_at) = YEAR(CURDATE()) ";
    }

    fputcsv($output, ['Client Name', 'Total Projects', 'Completed Projects', 'Running Projects', 'Total Value', 'Received Amount', 'Pending Amount']);

    $query = "
        SELECT
            c.client_name,
            COUNT(p.id) as total_projects,
            SUM(CASE WHEN p.status = 'Completed' THEN 1 ELSE 0 END) as completed_projects,
            SUM(CASE WHEN p.status = 'Running' THEN 1 ELSE 0 END) as running_projects,
            SUM(pp.project_amount) as total_value,
            SUM(pp.received_amount) as received_amount,
            SUM(pp.pending_amount) as pending_amount
        FROM clients c
        LEFT JOIN projects p ON c.id = p.client_id $date_sql AND p.deleted_at IS NULL
        LEFT JOIN project_payments pp ON p.id = pp.project_id
        WHERE c.agency_id = :agency_id AND c.deleted_at IS NULL
        GROUP BY c.id ORDER BY c.client_name ASC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['agency_id' => $agency_id]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }

} elseif ($report_type === 'projects' && in_array($role_name, ['Super Admin', 'Agency Owner', 'Manager'])) {
    $date_sql = "";
    if ($date_filter === 'Today') {
        $date_sql = " AND DATE(p.created_at) = CURDATE() ";
    } elseif ($date_filter === 'This Week') {
        $date_sql = " AND YEARWEEK(p.created_at, 1) = YEARWEEK(CURDATE(), 1) ";
    } elseif ($date_filter === 'This Month') {
        $date_sql = " AND MONTH(p.created_at) = MONTH(CURDATE()) AND YEAR(p.created_at) = YEAR(CURDATE()) ";
    } elseif ($date_filter === 'This Year') {
        $date_sql = " AND YEAR(p.created_at) = YEAR(CURDATE()) ";
    }

    fputcsv($output, ['Project Name', 'Client', 'Status', 'Start Date', 'Expected Completion', 'Assigned CRM']);

    $query = "
        SELECT p.project_name, c.client_name, p.status, p.start_date, p.expected_completion_date, u.full_name as crm_name
        FROM projects p
        JOIN clients c ON p.client_id = c.id
        LEFT JOIN users u ON p.crm_id = u.id
        WHERE p.agency_id = :agency_id AND p.deleted_at IS NULL $date_sql
        ORDER BY p.created_at DESC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['agency_id' => $agency_id]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }

} elseif ($report_type === 'payments' && in_array($role_name, ['Super Admin', 'Agency Owner', 'Manager'])) {
    $date_sql = "";
    if ($date_filter === 'Today') {
        $date_sql = " AND DATE(pp.updated_at) = CURDATE() ";
    } elseif ($date_filter === 'This Week') {
        $date_sql = " AND YEARWEEK(pp.updated_at, 1) = YEARWEEK(CURDATE(), 1) ";
    } elseif ($date_filter === 'This Month') {
        $date_sql = " AND MONTH(pp.updated_at) = MONTH(CURDATE()) AND YEAR(pp.updated_at) = YEAR(CURDATE()) ";
    } elseif ($date_filter === 'This Year') {
        $date_sql = " AND YEAR(pp.updated_at) = YEAR(CURDATE()) ";
    }

    fputcsv($output, ['Project', 'Client', 'Project Value', 'Received Amount', 'Pending Amount', 'Status', 'Last Payment Date']);

    $query = "
        SELECT p.project_name, c.client_name, pp.project_amount, pp.received_amount, pp.pending_amount, pp.payment_status, pp.last_payment_date
        FROM project_payments pp
        JOIN projects p ON pp.project_id = p.id
        JOIN clients c ON p.client_id = c.id
        WHERE p.agency_id = :agency_id AND p.deleted_at IS NULL $date_sql
        ORDER BY pp.updated_at DESC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['agency_id' => $agency_id]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
} else {
    fputcsv($output, ['Error', 'Invalid report type or access denied.']);
}

fclose($output);
exit;
