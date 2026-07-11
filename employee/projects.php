<?php
// File: /employee/projects.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

checkAuth(['Employee']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];
$user_id = $_SESSION['user_id'];

// Search and Filter
$search = $_GET['search'] ?? '';

// Pagination
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build Query: Only projects where employee is assigned
$query = "
    SELECT p.*, c.client_name, pa.role as my_role
    FROM projects p
    JOIN clients c ON p.client_id = c.id
    JOIN project_assignments pa ON p.id = pa.project_id
    WHERE p.agency_id = :agency_id AND pa.employee_id = :employee_id AND p.deleted_at IS NULL
";
$params = ['agency_id' => $agency_id, 'employee_id' => $user_id];

if ($search) {
    $query .= " AND (p.project_name LIKE :search OR c.client_name LIKE :search)";
    $params['search'] = "%$search%";
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

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6 flex justify-between items-center">
    <h1 class="text-3xl font-bold leading-tight text-gray-900">My Projects</h1>
</div>

<div class="px-4 sm:px-6 mb-6">
    <form method="GET" action="projects.php" class="flex flex-col sm:flex-row gap-4">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search projects or clients..." class="px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 w-full sm:w-64 outline-none">
        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition font-medium">Search</button>
        <?php if($search): ?>
            <a href="projects.php" class="bg-red-50 text-red-600 px-4 py-2 rounded-lg hover:bg-red-100 transition font-medium text-center">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="px-4 sm:px-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project Name</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">My Role</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (count($projects) > 0): ?>
                    <?php foreach ($projects as $project): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($project['project_name']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($project['project_type']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($project['client_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="bg-indigo-50 text-indigo-700 px-2 py-1 rounded text-xs font-medium"><?php echo htmlspecialchars($project['my_role']); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    <?php echo htmlspecialchars($project['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No projects assigned to you.</td>
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
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $page === $i ? 'text-indigo-600 bg-indigo-50 z-10' : 'text-gray-700 hover:bg-gray-50'; ?>">
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
