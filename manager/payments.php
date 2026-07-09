<?php
// File: /manager/payments.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

checkAuth(['Manager']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];

// Filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Pagination
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build Query
$query = "
    SELECT pp.*, p.project_name, c.client_name, p.id as p_id
    FROM project_payments pp
    JOIN projects p ON pp.project_id = p.id
    JOIN clients c ON p.client_id = c.id
    WHERE p.agency_id = :agency_id AND p.deleted_at IS NULL
";
$params = ['agency_id' => $agency_id];

if ($search) {
    $query .= " AND (p.project_name LIKE :search OR c.client_name LIKE :search)";
    $params['search'] = "%$search%";
}

if ($status_filter) {
    $query .= " AND pp.payment_status = :status";
    $params['status'] = $status_filter;
}

// Count total for pagination
$count_query = preg_replace('/SELECT .*? FROM/s', 'SELECT COUNT(*) FROM', $query, 1);
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch data
$query .= " ORDER BY pp.updated_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Totals calculation (without pagination limits)
$totals_query = "
    SELECT
        SUM(pp.project_amount) as total_amount,
        SUM(pp.received_amount) as total_received,
        SUM(pp.pending_amount) as total_pending
    FROM project_payments pp
    JOIN projects p ON pp.project_id = p.id
    WHERE p.agency_id = :agency_id AND p.deleted_at IS NULL
";
$totals_stmt = $pdo->prepare($totals_query);
$totals_stmt->execute(['agency_id' => $agency_id]);
$totals = $totals_stmt->fetch();

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <h1 class="text-3xl font-bold leading-tight text-gray-900">Payment Management</h1>
</div>

<div class="px-4 sm:px-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
            <p class="text-sm text-gray-500 font-medium">Total Project Value</p>
            <p class="text-2xl font-bold text-gray-900">$<?php echo number_format($totals['total_amount'] ?? 0, 2); ?></p>
        </div>
        <div class="bg-green-50 p-4 rounded-xl shadow-sm border border-green-200">
            <p class="text-sm text-green-600 font-medium">Total Received</p>
            <p class="text-2xl font-bold text-green-700">$<?php echo number_format($totals['total_received'] ?? 0, 2); ?></p>
        </div>
        <div class="bg-red-50 p-4 rounded-xl shadow-sm border border-red-200">
            <p class="text-sm text-red-600 font-medium">Total Pending</p>
            <p class="text-2xl font-bold text-red-700">$<?php echo number_format($totals['total_pending'] ?? 0, 2); ?></p>
        </div>
    </div>

    <form method="GET" action="payments.php" class="flex flex-col sm:flex-row gap-4">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search project or client..." class="px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 w-full sm:w-64 outline-none">
        <select name="status" class="px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
            <option value="">All Statuses</option>
            <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="Partial" <?php echo $status_filter === 'Partial' ? 'selected' : ''; ?>>Partial</option>
            <option value="Completed" <?php echo $status_filter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
        </select>
        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition font-medium">Filter</button>
        <?php if($search || $status_filter): ?>
            <a href="payments.php" class="bg-red-50 text-red-600 px-4 py-2 rounded-lg hover:bg-red-100 transition font-medium text-center">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="px-4 sm:px-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project / Client</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project Amount</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Received</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pending</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (count($payments) > 0): ?>
                    <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-indigo-600"><a href="project-view.php?id=<?php echo $pay['p_id']; ?>"><?php echo htmlspecialchars($pay['project_name']); ?></a></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($pay['client_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                $<?php echo number_format($pay['project_amount'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-medium">
                                $<?php echo number_format($pay['received_amount'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-medium">
                                $<?php echo number_format($pay['pending_amount'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    <?php
                                    if($pay['payment_status'] === 'Completed') echo 'bg-green-100 text-green-800';
                                    elseif($pay['payment_status'] === 'Partial') echo 'bg-yellow-100 text-yellow-800';
                                    else echo 'bg-red-100 text-red-800';
                                    ?>">
                                    <?php echo htmlspecialchars($pay['payment_status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="project-payment.php?id=<?php echo $pay['p_id']; ?>" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-3 py-1 rounded">Update</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No payment records found.</td>
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
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $page === $i ? 'text-indigo-600 bg-indigo-50 z-10' : 'text-gray-700 hover:bg-gray-50'; ?>">
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
