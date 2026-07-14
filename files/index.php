<?php
// File: /files/index.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

// Requires auth for everyone
checkAuth();

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];
$user_id = $_SESSION['user_id'];
$role_name = $_SESSION['role_name'] ?? '';

// Search and Filters
$search = $_GET['search'] ?? '';
$project_filter = $_GET['project_id'] ?? '';
$client_filter = $_GET['client_id'] ?? '';
$type_filter = $_GET['file_type'] ?? '';

// Pagination
$limit = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Base query for files
$query = "
    SELECT f.*, p.project_name, c.client_name, u.full_name as uploader_name
    FROM google_drive_files f
    JOIN projects p ON f.project_id = p.id
    JOIN clients c ON p.client_id = c.id
    JOIN users u ON f.uploader_id = u.id
    WHERE f.agency_id = :agency_id AND f.status = 'Active'
";
$params = ['agency_id' => $agency_id];

// Security/Scoping by role
if ($role_name === 'CRM') {
    $query .= " AND p.crm_id = :user_id";
    $params['user_id'] = $user_id;
} elseif ($role_name === 'Employee') {
    $query .= " AND f.project_id IN (SELECT project_id FROM project_assignments WHERE employee_id = :user_id)";
    $params['user_id'] = $user_id;
}

// Apply Filters
if ($search) {
    $query .= " AND f.file_name LIKE :search";
    $params['search'] = "%$search%";
}
if ($project_filter) {
    $query .= " AND f.project_id = :project_id";
    $params['project_id'] = $project_filter;
}
if ($client_filter) {
    $query .= " AND p.client_id = :client_id";
    $params['client_id'] = $client_filter;
}
if ($type_filter) {
    $query .= " AND f.file_type = :file_type";
    $params['file_type'] = $type_filter;
}

// Count total
$count_query = preg_replace('/SELECT .*? FROM/s', 'SELECT COUNT(*) FROM', $query, 1);
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch data
$query .= " ORDER BY f.created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$files = $stmt->fetchAll();

// Fetch filter options based on role
if (in_array($role_name, ['Super Admin', 'Agency Owner', 'Manager'])) {
    $proj_stmt = $pdo->prepare("SELECT id, project_name FROM projects WHERE agency_id = :agency_id AND deleted_at IS NULL");
    $proj_stmt->execute(['agency_id' => $agency_id]);
    $cli_stmt = $pdo->prepare("SELECT id, client_name FROM clients WHERE agency_id = :agency_id AND deleted_at IS NULL");
    $cli_stmt->execute(['agency_id' => $agency_id]);
} elseif ($role_name === 'CRM') {
    $proj_stmt = $pdo->prepare("SELECT id, project_name FROM projects WHERE crm_id = :crm_id AND deleted_at IS NULL");
    $proj_stmt->execute(['crm_id' => $user_id]);
    $cli_stmt = $pdo->prepare("SELECT DISTINCT c.id, c.client_name FROM clients c JOIN projects p ON c.id = p.client_id WHERE p.crm_id = :crm_id AND c.deleted_at IS NULL");
    $cli_stmt->execute(['crm_id' => $user_id]);
} else {
    $proj_stmt = $pdo->prepare("SELECT p.id, p.project_name FROM projects p JOIN project_assignments pa ON p.id = pa.project_id WHERE pa.employee_id = :employee_id AND p.deleted_at IS NULL");
    $proj_stmt->execute(['employee_id' => $user_id]);
    $cli_stmt = $pdo->prepare("SELECT DISTINCT c.id, c.client_name FROM clients c JOIN projects p ON c.id = p.client_id JOIN project_assignments pa ON p.id = pa.project_id WHERE pa.employee_id = :employee_id AND c.deleted_at IS NULL");
    $cli_stmt->execute(['employee_id' => $user_id]);
}
$filter_projects = $proj_stmt->fetchAll();
$filter_clients = $cli_stmt->fetchAll();

// Get unique file types safely across agency
$types_stmt = $pdo->prepare("SELECT DISTINCT file_type FROM google_drive_files WHERE agency_id = :agency_id AND status = 'Active'");
$types_stmt->execute(['agency_id' => $agency_id]);
$filter_types = $types_stmt->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/../includes/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-4">
        <h1 class="text-3xl font-bold leading-tight text-gray-900">File Browser</h1>
        <?php if ($role_name === 'Agency Owner'): ?>
            <a href="<?php echo BASE_URL; ?>/agency/google-drive.php" class="mt-4 sm:mt-0 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-full hover:bg-gray-50 transition shadow-sm text-sm font-medium">Manage Drive Connection</a>
        <?php endif; ?>
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

