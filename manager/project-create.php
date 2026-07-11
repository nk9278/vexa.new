<?php
// File: /manager/project-create.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../includes/notification_functions.php';

checkAuth(['Manager']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];

// Fetch Clients
$stmt = $pdo->prepare("SELECT id, client_name FROM clients WHERE agency_id = :agency_id AND status = 'Active' AND deleted_at IS NULL ORDER BY client_name");
$stmt->execute(['agency_id' => $agency_id]);
$clients = $stmt->fetchAll();

// Fetch CRMs
$stmt = $pdo->prepare("SELECT u.id, u.full_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.agency_id = :agency_id AND r.name = 'CRM' AND u.account_status = 'Active' AND u.deleted_at IS NULL ORDER BY u.full_name");
$stmt->execute(['agency_id' => $agency_id]);
$crms = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf_token)) {
        $error = "Invalid CSRF token.";
    } else {
        $client_id = filter_var($_POST['client_id'] ?? '', FILTER_VALIDATE_INT);
        $project_name = sanitizeInput($_POST['project_name'] ?? '');
    $project_type = sanitizeInput($_POST['project_type'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $start_date = $_POST['start_date'] ?? null;
    $expected_completion_date = $_POST['expected_completion_date'] ?? null;
    $status = $_POST['status'] ?? 'Pending';
    $priority = $_POST['priority'] ?? 'Medium';
    $crm_id = filter_var($_POST['crm_id'] ?? '', FILTER_VALIDATE_INT);
    $project_amount = filter_var($_POST['project_amount'] ?? 0, FILTER_VALIDATE_FLOAT);

    if (empty($client_id) || empty($project_name) || empty($project_type) || empty($crm_id)) {
        $error = "Client, Project Name, Project Type, and CRM are required.";
    } else {
        $pdo->beginTransaction();
        try {
            // Insert Project
            $stmt = $pdo->prepare("
                INSERT INTO projects (agency_id, client_id, project_name, project_type, description, start_date, expected_completion_date, status, priority, crm_id)
                VALUES (:agency_id, :client_id, :project_name, :project_type, :description, :start_date, :expected_completion_date, :status, :priority, :crm_id)
            ");
            $stmt->execute([
                'agency_id' => $agency_id,
                'client_id' => $client_id,
                'project_name' => $project_name,
                'project_type' => $project_type,
                'description' => $description,
                'start_date' => $start_date ?: null,
                'expected_completion_date' => $expected_completion_date ?: null,
                'status' => $status,
                'priority' => $priority,
                'crm_id' => $crm_id
            ]);
            $project_id = $pdo->lastInsertId();

            // Insert Initial Payment Record
            $stmt = $pdo->prepare("INSERT INTO project_payments (project_id, project_amount, pending_amount) VALUES (:project_id, :project_amount, :pending_amount)");
            $stmt->execute([
                'project_id' => $project_id,
                'project_amount' => $project_amount ?: 0,
                'pending_amount' => $project_amount ?: 0
            ]);

            logActivity($agency_id, $_SESSION['user_id'], 'New Project Created', "Project '$project_name' was created.", $project_id, $client_id);
            logAudit($agency_id, $_SESSION['user_id'], $_SESSION['role_name'], "Created Project: $project_name", $project_id, $client_id);
            createNotification($crm_id, 'Assigned to Project', "You have been assigned to project: $project_name", "/crm/project-view.php?id=$project_id");

            $pdo->commit();
            $_SESSION['success_msg'] = "Project created successfully.";
            redirect('/manager/projects.php');
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
        <a href="projects.php" class="text-gray-500 hover:text-gray-700 mr-4">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        </a>
        <h1 class="text-2xl font-bold leading-tight text-gray-900">Create New Project</h1>
    </div>
</div>

<div class="px-4 sm:px-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden max-w-4xl">
        <form method="POST" action="project-create.php" class="p-6 sm:p-8">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

            <?php if ($error): ?>
                <div class="bg-red-50 text-red-500 p-4 rounded-lg mb-6 text-sm">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Info -->
                <div class="col-span-1 md:col-span-2">
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Project Information</h3>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Client *</label>
                    <select name="client_id" required class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                        <option value="">Select a Client</option>
                        <?php foreach($clients as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo (isset($_GET['client_id']) && $_GET['client_id'] == $c['id']) || ($_POST['client_id'] ?? '') == $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['client_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Project Name *</label>
                    <input type="text" name="project_name" required class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none" value="<?php echo htmlspecialchars($_POST['project_name'] ?? ''); ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Project Type *</label>
                    <input type="text" name="project_type" required placeholder="e.g. Website, SEO, Google Ads" list="project_types" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none" value="<?php echo htmlspecialchars($_POST['project_type'] ?? ''); ?>">
                    <datalist id="project_types">
                        <option value="Website">
                        <option value="SEO">
                        <option value="Google Ads">
                        <option value="Meta Ads">
                        <option value="Social Media">
                    </datalist>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Total Project Amount</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">₹</span>
                        </div>
                        <input type="number" step="0.01" name="project_amount" class="w-full pl-7 px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none" value="<?php echo htmlspecialchars($_POST['project_amount'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Assignment & Status -->
                <div class="col-span-1 md:col-span-2 mt-4">
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Assignment & Status</h3>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Assign CRM *</label>
                    <select name="crm_id" required class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                        <option value="">Select a CRM</option>
                        <?php foreach($crms as $crm): ?>
                            <option value="<?php echo $crm['id']; ?>" <?php echo ($_POST['crm_id'] ?? '') == $crm['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($crm['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                        <option value="Pending" <?php echo ($_POST['status'] ?? '') === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Running" <?php echo ($_POST['status'] ?? '') === 'Running' ? 'selected' : ''; ?>>Running</option>
                        <option value="On Hold" <?php echo ($_POST['status'] ?? '') === 'On Hold' ? 'selected' : ''; ?>>On Hold</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" name="start_date" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none" value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expected Completion Date</label>
                    <input type="date" name="expected_completion_date" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none" value="<?php echo htmlspecialchars($_POST['expected_completion_date'] ?? ''); ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                    <select name="priority" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                        <option value="Low" <?php echo ($_POST['priority'] ?? '') === 'Low' ? 'selected' : ''; ?>>Low</option>
                        <option value="Medium" <?php echo ($_POST['priority'] ?? 'Medium') === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="High" <?php echo ($_POST['priority'] ?? '') === 'High' ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>

                <div class="col-span-1 md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="4" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

            </div>

            <div class="mt-8 pt-5 border-t border-gray-200 flex justify-end">
                <a href="projects.php" class="bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-full hover:bg-gray-50 transition mr-3">Cancel</a>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-full hover:bg-indigo-700 transition shadow-sm">Save Project</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
