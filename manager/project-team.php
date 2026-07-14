<?php
// File: /manager/project-team.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

checkAuth(['Manager']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];

$project_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

if (!$project_id) {
    redirect('/manager/projects.php');
}

// Fetch existing project
$stmt = $pdo->prepare("SELECT project_name FROM projects WHERE id = :id AND agency_id = :agency_id AND deleted_at IS NULL");
$stmt->execute(['id' => $project_id, 'agency_id' => $agency_id]);
$project = $stmt->fetch();

if (!$project) {
    redirect('/manager/projects.php');
}

$error = '';
$success = '';

// Handle removal
if (isset($_POST['remove_employee_id'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (verifyCsrfToken($csrf_token)) {
        $emp_id = filter_var($_POST['remove_employee_id'], FILTER_VALIDATE_INT);
        if ($emp_id) {
            $stmt = $pdo->prepare("DELETE FROM project_assignments WHERE project_id = :project_id AND employee_id = :employee_id");
            $stmt->execute(['project_id' => $project_id, 'employee_id' => $emp_id]);
            $_SESSION['success_msg'] = "Employee removed from project.";
            redirect('/manager/project-team.php?id=' . $project_id);
        }
    } else {
        $error = "Invalid CSRF token.";
    }
}

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf_token)) {
        $error = "Invalid CSRF token.";
    } else {
        $employee_id = filter_var($_POST['employee_id'] ?? '', FILTER_VALIDATE_INT);
        $role = sanitizeInput($_POST['role'] ?? '');

    if (empty($employee_id) || empty($role)) {
        $error = "Please select an employee and specify a role.";
    } else {
        // Check if already assigned
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM project_assignments WHERE project_id = :project_id AND employee_id = :employee_id");
        $stmt->execute(['project_id' => $project_id, 'employee_id' => $employee_id]);
        if ($stmt->fetchColumn() > 0) {
            $error = "This employee is already assigned to this project.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO project_assignments (project_id, employee_id, role) VALUES (:project_id, :employee_id, :role)");
            try {
                $stmt->execute(['project_id' => $project_id, 'employee_id' => $employee_id, 'role' => $role]);

                logActivity($agency_id, $_SESSION['user_id'], 'Employee Assigned', "Employee assigned as $role.", $project_id);
                logAudit($agency_id, $_SESSION['user_id'], $_SESSION['role_name'], "Assigned employee ($employee_id) to Project ($project_id) as $role", $project_id);
                createNotification($employee_id, 'Assigned to Project', "You have been assigned to project: " . $project['project_name'], "/employee/projects.php");

                $_SESSION['success_msg'] = "Employee assigned successfully.";
                redirect('/manager/project-team.php?id=' . $project_id);
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
    }
}

// Fetch all agency employees for dropdown
$stmt = $pdo->prepare("SELECT u.id, u.full_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.agency_id = :agency_id AND r.name = 'Employee' AND u.account_status = 'Active' AND u.deleted_at IS NULL ORDER BY u.full_name");
$stmt->execute(['agency_id' => $agency_id]);
$employees = $stmt->fetchAll();

// Fetch currently assigned team
$stmt = $pdo->prepare("
    SELECT pa.*, u.full_name as employee_name
    FROM project_assignments pa
    JOIN users u ON pa.employee_id = u.id
    WHERE pa.project_id = :project_id
");
$stmt->execute(['project_id' => $project_id]);
$team = $stmt->fetchAll();

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <div class="flex items-center mb-4">
        <a href="project-view.php?id=<?php echo $project_id; ?>" class="text-gray-500 hover:text-gray-700 mr-4">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        </a>
        <h1 class="text-2xl font-bold leading-tight text-gray-900">Manage Team: <?php echo htmlspecialchars($project['project_name']); ?></h1>
    </div>
</div>

<?php if (isset($_SESSION['success_msg'])): ?>
    <div class="mx-4 sm:mx-6 mb-4 bg-green-50 text-green-600 p-3 rounded-lg text-sm">
        <?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
    </div>
<?php endif; ?>

<div class="px-4 sm:px-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Assignment Form -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Assign Employee</h3>
            </div>
            <form method="POST" action="project-team.php?id=<?php echo $project_id; ?>" class="p-6">
                <input type="hidden" name="assign" value="1">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                <?php if ($error): ?>
                    <div class="bg-red-50 text-red-500 p-3 rounded-lg mb-4 text-sm">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Employee *</label>
                        <select name="employee_id" required class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                            <option value="">Choose an employee...</option>
                            <?php foreach($employees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role in Project *</label>
                        <input type="text" name="role" required placeholder="e.g. Graphic Designer, Web Developer" list="roles_list" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        <datalist id="roles_list">
                            <option value="Sales">
                            <option value="Photographer">
                            <option value="Graphic Designer">
                            <option value="Video Editor">
                            <option value="Content Writer">
                            <option value="Content Approval">
                            <option value="Web Developer">
                        </datalist>
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition shadow-sm font-medium">Assign</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Assigned Team List -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Current Team Members</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned On</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($team) > 0): ?>
                            <?php foreach ($team as $member): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($member['employee_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="bg-indigo-50 text-indigo-700 px-2 py-1 rounded text-xs font-medium"><?php echo htmlspecialchars($member['role']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('d-m-Y', strtotime($member['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <form method="POST" action="project-team.php?id=<?php echo $project_id; ?>" onsubmit="return confirm('Remove this employee from the project?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                            <input type="hidden" name="remove_employee_id" value="<?php echo $member['employee_id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">
                                    <p>No employees are assigned to this project yet.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