<div class="px-4 sm:px-6 mb-6">
    <form method="GET" action="<?php echo BASE_URL; ?>/files/index.php" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-4">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search file names..." class="md:col-span-2 px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none">

        <select name="project_id" class="px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
            <option value="">All Projects</option>
            <?php foreach($filter_projects as $p): ?>
                <option value="<?php echo $p['id']; ?>" <?php echo $project_filter == $p['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['project_name']); ?></option>
            <?php endforeach; ?>
        </select>

        <select name="client_id" class="px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
            <option value="">All Clients</option>
            <?php foreach($filter_clients as $c): ?>
                <option value="<?php echo $c['id']; ?>" <?php echo $client_filter == $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['client_name']); ?></option>
            <?php endforeach; ?>
        </select>

        <select name="file_type" class="px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
            <option value="">All File Types</option>
            <?php foreach($filter_types as $t): ?>
                <?php if ($t): ?>
                <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $type_filter === $t ? 'selected' : ''; ?>>.<?php echo htmlspecialchars(strtoupper($t)); ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>

        <div class="flex space-x-2">
            <button type="submit" class="w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition font-medium">Filter</button>
            <?php if($search || $project_filter || $client_filter || $type_filter): ?>
                <a href="<?php echo BASE_URL; ?>/files/index.php" class="w-full bg-red-50 text-red-600 px-4 py-2 rounded-lg hover:bg-red-100 transition font-medium text-center flex items-center justify-center">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="px-4 sm:px-6 mb-16">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File Name</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type / Size</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project / Client</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uploaded By</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (count($files) > 0): ?>
                    <?php foreach ($files as $file): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <svg class="flex-shrink-0 h-5 w-5 text-gray-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($file['file_name']); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars(strtoupper($file['file_type'] ?: 'FILE')); ?></div>
                                <div class="text-xs text-gray-500"><?php echo number_format($file['file_size'] / 1024 / 1024, 2); ?> MB</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-indigo-600"><?php echo htmlspecialchars($file['project_name']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($file['client_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($file['uploader_name']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo date('d-m-Y', strtotime($file['created_at'])); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <?php if ($file['drive_link']): ?>
                                    <a href="<?php echo htmlspecialchars($file['drive_link']); ?>" target="_blank" class="text-indigo-600 hover:text-indigo-900">Preview</a>
                                <?php endif; ?>

                                <button onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($file['drive_link'] ?? ''); ?>'); alert('Link copied!');" class="text-gray-600 hover:text-gray-900">Copy Link</button>

                                <!-- Rename Form inline -->
                                <button onclick="document.getElementById('rename-form-<?php echo $file['id']; ?>').classList.toggle('hidden')" class="text-blue-600 hover:text-blue-900">Rename</button>

                                <!-- Delete Form inline -->
                                <form method="POST" action="<?php echo BASE_URL; ?>/api/file-actions.php" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this file?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="file_id" value="<?php echo $file['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                </form>

                                <!-- Hidden Rename Modal/Row -->
                                <div id="rename-form-<?php echo $file['id']; ?>" class="hidden mt-2 p-2 bg-white border border-gray-200 rounded shadow-lg absolute right-6 z-10">
                                    <form method="POST" action="<?php echo BASE_URL; ?>/api/file-actions.php" class="flex items-center space-x-2">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                        <input type="hidden" name="action" value="rename">
                                        <input type="hidden" name="file_id" value="<?php echo $file['id']; ?>">
                                        <input type="text" name="new_name" value="<?php echo htmlspecialchars($file['file_name']); ?>" required class="border border-gray-300 rounded px-2 py-1 text-sm outline-none">
                                        <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">Save</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                            No files found in the agency drive.
                        </td>
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
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&project_id=<?php echo urlencode($project_filter); ?>&client_id=<?php echo urlencode($client_filter); ?>&file_type=<?php echo urlencode($type_filter); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $page === $i ? 'text-indigo-600 bg-indigo-50 z-10' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </nav>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
