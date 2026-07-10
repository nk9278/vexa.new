<?php
// File: /agency/google-drive.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/notification_functions.php';

checkAuth(['Agency Owner']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];

// Disconnect Handle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disconnect'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (verifyCsrfToken($csrf_token)) {
        $stmt = $pdo->prepare("DELETE FROM google_drive_accounts WHERE agency_id = :agency_id");
        $stmt->execute(['agency_id' => $agency_id]);

        logActivity($agency_id, $_SESSION['user_id'], 'Google Drive Disconnected', "Google Drive was disconnected from the agency.", null);
        $_SESSION['success_msg'] = "Google Drive successfully disconnected.";
        header("Location: /agency/google-drive.php");
        exit;
    } else {
        $_SESSION['error_msg'] = "Invalid CSRF token.";
        header("Location: /agency/google-drive.php");
        exit;
    }
}

// Fetch current connection
$stmt = $pdo->prepare("SELECT * FROM google_drive_accounts WHERE agency_id = :agency_id");
$stmt->execute(['agency_id' => $agency_id]);
$drive_account = $stmt->fetch();

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <h1 class="text-3xl font-bold leading-tight text-gray-900">Google Drive Integration</h1>
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

<div class="px-4 sm:px-6 mb-16">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden max-w-3xl">
        <div class="p-6 sm:p-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Connection Status</h2>

            <?php if ($drive_account): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6 flex items-center justify-between">
                    <div>
                        <div class="flex items-center space-x-3 mb-2">
                            <span class="relative flex h-3 w-3">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                            </span>
                            <span class="text-green-800 font-bold">Connected</span>
                        </div>
                        <p class="text-sm text-green-700">Your agency is actively connected to Google Drive.</p>
                        <?php if ($drive_account['email']): ?>
                            <p class="text-sm text-green-700 font-medium mt-1">Account: <?php echo htmlspecialchars($drive_account['email']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <svg class="h-12 w-12 text-green-500 opacity-50" fill="currentColor" viewBox="0 0 24 24">
                            <!-- Drive Logo Path approx -->
                            <path d="M7.71 3.5L1.15 15l3.43 6 6.55-11.5M9.73 3.5h13.12l3.43 6H13.15M13.56 16.5l3.43 6H24l-3.43-6"/>
                        </svg>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-6 mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Manage Connection</h3>
                    <p class="text-sm text-gray-500 mb-4">If you disconnect, new files uploaded by your team will be stored locally on the server instead of Google Drive until you reconnect.</p>

                    <div class="flex space-x-4">
                        <a href="<?php echo BASE_URL; ?>/api/google-drive-auth.php?action=reconnect" class="bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-full hover:bg-gray-50 transition shadow-sm font-medium">Reconnect Account</a>

                        <form method="POST" action="google-drive.php" onsubmit="return confirm('Are you sure you want to disconnect Google Drive?');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                            <input type="hidden" name="disconnect" value="1">
                            <button type="submit" class="bg-red-50 text-red-600 px-6 py-2 rounded-full hover:bg-red-100 transition shadow-sm font-medium">Disconnect Drive</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6">
                    <div class="flex items-center space-x-3 mb-2">
                        <span class="h-3 w-3 rounded-full bg-gray-400"></span>
                        <span class="text-gray-800 font-bold">Not Connected</span>
                    </div>
                    <p class="text-sm text-gray-600">Your agency is not currently connected to Google Drive. File uploads are being stored locally on the server.</p>
                </div>

                <div class="border-t border-gray-200 pt-6 mt-6 text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M7.71 3.5L1.15 15l3.43 6 6.55-11.5M9.73 3.5h13.12l3.43 6H13.15M13.56 16.5l3.43 6H24l-3.43-6"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Connect Google Drive</h3>
                    <p class="text-sm text-gray-500 mb-6 max-w-lg mx-auto">Authorize <?php echo APP_NAME; ?> to automatically organize and store your agency's files, photos, videos, and documents directly into your Google Drive.</p>

                    <a href="<?php echo BASE_URL; ?>/api/google-drive-auth.php" class="inline-flex items-center bg-indigo-600 text-white px-8 py-3 rounded-full hover:bg-indigo-700 transition shadow-sm font-medium text-lg">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M7.71 3.5L1.15 15l3.43 6 6.55-11.5M9.73 3.5h13.12l3.43 6H13.15M13.56 16.5l3.43 6H24l-3.43-6"/></svg>
                        Connect Google Drive
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
