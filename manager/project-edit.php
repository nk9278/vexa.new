<?php
// File: /manager/project-edit.php
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
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = :id AND agency_id = :agency_id AND deleted_at IS NULL");
$stmt->execute(['id' => $project_id, 'agency_id' => $agency_id]);
$project = $stmt->fetch();

if (!$project) {
    redirect('/manager/projects.php');
}

// Fetch Clients
$stmt = $pdo->prepare("SELECT id, client_name FROM clients WHERE agency_id = :agency_id AND deleted_at IS NULL ORDER BY client_name");
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

    if (empty($client_id) || empty($project_name) || empty($project_type) || empty($crm_id)) {
        $error = "Client, Project Name, Project Type, and CRM are required.";
    } else {
        $stmt = $pdo->prepare("
            UPDATE projects SET
                client_id = :client_id,
                project_name = :project_name,
                project_type = :project_type,
                description = :description,
                start_date = :start_date,
                expected_completion_date = :expected_completion_date,
                status = :status,
                priority = :priority,
                crm_id = :crm_id
            WHERE id = :id AND agency_id = :agency_id
        ");

        try {
            $stmt->execute([
                'client_id' => $client_id,
                'project_name' => $project_name,
                'project_type' => $project_type,
                'description' => $description,
                'start_date' => $start_date ?: null,
                'expected_completion_date' => $expected_completion_date ?: null,
                'status' => $status,
                'priority' => $priority,
                'crm_id' => $crm_id,
                'id' => $project_id,
                'agency_id' => $agency_id
            ]);
            $_SESSION['success_msg'] = "Project updated successfully.";
            redirect('/manager/projects.php');
        } catch (PDOException $e) {
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
        <h1 class="text-2xl font-bold leading-tight text-gray-900">Edit Project: <?php echo htmlspecialchars($project['project_name']); ?></h1>
    </div>
</div>

<div class="px-4 sm:px-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden max-w-4xl">
        <form method="POST" action="project-edit.php?id=<?php echo $project_id; ?>" class="p-6 sm:p-8">
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
                            <option value="<?php echo $c['id']; ?>" <?php echo ($_POST['client_id'] ?? $project['client_id']) == $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['client_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Project Name *</label>
                    <input type="text" name="project_name" required class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none" value="<?php echo htmlspecialchars($_POST['project_name'] ?? $project['project_name']); ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Project Type *</label>
                    <input type="text" name="project_type" required placeholder="e.g. Website, SEO" list="project_types" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none" value="<?php echo htmlspecialchars($_POST['project_type'] ?? $project['project_type']); ?>">
                    <datalist id="project_types">
                        <option value="Website">
                        <option value="SEO">
                        <option value="Google Ads">
                        <option value="Meta Ads">
                        <option value="Social Media">
                    </datalist>
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
                            <option value="<?php echo $crm['id']; ?>" <?php echo ($_POST['crm_id'] ?? $project['crm_id']) == $crm['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($crm['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                        <option value="Pending" <?php echo ($_POST['status'] ?? $project['status']) === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Running" <?php echo ($_POST['status'] ?? $project['status']) === 'Running' ? 'selected' : ''; ?>>Running</option>
                        <option value="Completed" <?php echo ($_POST['status'] ?? $project['status']) === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="On Hold" <?php echo ($_POST['status'] ?? $project['status']) === 'On Hold' ? 'selected' : ''; ?>>On Hold</option>
                        <option value="Cancelled" <?php echo ($_POST['status'] ?? $project['status']) === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" name="start_date" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none" value="<?php echo htmlspecialchars($_POST['start_date'] ?? $project['start_date']); ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expected Completion Date</label>
                    <input type="date" name="expected_completion_date" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none" value="<?php echo htmlspecialchars($_POST['expected_completion_date'] ?? $project['expected_completion_date']); ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                    <select name="priority" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                        <option value="Low" <?php echo ($_POST['priority'] ?? $project['priority']) === 'Low' ? 'selected' : ''; ?>>Low</option>
                        <option value="Medium" <?php echo ($_POST['priority'] ?? $project['priority']) === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="High" <?php echo ($_POST['priority'] ?? $project['priority']) === 'High' ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>

                <div class="col-span-1 md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="4" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none"><?php echo htmlspecialchars($_POST['description'] ?? $project['description']); ?></textarea>
                </div>

            </div>

            <div class="mt-8 pt-5 border-t border-gray-200 flex justify-end">
                <a href="projects.php" class="bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-full hover:bg-gray-50 transition mr-3">Cancel</a>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-full hover:bg-indigo-700 transition shadow-sm">Update Project</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
