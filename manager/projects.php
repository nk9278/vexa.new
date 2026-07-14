<?php
// File: /manager/projects.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

checkAuth(['Manager']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];

// Handle Soft Delete
if (isset($_POST['delete_project_id'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (verifyCsrfToken($csrf_token)) {
        $delete_id = filter_var($_POST['delete_project_id'], FILTER_VALIDATE_INT);
        if ($delete_id) {
            $stmt = $pdo->prepare("UPDATE projects SET deleted_at = NOW() WHERE id = :id AND agency_id = :agency_id");
            $stmt->execute(['id' => $delete_id, 'agency_id' => $agency_id]);
            $_SESSION['success_msg'] = "Project deleted successfully.";
            header("Location: projects.php");
            exit;
        }
    } else {
        $_SESSION['error_msg'] = "Invalid CSRF token.";
        header("Location: projects.php");
        exit;
    }
}

// Search and Filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';
$crm_filter = $_GET['crm_id'] ?? '';
$employee_filter = $_GET['employee_id'] ?? '';

// Pagination
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build Query
$query = "
    SELECT p.*, c.client_name, u.full_name as crm_name
    FROM projects p
    JOIN clients c ON p.client_id = c.id
    LEFT JOIN users u ON p.crm_id = u.id
    WHERE p.agency_id = :agency_id AND p.deleted_at IS NULL
";
$params = ['agency_id' => $agency_id];

if ($search) {
    $query .= " AND (p.project_name LIKE :search OR c.client_name LIKE :search)";
    $params['search'] = "%$search%";
}

if ($status_filter) {
    $query .= " AND p.status = :status";
    $params['status'] = $status_filter;
}

if ($type_filter) {
    $query .= " AND p.project_type = :type";
    $params['type'] = $type_filter;
}

if ($crm_filter) {
    $query .= " AND p.crm_id = :crm_id";
    $params['crm_id'] = $crm_filter;
}

if ($employee_filter) {
    $query .= " AND p.id IN (SELECT project_id FROM project_assignments WHERE employee_id = :employee_id)";
    $params['employee_id'] = $employee_filter;
}

// Count total for pagination
$count_query = preg_replace('/SELECT .*? FROM/s', 'SELECT COUNT(*) FROM', $query, 1);
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch data
$query .= " ORDER BY p.created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$projects = $stmt->fetchAll();

// Fetch filter options
$types_stmt = $pdo->prepare("SELECT DISTINCT project_type FROM projects WHERE agency_id = :agency_id AND deleted_at IS NULL AND project_type != ''");
$types_stmt->execute(['agency_id' => $agency_id]);
$types = $types_stmt->fetchAll(PDO::FETCH_COLUMN);

$crms_stmt = $pdo->prepare("SELECT u.id, u.full_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.agency_id = :agency_id AND r.name = 'CRM' AND u.deleted_at IS NULL");
$crms_stmt->execute(['agency_id' => $agency_id]);
$crms = $crms_stmt->fetchAll();

$emps_stmt = $pdo->prepare("SELECT u.id, u.full_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.agency_id = :agency_id AND r.name = 'Employee' AND u.deleted_at IS NULL");
$emps_stmt->execute(['agency_id' => $agency_id]);
$employees = $emps_stmt->fetchAll();

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6 flex justify-between items-center">
    <h1 class="text-3xl font-bold leading-tight text-gray-900">Projects</h1>
    <a href="project-create.php" class="bg-indigo-600 text-white px-4 py-2 rounded-full hover:bg-indigo-700 transition shadow-sm text-sm font-medium">Create Project</a>
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
    <form method="GET" action="projects.php" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-4">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search projects..." class="md:col-span-2 px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none">

        <select name="status" class="px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
            <option value="">All Statuses</option>
            <option value="Running" <?php echo $status_filter === 'Running' ? 'selected' : ''; ?>>Running</option>
            <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="Completed" <?php echo $status_filter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
            <option value="On Hold" <?php echo $status_filter === 'On Hold' ? 'selected' : ''; ?>>On Hold</option>
            <option value="Cancelled" <?php echo $status_filter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
        </select>

        <select name="type" class="px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
            <option value="">All Types</option>
            <?php foreach($types as $t): ?>
                <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $type_filter === $t ? 'selected' : ''; ?>><?php echo htmlspecialchars($t); ?></option>
            <?php endforeach; ?>
        </select>

        <select name="crm_id" class="px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
            <option value="">All CRMs</option>
            <?php foreach($crms as $c): ?>
                <option value="<?php echo $c['id']; ?>" <?php echo $crm_filter == $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['full_name']); ?></option>
            <?php endforeach; ?>
        </select>

        <div class="flex space-x-2">
            <button type="submit" class="w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition font-medium">Filter</button>
            <?php if($search || $status_filter || $type_filter || $crm_filter || $employee_filter): ?>
                <a href="projects.php" class="w-full bg-red-50 text-red-600 px-4 py-2 rounded-lg hover:bg-red-100 transition font-medium text-center flex items-center justify-center">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="px-4 sm:px-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CRM</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (count($projects) > 0): ?>
                    <?php foreach ($projects as $project): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($project['project_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500"><a href="client-view.php?id=<?php echo $project['client_id']; ?>" class="hover:text-indigo-600"><?php echo htmlspecialchars($project['client_name']); ?></a></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($project['project_type']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($project['crm_name'] ?: 'Unassigned'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    <?php echo htmlspecialchars($project['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="project-view.php?id=<?php echo $project['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">View</a>
                                <a href="project-edit.php?id=<?php echo $project['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                                <form method="POST" action="projects.php" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this project?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                    <input type="hidden" name="delete_project_id" value="<?php echo $project['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No projects found.</td>
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
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&type=<?php echo urlencode($type_filter); ?>&crm_id=<?php echo urlencode($crm_filter); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $page === $i ? 'text-indigo-600 bg-indigo-50 z-10' : 'text-gray-700 hover:bg-gray-50'; ?>">
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
