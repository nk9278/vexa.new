<?php
// File: /crm/task-create.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../includes/notification_functions.php';

checkAuth(['CRM']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];
$user_id = $_SESSION['user_id'];

// Fetch Assigned Projects
$stmt = $pdo->prepare("SELECT id, project_name FROM projects WHERE crm_id = :crm_id AND agency_id = :agency_id AND deleted_at IS NULL ORDER BY project_name");
$stmt->execute(['crm_id' => $user_id, 'agency_id' => $agency_id]);
$projects = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf_token)) {
        $error = "Invalid CSRF token.";
    } else {
        $project_id = filter_var($_POST['project_id'] ?? '', FILTER_VALIDATE_INT);
        $task_name = sanitizeInput($_POST['task_name'] ?? '');
        $task_type = $_POST['task_type'] ?? 'Custom';
        $priority = $_POST['priority'] ?? 'Medium';
        $due_date = $_POST['due_date'] ?? '';
        $description = sanitizeInput($_POST['description'] ?? '');
        $assigned_employee_ids = $_POST['assigned_employees'] ?? [];

        if (empty($project_id) || empty($task_name) || empty($due_date) || empty($priority)) {
            $error = "Project, Task Name, Priority, and Due Date are required.";
        } else {
            // Verify CRM owns this project
            $stmt = $pdo->prepare("SELECT id FROM projects WHERE id = :id AND crm_id = :crm_id AND deleted_at IS NULL");
            $stmt->execute(['id' => $project_id, 'crm_id' => $user_id]);
            if (!$stmt->fetch()) {
                $error = "Invalid Project selection.";
            } else {
                $pdo->beginTransaction();
                try {
                    // Create Task
                    $stmt = $pdo->prepare("
                        INSERT INTO tasks (project_id, crm_id, task_name, task_type, description, priority, due_date)
                        VALUES (:project_id, :crm_id, :task_name, :task_type, :description, :priority, :due_date)
                    ");
                    $stmt->execute([
                        'project_id' => $project_id,
                        'crm_id' => $user_id,
                        'task_name' => $task_name,
                        'task_type' => $task_type,
                        'description' => $description,
                        'priority' => $priority,
                        'due_date' => $due_date
                    ]);
                    $task_id = $pdo->lastInsertId();

                    // Handle Assignments (Only employees assigned to this project)
                    if (!empty($assigned_employee_ids)) {
                        $assign_stmt = $pdo->prepare("INSERT INTO task_assignments (task_id, employee_id) VALUES (:task_id, :employee_id)");

                        // Validate employees are actually assigned to the project
                        $valid_emps_stmt = $pdo->prepare("SELECT employee_id FROM project_assignments WHERE project_id = :project_id");
                        $valid_emps_stmt->execute(['project_id' => $project_id]);
                        $valid_employees = $valid_emps_stmt->fetchAll(PDO::FETCH_COLUMN);

                        foreach ($assigned_employee_ids as $emp_id) {
                            $emp_id = filter_var($emp_id, FILTER_VALIDATE_INT);
                            if (in_array($emp_id, $valid_employees)) {
                                $assign_stmt->execute(['task_id' => $task_id, 'employee_id' => $emp_id]);
                                createNotification($emp_id, 'Task Assigned', "You were assigned to task: $task_name", "/employee/task-view.php?id=$task_id");
                            }
                        }
                    }

                    logActivity($agency_id, $user_id, 'Task Created', "Task '$task_name' created.", $project_id);
                    logAudit($agency_id, $user_id, 'CRM', "Created Task: $task_name", $project_id);

                    $pdo->commit();
                    $_SESSION['success_msg'] = "Task created successfully.";
                    redirect('/crm/tasks.php');
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = "Database error: " . $e->getMessage();
                }
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
        <h1 class="text-2xl font-bold leading-tight text-gray-900">Create New Task</h1>
    </div>
</div>

<div class="px-4 sm:px-6 mb-16">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden max-w-4xl">
        <form method="POST" action="task-create.php" class="p-6 sm:p-8" id="taskForm">
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

                <div class="col-span-1 md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Project *</label>
                    <select name="project_id" id="projectSelect" required class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                        <option value="">Select a Project</option>
                        <?php foreach($projects as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo (isset($_GET['project_id']) && $_GET['project_id'] == $p['id']) || ($_POST['project_id'] ?? '') == $p['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['project_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Task Name *</label>
                    <input type="text" name="task_name" required placeholder="e.g. 10 Social Media Posts" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none" value="<?php echo htmlspecialchars($_POST['task_name'] ?? ''); ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Task Type</label>
                    <select name="task_type" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                        <option value="Daily" <?php echo ($_POST['task_type'] ?? '') === 'Daily' ? 'selected' : ''; ?>>Daily</option>
                        <option value="Weekly" <?php echo ($_POST['task_type'] ?? '') === 'Weekly' ? 'selected' : ''; ?>>Weekly</option>
                        <option value="Monthly" <?php echo ($_POST['task_type'] ?? '') === 'Monthly' ? 'selected' : ''; ?>>Monthly</option>
                        <option value="Custom" <?php echo ($_POST['task_type'] ?? 'Custom') === 'Custom' ? 'selected' : ''; ?>>Custom</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Priority *</label>
                    <select name="priority" required class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                        <option value="Low" <?php echo ($_POST['priority'] ?? '') === 'Low' ? 'selected' : ''; ?>>Low</option>
                        <option value="Medium" <?php echo ($_POST['priority'] ?? 'Medium') === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="High" <?php echo ($_POST['priority'] ?? '') === 'High' ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Due Date *</label>
                    <input type="date" name="due_date" required class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none" value="<?php echo htmlspecialchars($_POST['due_date'] ?? ''); ?>">
                </div>

                <div class="col-span-1 md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="4" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <!-- Assignments -->
                <div class="col-span-1 md:col-span-2 mt-4">
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Assign Employees</h3>
                    <p class="text-sm text-gray-500 mb-3">Only employees assigned to the selected project by the Manager will appear here.</p>

                    <div id="employeeList" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <p class="text-sm text-gray-500 italic text-center">Please select a project first.</p>
                    </div>
                </div>

            </div>

            <div class="mt-8 pt-5 border-t border-gray-200 flex justify-end">
                <a href="tasks.php" class="bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-full hover:bg-gray-50 transition mr-3">Cancel</a>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-full hover:bg-indigo-700 transition shadow-sm">Save Task</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const projectSelect = document.getElementById('projectSelect');
    const employeeList = document.getElementById('employeeList');

    function fetchEmployees(projectId) {
        if (!projectId) {
            employeeList.innerHTML = '<p class="text-sm text-gray-500 italic text-center">Please select a project first.</p>';
            return;
        }

        employeeList.innerHTML = '<p class="text-sm text-gray-500 text-center">Loading employees...</p>';

        // Using AJAX to fetch employees assigned to this project
        fetch(`get_project_employees.php?project_id=${projectId}`)
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    employeeList.innerHTML = '<p class="text-sm text-red-500 text-center">No employees are assigned to this project yet. Contact the Manager.</p>';
                    return;
                }

                let html = '<div class="space-y-2">';
                data.forEach(emp => {
                    html += `
                        <label class="flex items-center space-x-3 p-2 hover:bg-gray-100 rounded cursor-pointer">
                            <input type="checkbox" name="assigned_employees[]" value="${emp.employee_id}" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <span class="text-gray-900 font-medium">${emp.full_name}</span>
                            <span class="text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded ml-auto">${emp.role}</span>
                        </label>
                    `;
                });
                html += '</div>';
                employeeList.innerHTML = html;
            })
            .catch(error => {
                employeeList.innerHTML = '<p class="text-sm text-red-500 text-center">Error loading employees.</p>';
            });
    }

    projectSelect.addEventListener('change', function() {
        fetchEmployees(this.value);
    });

    // If project is already selected (e.g. via GET or old POST), fetch immediately
    if (projectSelect.value) {
        fetchEmployees(projectSelect.value);
    }
});
</script>

<?php include __DIR__ . '/footer.php'; ?>
