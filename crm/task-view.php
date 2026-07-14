<?php
// File: /crm/task-view.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

checkAuth(['CRM']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];
$user_id = $_SESSION['user_id'];

$task_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

if (!$task_id) {
    redirect('/crm/tasks.php');
}

// Fetch Task Details
$stmt = $pdo->prepare("
    SELECT t.*, p.project_name
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    WHERE t.id = :id AND t.crm_id = :crm_id AND t.deleted_at IS NULL
");
$stmt->execute(['id' => $task_id, 'crm_id' => $user_id]);
$task = $stmt->fetch();

if (!$task) {
    redirect('/crm/tasks.php');
}

// Fetch Assigned Employees
$stmt = $pdo->prepare("
    SELECT u.full_name, pa.role
    FROM task_assignments ta
    JOIN users u ON ta.employee_id = u.id
    LEFT JOIN project_assignments pa ON ta.employee_id = pa.employee_id AND pa.project_id = :project_id
    WHERE ta.task_id = :task_id
");
$stmt->execute(['task_id' => $task_id, 'project_id' => $task['project_id']]);
$assigned_employees = $stmt->fetchAll();

// Handle basic status update from this view (if not going through submission workflow)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (verifyCsrfToken($csrf_token)) {
        $new_status = $_POST['status'] ?? '';
        $valid_statuses = ['Pending', 'In Progress', 'Waiting For Approval', 'Revision Required', 'Completed'];

        if (in_array($new_status, $valid_statuses)) {
            $stmt = $pdo->prepare("UPDATE tasks SET status = :status WHERE id = :id AND crm_id = :crm_id");
            $stmt->execute(['status' => $new_status, 'id' => $task_id, 'crm_id' => $user_id]);
            $_SESSION['success_msg'] = "Task status updated.";
            redirect('/crm/task-view.php?id=' . $task_id);
        }
    } else {
        $_SESSION['error_msg'] = "Invalid CSRF token.";
    }
}

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center">
            <a href="tasks.php" class="text-gray-500 hover:text-gray-700 mr-4">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            </a>
            <h1 class="text-2xl font-bold leading-tight text-gray-900">Task: <?php echo htmlspecialchars($task['task_name']); ?></h1>
        </div>
        <div>
            <a href="task-edit.php?id=<?php echo $task_id; ?>" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-full hover:bg-gray-50 transition shadow-sm text-sm font-medium mr-2">Edit Task</a>
            <a href="task-submissions.php?id=<?php echo $task_id; ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-full hover:bg-indigo-700 transition shadow-sm text-sm font-medium">View Submissions</a>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['success_msg'])): ?>
    <div class="mx-4 sm:mx-6 mb-4 bg-green-50 text-green-600 p-3 rounded-lg text-sm">
        <?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
    </div>
<?php endif; ?>
<?php if (isset($_SESSION['error_msg'])): ?>
    <div class="mx-4 sm:mx-6 mb-4 bg-red-50 text-red-600 p-3 rounded-lg text-sm">
        <?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
    </div>
<?php endif; ?>

<div class="px-4 sm:px-6 grid grid-cols-1 lg:grid-cols-3 gap-6 mb-16">
    <!-- Left Column: Details -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden p-6">
            <h3 class="text-lg font-medium text-gray-900 border-b pb-3 mb-4">Task Details</h3>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-6">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Project</dt>
                    <dd class="mt-1 text-sm font-medium text-indigo-600"><a href="project-view.php?id=<?php echo $task['project_id']; ?>"><?php echo htmlspecialchars($task['project_name']); ?></a></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Type</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($task['task_type']); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Due Date</dt>
                    <dd class="mt-1 text-sm font-medium <?php echo (strtotime($task['due_date']) < time() && $task['status'] !== 'Completed') ? 'text-red-600' : 'text-gray-900'; ?>">
                        <?php echo htmlspecialchars($task['due_date']); ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Priority</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                            <?php echo $task['priority'] === 'High' ? 'bg-red-100 text-red-800' : ($task['priority'] === 'Medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?>">
                            <?php echo htmlspecialchars($task['priority']); ?>
                        </span>
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Current Status</dt>
                    <dd class="mt-2 text-sm text-gray-900 flex items-center">
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full mr-4
                            <?php
                            if($task['status'] === 'Completed') echo 'bg-green-100 text-green-800';
                            elseif($task['status'] === 'Waiting For Approval') echo 'bg-purple-100 text-purple-800';
                            elseif($task['status'] === 'Revision Required') echo 'bg-red-100 text-red-800';
                            elseif($task['status'] === 'In Progress') echo 'bg-blue-100 text-blue-800';
                            else echo 'bg-gray-100 text-gray-800';
                            ?>
                        ">
                            <?php echo htmlspecialchars($task['status']); ?>
                        </span>

                        <!-- Inline Status Update -->
                        <form method="POST" action="task-view.php?id=<?php echo $task_id; ?>" class="flex items-center">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                            <input type="hidden" name="update_status" value="1">
                            <select name="status" class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 outline-none focus:ring-indigo-500 focus:border-indigo-500 mr-2">
                                <option value="Pending" <?php echo $task['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="In Progress" <?php echo $task['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="Waiting For Approval" <?php echo $task['status'] === 'Waiting For Approval' ? 'selected' : ''; ?>>Waiting For Approval</option>
                                <option value="Revision Required" <?php echo $task['status'] === 'Revision Required' ? 'selected' : ''; ?>>Revision Required</option>
                                <option value="Completed" <?php echo $task['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                            <button type="submit" class="bg-gray-100 text-gray-700 px-3 py-1.5 rounded-lg hover:bg-gray-200 transition text-sm font-medium">Update</button>
                        </form>
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Description</dt>
                    <dd class="mt-1 text-sm text-gray-900 bg-gray-50 p-4 rounded-lg border border-gray-100">
                        <?php echo nl2br(htmlspecialchars($task['description'] ?: 'No description provided.')); ?>
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Right Column: Assignments -->
    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Assigned Employees</h3>
            </div>
            <div class="p-6">
                <?php if (count($assigned_employees) > 0): ?>
                    <ul class="space-y-4">
                        <?php foreach ($assigned_employees as $emp): ?>
                            <li class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($emp['full_name']); ?></span>
                                <span class="text-xs bg-indigo-50 text-indigo-700 px-2 py-1 rounded"><?php echo htmlspecialchars($emp['role'] ?: 'Employee'); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-sm text-gray-500 text-center py-4">No employees assigned to this task.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
