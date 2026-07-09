<?php
// File: /agency/managers.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';

checkAuth(['Agency Owner']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];

// Pagination and Filtering setup
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$where_clauses = ["u.agency_id = :agency_id", "u.role_id = :role_id", "u.deleted_at IS NULL"];
$params = [
    'agency_id' => $agency_id,
    'role_id' => ROLE_MANAGER
];

if ($search) {
    $where_clauses[] = "(u.full_name LIKE :search OR u.email_address LIKE :search OR m.phone LIKE :search OR m.designation LIKE :search)";
    $params['search'] = "%$search%";
}
if ($status_filter) {
    $where_clauses[] = "u.account_status = :status";
    $params['status'] = $status_filter;
}

$where_sql = implode(' AND ', $where_clauses);

// Get total for pagination
$count_sql = "SELECT COUNT(*) FROM users u LEFT JOIN managers m ON u.id = m.user_id WHERE $where_sql";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch managers
$sql = "SELECT u.id, u.full_name, u.email_address, u.account_status, u.created_at, m.phone, m.designation
        FROM users u
        LEFT JOIN managers m ON u.id = m.user_id
        WHERE $where_sql
        ORDER BY u.created_at DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => &$val) {
    $stmt->bindParam($key, $val);
}
$stmt->bindParam('limit', $limit, PDO::PARAM_INT);
$stmt->bindParam('offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$managers = $stmt->fetchAll();

function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'active': return 'bg-green-100 text-green-800';
        case 'temporary suspend': return 'bg-yellow-100 text-yellow-800';
        case 'permanent suspend': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6 flex justify-between items-center">
    <h1 class="text-3xl font-bold leading-tight text-gray-900">Managers</h1>
    <a href="manager-add.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-full shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition">
        Add Manager
    </a>
</div>

<!-- Search & Filter -->
<div class="px-4 sm:px-6 mb-6">
    <form method="GET" action="managers.php" class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col sm:flex-row gap-4 items-end">
        <div class="w-full sm:w-1/2">
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
            <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, Email, Phone..." class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
        </div>
        <div class="w-full sm:w-1/3">
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" id="status" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                <option value="">All Statuses</option>
                <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active</option>
                <option value="Temporary Suspend" <?php echo $status_filter === 'Temporary Suspend' ? 'selected' : ''; ?>>Temporary Suspend</option>
                <option value="Permanent Suspend" <?php echo $status_filter === 'Permanent Suspend' ? 'selected' : ''; ?>>Permanent Suspend</option>
            </select>
        </div>
        <div class="w-full sm:w-auto">
            <button type="submit" class="w-full inline-flex justify-center items-center px-6 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 transition">
                Filter
            </button>
        </div>
    </form>
</div>

<!-- Table -->
<div class="px-4 sm:px-6">
    <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Manager</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($managers) > 0): ?>
                        <?php foreach ($managers as $manager): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($manager['full_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($manager['designation'] ?? 'Manager'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($manager['email_address']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($manager['phone'] ?? ''); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusBadgeClass($manager['account_status']); ?>">
                                        <?php echo htmlspecialchars($manager['account_status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="manager-view.php?id=<?php echo $manager['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">View</a>
                                    <a href="manager-edit.php?id=<?php echo $manager['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                                    <form method="POST" action="manager-status.php" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="id" value="<?php echo $manager['id']; ?>">
                                        <?php if ($manager['account_status'] === 'Active'): ?>
                                            <input type="hidden" name="status" value="Temporary Suspend">
                                            <button type="submit" class="text-yellow-600 hover:text-yellow-900 bg-transparent border-none cursor-pointer">Deactivate</button>
                                        <?php else: ?>
                                            <input type="hidden" name="status" value="Active">
                                            <button type="submit" class="text-green-600 hover:text-green-900 bg-transparent border-none cursor-pointer">Activate</button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No managers found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $limit, $total_records); ?></span> of <span class="font-medium"><?php echo $total_records; ?></span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 <?php echo $i === $page ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </nav>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
