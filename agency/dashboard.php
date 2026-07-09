<?php
// File: /agency/dashboard.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';

checkAuth(['Agency Owner']);

include __DIR__ . '/header.php';
?>

<?php
// Fetch actual manager count since that's part of this phase
$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'] ?? 0;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE agency_id = :agency_id AND role_id = :role_id AND deleted_at IS NULL");
$stmt->execute(['agency_id' => $agency_id, 'role_id' => ROLE_MANAGER]);
$total_managers = $stmt->fetchColumn();

// Placeholders for features not yet built
$placeholders = [
    'employees' => 15,
    'total_clients' => 45,
    'active_clients' => 38,
    'completed_projects' => 120,
    'running_projects' => 12,
    'pending_projects' => 5,
    'total_revenue' => 150000.00,
    'pending_revenue' => 12500.00,
    'todays_tasks' => 24
];
?>

<div class="px-4 py-5 sm:px-6 flex justify-between items-center">
    <h1 class="text-3xl font-bold leading-tight text-gray-900">Agency Dashboard</h1>
    <div class="flex space-x-2">
        <a href="drive.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Drive Connection</a>
        <a href="notifications.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Notifications</a>
    </div>
</div>

<div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 px-4 sm:px-6">
    <!-- Team Stats -->
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition transform hover:-translate-y-1">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Total Managers</dt>
            <dd class="mt-1 text-3xl font-semibold text-indigo-600"><?php echo $total_managers; ?></dd>
        </div>
    </div>
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition transform hover:-translate-y-1">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Total Employees</dt>
            <dd class="mt-1 text-3xl font-semibold text-indigo-500"><?php echo $placeholders['employees']; ?></dd>
        </div>
    </div>

    <!-- Client Stats -->
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition transform hover:-translate-y-1">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Total Clients</dt>
            <dd class="mt-1 text-3xl font-semibold text-blue-600"><?php echo $placeholders['total_clients']; ?></dd>
        </div>
    </div>
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition transform hover:-translate-y-1">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Active Clients</dt>
            <dd class="mt-1 text-3xl font-semibold text-green-600"><?php echo $placeholders['active_clients']; ?></dd>
        </div>
    </div>

    <!-- Project Stats -->
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition transform hover:-translate-y-1">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Completed Projects</dt>
            <dd class="mt-1 text-3xl font-semibold text-gray-700"><?php echo $placeholders['completed_projects']; ?></dd>
        </div>
    </div>
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition transform hover:-translate-y-1">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Running Projects</dt>
            <dd class="mt-1 text-3xl font-semibold text-green-500"><?php echo $placeholders['running_projects']; ?></dd>
        </div>
    </div>
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition transform hover:-translate-y-1">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Pending Projects</dt>
            <dd class="mt-1 text-3xl font-semibold text-orange-500"><?php echo $placeholders['pending_projects']; ?></dd>
        </div>
    </div>
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition transform hover:-translate-y-1">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Today's Tasks</dt>
            <dd class="mt-1 text-3xl font-semibold text-purple-600"><?php echo $placeholders['todays_tasks']; ?></dd>
        </div>
    </div>
</div>

<!-- Revenue Stats -->
<div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 px-4 sm:px-6">
    <div class="bg-gradient-to-r from-green-500 to-emerald-600 overflow-hidden shadow-md rounded-xl text-white hover:shadow-lg transition">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium truncate opacity-80">Total Revenue</dt>
            <dd class="mt-1 text-4xl font-bold">$<?php echo number_format($placeholders['total_revenue'], 2); ?></dd>
        </div>
    </div>
    <div class="bg-gradient-to-r from-orange-400 to-red-500 overflow-hidden shadow-md rounded-xl text-white hover:shadow-lg transition">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium truncate opacity-80">Pending Revenue</dt>
            <dd class="mt-1 text-4xl font-bold">$<?php echo number_format($placeholders['pending_revenue'], 2); ?></dd>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
