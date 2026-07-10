<?php
// File: /manager/clients.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

checkAuth(['Manager']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];

// Handle Soft Delete
if (isset($_POST['delete_client_id'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (verifyCsrfToken($csrf_token)) {
        $delete_id = filter_var($_POST['delete_client_id'], FILTER_VALIDATE_INT);
        if ($delete_id) {
            $stmt = $pdo->prepare("UPDATE clients SET deleted_at = NOW() WHERE id = :id AND agency_id = :agency_id");
            $stmt->execute(['id' => $delete_id, 'agency_id' => $agency_id]);
            $_SESSION['success_msg'] = "Client deleted successfully.";
            redirect('/manager/clients.php');
        }
    } else {
        $_SESSION['error_msg'] = "Invalid CSRF token.";
        redirect('/manager/clients.php');
    }
}

// Search and Filter
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Pagination
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build Query
$query = "SELECT * FROM clients WHERE agency_id = :agency_id AND deleted_at IS NULL";
$params = ['agency_id' => $agency_id];

if ($search) {
    $query .= " AND (client_name LIKE :search OR company_name LIKE :search OR email LIKE :search OR phone LIKE :search)";
    $params['search'] = "%$search%";
}

if ($status_filter) {
    $query .= " AND status = :status";
    $params['status'] = $status_filter;
}

// Count total for pagination
$count_stmt = $pdo->prepare(str_replace("SELECT *", "SELECT COUNT(*)", $query));
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch data
$query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$clients = $stmt->fetchAll();

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6 flex justify-between items-center">
    <h1 class="text-3xl font-bold leading-tight text-gray-900">Clients</h1>
    <a href="client-create.php" class="bg-indigo-600 text-white px-4 py-2 rounded-full hover:bg-indigo-700 transition shadow-sm text-sm font-medium">Add Client</a>
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
    <form method="GET" action="clients.php" class="flex flex-col sm:flex-row gap-4">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search name, email, phone..." class="px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 w-full sm:w-64 outline-none">
        <select name="status" class="px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
            <option value="">All Statuses</option>
            <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active</option>
            <option value="Inactive" <?php echo $status_filter === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
        </select>
        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition font-medium">Filter</button>
        <?php if($search || $status_filter): ?>
            <a href="clients.php" class="bg-red-50 text-red-600 px-4 py-2 rounded-lg hover:bg-red-100 transition font-medium text-center">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="px-4 sm:px-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client Name</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (count($clients) > 0): ?>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($client['client_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($client['company_name'] ?: '-'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($client['email']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($client['phone']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $client['status'] === 'Active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo htmlspecialchars($client['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="client-view.php?id=<?php echo $client['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">View</a>
                                <a href="client-edit.php?id=<?php echo $client['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                                <form method="POST" action="clients.php" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this client?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                    <input type="hidden" name="delete_client_id" value="<?php echo $client['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No clients found.</td>
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
