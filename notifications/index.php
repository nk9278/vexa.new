<?php
// File: /notifications/index.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

// All logged-in roles can access notifications
checkAuth();

$pdo = getDbConnection();
$user_id = $_SESSION['user_id'];

// Handle Actions (Mark Read, Delete, Mark All Read)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (verifyCsrfToken($csrf_token)) {
        if (isset($_POST['mark_all_read'])) {
            $stmt = $pdo->prepare("UPDATE notifications SET status = 'Read' WHERE user_id = :user_id AND status = 'Unread'");
            $stmt->execute(['user_id' => $user_id]);
            $_SESSION['success_msg'] = "All notifications marked as read.";
        } elseif (isset($_POST['mark_read_id'])) {
            $id = filter_var($_POST['mark_read_id'], FILTER_VALIDATE_INT);
            if ($id) {
                $stmt = $pdo->prepare("UPDATE notifications SET status = 'Read' WHERE id = :id AND user_id = :user_id");
                $stmt->execute(['id' => $id, 'user_id' => $user_id]);
                $_SESSION['success_msg'] = "Notification marked as read.";
            }
        } elseif (isset($_POST['delete_id'])) {
            $id = filter_var($_POST['delete_id'], FILTER_VALIDATE_INT);
            if ($id) {
                $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = :id AND user_id = :user_id");
                $stmt->execute(['id' => $id, 'user_id' => $user_id]);
                $_SESSION['success_msg'] = "Notification deleted.";
            }
        }
        header("Location: /notifications/index.php");
        exit;
    } else {
        $_SESSION['error_msg'] = "Invalid CSRF token.";
        header("Location: /notifications/index.php");
        exit;
    }
}

// Search and Filter
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Pagination
$limit = 15;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build Query
$query = "SELECT * FROM notifications WHERE user_id = :user_id";
$params = ['user_id' => $user_id];

if ($search) {
    $query .= " AND (title LIKE :search OR message LIKE :search)";
    $params['search'] = "%$search%";
}

if ($status_filter) {
    $query .= " AND status = :status";
    $params['status'] = $status_filter;
}

// Count total
$count_query = preg_replace('/SELECT \*/', 'SELECT COUNT(*)', $query, 1);
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch data
$query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$notifications = $stmt->fetchAll();

// Depending on the role, we might want to use their specific header/footer for layout consistency,
// but the requirement says "shared across the entire application" with top nav.
// Since all modules use includes/header.php indirectly, we'll just require that.
include __DIR__ . '/../includes/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-4">
        <h1 class="text-3xl font-bold leading-tight text-gray-900">Notification Center</h1>
        <div class="mt-4 sm:mt-0 flex space-x-2">
            <a href="<?php echo BASE_URL; ?>/notifications/activity.php" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-full hover:bg-gray-50 transition shadow-sm text-sm font-medium">Activity Timeline</a>
            <a href="<?php echo BASE_URL; ?>/notifications/audit.php" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-full hover:bg-gray-50 transition shadow-sm text-sm font-medium">Audit Logs</a>

            <form method="POST" action="<?php echo BASE_URL; ?>/notifications/index.php" class="inline-block">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <input type="hidden" name="mark_all_read" value="1">
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-full hover:bg-indigo-700 transition shadow-sm text-sm font-medium">Mark All as Read</button>
            </form>
        </div>
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
    <form method="GET" action="<?php echo BASE_URL; ?>/notifications/index.php" class="flex flex-col sm:flex-row gap-4">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search notifications..." class="px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 w-full sm:w-64 outline-none">
        <select name="status" class="px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
            <option value="">All Statuses</option>
            <option value="Unread" <?php echo $status_filter === 'Unread' ? 'selected' : ''; ?>>Unread</option>
            <option value="Read" <?php echo $status_filter === 'Read' ? 'selected' : ''; ?>>Read</option>
            <option value="Archived" <?php echo $status_filter === 'Archived' ? 'selected' : ''; ?>>Archived</option>
        </select>
        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition font-medium">Filter</button>
        <?php if($search || $status_filter): ?>
            <a href="<?php echo BASE_URL; ?>/notifications/index.php" class="bg-red-50 text-red-600 px-4 py-2 rounded-lg hover:bg-red-100 transition font-medium text-center">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="px-4 sm:px-6 mb-16">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <ul class="divide-y divide-gray-200">
            <?php if (count($notifications) > 0): ?>
                <?php foreach ($notifications as $note): ?>
                    <li class="p-6 transition <?php echo $note['status'] === 'Unread' ? 'bg-indigo-50/30' : 'hover:bg-gray-50'; ?>">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0 pt-1">
                                <?php if ($note['status'] === 'Unread'): ?>
                                    <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-indigo-100">
                                        <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-gray-100">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start">
                                    <p class="text-sm font-bold text-gray-900">
                                        <?php if ($note['link']): ?>
                                            <a href="<?php echo htmlspecialchars($note['link']); ?>" class="hover:underline"><?php echo htmlspecialchars($note['title']); ?></a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($note['title']); ?>
                                        <?php endif; ?>
                                    </p>
                                    <p class="text-xs text-gray-400 whitespace-nowrap ml-4"><?php echo date('M d, h:i A', strtotime($note['created_at'])); ?></p>
                                </div>
                                <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($note['message']); ?></p>

                                <div class="mt-3 flex space-x-3">
                                    <?php if ($note['status'] === 'Unread'): ?>
                                        <form method="POST" action="<?php echo BASE_URL; ?>/notifications/index.php" class="inline-block">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                            <input type="hidden" name="mark_read_id" value="<?php echo $note['id']; ?>">
                                            <button type="submit" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Mark as Read</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" action="<?php echo BASE_URL; ?>/notifications/index.php" class="inline-block" onsubmit="return confirm('Delete this notification?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                        <input type="hidden" name="delete_id" value="<?php echo $note['id']; ?>">
                                        <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-800">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="p-6 text-center text-sm text-gray-500">
                    No notifications found.
                </li>
            <?php endif; ?>
        </ul>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
