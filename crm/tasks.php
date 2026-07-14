<?php
// File: /crm/tasks.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

checkAuth(['CRM']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];
$user_id = $_SESSION['user_id'];

// Handle Soft Delete
if (isset($_POST['delete_task_id'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (verifyCsrfToken($csrf_token)) {
        $delete_id = filter_var($_POST['delete_task_id'], FILTER_VALIDATE_INT);
        if ($delete_id) {
            // Verify ownership
            $stmt = $pdo->prepare("SELECT id FROM tasks WHERE id = :id AND crm_id = :crm_id AND deleted_at IS NULL");
            $stmt->execute(['id' => $delete_id, 'crm_id' => $user_id]);
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE tasks SET deleted_at = NOW() WHERE id = :id");
                $stmt->execute(['id' => $delete_id]);
                $_SESSION['success_msg'] = "Task deleted successfully.";
            }
            header("Location: tasks.php");
            exit;
        }
    } else {
        $_SESSION['error_msg'] = "Invalid CSRF token.";
        header("Location: tasks.php");
        exit;
    }
}

// Search and Filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$project_filter = $_GET['project_id'] ?? '';

// Pagination
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build Query
$query = "
    SELECT t.*, p.project_name, GROUP_CONCAT(u.full_name SEPARATOR ', ') as assigned_to
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    LEFT JOIN task_assignments ta ON t.id = ta.task_id
    LEFT JOIN users u ON ta.employee_id = u.id
    WHERE t.crm_id = :crm_id AND p.agency_id = :agency_id AND t.deleted_at IS NULL
";
$params = ['crm_id' => $user_id, 'agency_id' => $agency_id];

if ($search) {
    $query .= " AND (t.task_name LIKE :search OR p.project_name LIKE :search)";
    $params['search'] = "%$search%";
}

if ($status_filter) {
    $query .= " AND t.status = :status";
    $params['status'] = $status_filter;
}

if ($project_filter) {
    $query .= " AND t.project_id = :project_id";
    $params['project_id'] = $project_filter;
}

$query .= " GROUP BY t.id";

// Count total for pagination
$count_query = "SELECT COUNT(*) FROM ($query) as sub";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch data
$query .= " ORDER BY t.created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Fetch Projects for Filter
$proj_stmt = $pdo->prepare("SELECT id, project_name FROM projects WHERE crm_id = :crm_id AND agency_id = :agency_id AND deleted_at IS NULL");
$proj_stmt->execute(['crm_id' => $user_id, 'agency_id' => $agency_id]);
$projects = $proj_stmt->fetchAll();

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6 flex justify-between items-center">
    <h1 class="text-3xl font-bold leading-tight text-gray-900">Task Management</h1>
    <a href="task-create.php" class="bg-indigo-600 text-white px-4 py-2 rounded-full hover:bg-indigo-700 transition shadow-sm text-sm font-medium">Create Task</a>
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

<div class="px-4 sm:px-6 mb-6">
    <form method="GET" action="tasks.php" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search tasks..." class="md:col-span-2 px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none">

        <select name="status" class="px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
            <option value="">All Statuses</option>
            <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="In Progress" <?php echo $status_filter === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
            <option value="Waiting For Approval" <?php echo $status_filter === 'Waiting For Approval' ? 'selected' : ''; ?>>Waiting For Approval</option>
            <option value="Revision Required" <?php echo $status_filter === 'Revision Required' ? 'selected' : ''; ?>>Revision Required</option>
            <option value="Completed" <?php echo $status_filter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
        </select>

        <select name="project_id" class="px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
            <option value="">All Projects</option>
            <?php foreach($projects as $p): ?>
                <option value="<?php echo $p['id']; ?>" <?php echo $project_filter == $p['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['project_name']); ?></option>
            <?php endforeach; ?>
        </select>

        <div class="flex space-x-2">
            <button type="submit" class="w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition font-medium">Filter</button>
            <?php if($search || $status_filter || $project_filter): ?>
                <a href="tasks.php" class="w-full bg-red-50 text-red-600 px-4 py-2 rounded-lg hover:bg-red-100 transition font-medium text-center flex items-center justify-center">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="px-4 sm:px-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Task</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (count($tasks) > 0): ?>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($task['task_name']); ?></div>
                                <div class="text-xs text-gray-500">Due: <?php echo htmlspecialchars($task['due_date']); ?> | <?php echo htmlspecialchars($task['task_type']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500"><a href="project-view.php?id=<?php echo $task['project_id']; ?>" class="hover:text-indigo-600"><?php echo htmlspecialchars($task['project_name']); ?></a></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($task['assigned_to'] ?: 'Unassigned'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
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
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="task-view.php?id=<?php echo $task['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">View</a>
                                <a href="task-edit.php?id=<?php echo $task['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                                <form method="POST" action="tasks.php" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this task?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                    <input type="hidden" name="delete_task_id" value="<?php echo $task['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No tasks found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="mt-4 flex justify-between items-center bg-white px-4 py-3 border border-gray-200 rounded-xl sm:px-6">
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $limit, $total_records); ?></span> of <span class="font-medium"><?php echo $total_records; ?></span> results
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&project_id=<?php echo urlencode($project_filter); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $page === $i ? 'text-indigo-600 bg-indigo-50 z-10' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </nav>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>
