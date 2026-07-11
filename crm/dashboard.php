<?php
// File: /crm/dashboard.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

checkAuth(['CRM']);

$pdo = getDbConnection();
$user_id = $_SESSION['user_id'];
$agency_id = $_SESSION['agency_id'];

// Assigned Projects Count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE crm_id = :crm_id AND agency_id = :agency_id AND deleted_at IS NULL");
$stmt->execute(['crm_id' => $user_id, 'agency_id' => $agency_id]);
$assigned_projects = $stmt->fetchColumn();

// Assigned Clients Count
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT client_id) FROM projects WHERE crm_id = :crm_id AND agency_id = :agency_id AND deleted_at IS NULL");
$stmt->execute(['crm_id' => $user_id, 'agency_id' => $agency_id]);
$assigned_clients = $stmt->fetchColumn();

// Pending Tasks
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE crm_id = :crm_id AND status = 'Pending' AND deleted_at IS NULL");
$stmt->execute(['crm_id' => $user_id]);
$pending_tasks = $stmt->fetchColumn();

// Completed Tasks
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE crm_id = :crm_id AND status = 'Completed' AND deleted_at IS NULL");
$stmt->execute(['crm_id' => $user_id]);
$completed_tasks = $stmt->fetchColumn();

// Waiting For Approval
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE crm_id = :crm_id AND status = 'Waiting For Approval' AND deleted_at IS NULL");
$stmt->execute(['crm_id' => $user_id]);
$waiting_approval = $stmt->fetchColumn();

// Pending Revisions
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE crm_id = :crm_id AND status = 'Revision Required' AND deleted_at IS NULL");
$stmt->execute(['crm_id' => $user_id]);
$pending_revisions = $stmt->fetchColumn();

// Today's Work
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE crm_id = :crm_id AND DATE(created_at) = CURDATE() AND deleted_at IS NULL");
$stmt->execute(['crm_id' => $user_id]);
$todays_work = $stmt->fetchColumn();

// Upcoming Deadlines (Next 7 days, excluding completed)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE crm_id = :crm_id AND status != 'Completed' AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND deleted_at IS NULL");
$stmt->execute(['crm_id' => $user_id]);
$upcoming_deadlines = $stmt->fetchColumn();

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6 flex justify-between items-center">
    <h1 class="text-3xl font-bold leading-tight text-gray-900">CRM Dashboard</h1>
</div>

<div class="mt-6 px-4 sm:px-6">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Assigned Clients</dt>
            <dd class="mt-2 text-3xl font-semibold text-indigo-600"><?php echo htmlspecialchars($assigned_clients); ?></dd>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Assigned Projects</dt>
            <dd class="mt-2 text-3xl font-semibold text-blue-600"><?php echo htmlspecialchars($assigned_projects); ?></dd>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Pending Tasks</dt>
            <dd class="mt-2 text-3xl font-semibold text-orange-500"><?php echo htmlspecialchars($pending_tasks); ?></dd>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Waiting For Approval</dt>
            <dd class="mt-2 text-3xl font-semibold text-purple-600"><?php echo htmlspecialchars($waiting_approval); ?></dd>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Pending Revisions</dt>
            <dd class="mt-2 text-3xl font-semibold text-red-500"><?php echo htmlspecialchars($pending_revisions); ?></dd>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Completed Tasks</dt>
            <dd class="mt-2 text-3xl font-semibold text-green-600"><?php echo htmlspecialchars($completed_tasks); ?></dd>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Today's Work</dt>
            <dd class="mt-2 text-3xl font-semibold text-teal-600"><?php echo htmlspecialchars($todays_work); ?></dd>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Upcoming Deadlines (7 Days)</dt>
            <dd class="mt-2 text-3xl font-semibold text-red-600"><?php echo htmlspecialchars($upcoming_deadlines); ?></dd>
        </div>

    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
