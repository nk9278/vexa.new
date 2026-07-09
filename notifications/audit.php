<?php
// File: /notifications/audit.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

// Typically Audit Logs are restricted to Super Admin or Agency Owner, maybe Manager.
// We'll restrict to those three roles for security.
checkAuth(['Super Admin', 'Agency Owner', 'Manager']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];
$role_name = $_SESSION['role_name'];

// Filters
$user_filter = $_GET['user_id'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Pagination
$limit = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Base query for audit logs tied to the agency
$query = "
    SELECT a.*, u.full_name, p.project_name, c.client_name
    FROM audit_logs a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN projects p ON a.project_id = p.id
    LEFT JOIN clients c ON a.client_id = c.id
    WHERE a.agency_id = :agency_id
";
$params = ['agency_id' => $agency_id];

// Apply Filters
if ($user_filter) {
    $query .= " AND a.user_id = :user_id";
    $params['user_id'] = $user_filter;
}
if ($date_filter) {
    $query .= " AND DATE(a.created_at) = :date_filter";
    $params['date_filter'] = $date_filter;
}

// Count total
$count_query = preg_replace('/SELECT .*? FROM/s', 'SELECT COUNT(*) FROM', $query, 1);
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch data
$query .= " ORDER BY a.created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Fetch Users for Filter
$users_stmt = $pdo->prepare("SELECT id, full_name, role_id FROM users WHERE agency_id = :agency_id AND deleted_at IS NULL ORDER BY full_name");
$users_stmt->execute(['agency_id' => $agency_id]);
$filter_users = $users_stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-4">
        <h1 class="text-3xl font-bold leading-tight text-gray-900">Audit Logs</h1>
        <div class="mt-4 sm:mt-0 flex space-x-2">
            <a href="/notifications/index.php" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-full hover:bg-gray-50 transition shadow-sm text-sm font-medium">Notification Center</a>
            <a href="/notifications/activity.php" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-full hover:bg-gray-50 transition shadow-sm text-sm font-medium">Activity Timeline</a>
        </div>
    </div>
</div>

<div class="px-4 sm:px-6 mb-6">
    <form method="GET" action="/notifications/audit.php" class="flex flex-col sm:flex-row gap-4">
        <select name="user_id" class="px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white sm:w-64">
            <option value="">All Users</option>
            <?php foreach($filter_users as $u): ?>
                <option value="<?php echo $u['id']; ?>" <?php echo $user_filter == $u['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['full_name']); ?></option>
            <?php endforeach; ?>
        </select>

        <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>" class="px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none">

        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition font-medium">Filter</button>
        <?php if($user_filter || $date_filter): ?>
            <a href="/notifications/audit.php" class="bg-red-50 text-red-600 px-4 py-2 rounded-lg hover:bg-red-100 transition font-medium text-center flex items-center justify-center">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="px-4 sm:px-6 mb-16">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date / Time</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Context (Project/Client)</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (count($logs) > 0): ?>
                    <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($log['full_name']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($log['role_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo htmlspecialchars($log['action']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php if ($log['project_name']): ?>
                                    <span class="block">Project: <?php echo htmlspecialchars($log['project_name']); ?></span>
                                <?php endif; ?>
                                <?php if ($log['client_name']): ?>
                                    <span class="block">Client: <?php echo htmlspecialchars($log['client_name']); ?></span>
                                <?php endif; ?>
                                <?php if (!$log['project_name'] && !$log['client_name']): ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                <?php echo htmlspecialchars($log['ip_address'] ?: 'Unknown'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                            No audit logs found for these filters.
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
                            <a href="?page=<?php echo $i; ?>&user_id=<?php echo urlencode($user_filter); ?>&date=<?php echo urlencode($date_filter); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $page === $i ? 'text-indigo-600 bg-indigo-50 z-10' : 'text-gray-700 hover:bg-gray-50'; ?>">
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
