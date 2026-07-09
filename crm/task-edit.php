<?php
// File: /crm/task-edit.php
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

// Fetch existing task
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = :id AND crm_id = :crm_id AND deleted_at IS NULL");
$stmt->execute(['id' => $task_id, 'crm_id' => $user_id]);
$task = $stmt->fetch();

if (!$task) {
    redirect('/crm/tasks.php');
}

// Fetch current assignments for this task
$stmt = $pdo->prepare("SELECT employee_id FROM task_assignments WHERE task_id = :task_id");
$stmt->execute(['task_id' => $task_id]);
$current_assignments = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch all possible employees for THIS task's project
$stmt = $pdo->prepare("
    SELECT pa.employee_id, pa.role, u.full_name
    FROM project_assignments pa
    JOIN users u ON pa.employee_id = u.id
    WHERE pa.project_id = :project_id
");
$stmt->execute(['project_id' => $task['project_id']]);
$project_employees = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf_token)) {
        $error = "Invalid CSRF token.";
    } else {
        $task_name = sanitizeInput($_POST['task_name'] ?? '');
        $task_type = $_POST['task_type'] ?? 'Custom';
        $priority = $_POST['priority'] ?? 'Medium';
        $due_date = $_POST['due_date'] ?? '';
        $description = sanitizeInput($_POST['description'] ?? '');
        $assigned_employee_ids = $_POST['assigned_employees'] ?? [];

        if (empty($task_name) || empty($due_date) || empty($priority)) {
            $error = "Task Name, Priority, and Due Date are required.";
        } else {
            $pdo->beginTransaction();
            try {
                // Update Task
                $stmt = $pdo->prepare("
                    UPDATE tasks SET
                        task_name = :task_name,
                        task_type = :task_type,
                        description = :description,
                        priority = :priority,
                        due_date = :due_date
                    WHERE id = :id AND crm_id = :crm_id
                ");
                $stmt->execute([
                    'task_name' => $task_name,
                    'task_type' => $task_type,
                    'description' => $description,
                    'priority' => $priority,
                    'due_date' => $due_date,
                    'id' => $task_id,
                    'crm_id' => $user_id
                ]);

                // Update Assignments (Clear and Re-insert)
                $stmt = $pdo->prepare("DELETE FROM task_assignments WHERE task_id = :task_id");
                $stmt->execute(['task_id' => $task_id]);

                if (!empty($assigned_employee_ids)) {
                    $assign_stmt = $pdo->prepare("INSERT INTO task_assignments (task_id, employee_id) VALUES (:task_id, :employee_id)");
                    $valid_employees = array_column($project_employees, 'employee_id');

                    foreach ($assigned_employee_ids as $emp_id) {
                        $emp_id = filter_var($emp_id, FILTER_VALIDATE_INT);
                        if (in_array($emp_id, $valid_employees)) {
                            $assign_stmt->execute(['task_id' => $task_id, 'employee_id' => $emp_id]);
                        }
                    }
                }

                $pdo->commit();
                $_SESSION['success_msg'] = "Task updated successfully.";
                redirect('/crm/tasks.php');
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <div class="flex items-center mb-4">
        <a href="tasks.php" class="text-gray-500 hover:text-gray-700 mr-4">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        </a>
        <h1 class="text-2xl font-bold leading-tight text-gray-900">Edit Task: <?php echo htmlspecialchars($task['task_name']); ?></h1>
    </div>
</div>

<div class="px-4 sm:px-6 mb-16">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden max-w-4xl">
        <form method="POST" action="task-edit.php?id=<?php echo $task_id; ?>" class="p-6 sm:p-8">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

            <?php if ($error): ?>
                <div class="bg-red-50 text-red-500 p-4 rounded-lg mb-6 text-sm">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Info -->
                <div class="col-span-1 md:col-span-2">
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Task Information</h3>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Task Name *</label>
                    <input type="text" name="task_name" required class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none" value="<?php echo htmlspecialchars($_POST['task_name'] ?? $task['task_name']); ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Task Type</label>
                    <select name="task_type" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                        <option value="Daily" <?php echo ($_POST['task_type'] ?? $task['task_type']) === 'Daily' ? 'selected' : ''; ?>>Daily</option>
                        <option value="Weekly" <?php echo ($_POST['task_type'] ?? $task['task_type']) === 'Weekly' ? 'selected' : ''; ?>>Weekly</option>
                        <option value="Monthly" <?php echo ($_POST['task_type'] ?? $task['task_type']) === 'Monthly' ? 'selected' : ''; ?>>Monthly</option>
                        <option value="Custom" <?php echo ($_POST['task_type'] ?? $task['task_type']) === 'Custom' ? 'selected' : ''; ?>>Custom</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Priority *</label>
                    <select name="priority" required class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                        <option value="Low" <?php echo ($_POST['priority'] ?? $task['priority']) === 'Low' ? 'selected' : ''; ?>>Low</option>
                        <option value="Medium" <?php echo ($_POST['priority'] ?? $task['priority']) === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="High" <?php echo ($_POST['priority'] ?? $task['priority']) === 'High' ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Due Date *</label>
                    <input type="date" name="due_date" required class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none" value="<?php echo htmlspecialchars($_POST['due_date'] ?? $task['due_date']); ?>">
                </div>

                <div class="col-span-1 md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="4" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none"><?php echo htmlspecialchars($_POST['description'] ?? $task['description']); ?></textarea>
                </div>

                <!-- Assignments -->
                <div class="col-span-1 md:col-span-2 mt-4">
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Assign Employees</h3>
                    <p class="text-sm text-gray-500 mb-3">Only employees assigned to this project by the Manager can be selected.</p>

                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <?php if (empty($project_employees)): ?>
                            <p class="text-sm text-red-500 text-center">No employees are assigned to this project yet. Contact the Manager.</p>
                        <?php else: ?>
                            <div class="space-y-2">
                                <?php foreach ($project_employees as $emp): ?>
                                    <label class="flex items-center space-x-3 p-2 hover:bg-gray-100 rounded cursor-pointer">
                                        <input type="checkbox" name="assigned_employees[]" value="<?php echo $emp['employee_id']; ?>" <?php echo in_array($emp['employee_id'], $current_assignments) ? 'checked' : ''; ?> class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                        <span class="text-gray-900 font-medium"><?php echo htmlspecialchars($emp['full_name']); ?></span>
                                        <span class="text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded ml-auto"><?php echo htmlspecialchars($emp['role']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <div class="mt-8 pt-5 border-t border-gray-200 flex justify-end">
                <a href="tasks.php" class="bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-full hover:bg-gray-50 transition mr-3">Cancel</a>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-full hover:bg-indigo-700 transition shadow-sm">Update Task</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
