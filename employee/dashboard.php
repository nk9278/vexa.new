<?php
// File: /employee/dashboard.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

checkAuth(['Employee']);

$pdo = getDbConnection();
$user_id = $_SESSION['user_id'];

// Get counts of assigned tasks
$base_query = "
    SELECT COUNT(t.id)
    FROM tasks t
    JOIN task_assignments ta ON t.id = ta.task_id
    WHERE ta.employee_id = :employee_id AND t.deleted_at IS NULL
";

// Assigned Tasks (Total)
$stmt = $pdo->prepare($base_query);
$stmt->execute(['employee_id' => $user_id]);
$assigned_tasks = $stmt->fetchColumn();

// Pending Tasks
$stmt = $pdo->prepare($base_query . " AND t.status = 'Pending'");
$stmt->execute(['employee_id' => $user_id]);
$pending_tasks = $stmt->fetchColumn();

// In Progress Tasks
$stmt = $pdo->prepare($base_query . " AND t.status = 'In Progress'");
$stmt->execute(['employee_id' => $user_id]);
$in_progress_tasks = $stmt->fetchColumn();

// Revision Required
$stmt = $pdo->prepare($base_query . " AND t.status = 'Revision Required'");
$stmt->execute(['employee_id' => $user_id]);
$revision_tasks = $stmt->fetchColumn();

// Completed Tasks
$stmt = $pdo->prepare($base_query . " AND t.status = 'Completed'");
$stmt->execute(['employee_id' => $user_id]);
$completed_tasks = $stmt->fetchColumn();

// Today's Tasks
$stmt = $pdo->prepare($base_query . " AND DATE(t.due_date) = CURDATE() AND t.status != 'Completed'");
$stmt->execute(['employee_id' => $user_id]);
$todays_tasks = $stmt->fetchColumn();

// Upcoming Deadlines (Next 7 days, excluding today)
$stmt = $pdo->prepare($base_query . " AND t.due_date > CURDATE() AND t.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND t.status != 'Completed'");
$stmt->execute(['employee_id' => $user_id]);
$upcoming_deadlines = $stmt->fetchColumn();

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6 flex justify-between items-center">
    <h1 class="text-3xl font-bold leading-tight text-gray-900">Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></h1>
</div>

<div class="mt-6 px-4 sm:px-6">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Total Assigned Tasks</dt>
            <dd class="mt-2 text-3xl font-semibold text-indigo-600"><?php echo htmlspecialchars($assigned_tasks); ?></dd>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Pending / New Tasks</dt>
            <dd class="mt-2 text-3xl font-semibold text-gray-600"><?php echo htmlspecialchars($pending_tasks); ?></dd>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">In Progress</dt>
            <dd class="mt-2 text-3xl font-semibold text-blue-500"><?php echo htmlspecialchars($in_progress_tasks); ?></dd>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Revision Required</dt>
            <dd class="mt-2 text-3xl font-semibold text-red-500"><?php echo htmlspecialchars($revision_tasks); ?></dd>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Completed Tasks</dt>
            <dd class="mt-2 text-3xl font-semibold text-green-600"><?php echo htmlspecialchars($completed_tasks); ?></dd>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Today's Tasks</dt>
            <dd class="mt-2 text-3xl font-semibold text-orange-500"><?php echo htmlspecialchars($todays_tasks); ?></dd>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Upcoming Deadlines (7 Days)</dt>
            <dd class="mt-2 text-3xl font-semibold text-purple-600"><?php echo htmlspecialchars($upcoming_deadlines); ?></dd>
        </div>

    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
