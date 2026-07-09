<?php
// File: /manager/dashboard.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

checkAuth(['Manager']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];

// Total Clients
$stmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE agency_id = :agency_id AND deleted_at IS NULL");
$stmt->execute(['agency_id' => $agency_id]);
$total_clients = $stmt->fetchColumn();

// Active Clients
$stmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE agency_id = :agency_id AND status = 'Active' AND deleted_at IS NULL");
$stmt->execute(['agency_id' => $agency_id]);
$active_clients = $stmt->fetchColumn();

// Total Projects
$stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE agency_id = :agency_id AND deleted_at IS NULL");
$stmt->execute(['agency_id' => $agency_id]);
$total_projects = $stmt->fetchColumn();

// Running Projects
$stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE agency_id = :agency_id AND status = 'Running' AND deleted_at IS NULL");
$stmt->execute(['agency_id' => $agency_id]);
$running_projects = $stmt->fetchColumn();

// Completed Projects
$stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE agency_id = :agency_id AND status = 'Completed' AND deleted_at IS NULL");
$stmt->execute(['agency_id' => $agency_id]);
$completed_projects = $stmt->fetchColumn();

// Pending Projects
$stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE agency_id = :agency_id AND status = 'Pending' AND deleted_at IS NULL");
$stmt->execute(['agency_id' => $agency_id]);
$pending_projects = $stmt->fetchColumn();

// Pending Payments (Projects with pending or partial payments)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM project_payments pp JOIN projects p ON pp.project_id = p.id WHERE p.agency_id = :agency_id AND pp.payment_status IN ('Pending', 'Partial') AND p.deleted_at IS NULL");
$stmt->execute(['agency_id' => $agency_id]);
$pending_payments = $stmt->fetchColumn();

// Total Employees Assigned
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT pa.employee_id) FROM project_assignments pa JOIN projects p ON pa.project_id = p.id WHERE p.agency_id = :agency_id AND p.deleted_at IS NULL");
$stmt->execute(['agency_id' => $agency_id]);
$total_employees_assigned = $stmt->fetchColumn();

// Total CRM Assigned
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT p.crm_id) FROM projects p WHERE p.agency_id = :agency_id AND p.deleted_at IS NULL AND p.crm_id IS NOT NULL");
$stmt->execute(['agency_id' => $agency_id]);
$total_crm_assigned = $stmt->fetchColumn();

// Today's Tasks - Hardcoded to 0 for now as per instructions
$todays_tasks = 0;

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6 flex justify-between items-center">
    <h1 class="text-3xl font-bold leading-tight text-gray-900">Manager Dashboard</h1>
</div>

<div class="mt-6 px-4 sm:px-6">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <!-- Dashboard Cards -->

        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Total Clients</dt>
            <dd class="mt-2 text-3xl font-semibold text-indigo-600"><?php echo htmlspecialchars($total_clients); ?></dd>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Active Clients</dt>
            <dd class="mt-2 text-3xl font-semibold text-green-600"><?php echo htmlspecialchars($active_clients); ?></dd>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Running Projects</dt>
            <dd class="mt-2 text-3xl font-semibold text-blue-600"><?php echo htmlspecialchars($running_projects); ?></dd>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Completed Projects</dt>
            <dd class="mt-2 text-3xl font-semibold text-gray-800"><?php echo htmlspecialchars($completed_projects); ?></dd>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Pending Projects</dt>
            <dd class="mt-2 text-3xl font-semibold text-orange-500"><?php echo htmlspecialchars($pending_projects); ?></dd>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Today's Tasks</dt>
            <dd class="mt-2 text-3xl font-semibold text-purple-600"><?php echo htmlspecialchars($todays_tasks); ?></dd>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Pending Payments</dt>
            <dd class="mt-2 text-3xl font-semibold text-red-600"><?php echo htmlspecialchars($pending_payments); ?></dd>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Total Employees Assigned</dt>
            <dd class="mt-2 text-3xl font-semibold text-teal-600"><?php echo htmlspecialchars($total_employees_assigned); ?></dd>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Total CRM Assigned</dt>
            <dd class="mt-2 text-3xl font-semibold text-cyan-600"><?php echo htmlspecialchars($total_crm_assigned); ?></dd>
        </div>

    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
