<?php
// File: /includes/mobile-menu.php
?>
<div class="fixed bottom-0 inset-x-0 bg-white border-t border-gray-200 z-50">
    <div class="flex justify-between items-center h-16 px-6">
        <?php
            $dashboard_url = '#';
            if (isset($_SESSION['role_name'])) {
                switch($_SESSION['role_name']) {
                    case 'Super Admin': $dashboard_url = BASE_URL . '/super-admin/dashboard.php'; break;
                    case 'Agency Owner': $dashboard_url = BASE_URL . '/agency/dashboard.php'; break;
                    case 'Manager': $dashboard_url = BASE_URL . '/manager/dashboard.php'; break;
                    case 'CRM': $dashboard_url = BASE_URL . '/crm/dashboard.php'; break;
                    default: $dashboard_url = BASE_URL . '/employee/dashboard.php'; break;
                }
            }
            $is_dashboard = strpos($_SERVER['REQUEST_URI'], 'dashboard.php') !== false;
            $is_notif = strpos($_SERVER['REQUEST_URI'], 'notifications') !== false;
        ?>
        <a href="<?php echo $dashboard_url; ?>" class="flex flex-col items-center <?php echo $is_dashboard ? 'text-indigo-600' : 'text-gray-500 hover:text-indigo-600'; ?>">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span class="text-xs mt-1">Dashboard</span>
        </a>

        <a href="<?php echo BASE_URL; ?>/notifications/index.php" class="flex flex-col items-center <?php echo $is_notif ? 'text-indigo-600' : 'text-gray-500 hover:text-indigo-600'; ?>">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span class="text-xs mt-1">Notifications</span>
        </a>

        <a href="<?php echo BASE_URL; ?>/logout.php" class="flex flex-col items-center text-red-500 hover:text-red-700">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span class="text-xs mt-1">Logout</span>
        </a>
    </div>
</div>
