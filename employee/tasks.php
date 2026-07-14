<?php
// File: /employee/tasks.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

checkAuth(['Employee']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];
$user_id = $_SESSION['user_id'];

// Handle Quick Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status_id'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (verifyCsrfToken($csrf_token)) {
        $task_id = filter_var($_POST['update_status_id'], FILTER_VALIDATE_INT);
        $new_status = sanitizeInput($_POST['status'] ?? '');

        // Employees can only move tasks to In Progress.
        // Waiting For Approval requires a submission.
        // Completed/Revision Required are controlled by CRM.
        if ($new_status === 'In Progress' && $task_id) {
            // Verify assigned
            $stmt = $pdo->prepare("SELECT ta.id FROM task_assignments ta JOIN tasks t ON ta.task_id = t.id WHERE ta.task_id = :task_id AND ta.employee_id = :employee_id AND t.status IN ('Pending', 'Revision Required')");
            $stmt->execute(['task_id' => $task_id, 'employee_id' => $user_id]);
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE tasks SET status = :status WHERE id = :id");
                $stmt->execute(['status' => $new_status, 'id' => $task_id]);
                $_SESSION['success_msg'] = "Task status updated to In Progress.";
            }
        }
        header("Location: tasks.php");
        exit;
    } else {
        $_SESSION['error_msg'] = "Invalid CSRF token.";
        header("Location: tasks.php");
        exit;
    }
}

// Search and Filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Pagination
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build Query
$query = "
    SELECT t.*, p.project_name
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    JOIN task_assignments ta ON t.id = ta.task_id
    WHERE ta.employee_id = :employee_id AND t.deleted_at IS NULL
";
$params = ['employee_id' => $user_id];

if ($search) {
    $query .= " AND (t.task_name LIKE :search OR p.project_name LIKE :search)";
    $params['search'] = "%$search%";
}

if ($status_filter) {
    $query .= " AND t.status = :status";
    $params['status'] = $status_filter;
}

// Count total for pagination
$count_query = preg_replace('/SELECT .*? FROM/s', 'SELECT COUNT(*) FROM', $query, 1);
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch data
$query .= " ORDER BY t.due_date ASC, t.priority DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6 flex justify-between items-center">
    <h1 class="text-3xl font-bold leading-tight text-gray-900">My Tasks</h1>
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
    <form method="GET" action="tasks.php" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search tasks..." class="md:col-span-2 px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none">

        <select name="status" class="px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
            <option value="">All Statuses</option>
            <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="In Progress" <?php echo $status_filter === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
            <option value="Waiting For Approval" <?php echo $status_filter === 'Waiting For Approval' ? 'selected' : ''; ?>>Waiting For Approval</option>
            <option value="Revision Required" <?php echo $status_filter === 'Revision Required' ? 'selected' : ''; ?>>Revision Required</option>
            <option value="Completed" <?php echo $status_filter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
        </select>

        <div class="flex space-x-2">
            <button type="submit" class="w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition font-medium">Filter</button>
            <?php if($search || $status_filter): ?>
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
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Task / Project</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority & Due Date</th>
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
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($task['project_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        <?php echo $task['priority'] === 'High' ? 'bg-red-100 text-red-800' : ($task['priority'] === 'Medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?>">
                                        <?php echo htmlspecialchars($task['priority']); ?>
                                    </span>
                                    <span class="text-sm <?php echo (strtotime($task['due_date']) < time() && $task['status'] !== 'Completed') ? 'text-red-600 font-bold' : 'text-gray-900'; ?>">
                                        <?php echo htmlspecialchars($task['due_date']); ?>
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-2">
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

                                    <?php if(in_array($task['status'], ['Pending', 'Revision Required'])): ?>
                                        <form method="POST" action="tasks.php" class="inline-block">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                            <input type="hidden" name="update_status_id" value="<?php echo $task['id']; ?>">
                                            <input type="hidden" name="status" value="In Progress">
                                            <button type="submit" class="text-xs bg-indigo-50 text-indigo-700 hover:bg-indigo-100 px-2 py-1 rounded transition">Start Task</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="task-view.php?id=<?php echo $task['id']; ?>" class="text-indigo-600 hover:text-indigo-900">Open</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No tasks assigned to you.</td>
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
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $page === $i ? 'text-indigo-600 bg-indigo-50 z-10' : 'text-gray-700 hover:bg-gray-50'; ?>">
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
