<?php
// File: /super-admin/agencies.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';

checkAuth(['Super Admin']);

$pdo = getDbConnection();

// Pagination and Filtering setup
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$where_clauses = ["agencies.deleted_at IS NULL"];
$params = [];

if ($search) {
    $where_clauses[] = "(agencies.name LIKE :search OR agencies.owner_name LIKE :search OR agencies.email LIKE :search OR agencies.phone LIKE :search)";
    $params['search'] = "%$search%";
}
if ($status_filter) {
    $where_clauses[] = "agencies.status = :status";
    $params['status'] = $status_filter;
}

$where_sql = implode(' AND ', $where_clauses);

// Get total for pagination
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM agencies WHERE $where_sql");
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch agencies with subscription info
$sql = "SELECT agencies.*,
               (SELECT plan_name FROM agency_subscriptions WHERE agency_id = agencies.id ORDER BY id DESC LIMIT 1) as plan_name,
               (SELECT start_date FROM agency_subscriptions WHERE agency_id = agencies.id ORDER BY id DESC LIMIT 1) as start_date,
               (SELECT end_date FROM agency_subscriptions WHERE agency_id = agencies.id ORDER BY id DESC LIMIT 1) as end_date
        FROM agencies
        WHERE $where_sql
        ORDER BY agencies.created_at DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => &$val) {
    $stmt->bindParam($key, $val);
}
$stmt->bindParam('limit', $limit, PDO::PARAM_INT);
$stmt->bindParam('offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$agencies = $stmt->fetchAll();

function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'active': return 'bg-green-100 text-green-800';
        case 'demo': return 'bg-blue-100 text-blue-800';
        case 'hold': return 'bg-orange-100 text-orange-800';
        case 'temporary suspend': return 'bg-yellow-100 text-yellow-800';
        case 'permanent suspend': return 'bg-red-100 text-red-800';
        case 'expired': return 'bg-gray-100 text-gray-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6 flex justify-between items-center">
    <h1 class="text-3xl font-bold leading-tight text-gray-900">Agencies</h1>
    <a href="agency-add.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-full shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
        Add Agency
    </a>
</div>

<!-- Search & Filter -->
<div class="px-4 sm:px-6 mb-6">
    <form method="GET" action="agencies.php" class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col sm:flex-row gap-4 items-end">
        <div class="w-full sm:w-1/2">
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
            <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, Owner, Email, Phone..." class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
        </div>
        <div class="w-full sm:w-1/3">
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" id="status" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                <option value="">All Statuses</option>
                <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active</option>
                <option value="Demo" <?php echo $status_filter === 'Demo' ? 'selected' : ''; ?>>Demo</option>
                <option value="Hold" <?php echo $status_filter === 'Hold' ? 'selected' : ''; ?>>Hold</option>
                <option value="Temporary Suspend" <?php echo $status_filter === 'Temporary Suspend' ? 'selected' : ''; ?>>Temporary Suspend</option>
                <option value="Permanent Suspend" <?php echo $status_filter === 'Permanent Suspend' ? 'selected' : ''; ?>>Permanent Suspend</option>
                <option value="Expired" <?php echo $status_filter === 'Expired' ? 'selected' : ''; ?>>Expired</option>
            </select>
        </div>
        <div class="w-full sm:w-auto">
            <button type="submit" class="w-full inline-flex justify-center items-center px-6 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 focus:outline-none transition">
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
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agency</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subscription</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($agencies) > 0): ?>
                        <?php foreach ($agencies as $agency): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($agency['name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($agency['company_name'] ?? ''); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($agency['owner_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($agency['email']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($agency['plan_name'] ?? 'N/A'); ?></div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo $agency['end_date'] ? 'Ends: ' . date('d-m-Y', strtotime($agency['end_date'])) : ''; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusBadgeClass($agency['status']); ?>">
                                        <?php echo htmlspecialchars($agency['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="agency-view.php?id=<?php echo $agency['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">View</a>
                                    <a href="agency-edit.php?id=<?php echo $agency['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                                    <form method="POST" action="agency-delete.php" class="inline" onsubmit="return confirm('Are you sure you want to delete this agency?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="id" value="<?php echo $agency['id']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900 bg-transparent border-none cursor-pointer">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No agencies found.</td>
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
